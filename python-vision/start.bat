@echo off
REM === Rangify Vision Service launcher ===
cd /d "%~dp0"

if not exist ".venv\Scripts\python.exe" (
    echo [setup] creating virtualenv...
    py -3 -m venv .venv
    if errorlevel 1 (
        echo [error] failed to create venv. Is Python installed? Try: py --version
        pause
        exit /b 1
    )
    echo [setup] installing requirements...
    .venv\Scripts\python.exe -m pip install --upgrade pip
    .venv\Scripts\python.exe -m pip install -r requirements.txt
    if errorlevel 1 (
        echo [error] pip install failed. Check network/proxy.
        pause
        exit /b 1
    )
)

REM === ensure the deep-learning ONNX weights exist (Segformer + SlimSAM, ~150MB) ===
if not exist "models\segformer_b2.onnx" (
    echo [setup] downloading vision models ^(~150MB, one-time^)...
    .venv\Scripts\python.exe _download_models.py
    if errorlevel 1 (
        echo [error] model download failed. If HuggingFace is blocked, enable a VPN and re-run.
        pause
        exit /b 1
    )
)

echo [run] starting on http://127.0.0.1:8001
.venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8001 --reload
