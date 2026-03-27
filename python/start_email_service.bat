@echo off
cd /d "%~dp0"
if exist requirements.txt (
  where py >nul 2>&1 && py -3 -m pip install -r requirements.txt || python -m pip install -r requirements.txt
)
where py >nul 2>&1 && py -3 email_service.py || python email_service.py
