#!/bin/bash

# Install Python dependencies for PDF statement parsing and email .msg parsing
echo "Installing Python dependencies for PDF/email parsing..."

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "Error: Python 3 is not installed. Please install Python 3 first."
    exit 1
fi

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo "Error: pip3 is not installed. Please install pip3 first."
    exit 1
fi

# Run from script's directory
cd "$(dirname "$0")"
pip3 install -r requirements.txt

echo "Python dependencies installed successfully!"
echo "PDF statement and email parsing are ready. Bank CSV import runs in Laravel/PHP."
