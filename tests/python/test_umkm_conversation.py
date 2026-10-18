from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

from fastapi.testclient import TestClient

from src.api import app, get_rag_service
from src.rag import RAGService
from src.umkm_directory import UmkmDirectory


CATALOGUE = [{'id': index, 'name': f'Usaha {index:02}'} for index in range(1, 8)]
QUESTION = 'Apa saja UMKM yang terdapat di Pekon Margodadi?'


class UmkmConversationTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.path = Path(self.temp.name) / 'sessions.sqlite'
        self.directory = UmkmDirectory(state_path=self.path)

    def answer(self, text=QUESTION, session='web:1', catalogue=None):
        return self.directory.answer(text, session, CATALOGUE if catalogue is None else catalogue)

    @staticmethod
    def ids(answer):
        return answer[1][0].metadata['umkm_ids'] if answer[1] else []

    def test_initial_five_then_rest_then_exhausted(self):
        first = self.answer()
        self.assertEqual([1, 2, 3, 4, 5], self.ids(first))
        self.assertIn('Ada 7 UMKM aktif', first[0])
        self.assertIn('masih ada beberapa UMKM lainnya', first[0])
        rest = self.answer('Apa saja itu?')
        self.assertEqual([6, 7], self.ids(rest))
        self.assertNotIn('masih ada beberapa', rest[0])
        self.assertIn('sudah saya tampilkan', rest[0])
        exhausted = self.answer('Apa saja yang lainnya?')
        self.assertEqual([], self.ids(exhausted))
        self.assertIn('tidak ada UMKM lainnya', exhausted[0])

    def test_multiple_sessions_and_channels_never_share_progress(self):
        self.answer()
        for session in ['web:2', 'whatsapp:1']:
            self.assertEqual([1, 2, 3, 4, 5], self.ids(self.answer(session=session)))
        self.assertEqual([6, 7], self.ids(self.answer('yang lainnya?', session='web:1')))

    def test_state_survives_a_new_service_instance(self):
        self.answer()
        another = UmkmDirectory(state_path=self.path)
        self.assertEqual([6, 7], self.ids(another.answer('apa saja itu', 'web:1', CATALOGUE)))

    def test_expired_session_requests_context_instead_of_repeating_a_page(self):
        clock = [1000]
        self.directory = UmkmDirectory(state_path=self.path, ttl=30, clock=lambda: clock[0])
        self.answer()
        clock[0] = 1031
        expired = self.answer('apa saja itu')
        self.assertEqual([], self.ids(expired))
        self.assertIn('terlebih dahulu', expired[0])

    def test_empty_small_and_exact_page_catalogues_never_claim_more_data(self):
        for catalogue in [[], CATALOGUE[:3], CATALOGUE[:5]]:
            response = self.answer(catalogue=catalogue)
            self.assertEqual(len(catalogue), len(self.ids(response)))
            self.assertNotIn('masih ada beberapa', response[0])
        self.assertIn('belum ada UMKM aktif', self.answer(catalogue=[])[0])

    def test_current_catalogue_changes_are_used_without_repeating_seen_ids(self):
        self.answer()
        changed = CATALOGUE[1:] + [{'id': 8, 'name': 'Usaha 08'}]
        response = self.answer('apa saja itu', catalogue=changed)
        self.assertEqual([6, 7, 8], self.ids(response))
        self.assertNotIn('Usaha 01', response[0])

    def test_new_listing_resets_progress_and_duplicate_ids_do_not_repeat(self):
        self.answer()
        self.answer('yang lainnya')
        self.assertEqual([1, 2, 3, 4, 5], self.ids(self.answer(catalogue=CATALOGUE + CATALOGUE)))

    def test_topic_change_and_new_session_do_not_reuse_an_old_list(self):
        self.answer()
        self.assertIsNone(self.answer('Apa saja syarat membuat KTP?'))
        self.assertIn('terlebih dahulu', self.answer('apa saja itu')[0])
        self.assertIn('terlebih dahulu', self.answer('apa saja itu', session='web:99')[0])

    def test_no_session_has_no_shared_history(self):
        self.assertEqual([1, 2, 3, 4, 5], self.ids(self.answer(session=None)))
        self.assertIn('terlebih dahulu', self.answer('apa saja itu', session=None)[0])
        self.assertFalse(self.path.exists())

    def test_natural_follow_up_variations(self):
        for text in ['apa saja itu?', 'yang lainnya?', 'apa lagi?', 'masih ada apa lagi?',
                     'sebutkan sisanya', 'UMKM lainnya', 'Apa saja UMKM yang lainnya?',
                     'UMKM apa lagi yang ada?', 'boleh tahu yang lainnya?', 'lanjut']:
            with self.subTest(text=text):
                self.answer()
                self.assertEqual([6, 7], self.ids(self.answer(text)))

    def test_detail_and_registration_questions_use_normal_rag(self):
        for text in ['Apa saja syarat registrasi UMKM?', 'Berapa harga kopi UMKM?',
                     'Apa alamat UMKM Kopi Margodadi?', 'Apa saja produk UMKM Kopi?']:
            self.assertIsNone(self.answer(text))

    def test_concurrent_continuations_do_not_repeat_entries(self):
        catalogue = [{'id': index, 'name': f'Usaha {index:02}'} for index in range(1, 16)]
        self.answer(catalogue=catalogue)
        with ThreadPoolExecutor(max_workers=2) as pool:
            results = list(pool.map(lambda _: self.answer('yang lainnya', catalogue=catalogue), range(2)))
        self.assertEqual(set(range(6, 16)), set(self.ids(results[0]) + self.ids(results[1])))
        self.assertFalse(set(self.ids(results[0])) & set(self.ids(results[1])))

    def test_api_directory_needs_no_embedding_or_llm_and_validates_session(self):
        service = RAGService()
        service.directory = self.directory
        app.dependency_overrides[get_rag_service] = lambda: service
        self.addCleanup(app.dependency_overrides.clear)
        with patch('src.rag.Retriever', side_effect=AssertionError('Directory must not load embeddings')), \
             patch('src.rag.APILLM', side_effect=AssertionError('Directory must not call the LLM')), TestClient(app) as client:
            payload = {'question': QUESTION, 'session_id': 'web:1', 'umkm_catalogue': CATALOGUE}
            response = client.post('/chat', json=payload)
            self.assertEqual(200, response.status_code)
            self.assertEqual([1, 2, 3, 4, 5], response.json()['sources'][0]['metadata']['umkm_ids'])
            response = client.post('/chat', json={**payload, 'question': 'apa saja itu'})
            self.assertEqual([6, 7], response.json()['sources'][0]['metadata']['umkm_ids'])
            invalid = client.post('/chat', json={**payload, 'session_id': 'x' * 129})
            self.assertEqual(422, invalid.status_code)

    def test_legacy_api_requests_and_unrelated_questions_keep_normal_rag(self):
        service = RAGService()
        service.directory = self.directory
        with patch.object(service, 'search', return_value=[]) as search, patch('src.rag.APILLM') as llm:
            llm.return_value.generate.return_value = 'Jawaban dari RAG'
            self.assertEqual(('Jawaban dari RAG', []), service.chat('Apa saja UMKM di Margodadi?'))
            search.assert_called_once()


if __name__ == '__main__':
    unittest.main()
