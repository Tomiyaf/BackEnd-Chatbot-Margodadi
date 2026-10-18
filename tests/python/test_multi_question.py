from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

from fastapi.testclient import TestClient

from src.api import app, get_rag_service
from src.answer_format import NO_INFORMATION
from src.question_plan import plan_questions
from src.rag import RAGService, UNAVAILABLE, build_prompt
from src.retriever import SearchResult
from src.umkm_directory import UmkmDirectory


CATALOGUE = [{'id': index, 'name': f'Usaha {index:02}'} for index in range(1, 8)]
LISTING = 'Apa saja UMKM yang terdapat di Pekon Margodadi'
NIKAH = 'bagaimana cara mengurus surat nikah'
SKU = 'apa saja syarat membuat surat keterangan usaha'
NIKAH_SOURCE = SearchResult(
    'SOP surat nikah: 1. Isi formulir. 2. Serahkan berkas ke loket.',
    'nikah.txt', 'txt', {'domain': 'PUBLIC_SERVICE'}, 0.1,
)
SKU_SOURCE = SearchResult(
    'Persyaratan SKU: fotokopi KTP dan KK.', 'sku.txt', 'txt',
    {'domain': 'PUBLIC_SERVICE'}, 0.2,
)
HOURS_SOURCE = SearchResult(
    'Jam buka kantor: Senin-Jumat, 08.00-15.00 WIB.', 'kantor.txt', 'txt', {}, 0.3,
)


class QuestionPlanningTest(unittest.TestCase):
    def questions(self, text):
        return [part.question for part in plan_questions(text)]

    def test_two_unrelated_questions_and_three_questions(self):
        self.assertEqual([LISTING, NIKAH], self.questions(f'{LISTING} dan {NIKAH}?'))
        self.assertEqual(3, len(self.questions(f'{LISTING}, {SKU}, dan kapan kantor buka?')))

    def test_question_marks_newlines_numbered_lists_and_semicolons(self):
        for text in [f'{LISTING}? {NIKAH}?', f'{LISTING}; {NIKAH}',
                     f'1. {LISTING}\n2. {NIKAH}', f'{LISTING}. {NIKAH}']:
            with self.subTest(text=text):
                self.assertEqual([LISTING, NIKAH], self.questions(text))

    def test_does_not_split_documents_actions_or_conditions_at_every_dan(self):
        for question in ['Apa syarat membuat KTP dan KK?',
                         'Bagaimana cara membawa KTP dan KK ke kantor?',
                         'Apa syarat dan ketentuan membuat SKU?',
                         'Apa syarat SKU jika pemohon belum menikah dan tinggal di Margodadi?',
                         'Apa saja syarat SKU?\nKTP dan KK perlu dibawa atau tidak?']:
            with self.subTest(question=question):
                self.assertEqual([question], self.questions(question))

    def test_resolves_back_reference_from_the_original_nikah_example(self):
        first = 'apa saja surat yang harus dibawa untuk membuat surat nikah'
        self.assertEqual([first, 'bagaimana cara membuat surat nikah'],
                         self.questions(first + ' dan bagaimana cara membuatnya'))

    def test_shared_subject_can_appear_after_the_first_question(self):
        self.assertEqual(['Apa syaratnya untuk surat nikah', 'bagaimana cara membuat surat nikah'],
                         self.questions('Apa syaratnya dan bagaimana cara membuat surat nikah?'))

    def test_subject_survives_a_chain_of_three_related_questions(self):
        self.assertEqual(
            ['Apa syarat surat nikah', 'bagaimana prosesnya untuk surat nikah',
             'berapa biayanya untuk surat nikah'],
            self.questions('Apa syarat surat nikah, bagaimana prosesnya, dan berapa biayanya?'),
        )

    def test_an_unrelated_explicit_topic_breaks_a_subject_reference_chain(self):
        questions = self.questions('Apa syarat surat nikah, kapan kantor buka, dan berapa biayanya?')
        self.assertEqual('berapa biayanya', questions[-1])

    def test_explicit_other_subject_is_never_overwritten(self):
        self.assertEqual(['Apa syarat surat nikah', 'berapa biaya KTP'],
                         self.questions('Apa syarat surat nikah dan berapa biaya KTP?'))

    def test_ambiguous_or_unknown_subject_is_not_invented(self):
        self.assertEqual('bagaimana cara membuatnya',
                         self.questions('Apa syarat KTP dan KK dan bagaimana cara membuatnya?')[1])
        self.assertEqual('bagaimana cara membuatnya',
                         self.questions('Siapa kepala pekon dan bagaimana cara membuatnya?')[1])

    def test_repeated_question_is_answered_once(self):
        self.assertEqual([LISTING], self.questions(f'{LISTING}? {LISTING}?'))


