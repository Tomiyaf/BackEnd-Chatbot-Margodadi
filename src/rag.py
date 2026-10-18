from src.config import RAG_TOP_K
from src.llm import APILLM
from src.retriever import Retriever, SearchResult
from src.umkm_directory import UmkmDirectory


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
        "Gunakan kalimat lengkap. Untuk pertanyaan identitas atau fakta tunggal, "
        "jawab dengan kalimat natural, misalnya: \"Kepala Desa Pekon Margodadi "
        "adalah Pak/Bu ...\" jika nama tersedia di konteks.\n"
        "Untuk jabatan kepala desa/lurah, gunakan sapaan \"Pak\" sebelum nama "
        "kecuali konteks jelas menyebut sapaan lain.\n"
        "Jika pertanyaan menyebut Pekon Margodadi dan konteks relevan berasal "
        "dari dokumen basis pengetahuan ini, sebutkan Pekon Margodadi dalam jawaban.\n"
        "Jika pertanyaan memakai istilah warga/orang tetapi konteks menyediakan "
        "satuan keluarga/rumah tangga, jawab menggunakan satuan yang tersedia dan "
        "jelaskan singkat perbedaan satuannya.\n"
        "Jangan meminta daftar keluarga mentah jika konteks sudah memuat jumlah agregat.\n"
        "Jika informasi benar-benar tidak ada di konteks, jawab: "
        "\"Maaf, informasi tersebut tidak tersedia dalam data.\"\n\n"
        f"Konteks:\n{context_text}\n\n"
        f"Pertanyaan:\n{question}"
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
        directory_answer = self.directory.answer(question, session_id, umkm_catalogue)
        if directory_answer is not None:
            return directory_answer

        contexts = self.search(question, top_k)
        prompt = build_prompt(question, contexts)

        if self.llm is None:
            self.llm = APILLM()

        answer = self.llm.generate(prompt)
        return answer, contexts
