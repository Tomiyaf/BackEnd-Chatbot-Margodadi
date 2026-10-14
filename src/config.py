import os
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parents[1]

try:
    from dotenv import load_dotenv
    load_dotenv(ROOT_DIR / ".env")
except ImportError:
    # Fallback parser bawaan Python jika library python-dotenv belum terinstall
    env_file = ROOT_DIR / ".env"
    if env_file.exists():
        try:
            with open(env_file, "r", encoding="utf-8") as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith("#") and "=" in line:
                        k, _, v = line.partition("=")
                        k = k.strip()
                        v = v.strip().strip("'\"")
                        if k and k not in os.environ:
                            os.environ[k] = v
        except Exception:
            pass

DATABASE_URL = os.getenv("DATABASE_URL", "")
EMBEDDING_MODEL_PATH = ROOT_DIR / os.getenv("EMBEDDING_MODEL_PATH", "./models/bge-m3")
RAG_TOP_K = int(os.getenv("RAG_TOP_K", "3"))
UPLOAD_DIR = ROOT_DIR / os.getenv("UPLOAD_DIR", "./uploads")

# ==========================================
# LLM API Configuration
# ==========================================
LLM_API_BASE_URL = os.getenv(
    "LLM_API_BASE_URL",
    "https://generativelanguage.googleapis.com/v1beta/openai",
)
LLM_API_CHAT_PATH = os.getenv("LLM_API_CHAT_PATH", "/chat/completions")
LLM_API_KEY = os.getenv("LLM_API_KEY", "")
LLM_API_MODEL = os.getenv("LLM_API_MODEL", "gemini-flash-lite-latest")
LLM_API_FALLBACK_MODELS = [
    model.strip()
    for model in os.getenv("LLM_API_FALLBACK_MODELS", "").split(",")
    if model.strip()
]
LLM_API_TIMEOUT = float(os.getenv("LLM_API_TIMEOUT", "60"))
LLM_API_TEMPERATURE = float(os.getenv("LLM_API_TEMPERATURE", "0.2"))

# ==========================================
# Embedding AI Configuration
# ==========================================
# Provider: 'api' (OpenAI / Gemini / dll) atau 'local' (SentenceTransformer BGE-M3)
# Default 'api' jika EMBEDDING_API_KEY atau LLM_API_KEY terisi, selain itu 'local'
_has_api_key = bool(os.getenv("EMBEDDING_API_KEY") or os.getenv("LLM_API_KEY"))
EMBEDDING_PROVIDER = os.getenv(
    "EMBEDDING_PROVIDER",
    "api" if _has_api_key else "local",
).lower().strip()

# API Key Embedding: jika kosong, otomatis mewarisi LLM_API_KEY
EMBEDDING_API_KEY = os.getenv("EMBEDDING_API_KEY", "") or LLM_API_KEY

# Base URL Embedding: default mewarisi LLM_API_BASE_URL atau Gemini OpenAI endpoint
EMBEDDING_API_BASE_URL = (
    os.getenv("EMBEDDING_API_BASE_URL", "")
    or LLM_API_BASE_URL
    or "https://generativelanguage.googleapis.com/v1beta/openai"
)
EMBEDDING_API_PATH = os.getenv("EMBEDDING_API_PATH", "/embeddings")

# Nama model embedding AI:
# - Gemini: text-embedding-004
# - OpenAI: text-embedding-3-small / text-embedding-3-large
EMBEDDING_API_MODEL = os.getenv("EMBEDDING_API_MODEL", "text-embedding-004")

# Dimensi vektor embedding:
# - Gemini text-embedding-004: 768
# - OpenAI text-embedding-3-small: 1536
# - BGE-M3 (lokal): 1024
def _detect_default_dimension() -> int:
    if "text-embedding-004" in EMBEDDING_API_MODEL or "gemini-embedding" in EMBEDDING_API_MODEL:
        return 768
    if "text-embedding-3-small" in EMBEDDING_API_MODEL:
        return 1536
    if "text-embedding-3-large" in EMBEDDING_API_MODEL:
        return 3072
    if EMBEDDING_PROVIDER == "local":
        return 1024
    return 768

EMBEDDING_DIMENSION = int(
    os.getenv("EMBEDDING_DIMENSION", str(_detect_default_dimension()))
)

EMBEDDING_API_TIMEOUT = float(os.getenv("EMBEDDING_API_TIMEOUT", "60"))
EMBEDDING_BATCH_SIZE = int(os.getenv("EMBEDDING_BATCH_SIZE", "32"))
