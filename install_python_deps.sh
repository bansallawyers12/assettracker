#!/bin/bash

# Install Python dependencies for bank statement parsing
echo "Installing Python dependencies for bank statement parsing..."

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

# Install required packages
pip3 install -r requirements.txt

echo "Python dependencies installed successfully!"
echo "You can now use the bank import functionality."
