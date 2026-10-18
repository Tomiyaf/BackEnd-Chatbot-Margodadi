Set-StrictMode -Version Latest

$python = Join-Path $PSScriptRoot ".venv\Scripts\python.exe"

if (-not (Test-Path -LiteralPath $python -PathType Leaf)) {
    Write-Error "Virtual environment tidak ditemukan: $python. Buat .venv dan instal dependensi Python di folder $PSScriptRoot terlebih dahulu."
    exit 1
}

$envFile = Join-Path $PSScriptRoot ".env"
if (-not (Test-Path -LiteralPath $envFile -PathType Leaf)) {
    Write-Error "Konfigurasi tidak ditemukan: $envFile. Siapkan .env sebelum menjalankan API."
    exit 1
}

$apiExitCode = 1
Push-Location -LiteralPath $PSScriptRoot -ErrorAction Stop
try {
    & $python -m uvicorn src.api:app --reload --host 127.0.0.1 --port 8001
    $apiExitCode = $LASTEXITCODE
} finally {
    Pop-Location
}

exit $apiExitCode
