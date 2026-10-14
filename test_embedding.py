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
    EMBEDDING_API_MODEL,
    EMBEDDING_DIMENSION,
    EMBEDDING_MODEL_PATH,
    EMBEDDING_PROVIDER,
)
from src.embedder import Embedder, cosine_similarity


def main() -> None:
    print("=" * 60)
    print("   UJI COBA EMBEDDER RAG PEKON MARGODADI")
    print("=" * 60)
    print(f"Mode Provider : {EMBEDDING_PROVIDER.upper()}")
    if EMBEDDING_PROVIDER == "api":
        print(f"Model Cloud   : {EMBEDDING_API_MODEL} (Target Dimensi: {EMBEDDING_DIMENSION})")
    else:
        print(f"Model Lokal   : {EMBEDDING_MODEL_PATH}")
    print("=" * 60)

    print("\n[1/3] Memuat Embedder...")
    try:
        embedder = Embedder()
        print(f"[OK] Engine Embedder [{embedder.provider_name.upper()}] siap.")
    except Exception as exc:
        print(f"[ERROR] Gagal memuat embedder: {exc}")
        if EMBEDDING_PROVIDER == "api":
            print("\nTips: Pastikan EMBEDDING_API_KEY atau LLM_API_KEY sudah diisi di file .env.")
        else:
            print("\nTips: Pastikan file model lokal ada atau gunakan mode API dengan mengubah EMBEDDING_PROVIDER=api di .env.")
        sys.exit(1)

    query = "Apa syarat membuat surat domisili di Pekon Margodadi?"
    documents = [
        "Persyaratan pembuatan surat domisili adalah KTP dan Kartu Keluarga pemohon.",
        "Pelayanan kantor Pekon dilaksanakan dari hari Senin sampai Jumat.",
        "Pemerintah Pekon mengadakan kegiatan gotong royong setiap bulan.",
    ]

    print("\n[2/3] Membuat vektor embedding...")
    t0 = time.perf_counter()
    try:
        query_vec = embedder.encode([query])[0]
        doc_vecs = embedder.encode(documents)
    except Exception as exc:
        print(f"[ERROR] Terjadi kesalahan saat encoding teks: {exc}")
        sys.exit(1)

    elapsed_ms = (time.perf_counter() - t0) * 1000.0
    print(f"[OK] Embedding berhasil dibuat dalam {elapsed_ms:.1f} ms.")
    print(f"[*] Dimensi Query   : {len(query_vec)}")
    print(f"[*] Dimensi Dokumen : {len(doc_vecs[0])}")

    print("\n[3/3] Menghitung kemiripan (Cosine Similarity)...")
    print("-" * 60)

    for i, (doc, vec) in enumerate(zip(documents, doc_vecs), start=1):
        score = cosine_similarity(query_vec, vec)
        print(f"\nDokumen {i}")
        print(f"Teks  : \"{doc}\"")
        print(f"Skor  : {score:.4f}")

    print("\n" + "=" * 60)
    print("Pengujian embedding selesai dengan sukses!")
    print("=" * 60)


if __name__ == "__main__":
    main()