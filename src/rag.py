from concurrent.futures import ThreadPoolExecutor
import json
import logging

import httpx
import psycopg

from src.config import RAG_TOP_K
from src.llm import APILLM
from src.retriever import Retriever, SearchResult
from src.umkm_directory import UmkmDirectory, is_continuation, is_directory_request, normalize_question
from src.answer_format import NO_INFORMATION, formatting_instruction, normalize_answer_layout, requested_format
from src.question_plan import QuestionPart, plan_questions


logger = logging.getLogger(__name__)
UNAVAILABLE = (
    'Maaf, bagian pertanyaan ini belum dapat diproses karena layanan sedang '
    'mengalami gangguan. Silakan coba lagi.'
)


def build_prompt(question: str, contexts: list[SearchResult]) -> str:
    context_text = "\n\n".join(
        f"[Sumber {index}]\n"
        f"File: {result.file_name}\n"
        f"Metadata: {result.metadata}\n"
        f"Isi: {result.content}"
        for index, result in enumerate(contexts, start=1)
    )

    return (
        "Anda menjawab untuk basis data Pekon Margodadi.\n"
        "Semua konteks yang diberikan berasal dari dokumen yang sudah diunggah "
        "ke basis pengetahuan Pekon Margodadi, kecuali konteks menyatakan hal lain.\n"
        "Jawab pertanyaan hanya berdasarkan konteks.\n"
        "Jawab hanya hal yang ditanyakan. Jangan menambahkan bagian identitas, biaya, "
        "waktu, kontak, atau fakta lain jika tidak diminta. Jangan menulis placeholder "
        "nama atau nilai rekaan. Gunakan kalimat lengkap dan natural untuk fakta tunggal.\n"
        "Untuk jabatan kepala desa/lurah, gunakan sapaan \"Pak\" sebelum nama "
        "kecuali konteks jelas menyebut sapaan lain.\n"
        "Jika pertanyaan menyebut Pekon Margodadi dan konteks relevan berasal "
        "dari dokumen basis pengetahuan ini, sebutkan Pekon Margodadi dalam jawaban.\n"
        "Jika pertanyaan memakai istilah warga/orang tetapi konteks menyediakan "
        "satuan keluarga/rumah tangga, jawab menggunakan satuan yang tersedia dan "
        "jelaskan singkat perbedaan satuannya.\n"
        "Jangan meminta daftar keluarga mentah jika konteks sudah memuat jumlah agregat.\n"
        "Jika informasi benar-benar tidak ada di konteks, jawab: "
        "\"Maaf, informasi tersebut tidak tersedia dalam data.\" "
        "Gunakan kalimat tersebut sebagai satu-satunya jawaban hanya ketika tidak ada informasi yang menjawab pertanyaan. "
        "Jangan menambahkannya setelah jawaban yang sudah menjawab pertanyaan. "
        "Jika hanya sebagian informasi tidak tersedia, jelaskan bagian yang belum tersedia secara spesifik.\n\n"
        f"Konteks:\n{context_text}\n\n"
        f"Pertanyaan:\n{question}\n\n"
        f"{formatting_instruction(question)}"
        "Jawaban akhir hanya menjawab pertanyaan di atas, bukan ringkasan seluruh konteks. "
        "Jangan menambahkan kategori atau informasi lain yang tidak ditanyakan. "
        "Pertahankan nama layanan dari sumber tanpa membuat singkatan baru.\n"
    )


class RAGService:
    def __init__(self) -> None:
        self._retriever: Retriever | None = None
        self.llm: APILLM | None = None
        self.directory = UmkmDirectory()

    @property
    def retriever(self) -> Retriever:
        if self._retriever is None:
            self._retriever = Retriever()
        return self._retriever

    def search(self, question: str, top_k: int = RAG_TOP_K) -> list[SearchResult]:
        return self.retriever.search(question, top_k)

    def chat(
        self,
        question: str,
        top_k: int = RAG_TOP_K,
        session_id: str | None = None,
        umkm_catalogue: list[dict] | None = None,
    ) -> tuple[str, list[SearchResult]]:
        parts = plan_questions(question)
        if len(parts) > 1:
            return self._chat_multiple(parts, top_k, session_id, umkm_catalogue)
        question = parts[0].question

        directory_answer = self.directory.answer(question, session_id, umkm_catalogue)
        if directory_answer is not None:
            return directory_answer

        contexts = self.search(question, top_k)
        return self._generate_answer(question, contexts), contexts

    def _generate_answer(self, question: str, contexts: list[SearchResult]) -> str:
        prompt = build_prompt(question, contexts)

        if self.llm is None:
            self.llm = APILLM()

        if requested_format(question) == 'plain':
            answer = self.llm.generate(prompt)
        else:
            # Leave room for complete lists instead of cutting a procedure mid-step.
            answer = self.llm.generate(prompt, max_new_tokens=768)
        return normalize_answer_layout(question, answer)

    def _chat_multiple(
        self,
        parts: list[QuestionPart],
        top_k: int,
        session_id: str | None,
        umkm_catalogue: list[dict] | None,
    ) -> tuple[str, list[SearchResult]]:
        # A second topic in the same message must not erase a just-shown UMKM
        # page. An entirely unrelated next message still resets it as before.
        includes_directory = any(
            is_directory_request(normalize_question(part.question))
            or is_continuation(normalize_question(part.question)) for part in parts
        )
        answers: list[str | None] = [None] * len(parts)
        sources: list[list[SearchResult]] = [[] for _ in parts]
        pending = []
        for index, part in enumerate(parts):
            try:
                directory_answer = self.directory.answer(
                    part.question, session_id, umkm_catalogue,
                    reset_on_topic_change=not includes_directory,
                )
                if directory_answer is not None:
                    answers[index], sources[index] = directory_answer
                    continue
                # Each part gets its own top_k, with no competition for sources.
                contexts = self.search(part.question, top_k)
                sources[index] = contexts
                if contexts:
                    pending.append((index, part.question, contexts))
                else:
                    answers[index] = NO_INFORMATION
            except (RuntimeError, httpx.HTTPError, psycopg.Error):
                logger.exception('Retrieval failed for question part %s', index + 1)
                answers[index] = UNAVAILABLE

        def generate(item):
            index, question, contexts = item
            try:
                return index, self._generate_answer(question, contexts)
            except (RuntimeError, httpx.HTTPError):
                logger.exception('Generation failed for question part %s', index + 1)
                return index, UNAVAILABLE

        # Retrieval/embedding stays sequential. Independent generation overlaps
        # to avoid multiplying Laravel's request time by the number of questions.
        if pending:
            with ThreadPoolExecutor(max_workers=min(3, len(pending))) as pool:
                for index, answer in pool.map(generate, pending):
                    answers[index] = answer

        combined = '\n\n'.join(
            f'Pertanyaan {index + 1}: {part.label}\n{answers[index]}'
            for index, part in enumerate(parts)
        )
        # Keep all distinct source passages in question order and retain their
        # original metadata so the existing API/frontend contract stays valid.
        unique_sources = []
        seen = set()
        for group in sources:
            for source in group:
                key = (source.file_name, source.file_type, source.content,
                       json.dumps(source.metadata, sort_keys=True, default=str))
                if key not in seen:
                    unique_sources.append(source)
                    seen.add(key)
        return combined, unique_sources
