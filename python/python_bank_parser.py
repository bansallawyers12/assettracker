#!/usr/bin/env python3
"""
Parse bank statement files (CSV, XLSX, XLS) and output JSON for Laravel import.
Usage: python_bank_parser.py <file_path> --bank-name <bank_name>
"""

import argparse
import json
import sys
from datetime import datetime
from pathlib import Path

import pandas as pd


# Common column name variations for bank statements
DATE_COLUMNS = ['date', 'transaction date', 'trans date', 'value date', 'posting date', 'post date']
DESCRIPTION_COLUMNS = ['description', 'details', 'particulars', 'narration', 'memo', 'reference', 'payee', 'payer']
DEBIT_COLUMNS = ['debit', 'withdrawal', 'out', 'dr', 'expense']
CREDIT_COLUMNS = ['credit', 'deposit', 'in', 'cr', 'income']
AMOUNT_COLUMNS = ['amount', 'balance', 'value']
REFERENCE_COLUMNS = ['reference', 'ref', 'transaction id', 'cheque no', 'cheque number']


def find_column(df, candidates):
    """Find first matching column (case-insensitive)."""
    cols_lower = {c.lower().strip(): c for c in df.columns}
    for cand in candidates:
        if cand in cols_lower:
            return cols_lower[cand]
    return None


def parse_amount(val):
    """Parse amount from string or number. Returns float. Positive = credit, negative = debit."""
    if pd.isna(val):
        return 0.0
    if isinstance(val, (int, float)):
        return float(val)
    s = str(val).strip().replace(',', '').replace(' ', '')
    # Remove currency symbols and parentheses (accounting negative)
    for sym in ['$', '€', '£', '₹', '(', ')']:
        s = s.replace(sym, '')
    if s.startswith('-') or s.endswith('-') or '(' in str(val):
        try:
            return -abs(float(s.replace('(', '').replace(')', '')))
        except ValueError:
            return 0.0
    try:
        return float(s)
    except ValueError:
        return 0.0


def parse_date(val):
    """Parse date to YYYY-MM-DD string."""
    if pd.isna(val):
        return None
    if isinstance(val, datetime):
        return val.strftime('%Y-%m-%d')
    if isinstance(val, str):
        for fmt in ['%Y-%m-%d', '%d/%m/%Y', '%m/%d/%Y', '%d-%m-%Y', '%Y/%m/%d', '%d %b %Y', '%b %d, %Y']:
            try:
                return datetime.strptime(val.strip(), fmt).strftime('%Y-%m-%d')
            except ValueError:
                continue
    try:
        return pd.to_datetime(val).strftime('%Y-%m-%d')
    except Exception:
        return None


def parse_file(file_path):
    """Read CSV or Excel file into DataFrame."""
    path = Path(file_path)
    if not path.exists():
        raise FileNotFoundError(f"File not found: {file_path}")

    suffix = path.suffix.lower()
    if suffix == '.csv':
        df = pd.read_csv(file_path, encoding='utf-8', on_bad_lines='skip')
    elif suffix in ['.xlsx', '.xls']:
        df = pd.read_excel(file_path, engine='openpyxl' if suffix == '.xlsx' else 'xlrd')
    else:
        raise ValueError(f"Unsupported file type: {suffix}")

    # Normalize column names (strip whitespace)
    df.columns = [str(c).strip() for c in df.columns]
    return df


def extract_entries(df):
    """Extract transaction entries from DataFrame."""
    date_col = find_column(df, DATE_COLUMNS)
    desc_col = find_column(df, DESCRIPTION_COLUMNS)
    debit_col = find_column(df, DEBIT_COLUMNS)
    credit_col = find_column(df, CREDIT_COLUMNS)
    amount_col = find_column(df, AMOUNT_COLUMNS)
    ref_col = find_column(df, REFERENCE_COLUMNS)

    if not date_col:
        # Try first column as date
        date_col = df.columns[0] if len(df.columns) > 0 else None
    if not desc_col and len(df.columns) > 1:
        desc_col = df.columns[1]

    if not date_col:
        raise ValueError("Could not find date column. Tried: " + ", ".join(DATE_COLUMNS))

    entries = []
    for _, row in df.iterrows():
        date_str = parse_date(row.get(date_col))
        if not date_str:
            continue

        description = str(row.get(desc_col, '')).strip() if desc_col else ''
        reference = str(row.get(ref_col, '')).strip() if ref_col else None

        amount = 0.0
        if debit_col and credit_col:
            debit_val = parse_amount(row.get(debit_col, 0))
            credit_val = parse_amount(row.get(credit_col, 0))
            # Typically: debit = negative (outflow), credit = positive (inflow)
            if debit_val != 0 and credit_val == 0:
                amount = -abs(debit_val)
            elif credit_val != 0 and debit_val == 0:
                amount = abs(credit_val)
            else:
                amount = credit_val - debit_val
        elif amount_col:
            amount = parse_amount(row.get(amount_col, 0))
        elif debit_col:
            amount = -abs(parse_amount(row.get(debit_col, 0)))
        elif credit_col:
            amount = abs(parse_amount(row.get(credit_col, 0)))
        else:
            # Try to find any numeric column that might be amount
            for col in df.columns:
                if col != date_col and df[col].dtype in ['float64', 'int64']:
                    amount = parse_amount(row.get(col, 0))
                    break

        # Skip zero-amount rows (often header or summary)
        if amount == 0 and not description:
            continue

        transaction_type = 'credit' if amount >= 0 else 'debit'
        entries.append({
            'date': date_str,
            'amount': round(amount, 2),
            'description': description or 'Transaction',
            'transaction_type': transaction_type,
            'reference': reference
        })

    return entries


def main():
    parser = argparse.ArgumentParser(description='Parse bank statement files')
    parser.add_argument('file_path', help='Path to bank statement file (CSV, XLSX, XLS)')
    parser.add_argument('--bank-name', default='', help='Bank name (for future format-specific parsing)')
    args = parser.parse_args()

    try:
        df = parse_file(args.file_path)
        if df.empty:
            output = {'success': True, 'entries': [], 'message': 'File is empty'}
        else:
            entries = extract_entries(df)
            output = {'success': True, 'entries': entries}
        print(json.dumps(output, indent=2))
    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e),
            'entries': []
        }), file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