class MultiQuestionChatTest(unittest.TestCase):
    def setUp(self):
        temp = tempfile.TemporaryDirectory()
        self.addCleanup(temp.cleanup)
        self.state = Path(temp.name) / 'sessions.sqlite'
        self.service = RAGService()
        self.service.directory = UmkmDirectory(state_path=self.state)
        self.searches = []
        self.prompts = []
        self.service.search = self.search
        self.llm_patch = patch('src.rag.APILLM')
        self.llm = self.llm_patch.start().return_value
        self.addCleanup(self.llm_patch.stop)
        self.llm.generate.side_effect = self.generate

    def search(self, question, top_k):
        self.searches.append((question, top_k))
        if 'nikah' in question.casefold():
            return [NIKAH_SOURCE]
        if 'usaha' in question.casefold():
            return [SKU_SOURCE]
        if 'kantor' in question.casefold():
            return [HOURS_SOURCE]
        return []

    def generate(self, prompt, **kwargs):
        self.prompts.append(prompt)
        if 'nikah.txt' in prompt:
            return '1) Isi formulir.\n2) Serahkan berkas ke loket.'
        if 'sku.txt' in prompt:
            return '- Fotokopi KTP.\n- Fotokopi KK.'
        return 'Kantor buka Senin-Jumat, 08.00-15.00 WIB.'

    def chat(self, text, session='web:multi'):
        return self.service.chat(text, top_k=4, session_id=session, umkm_catalogue=CATALOGUE)

    def test_previously_failing_umkm_and_nikah_answer_both(self):
        answer, sources = self.chat(f'{LISTING} dan {NIKAH}?')
        self.assertIn('Ada 7 UMKM aktif', answer)
        self.assertIn('Usaha 05', answer)
        self.assertNotIn('Usaha 06', answer)
        self.assertIn('masih ada beberapa UMKM lainnya', answer)
        self.assertIn('1. Isi formulir.\n2. Serahkan berkas ke loket.', answer)
        self.assertEqual([(NIKAH, 4)], self.searches)
        self.assertEqual(['database', 'txt'], [source.file_type for source in sources])
        self.assertNotIn(NO_INFORMATION, answer)
        self.assertNotIn(LISTING, self.prompts[0].split('Pertanyaan:\n')[-1])

    def test_more_than_three_questions_are_all_answered(self):
        question = ('Bagaimana cara mengurus surat nikah? '
                    'Apa syarat membuat surat keterangan usaha? '
                    'Kapan kantor buka? '
                    'Berapa biaya paspor? '
                    'Apa saja UMKM di Margodadi?')
        answer, sources = self.chat(question)
        for number in range(1, 6):
            self.assertIn(f'Pertanyaan {number}:', answer)
        self.assertIn('Usaha 01', answer)
        self.assertIn('Serahkan berkas', answer)
        self.assertIn('08.00-15.00', answer)
        self.assertEqual(4, len(sources))

    def test_three_questions_each_get_a_separate_section_and_retrieval_budget(self):
        answer, sources = self.chat(f'{LISTING}, {SKU}, dan kapan kantor buka?')
        self.assertIn('Pertanyaan 3:', answer)
        self.assertIn('- Fotokopi KTP.\n- Fotokopi KK.', answer)
        self.assertIn('08.00-15.00', answer)
        self.assertEqual([(SKU, 4), ('kapan kantor buka', 4)], self.searches)
        self.assertEqual(3, len(sources))
        self.assertLess(answer.index('Usaha 01'), answer.index('Fotokopi'))
        self.assertLess(answer.index('Fotokopi'), answer.index('08.00'))

    def test_continuation_after_a_mixed_message_does_not_repeat_first_page(self):
        for message in [f'{LISTING} dan {NIKAH}', f'{NIKAH} dan {LISTING}']:
            with self.subTest(message=message):
                self.chat(message)
                answer, sources = self.chat('apa saja itu?')
                self.assertEqual([6, 7], sources[0].metadata['umkm_ids'])
                self.assertNotIn('Usaha 01', answer)

    def test_continuation_can_itself_be_combined_with_another_question(self):
        self.chat(LISTING)
        answer, sources = self.chat(f'Apa saja itu dan {NIKAH}?')
        self.assertEqual([6, 7], sources[0].metadata['umkm_ids'])
        self.assertIn('Serahkan berkas', answer)

    def test_an_entirely_different_next_message_still_clears_directory_context(self):
        self.chat(f'{LISTING} dan {NIKAH}')
        self.chat(f'{SKU} dan kapan kantor buka?')
        answer, _ = self.chat('apa saja itu?')
        self.assertIn('terlebih dahulu', answer)

    def test_one_missing_topic_does_not_hide_available_answers(self):
        answer, sources = self.chat(f'{LISTING}, {NIKAH}, dan berapa biaya paspor?')
        self.assertIn('Usaha 01', answer)
        self.assertIn('Serahkan berkas', answer)
        self.assertIn('Pertanyaan 3: berapa biaya paspor\n' + NO_INFORMATION, answer)
        self.assertEqual(1, self.llm.generate.call_count)
        self.assertEqual(2, len(sources))

    def test_a_generation_failure_is_distinct_from_missing_data_and_is_scoped(self):
        self.llm.generate.side_effect = RuntimeError('private upstream detail')
        with self.assertLogs('src.rag', level='ERROR'):
            answer, _ = self.chat(f'{LISTING} dan {NIKAH}')
        self.assertIn('Usaha 01', answer)
        self.assertIn(UNAVAILABLE, answer)
        self.assertNotIn('private upstream detail', answer)
        self.assertNotIn(NO_INFORMATION, answer)

    def test_a_retrieval_failure_does_not_hide_the_other_question(self):
        self.service.search = lambda *_: (_ for _ in ()).throw(RuntimeError('offline'))
        with self.assertLogs('src.rag', level='ERROR'):
            answer, _ = self.chat(f'{LISTING} dan {NIKAH}')
        self.assertIn('Usaha 01', answer)
        self.assertIn(UNAVAILABLE, answer)

    def test_shared_sources_are_deduplicated_without_mutating_metadata(self):
        self.service.search = lambda *_: [SKU_SOURCE]
        answer, sources = self.chat('Apa syarat SKU dan berapa biaya SKU?')
        self.assertEqual([SKU_SOURCE], sources)
        self.assertEqual({'domain': 'PUBLIC_SERVICE'}, sources[0].metadata)
        self.assertIn('Pertanyaan 2:', answer)

    def test_original_same_topic_example_resolves_nya_for_retrieval(self):
        question = 'apa saja surat yang harus dibawa untuk membuat surat nikah dan bagaimana cara membuatnya'
        self.chat(question)
        self.assertEqual('bagaimana cara membuat surat nikah', self.searches[1][0])
        self.assertEqual(2, self.llm.generate.call_count)

    def test_stateless_mixed_request_does_not_create_a_history_database(self):
        self.chat(f'{LISTING} dan {NIKAH}', session=None)
        self.assertFalse(self.state.exists())

    def test_api_keeps_response_contract_for_existing_laravel_and_frontend(self):
        app.dependency_overrides[get_rag_service] = lambda: self.service
        self.addCleanup(app.dependency_overrides.clear)
        question = f'{LISTING} dan {NIKAH}?'
        with TestClient(app) as client:
            response = client.post('/chat', json={
                'question': question, 'top_k': 4, 'session_id': 'web:api',
                'umkm_catalogue': CATALOGUE,
            })
            self.assertEqual(200, response.status_code)
            data = response.json()
            self.assertEqual({'question', 'answer', 'sources'}, set(data))
            self.assertEqual(question, data['question'])
            self.assertIn('Pertanyaan 2:', data['answer'])
            self.assertEqual(2, len(data['sources']))
            self.assertEqual({'file_name', 'file_type', 'metadata', 'distance', 'content'},
                             set(data['sources'][0]))

    def test_legacy_client_without_catalogue_still_retrieves_each_question(self):
        answer, sources = self.service.chat(f'{NIKAH} dan {SKU}?')
        self.assertEqual(2, len(self.searches))
        self.assertEqual(2, len(sources))
        self.assertIn('Serahkan berkas', answer)
        self.assertIn('Fotokopi', answer)

    def test_prompt_does_not_encourage_unrequested_identity_placeholders(self):
        prompt = build_prompt(NIKAH, [NIKAH_SOURCE])
        self.assertNotIn('Pak/Bu ...', prompt)
        self.assertIn('Jawab hanya hal yang ditanyakan', prompt)


if __name__ == '__main__':
    unittest.main()
