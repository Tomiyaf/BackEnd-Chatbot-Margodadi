"""Authoritative UMKM listings with per-conversation continuation.

Laravel sends published names on each chat request. SQLite stores only shown
IDs and an expiry timestamp, so no catalogue copy or user message is retained.
The transaction keeps continuations consistent across threads and workers.
"""
from contextlib import closing, contextmanager
import json
import os
from pathlib import Path
import re
import sqlite3
import time

from src.config import ROOT_DIR
from src.retriever import SearchResult


def normalize_question(question: str) -> str:
    return re.sub(r"\s+", " ", re.sub(r"[^\w\s]", " ", question.lower())).strip()


def is_directory_request(question: str) -> bool:
    if not re.search(r"\bumkm\b", question):
        return False
    if re.search(r"\b(syarat|persyaratan|izin|perizinan|alamat|harga|kontak|produk|pemilik|cara|pendaftaran|registrasi)\b", question):
        return False
    return bool(re.search(
        r"\b(apa saja|apa aja|daftar|sebutkan|umkm apa|umkm di|umkm yang ada)\b",
        question,
    ))


def is_continuation(question: str) -> bool:
    return bool(re.fullmatch(
        r"(?:oke |ok |baik |iya |ya |lalu |terus |kemudian )?"
        r"(?:apa saja(?: itu)?|apa aja(?: itu)?|apa saja yang lain(?:nya)?|apa aja yang lain(?:nya)?|"
        r"apa saja umkm yang lain(?:nya)?|umkm apa lagi(?: yang ada)?|"
        r"apa saja (?:umkm )?(?:lainnya|sisanya)|(?:umkm )?yang lain(?:nya)?(?: apa(?: saja)?)?|"
        r"(?:umkm )?lainnya|(?:umkm )?sisanya(?: apa(?: saja)?)?|apa lagi|apalagi|"
        r"masih ada apa lagi|(?:ada )?yang lain(?:nya)?|"
        r"(?:sebutkan|tampilkan|lihat|boleh tahu) (?:umkm )?(?:yang lain(?:nya)?|sisanya)|"
        r"lanjut(?:kan)?(?: (?:daftar|umkm|yang lainnya))?)"
        r"(?: dong| ya| lagi| juga)?",
        question,
    ))


class UmkmDirectory:
    def __init__(self, state_path: Path | None = None, page_size: int = 5, ttl: int = 1800, clock=time.time):
        self.state_path = Path(state_path or os.getenv(
            "CHATBOT_SESSION_DB", str(ROOT_DIR / "storage/app/private/chatbot-conversations.sqlite")
        ))
        self.page_size = page_size
        self.ttl = ttl
        self.clock = clock

    @contextmanager
    def transaction(self):
        self.state_path.parent.mkdir(parents=True, exist_ok=True)
        with closing(sqlite3.connect(self.state_path, timeout=10)) as conn:
            with conn:
                conn.execute("BEGIN IMMEDIATE")
                conn.execute("""CREATE TABLE IF NOT EXISTS umkm_directory_sessions (
                    session_id TEXT PRIMARY KEY, shown_ids TEXT NOT NULL, touched_at REAL NOT NULL
                )""")
                conn.execute("DELETE FROM umkm_directory_sessions WHERE touched_at <= ?", (self.clock() - self.ttl,))
                yield conn

    def answer(self, question: str, session_id: str | None, catalogue: list[dict] | None,
               *, reset_on_topic_change: bool = True):
        question = normalize_question(question)
        continuation = is_continuation(question)
        listing = is_directory_request(question)
        # Requests from older clients without a catalogue retain the existing RAG flow.
        if catalogue is None:
            return None

        if not listing and not continuation:
            # An ambiguous follow-up must not refer back to a list after a topic change.
            if reset_on_topic_change and session_id and self.state_path.exists():
                with self.transaction() as conn:
                    conn.execute("DELETE FROM umkm_directory_sessions WHERE session_id = ?", (session_id,))
            return None

        # De-duplicate by stable ID; renamed/deactivated entries are handled on the next turn.
        entries = {int(item['id']): {'id': int(item['id']), 'name': ' '.join(item['name'].split())}
                   for item in catalogue}
        ordered = sorted(entries.values(), key=lambda item: (item['name'].casefold(), item['id']))
        if not session_id:
            if continuation:
                return 'Daftar UMKM yang mana yang Anda maksud? Silakan tanyakan daftar UMKM Pekon Margodadi terlebih dahulu.', []
            return self.format_page(ordered[:self.page_size], len(ordered), max(0, len(ordered) - self.page_size), False)

        with self.transaction() as conn:
            row = conn.execute('SELECT shown_ids FROM umkm_directory_sessions WHERE session_id = ?', (session_id,)).fetchone()
            if continuation and row is None:
                return 'Daftar UMKM yang mana yang Anda maksud? Silakan tanyakan daftar UMKM Pekon Margodadi terlebih dahulu.', []
            seen = set(json.loads(row[0])) if continuation else set()
            pending = [item for item in ordered if item['id'] not in seen]
            page = pending[:self.page_size]
            seen.update(item['id'] for item in page)
            conn.execute('''INSERT INTO umkm_directory_sessions (session_id, shown_ids, touched_at)
                VALUES (?, ?, ?) ON CONFLICT(session_id) DO UPDATE
                SET shown_ids=excluded.shown_ids, touched_at=excluded.touched_at''',
                (session_id, json.dumps(sorted(seen)), self.clock()))
            return self.format_page(page, len(ordered), len(pending) - len(page), continuation)

    @staticmethod
    def format_page(page: list[dict], total: int, remaining: int, continuation: bool):
        if total == 0:
            return 'Saat ini belum ada UMKM aktif yang terdaftar di Pekon Margodadi.', []
        if not page:
            return 'Seluruh UMKM aktif yang terdaftar di Pekon Margodadi sudah saya tampilkan. Saat ini tidak ada UMKM lainnya dalam daftar tersebut.', []
        lines = '\n'.join(f"{index}. {item['name']}" for index, item in enumerate(page, 1))
        if continuation:
            introduction = 'UMKM lainnya yang terdaftar di Pekon Margodadi adalah:'
        else:
            introduction = f'Ada {total} UMKM aktif yang terdaftar di Pekon Margodadi. Berikut beberapa di antaranya:'
        closing = ('Selain itu, masih ada beberapa UMKM lainnya.' if remaining
                   else 'Sekarang seluruh UMKM aktif yang terdaftar sudah saya tampilkan.')
        answer = f'{introduction}\n\n{lines}\n\n{closing}'
        source = SearchResult(
            file_name='Direktori UMKM Pekon Margodadi', file_type='database',
            metadata={'title': 'Direktori UMKM Pekon Margodadi', 'domain': 'UMKM',
                      'source': 'Database UMKM aktif Pekon Margodadi', 'umkm_ids': [item['id'] for item in page],
                      'total_active': total, 'remaining': remaining},
            distance=0.0, content=lines,
        )
        return answer, [source]
