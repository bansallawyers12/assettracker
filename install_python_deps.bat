@echo off
echo Installing Python dependencies for bank statement parsing...

REM Check if Python 3 is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo Error: Python is not installed or not in PATH. Please install Python 3 first.
    pause
    exit /b 1
)

REM Check if pip is installed
pip --version >nul 2>&1
if errorlevel 1 (
    echo Error: pip is not installed. Please install pip first.
    pause
    exit /b 1
)

REM Install required packages
pip install -r requirements.txt

if errorlevel 1 (
    echo Error: Failed to install Python dependencies.
    pause
    exit /b 1
)

echo Python dependencies installed successfully!
echo You can now use the bank import functionality.
pause
