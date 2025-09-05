# Bank Statement Import Feature

This feature allows you to import bank statements from Excel (.xlsx, .xls) or CSV files and automatically match them to your chart of accounts, similar to Xero's bank reconciliation functionality.

## Features

- **File Upload**: Support for Excel and CSV bank statement files
- **Automatic Parsing**: Python backend intelligently detects columns (date, amount, description, etc.)
- **Smart Matching**: Auto-match bank entries to chart of accounts based on amount and description
- **Manual Override**: Full control to manually match entries to specific accounts
- **Transaction Creation**: Automatically creates transactions in your accounting system
- **Duplicate Prevention**: Prevents importing duplicate entries

## Setup

### 1. Install Python Dependencies

**Windows:**
```bash
install_python_deps.bat
```

**Linux/Mac:**
```bash
chmod +x install_python_deps.sh
./install_python_deps.sh
```

**Manual Installation:**
```bash
pip install pandas openpyxl xlrd
```

### 2. Test the Parser

```bash
python test_bank_parser.py
```

## How to Use

### 1. Access Bank Import Tab

1. Navigate to any Business Entity
2. Click on the "Bank Import" tab
3. Click "Upload Statement"

### 2. Upload Bank Statement

1. Select the bank account for this statement
2. Choose your bank statement file (Excel or CSV)
3. Click "Process File"

### 3. Match Bank Entries

After processing, you'll see:
- **Left Panel**: Bank statement entries from your file
- **Right Panel**: Your chart of accounts

**Auto Match:**
- Click "Auto Match" to automatically match entries based on amount and account type
- Positive amounts → Income accounts
- Negative amounts → Expense accounts

**Manual Match:**
- Select an account from the dropdown for each bank entry
- Use the search box to quickly find accounts
- Click "Save Matches" when done

### 4. Review and Save

- Review all matches before saving
- Click "Save Matches" to create transactions
- The system will automatically post journal entries

## Supported File Formats

### Excel Files (.xlsx, .xls)
- First row should contain column headers
- Common column names are automatically detected

### CSV Files (.csv)
- Comma-separated values
- First row should contain column headers

## Column Detection

The parser automatically detects these columns:
- **Date**: date, transaction_date, value_date, posting_date
- **Amount**: amount, transaction_amount, debit, credit, value
- **Description**: description, narrative, details, particulars, memo
- **Transaction Type**: type, transaction_type, dr_cr, debit_credit
- **Reference**: reference, ref, transaction_ref, cheque_no

## Date Formats Supported

- YYYY-MM-DD (2024-01-15)
- DD/MM/YYYY (15/01/2024)
- MM/DD/YYYY (01/15/2024)
- DD-MM-YYYY (15-01-2024)
- DD.MM.YYYY (15.01.2024)

## Amount Formats Supported

- 150.00
- -150.00
- (150.00) for negative amounts
- $150.00 (currency symbols are stripped)

## Troubleshooting

### Python Not Found
- Ensure Python 3 is installed and in your system PATH
- On Windows, you may need to restart your command prompt after installing Python

### File Upload Errors
- Check file size (max 10MB)
- Ensure file is in supported format (.xlsx, .xls, .csv)
- Verify file is not corrupted

### Parsing Errors
- Check that your file has proper column headers
- Ensure date and amount columns are in recognizable formats
- Try opening the file in Excel to verify it's not corrupted

### Matching Issues
- Use the search box to find specific accounts
- Check that your chart of accounts is properly set up
- Verify bank account belongs to the correct business entity

## Technical Details

### File Processing Flow
1. File uploaded to Laravel backend
2. Python parser processes the file
3. Bank statement entries stored in database
4. Matching interface displays entries and accounts
5. Matches saved as transactions
6. Journal entries automatically posted

### Database Tables Used
- `bank_statement_entries`: Stores imported bank entries
- `transactions`: Created from matched entries
- `journal_entries`: Auto-generated from transactions
- `chart_of_accounts`: Used for matching

### Security
- File uploads are validated for type and size
- Bank accounts are verified to belong to the business entity
- CSRF protection on all forms
- File cleanup after processing

## Support

If you encounter issues:
1. Check the Laravel logs in `storage/logs/laravel.log`
2. Verify Python dependencies are installed
3. Test with the provided sample file
4. Check file format and column headers
