#!/bin/bash
cd "$(dirname "$0")"
if [ -f "requirements.txt" ]; then
  python3 -m pip install -r requirements.txt
fi
python3 email_service.py
