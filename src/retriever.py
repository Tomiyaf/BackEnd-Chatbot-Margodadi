from dataclasses import dataclass
import re
from typing import Any

from pgvector.psycopg import register_vector

from src.config import RAG_TOP_K
from src.db import get_connection
from src.embedder import Embedder


@dataclass(frozen=True)
class SearchResult:
    content: str
    file_name: str
    file_type: str
    metadata: dict[str, Any]
    distance: float


class Retriever:
    STOPWORDS = {
        "ada",
        "apa",
        "apakah",
        "saja",
        "siapa",
        "yang",
        "dan",
        "atau",
        "pada",
        "di",
        "ke",
        "dari",
        "itu",
        "ini",
        "berapa",
        "jumlah",
        "pekon",
        "desa",
        "kelurahan",
        "margodadi",
    }

    def __init__(self) -> None:
        self.embedder = Embedder()

    def _normalized_question(self, question: str) -> str:
        normalized = question.lower()
        replacements = {
            "masyarakat": "penduduk",
            "warga": "penduduk",
        }

        for source, target in replacements.items():
            normalized = re.sub(rf"(?<!\w){source}(?!\w)", target, normalized)

        normalized = re.sub(r"\b([ds])\s*-?\s*(\d+)\b", r"\1-\2", normalized)
        return normalized

    def _query_terms(self, question: str) -> list[str]:
        normalized = self._normalized_question(question)
        words = re.findall(r"[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)?", normalized)
        terms = []
        for word in words:
            is_level_code = bool(re.fullmatch(r"[a-z]-\d+", word))
            if (len(word) < 4 and not is_level_code) or word in self.STOPWORDS:
                continue
            if word not in terms:
                terms.append(word)
        return terms[:8]

    def _query_phrases(self, question: str) -> list[str]:
        normalized = self._normalized_question(question)
        words = re.findall(r"[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)?", normalized)
        phrases = []

        for size in (3, 2):
            for index in range(len(words) - size + 1):
                chunk = words[index : index + size]
                has_informative_word = any(
                    len(word) >= 4 and word not in self.STOPWORDS for word in chunk
                )
                if not has_informative_word:
                    continue

                phrase = " ".join(chunk)
                if phrase not in phrases:
                    phrases.append(phrase)

        return phrases[:12]

    @staticmethod
    def _exact_count(content: str, term: str) -> int:
        return len(re.findall(rf"(?<!\w){re.escape(term)}(?!\w)", content))

    def _hybrid_score(
        self,
        row: tuple[Any, ...],
        terms: list[str],
        phrases: list[str],
    ) -> float:
        content = row[1].lower()
        exact_counts = [self._exact_count(content, term) for term in terms]
        exact_matches = sum(1 for count in exact_counts if count > 0)
        total_occurrences = sum(exact_counts)
        phrase_matches = sum(1 for phrase in phrases if phrase in content)

        exact_boost = min(0.60, exact_matches * 0.24)
        occurrence_boost = min(0.16, max(0, total_occurrences - exact_matches) * 0.03)
        phrase_boost = min(0.40, phrase_matches * 0.20)
        keyword_boost = exact_boost + occurrence_boost + phrase_boost
        return float(row[5]) - keyword_boost

    def search(self, question: str, top_k: int = RAG_TOP_K) -> list[SearchResult]:
        query_embedding = self.embedder.encode([question])[0]
        terms = self._query_terms(question)
        phrases = self._query_phrases(question)
        candidate_limit = max(top_k * 5, 30)

        with get_connection() as conn:
            register_vector(conn)
            with conn.cursor() as cur:
                cur.execute(
                    """
                    SELECT
                        c.id,
                        c.content,
                        d.file_name,
                        d.file_type,
                        c.metadata,
                        c.embedding <=> %s::vector AS distance
                    FROM document_chunks c
                    JOIN documents d ON d.id = c.document_id
                    ORDER BY c.embedding <=> %s::vector
                    LIMIT %s
                    """,
                    (query_embedding, query_embedding, candidate_limit),
                )
                rows = list(cur.fetchall())

                keyword_queries = [*phrases, *terms]
                if keyword_queries:
                    conditions = " OR ".join(["c.content ILIKE %s"] * len(keyword_queries))
                    params = [f"%{query}%" for query in keyword_queries]
                    rank_expr = " + ".join(
                        ["CASE WHEN c.content ILIKE %s THEN 1 ELSE 0 END"] * len(keyword_queries)
                    )
                    keyword_limit = max(top_k * 20, 80)
                    cur.execute(
                        f"""
                        SELECT
                            c.id,
                            c.content,
                            d.file_name,
                            d.file_type,
                            c.metadata,
                            c.embedding <=> %s::vector AS distance
                            , ({rank_expr}) AS keyword_rank
                        FROM document_chunks c
                        JOIN documents d ON d.id = c.document_id
                        WHERE {conditions}
                        ORDER BY keyword_rank DESC, c.embedding <=> %s::vector
                        LIMIT %s
                        """,
                        (
                            query_embedding,
                            *params,
                            *params,
                            query_embedding,
                            keyword_limit,
                        ),
                    )
                    rows.extend(cur.fetchall())

        unique_rows = {}
        for row in rows:
            unique_rows[row[0]] = row

        ranked_rows = sorted(
            unique_rows.values(),
            key=lambda row: self._hybrid_score(row, terms, phrases),
        )[:top_k]

        return [
            SearchResult(
                content=row[1],
                file_name=row[2],
                file_type=row[3],
                metadata=row[4],
                distance=float(row[5]),
            )
            for row in ranked_rows
        ]
