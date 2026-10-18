"""Choose answer layout without changing source facts or inventing procedural order."""
import re
from typing import Literal


AnswerFormat = Literal['plain', 'steps', 'requirements', 'steps_and_requirements']
NO_INFORMATION = 'Maaf, informasi tersebut tidak tersedia dalam data.'


def requested_format(question: str) -> AnswerFormat:
    normalized = re.sub(r'\s+', ' ', question.casefold())
    steps = bool(re.search(r'\b(langkah(?:\s+langkah)?|tahap(?:an)?|prosedur|alur|cara)(?:nya)?\b', normalized))
    steps = steps or bool(re.search(
        r'\bbagaimana\b.*\b(mengurus|mendaftar|mengajukan|membuat|mendapatkan|memperoleh|memproses|melakukan)\b',
        normalized,
    ))
    requirements = bool(re.search(r'\b(syarat|persyaratan|kelengkapan)(?:nya)?\b', normalized))
    requirements = requirements or bool(re.search(
        r'\b(dokumen|berkas|perlengkapan)\b.*\b(dibutuhkan|diperlukan|disiapkan|dibawa|dipersiapkan|harus|wajib)\b',
        normalized,
    ))
    requirements = requirements or bool(re.search(
        r'\bapa\b.*\b(perlu|harus|wajib)\b.*\b(dibawa|disiapkan|dipersiapkan)\b', normalized,
    ))
    if steps and requirements:
        return 'steps_and_requirements'
    if steps:
        return 'steps'
    if requirements:
        return 'requirements'
    return 'plain'


def formatting_instruction(question: str) -> str:
    layout = requested_format(question)
    if layout == 'plain':
        return 'Format jawaban: gunakan kalimat natural untuk fakta atau identitas; jangan memaksakan daftar.\n'
    common = (
        'Aturan penyajian: tulis setiap butir pada BARIS BARU, bukan dalam satu paragraf. '
        'Gunakan teks biasa tanpa blok kode atau tabel. Kalimat pembuka boleh singkat. '
        'Pertahankan nama, angka, biaya, waktu, satuan, kondisi, dan pengecualian dari sumber. '
        'Jangan menambah fakta, dokumen, persyaratan, tindakan, atau urutan yang tidak didukung konteks. '
        'Jika informasi yang diminta tidak tersedia, nyatakan keterbatasannya; jangan mengarang daftar.\n'
    )
    if layout == 'requirements':
        return common + (
            'Format jawaban: daftar poin dengan awalan "- " untuk persyaratan, dokumen, atau perlengkapan. '
            'Satu persyaratan per butir. Langsung tulis daftar persyaratannya, tanpa kategori '
            'tambahan seperti "Fakta/Identitas". Keterangan lokasi, kontak, biaya, waktu, '
            'dan instansi lanjutan bukan persyaratan; jangan menyertakannya kecuali ditanyakan.\n'
        )
    if layout == 'steps_and_requirements':
        return common + (
            'Format jawaban: pisahkan bagian "Persyaratan:" dengan daftar "- " dan bagian "Langkah-langkah:" '
            'dengan nomor "1.", "2.", dan seterusnya. Satu tindakan per nomor. '
            'Jika salah satu bagian tidak tersedia di konteks, jelaskan bahwa bagian itu tidak tersedia; '
            'jangan mengisi bagian tersebut dengan informasi rekaan. '
            'Ikuti urutan prosedur yang dinyatakan dalam sumber.\n'
        )
    return common + (
        'Format jawaban: daftar bernomor "1.", "2.", "3.", dan seterusnya untuk langkah/prosedur. '
        'Satu tindakan per nomor; ikuti urutan yang dinyatakan dalam sumber. '
        'Jika sumber hanya berisi persyaratan atau informasi tanpa urutan prosedur, jelaskan keterbatasan itu '
        'dan sajikan informasi yang memang tersedia sebagai poin "- "; jangan menyulap persyaratan menjadi tahapan.\n'
    )


def normalize_answer_layout(question: str, answer: str) -> str:
    """Normalize list markers/line breaks only; never split ordinary factual sentences.

    A prose-only response becomes one unordered point rather than fabricated
    procedural steps. Missing-information responses and factual answers remain intact.
    """
    layout = requested_format(question)
    if layout == 'plain':
        return answer
    text = answer.strip().replace('\r\n', '\n').replace('\r', '\n')
    if not text or text == NO_INFORMATION:
        return text
    if text.startswith('```') and text.endswith('```'):
        text = re.sub(r'^```[^\n]*\n', '', text)
        text = re.sub(r'\n```$', '', text).strip()
    # Only split inline enumeration when at least two consecutive markers exist.
    # Dates, decimal amounts and ordinary sentences are not used as split points.
    numbered = list(re.finditer(r'(?<!\S)(\d{1,2})[.)]\s+', text))
    numbers = [int(match.group(1)) for match in numbered]
    if len(numbered) >= 2 and numbers == list(range(1, len(numbers) + 1)):
        text = re.sub(r'(?<!\n)(?<!\S)(?=\d{1,2}[.)]\s+)', '\n', text).strip()
    lines = text.split('\n')
    # A numbered refusal plus requirements is not a procedure. Remove the implied
    # order if the answer explicitly says the procedural order is unavailable.
    missing_order = bool(re.search(
        r'\b(?:langkah|urutan|prosedur|tahapan)\b[^.!?\n]{0,100}'
        r'\b(?:tidak|belum)\s+(?:tersedia|dijelaskan|disebutkan|tercantum)\b'
        r'|\b(?:tidak|belum)\s+(?:menjelaskan|menyebutkan|memuat|menyediakan)\b'
        r'[^.!?\n]{0,100}\b(?:langkah|urutan|prosedur|tahapan)\b',
        text, re.I,
    ))
    has_list = False
    for index, line in enumerate(lines):
        bullet = re.match(r'^\s*[-*\u2022]\s+(.+)$', line)
        ordered = re.match(r'^\s*(?:\((\d+)\)|(\d+)[.)])\s+(.+)$', line)
        if bullet:
            lines[index] = '- ' + bullet.group(1)
            has_list = True
        elif ordered:
            unordered = layout == 'requirements' or missing_order
            marker = '- ' if unordered else f'{ordered.group(1) or ordered.group(2)}. '
            lines[index] = marker + ordered.group(3)
            has_list = True
    if has_list:
        return '\n'.join(lines)
    # Avoid changing a refusal or a statement that the requested details are absent.
    if re.search(r'\b(tidak|belum)\s+(?:tersedia|disebutkan|dijelaskan|tercantum|ada)\b', text, re.I):
        return text
    return '- ' + text
