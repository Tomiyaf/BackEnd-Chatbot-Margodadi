from __future__ import annotations

from pathlib import Path
import re

from src.config import EMBEDDING_DIMENSION
from src.db import get_connection


def main() -> None:
    schema_path = Path(__file__).with_name("schema.sql")
    schema_sql = schema_path.read_text(encoding="utf-8")

    with get_connection() as conn:
        with conn.cursor() as cur:
            # Cek ketersediaan ekstensi vector di PostgreSQL
            cur.execute("SELECT 1 FROM pg_available_extensions WHERE name = 'vector'")
            has_vector = bool(cur.fetchone())

            if has_vector:
                try:
                    cur.execute("CREATE EXTENSION IF NOT EXISTS vector;")
                except Exception:
                    has_vector = False

            if has_vector:
                adjusted_sql = re.sub(
                    r"vector\(\s*\d+\s*\)",
                    f"vector({target_dim})",
                    schema_sql,
                )
            else:
                print("[INFO] Ekstensi PostgreSQL 'vector' (pgvector) belum terpasang di sistem.")
                print("[INFO] Menyiapkan schema dengan mode fallback teks/JSON untuk embedding.")
                adjusted_sql = schema_sql.replace("CREATE EXTENSION IF NOT EXISTS vector;", "")
                adjusted_sql = re.sub(
                    r"embedding\s+vector\(\s*\d+\s*\)\s+NOT\s+NULL",
                    "embedding text NOT NULL",
                    adjusted_sql,
                )
                adjusted_sql = re.sub(
                    r"CREATE\s+INDEX\s+IF\s+NOT\s+EXISTS\s+document_chunks_embedding_hnsw_idx[^;]+;",
                    "",
                    adjusted_sql,
                )

            # 1. Jalankan schema SQL dasar
            cur.execute(adjusted_sql)

            # 2. Jika vector tersedia, cek kesesuaian dimensi
            if has_vector:
                cur.execute(
                    """
                    SELECT atttypmod
                    FROM pg_attribute
                    JOIN pg_class ON pg_class.oid = pg_attribute.attrelid
                    JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
                    WHERE pg_class.relname = 'document_chunks'
                      AND pg_attribute.attname = 'embedding'
                      AND pg_attribute.attnum > 0;
                    """
                )
                row = cur.fetchone()
                if row and row[0] is not None and row[0] > 0 and row[0] != target_dim:
                    current_dim = row[0]
                    print(
                        f"⚠️ Dimensi vektor saat ini ({current_dim}) berbeda dari konfigurasi "
                        f"target ({target_dim}). Memperbarui tipe kolom..."
                    )
                    try:
                        cur.execute("DROP INDEX IF EXISTS document_chunks_embedding_hnsw_idx;")
                        cur.execute(
                            f"ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector({target_dim});"
                        )
                        cur.execute(
                            "CREATE INDEX IF NOT EXISTS document_chunks_embedding_hnsw_idx "
                            "ON document_chunks USING hnsw (embedding vector_cosine_ops);"
                        )
                        print(f"✅ Dimensi kolom embedding berhasil diubah menjadi vector({target_dim}).")
                    except Exception as exc:
                        print(
                            f"ℹ️ Catatan: Jika tabel sudah berisi data dengan dimensi lama, "
                            f"Anda dapat mengosongkan chunks atau reindex: {exc}"
                        )

    mode_info = f"vektor native ({target_dim} dimensi)" if has_vector else "fallback JSON/teks"
    print(f"[OK] Database schema berhasil disiapkan dengan mode: {mode_info}.")


if __name__ == "__main__":
    main()
