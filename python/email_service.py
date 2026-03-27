#!/usr/bin/env python3
"""
Asset Tracker Email Python Service

Endpoints:
- GET  /health
- POST /email/parse
"""

import json
import os
import subprocess
import sys
import tempfile
from pathlib import Path

from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse
import uvicorn


BASE_DIR = Path(__file__).resolve().parent
PARSER_SCRIPT = BASE_DIR / "parse_msg_simple.py"

app = FastAPI(
    title="Asset Tracker Email Service",
    version="1.0.0",
)


@app.get("/health")
def health():
    return {
        "status": "healthy",
        "service": "assettracker-email-parser",
        "parser_script": str(PARSER_SCRIPT),
        "parser_exists": PARSER_SCRIPT.exists(),
    }


@app.post("/email/parse")
async def parse_email(file: UploadFile = File(...)):
    filename = file.filename or ""
    if not filename.lower().endswith(".msg"):
        raise HTTPException(status_code=400, detail="Only .msg files are supported")
    if not PARSER_SCRIPT.exists():
        raise HTTPException(status_code=500, detail="parse_msg_simple.py not found")

    content = await file.read()
    temp_path = None

    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".msg") as temp:
            temp.write(content)
            temp_path = temp.name

        cmd = [sys.executable, str(PARSER_SCRIPT), temp_path]
        result = subprocess.run(cmd, capture_output=True, text=False, check=False)
        stdout = result.stdout.decode("utf-8", errors="replace") if result.stdout else ""
        stderr = result.stderr.decode("utf-8", errors="replace") if result.stderr else ""
        combined_output = stdout + "\n" + stderr

        start = combined_output.find("{")
        end = combined_output.rfind("}")
        if start == -1 or end == -1:
            return JSONResponse(
                status_code=500,
                content={"success": False, "error": "No JSON found in parser output"},
            )

        payload = combined_output[start : end + 1]
        try:
            data = json.loads(payload)
        except Exception as exc:
            return JSONResponse(
                status_code=500,
                content={"success": False, "error": f"Invalid parser JSON: {exc}"},
            )

        if isinstance(data, dict) and data.get("error"):
            return JSONResponse(status_code=500, content={"success": False, "error": data.get("error")})

        if isinstance(data, dict) and "success" not in data:
            data["success"] = True

        return JSONResponse(content=data)
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc))
    finally:
        if temp_path and os.path.exists(temp_path):
            try:
                os.remove(temp_path)
            except OSError:
                pass


if __name__ == "__main__":
    host = os.environ.get("PYTHON_EMAIL_SERVICE_HOST", "127.0.0.1")
    port = int(os.environ.get("PYTHON_EMAIL_SERVICE_PORT", "5001"))
    uvicorn.run(app, host=host, port=port, log_level="info")
