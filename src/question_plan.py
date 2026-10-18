"""Split explicit Indonesian requests without splitting lists such as KTP dan KK.

This planner is deliberately local: it adds no model call, invented subjects or
external facts. Only subjects already present in the message can be inherited.
"""
from dataclasses import dataclass
import re


_HEAD = (
    r'(?:apa(?:kah)?|bagaimana|gimana|berapa|siapa|kapan|di\s+mana|dimana|'
    r'ke\s+mana|kemana|mengapa|kenapa|sebutkan|tampilkan|jelaskan|tuliskan|daftarkan)\b'
)
_ASPECT = (
    r'(?:syarat|persyaratan|prosedur|langkah(?:-langkah)?|tahapan|biaya|'
    r'jam\s+(?:buka|pelayanan|operasional)|alamat|nomor\s+(?:telepon|kontak)|'
    r'jumlah\s+penduduk|waktu\s+(?:pelayanan|pengurusan|pembuatan))\b'
)
_JOIN = r'(?:dan\s+juga|dan|serta|lalu|kemudian|juga|selain\s+itu)'
_SUBJECT = re.compile(
    r'\bsurat\s+(?:keterangan\s+)?(?:tidak\s+mampu|belum\s+menikah|'
    r'pengantar\s+nikah|nikah|usaha|domisili|kelahiran|kematian|pindah|kehilangan)\b'
    r'|\bkartu\s+(?:tanda\s+penduduk|keluarga|identitas\s+anak)\b'
    r'|\bakta\s+(?:kelahiran|kematian|perkawinan)\b'
    r'|\b(?:KTP|KK|SKU|SKTM|KIA|UMKM|paspor|BPJS)\b', re.I,
)


@dataclass(frozen=True)
class QuestionPart:
    # label preserves the user's wording; question is resolved for retrieval.
    label: str
    question: str


def _subject(text: str) -> str | None:
    matches = list(_SUBJECT.finditer(text))
    # Do not arbitrarily choose one of several services (e.g. KTP dan KK).
    subjects = list(dict.fromkeys(match.group(0).casefold() for match in matches))
    if len(subjects) != 1:
        return None
    return matches[0].group(0)


def _resolve(text: str, subject: str | None) -> str:
    if not subject or _SUBJECT.search(text):
        return text
    # Only explicitly elliptical requests inherit a neighbouring subject.
    if re.search(r'\b(?:membuat|mengurus|mengajukan|mendaftar|mendapatkan|memperoleh|memproses)nya\b', text, re.I):
        return re.sub(
            r'\b(membuat|mengurus|mengajukan|mendaftar|mendapatkan|memperoleh|memproses)nya\b',
            lambda match: f'{match.group(1)} {subject}', text, flags=re.I,
        )
    if re.fullmatch(
        r'(?:(?:dan|serta)\s+)?(?:(?:apa(?:\s+saja)?|bagaimana|gimana|berapa)\s+)?'
        r'(?:(?:cara|langkah(?:-langkah)?|prosedur|alur|proses|pengurusan|pembuatan|'
        r'pendaftaran|syarat|persyaratan|biaya|lama|waktu)(?:nya)?\s*)+'
        r'(?:yang\s+(?:dibutuhkan|diperlukan))?', text, re.I,
    ):
        return f'{text} untuk {subject}'
    return text


def plan_questions(message: str) -> list[QuestionPart]:
    original = message.strip()
    # Question marks/semicolons and numbered lines are explicit boundaries.
    text = re.sub(r'(?m)^\s*(?:\d+[.)]|[-*])\s+', '', original)
    text = re.sub(rf'\s*[,;]?\s*\b{_JOIN}\s+(?={_HEAD}|{_ASPECT})', '\n', text, flags=re.I)
    text = re.sub(rf'[,;]\s*(?={_HEAD})', '\n', text, flags=re.I)
    text = re.sub(rf'\.\s+(?={_HEAD})', '\n', text, flags=re.I)
    candidates = []
    for candidate in re.split(r'[?;\n]+', text):
        candidate = re.sub(rf'^\s*{_JOIN}\s+(?={_HEAD}|{_ASPECT})', '', candidate, flags=re.I)
        candidate = ' '.join(candidate.strip(' ,.!').split())
        if candidate:
            candidates.append(candidate)
    if len(candidates) < 2:
        return [QuestionPart(original, original)]

    # A wrapped line or a list of documents is not automatically a new question.
    groups: list[str] = []
    for candidate in candidates:
        if groups and not re.match(rf'{_HEAD}|{_ASPECT}', candidate, re.I):
            groups[-1] += ' ' + candidate
        else:
            groups.append(candidate)
    if len(groups) < 2:
        return [QuestionPart(original, original)]

    parts = []
    seen = set()
    previous_subject = None
    for index, label in enumerate(groups):
        neighbouring_subject = previous_subject
        if not neighbouring_subject and index + 1 < len(groups):
            neighbouring_subject = _subject(groups[index + 1])
        question = _resolve(label, neighbouring_subject)
        previous_subject = _subject(question)
        key = question.casefold()
        if key not in seen:
            parts.append(QuestionPart(label, question))
            seen.add(key)
    return parts
