import unittest
from unittest.mock import patch

from src.answer_format import NO_INFORMATION, normalize_answer_layout, requested_format
from src.rag import RAGService, build_prompt
from src.retriever import SearchResult


CONTEXT = SearchResult(
    content='Prosedur SKU: 1. Isi formulir. 2. Serahkan formulir ke loket. Syarat: fotokopi KTP dan KK. Biaya: gratis.',
    file_name='sop-test.txt', file_type='txt', metadata={'domain': 'PUBLIC_SERVICE'}, distance=0.1,
)


class AnswerFormatTest(unittest.TestCase):
    def test_natural_procedure_requirements_combined_and_fact_questions(self):
        cases = {
            'Bagaimana langkah-langkah mengurus SKU?': 'steps',
            'Apa tahapan pendaftaran?': 'steps',
            'Jelaskan alur permohonan surat': 'steps',
            'Bagaimana mengajukan surat domisili?': 'steps',
            'Apa saja syarat membuat SKU?': 'requirements',
            'Berkas apa yang harus saya bawa untuk SKU?': 'requirements',
            'Apa saja yang perlu dibawa untuk mengurus surat?': 'requirements',
            'Apa persyaratan dan langkah pengurusannya?': 'steps_and_requirements',
            'Bagaimana cara mengurus SKU dan apa syaratnya?': 'steps_and_requirements',
            'Siapa Kepala Pekon Margodadi?': 'plain',
            'Berapa biaya pembuatan SKU?': 'plain',
            'Apa kode layanan uji integrasi?': 'plain',
        }
        for question, expected in cases.items():
            with self.subTest(question=question):
                self.assertEqual(expected, requested_format(question))

    def test_inline_numbered_actions_become_separate_lines(self):
        answer = 'Berikut langkahnya: 1. Isi formulir. 2. Serahkan formulir ke loket.'
        result = normalize_answer_layout('Bagaimana langkah mengurus SKU?', answer)
        self.assertIn('\n1. Isi formulir.', result)
        self.assertIn('\n2. Serahkan formulir ke loket.', result)
        self.assertNotIn(' 2. ', result)

    def test_requirements_use_bullets_without_changing_the_items(self):
        answer = 'Persyaratan:\n1) Fotokopi KTP.\n2) Fotokopi KK.\n* Pengantar RT/RW.'
        self.assertEqual('Persyaratan:\n- Fotokopi KTP.\n- Fotokopi KK.\n- Pengantar RT/RW.',
                         normalize_answer_layout('Apa persyaratan SKU?', answer))

    def test_preserves_factual_numbers_dates_and_exceptions(self):
        answer = 'Biaya Rp 10.000,00 pada 18.10.2026. No. 12 berlaku bagi RT 01/RW 02, kecuali pemohon di bawah 17 tahun.'
        result = normalize_answer_layout('Apa syaratnya?', answer)
        self.assertEqual('- ' + answer, result)
        self.assertNotIn('\n', result)

    def test_prose_is_not_turned_into_invented_ordered_steps(self):
        answer = 'Dokumen yang diperlukan adalah KTP dan KK.'
        self.assertEqual('- ' + answer, normalize_answer_layout('Bagaimana langkah mengurus SKU?', answer))
        missing = 'Urutan prosedur tidak tersedia dalam sumber; dokumen hanya menyebutkan KTP dan KK.'
        self.assertEqual(missing, normalize_answer_layout('Apa langkahnya?', missing))

    def test_numbered_requirements_are_not_presented_as_unavailable_procedural_order(self):
        answer = '1. Anda perlu fotokopi KTP dan KK.\n2. Maaf, urutan prosedur SKU tidak tersedia dalam data.'
        result = normalize_answer_layout('Bagaimana prosedur SKU?', answer)
        self.assertEqual('- Anda perlu fotokopi KTP dan KK.\n- Maaf, urutan prosedur SKU tidak tersedia dalam data.', result)

    def test_combined_question_does_not_imply_an_order_absent_from_the_source(self):
        answer = 'Persyaratan:\n- KTP\n- KK\nProsedur:\n1. Urutan prosedur tidak tersedia dalam sumber.'
        result = normalize_answer_layout('Apa syarat dan langkah mengurusnya?', answer)
        self.assertNotIn('\n1.', result)
        self.assertIn('Urutan prosedur tidak tersedia dalam sumber.', result)

    def test_absent_information_and_plain_facts_remain_unchanged(self):
        self.assertEqual(NO_INFORMATION, normalize_answer_layout('Apa langkah mengurusnya?', NO_INFORMATION))
        answer = 'Kepala Pekon Margodadi adalah Pak Contoh.'
        self.assertEqual(answer, normalize_answer_layout('Siapa kepala pekon?', answer))

    def test_source_list_and_grounding_instructions_are_preserved(self):
        for question in ['Apa prosedur SKU?', 'Apa syarat SKU?', 'Apa syarat dan langkah SKU?']:
            with self.subTest(question=question):
                prompt = build_prompt(question, [CONTEXT])
                self.assertIn(CONTEXT.content, prompt)
                self.assertIn('Jangan menambah fakta', prompt)
                self.assertIn('BARIS BARU', prompt)
                self.assertIn('tidak tersedia', prompt)

    def test_chat_requests_room_for_complete_lists_and_keeps_source_references(self):
        service = RAGService()
        with patch.object(service, 'search', return_value=[CONTEXT]), patch('src.rag.APILLM') as llm:
            llm.return_value.generate.return_value = '1) Isi formulir.\n2) Serahkan formulir ke loket.'
            answer, sources = service.chat('Bagaimana prosedur SKU?')
            self.assertEqual('1. Isi formulir.\n2. Serahkan formulir ke loket.', answer)
            self.assertEqual([CONTEXT], sources)
            self.assertEqual(768, llm.return_value.generate.call_args.kwargs['max_new_tokens'])

    def test_plain_fact_generation_keeps_the_existing_token_budget(self):
        service = RAGService()
        with patch.object(service, 'search', return_value=[CONTEXT]), patch('src.rag.APILLM') as llm:
            llm.return_value.generate.return_value = 'Biaya pengurusan SKU gratis.'
            answer, _ = service.chat('Berapa biaya SKU?')
            self.assertEqual('Biaya pengurusan SKU gratis.', answer)
            self.assertNotIn('max_new_tokens', llm.return_value.generate.call_args.kwargs)


if __name__ == '__main__':
    unittest.main()
