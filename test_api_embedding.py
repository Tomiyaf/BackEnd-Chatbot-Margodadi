from __future__ import annotations

import sys
import time

# Pastikan encoding UTF-8 di Windows Console
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except Exception:
        pass

from src.config import (
    EMBEDDING_API_BASE_URL,
    EMBEDDING_API_KEY,
    EMBEDDING_API_MODEL,
    EMBEDDING_API_PATH,
    EMBEDDING_DIMENSION,
    EMBEDDING_PROVIDER,
)
from src.embedder import APIEmbedder, cosine_similarity


def mask_key(key: str) -> str:
    if not key:
        return "(KOSONG - belum diisi)"
    if len(key) <= 8:
        return key[:2] + "****"
    return key[:6] + "..." + key[-4:]


def main() -> None:
    print("=" * 60)
    print("   UJI KONEKSI & EMBEDDING AI (PEKON MARGODADI)")
    print("=" * 60)
    print(f"Provider aktif : {EMBEDDING_PROVIDER}")
    print(f"Base URL       : {EMBEDDING_API_BASE_URL}{EMBEDDING_API_PATH}")
    print(f"Model          : {EMBEDDING_API_MODEL}")
    print(f"Target Dimensi : {EMBEDDING_DIMENSION}")
    print(f"API Key        : {mask_key(EMBEDDING_API_KEY)}")
    print("=" * 60)

    if not EMBEDDING_API_KEY or EMBEDDING_API_KEY == "isi_api_key_di_sini":
        print("\n[!] PERINGATAN: API KEY BELUM DIISI!")
        print("------------------------------------------------------------")
        print("Petunjuk pengisian:")
        print("1. Buka file: bechatbot/.env")
        print("2. Masukkan API Key Anda pada baris:")
        print("   EMBEDDING_API_KEY=AIzaSy... (atau pada LLM_API_KEY=...)")
        print("3. Simpan file .env, lalu jalankan kembali:")
        print("   python test_api_embedding.py")
        print("------------------------------------------------------------\n")
        sys.exit(1)

    print("\n[1/3] Menginisialisasi Wadah AI Embedding (APIEmbedder)...")
    try:
        embedder = APIEmbedder()
        print("[OK] Wadah AI Embedder berhasil dibuat.")
    except Exception as exc:
        print(f"[ERROR] Gagal inisialisasi: {exc}")
        sys.exit(1)

    query = "Apa syarat membuat surat domisili di Pekon Margodadi?"
    documents = [
        "Persyaratan pembuatan surat domisili adalah fotokopi KTP dan Kartu Keluarga asli pemohon.",
        "Pelayanan administrasi kantor Pekon Margodadi buka setiap hari kerja Senin sampai Jumat.",
        "Warga Pekon Margodadi rutin melaksanakan kerja bakti gotong royong membersihkan selokan setiap bulan.",
    ]

    print(f"\n[2/3] Mengirim request ke Embedding API ({EMBEDDING_API_MODEL})...")
    print(f"Query : \"{query}\"")
    print(f"Jumlah dokumen pengujian: {len(documents)}")

    start_time = time.perf_counter()
    try:
        query_vec = embedder.encode([query])[0]
        doc_vecs = embedder.encode(documents)
    except Exception as exc:
        print(f"\n[ERROR] Terjadi kesalahan saat memanggil Embedding API:")
        print(f"   {exc}\n")
        print("Tips Troubleshooting:")
        print("- Pastikan koneksi internet aktif.")
        print("- Periksa apakah API Key sudah benar dan memiliki akses ke model.")
        print(f"- Coba cek base URL di .env: {EMBEDDING_API_BASE_URL}")
        sys.exit(1)

    elapsed_ms = (time.perf_counter() - start_time) * 1000.0

    print("[OK] Embedding berhasil diterima dari Cloud API!")
    print(f"[*] Waktu respons : {elapsed_ms:.1f} ms")
    print(f"[*] Dimensi vektor: {len(query_vec)} dimensi")

    print("\n[3/3] Menghitung Skor Kemiripan Semantik (Cosine Similarity)...")
    print("-" * 60)

    results = []
    for idx, (doc, vec) in enumerate(zip(documents, doc_vecs), start=1):
        score = cosine_similarity(query_vec, vec)
        results.append((idx, doc, score))

    # Urutkan dari skor tertinggi
    results.sort(key=lambda x: x[2], reverse=True)

    for rank, (idx, doc, score) in enumerate(results, start=1):
        relevance = "SANGAT RELEVAN" if score > 0.60 else ("KURANG RELEVAN" if score > 0.35 else "TIDAK RELEVAN")
        print(f"\nPeringkat #{rank} [Dokumen {idx}] - Skor: {score:.4f} ({relevance})")
        print(f"Isi: \"{doc}\"")

    print("\n" + "=" * 60)
    print("[SUKSES] Wadah Embedding AI siap digunakan oleh RAG & Chatbot.")
    print("=" * 60)


if __name__ == "__main__":
    main()
