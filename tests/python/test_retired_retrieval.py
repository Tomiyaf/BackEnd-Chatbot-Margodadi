"""Run on a restored PostgreSQL copy: set RAG_TEST_DATABASE_URL explicitly."""
import os
import unittest
from unittest.mock import patch

import psycopg
from psycopg.conninfo import conninfo_to_dict
from psycopg.types.json import Jsonb

TEST_DSN = os.environ.get("RAG_TEST_DATABASE_URL", "")
if not TEST_DSN or not conninfo_to_dict(TEST_DSN).get("dbname", "").startswith("step9_chatbot_"):
    raise RuntimeError("Use an isolated step9_chatbot_ database, never the application database.")
os.environ["DATABASE_URL"] = TEST_DSN

from src.retriever import Retriever
from src.vector_store import list_documents


class RetiredRetrievalTest(unittest.TestCase):
    def setUp(self):
        self.ids = []
        self.allowed = set()
        self.blocked = set()
        self.vector = [1.0] + [0.0] * 1023
        cases = [
            ({"domain": "PUBLIC_SERVICE"}, {"domain": "PUBLIC_SERVICE"}, True),
            ({"domain": "UMKM"}, {"domain": "UMKM"}, True),
            ({}, {}, True),  # Existing local PDF/Excel files need no domain label.
            ({"domain": "WASTE_EDUCATION"}, {}, False),
            ({}, {"domain": "WASTE_EDUCATION"}, False),
            ({"is_active": False}, {}, False),
            ({}, {"is_active": False}, False),
        ]
        with psycopg.connect(TEST_DSN, connect_timeout=5) as conn:
            for index, (document_meta, chunk_meta, allowed) in enumerate(cases):
                document_id = conn.execute(
                    "INSERT INTO documents (file_name,file_type,source_path,metadata,status) "
                    "VALUES (%s,'txt',%s,%s,'indexed') RETURNING id",
                    (f"regression-policy-{index}.txt", f"policy-test/{index}", Jsonb(document_meta)),
                ).fetchone()[0]
                self.ids.append(document_id)
                file_name = f"regression-policy-{index}.txt"
                (self.allowed if allowed else self.blocked).add(file_name)
                conn.execute(
                    "INSERT INTO document_chunks (document_id,chunk_index,content,metadata,embedding) "
                    "VALUES (%s,0,%s,%s,%s::vector)",
                    (document_id, "REGRESSION2026 RETIREDMARKER policy regression", Jsonb(chunk_meta), str(self.vector)),
                )

    def tearDown(self):
        with psycopg.connect(TEST_DSN, connect_timeout=5) as conn:
            conn.execute("DELETE FROM documents WHERE id = ANY(%s)", (self.ids,))

    def verify_search(self, vector_mode):
        retriever = object.__new__(Retriever)
        class FixedEmbedder:
            def encode(_, texts):
                return [self.vector for _ in texts]
        retriever.embedder = FixedEmbedder()
        with patch("src.retriever._has_vector_type", return_value=vector_mode):
            results = retriever.search("REGRESSION2026 RETIREDMARKER", top_k=20)
        found = {result.file_name for result in results}
        self.assertTrue(self.allowed <= found, found)
        self.assertFalse(self.blocked & found, found)

    def test_vector_and_keyword_retrieval_exclude_retired_and_inactive_sources(self):
        self.verify_search(True)

    def test_text_embedding_fallback_preserves_the_same_visibility_rules(self):
        self.verify_search(False)

    def test_catalogue_hides_retired_documents_and_chunks_without_deleting_them(self):
        catalogue = {item["file_name"]: item for item in list_documents()}
        self.assertNotIn("regression-policy-3.txt", catalogue)
        self.assertEqual(0, catalogue["regression-policy-4.txt"]["chunk_count"])
        self.assertIn("regression-policy-2.txt", catalogue)
        with psycopg.connect(TEST_DSN, connect_timeout=5) as conn:
            self.assertEqual(7, conn.execute("SELECT count(*) FROM documents WHERE id = ANY(%s)", (self.ids,)).fetchone()[0])


if __name__ == "__main__":
    unittest.main()
