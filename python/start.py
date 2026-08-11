#!/usr/bin/env python3
"""
Start / initialize Python services for the Asset Tracker.
- Installs dependencies (extract-msg, pdfplumber, FastAPI)
- Verifies email and PDF parser scripts are ready
"""

import subprocess
import sys
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent


def check_python():
    """Ensure Python 3 is available."""
    if sys.version_info < (3, 8):
        print("Error: Python 3.8+ required. Current:", sys.version)
        return False
    print(f"Python {sys.version.split()[0]} OK")
    return True


def install_dependencies():
    """Install requirements.txt."""
    req_file = SCRIPT_DIR / "requirements.txt"
    if not req_file.exists():
        print("Error: requirements.txt not found")
        return False

    print("\nInstalling dependencies...")
    try:
        subprocess.run(
            [sys.executable, "-m", "pip", "install", "-r", str(req_file), "-q"],
            check=True,
            capture_output=True,
        )
        print("Dependencies installed OK")
        return True
    except subprocess.CalledProcessError as e:
        print(f"Error installing dependencies: {e}")
        return False


def verify_imports():
    """Verify all required packages can be imported."""
    print("\nVerifying packages...")
    try:
        import extract_msg
        import pdfplumber
        print("  extract_msg, pdfplumber OK")
        return True
    except ImportError as e:
        print(f"  Missing package: {e}")
        return False


def verify_scripts():
    """Verify parser scripts exist."""
    print("\nVerifying scripts...")
    pdf = SCRIPT_DIR / "python_bank_pdf_parser.py"
    msg = SCRIPT_DIR / "parse_msg_simple.py"
    if pdf.exists():
        print("  python_bank_pdf_parser.py OK")
    else:
        print("  python_bank_pdf_parser.py MISSING")
        return False
    if msg.exists():
        print("  parse_msg_simple.py OK")
    else:
        print("  parse_msg_simple.py MISSING")
        return False
    return True


def main():
    print("=" * 50)
    print("Asset Tracker - Python Services")
    print("=" * 50)

    if not check_python():
        sys.exit(1)

    if not install_dependencies():
        sys.exit(1)

    if not verify_imports():
        sys.exit(1)

    if not verify_scripts():
        sys.exit(1)

    print("\n" + "=" * 50)
    print("All Python services ready.")
    print("- PDF statement parser: python_bank_pdf_parser.py")
    print("- Email parser: parse_msg_simple.py")
    print("- Bank CSV import: handled in Laravel/PHP")
    print("=" * 50)


if __name__ == "__main__":
    main()
