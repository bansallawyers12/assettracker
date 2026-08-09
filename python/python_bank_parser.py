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
DATE_COLUMNS = [
    'date', 'transaction date', 'trans date', 'value date', 'posting date', 'post date',
]
DESCRIPTION_COLUMNS = [
    'description', 'details', 'particulars', 'narration', 'memo', 'reference', 'payee', 'payer',
    'original description', 'narrative',
]
DEBIT_COLUMNS = ['debit', 'debit amount', 'withdrawal', 'out', 'dr', 'expense']
CREDIT_COLUMNS = ['credit', 'credit amount', 'deposit', 'in', 'cr', 'income']
AMOUNT_COLUMNS = ['amount', 'transaction amount', 'net amount', 'value']
REFERENCE_COLUMNS = ['reference', 'ref', 'transaction id', 'cheque no', 'cheque number']
BALANCE_COLUMNS = ['balance', 'running balance', 'account balance']
CATEGORY_COLUMNS = ['category']
SUBCATEGORY_COLUMNS = ['subcategory', 'sub category', 'sub-category']
ORIGINAL_DESCRIPTION_COLUMNS = ['original description']


def find_column(df, candidates):
    """Find first matching column (case-insensitive)."""
    cols_lower = {c.lower().strip(): c for c in df.columns}
    for cand in candidates:
        if cand in cols_lower:
            return cols_lower[cand]
    return None


def detect_profile(df):
    """Detect bank statement profile from headers."""
    cols = {c.lower().strip() for c in df.columns}
    has_original = 'original description' in cols
    has_subcategory = 'subcategory' in cols or 'sub category' in cols or 'sub-category' in cols
    has_txn_date = 'transaction date' in cols
    if has_original and has_subcategory and has_txn_date:
        return 'macquarie'
    if has_original and has_subcategory:
        return 'macquarie'
    return 'generic'


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
        for fmt in [
            '%Y-%m-%d', '%d/%m/%Y', '%m/%d/%Y', '%d-%m-%Y', '%Y/%m/%d',
            '%d %b %Y', '%b %d, %Y', '%d-%b-%y', '%d-%b-%Y', '%d %b %y',
        ]:
            try:
                return datetime.strptime(val.strip(), fmt).strftime('%Y-%m-%d')
            except ValueError:
                continue
    try:
        return pd.to_datetime(val, dayfirst=True).strftime('%Y-%m-%d')
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


def extract_entries(df, bank_name=''):
    """Extract transaction entries from DataFrame."""
    profile = detect_profile(df)

    date_col = find_column(df, DATE_COLUMNS)
    original_desc_col = find_column(df, ORIGINAL_DESCRIPTION_COLUMNS)
    desc_col = find_column(df, DESCRIPTION_COLUMNS)
    debit_col = find_column(df, DEBIT_COLUMNS)
    credit_col = find_column(df, CREDIT_COLUMNS)
    amount_col = find_column(df, AMOUNT_COLUMNS)
    ref_col = find_column(df, REFERENCE_COLUMNS)
    balance_col = find_column(df, BALANCE_COLUMNS)
    category_col = find_column(df, CATEGORY_COLUMNS)
    subcategory_col = find_column(df, SUBCATEGORY_COLUMNS)

    if profile == 'macquarie' and original_desc_col:
        desc_col = original_desc_col

    if not date_col:
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
        if reference in ('', 'nan', 'None'):
            reference = None

        amount = 0.0
        if debit_col and credit_col:
            debit_val = parse_amount(row.get(debit_col, 0))
            credit_val = parse_amount(row.get(credit_col, 0))
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
            for col in df.columns:
                if col != date_col and df[col].dtype in ['float64', 'int64']:
                    amount = parse_amount(row.get(col, 0))
                    break

        if amount == 0 and not description:
            continue

        meta = {
            'bank_profile': profile,
        }
        if bank_name:
            meta['bank_name'] = bank_name
        if balance_col is not None:
            bal = parse_amount(row.get(balance_col, 0))
            meta['balance_after'] = round(bal, 2)
        if category_col is not None:
            cat = str(row.get(category_col, '')).strip()
            if cat and cat.lower() not in ('nan', 'none'):
                meta['category'] = cat
        if subcategory_col is not None:
            sub = str(row.get(subcategory_col, '')).strip()
            if sub and sub.lower() not in ('nan', 'none'):
                meta['subcategory'] = sub
        if original_desc_col is not None and desc_col != original_desc_col:
            original = str(row.get(original_desc_col, '')).strip()
            if original and original.lower() not in ('nan', 'none'):
                meta['original_description'] = original

        transaction_type = 'credit' if amount >= 0 else 'debit'
        entries.append({
            'date': date_str,
            'amount': round(amount, 2),
            'description': description or 'Transaction',
            'transaction_type': transaction_type,
            'reference': reference,
            'meta': meta,
        })

    return entries, profile


def main():
    parser = argparse.ArgumentParser(description='Parse bank statement files')
    parser.add_argument('file_path', help='Path to bank statement file (CSV, XLSX, XLS)')
    parser.add_argument('--bank-name', default='', help='Bank name (optional hint; profile is header-detected)')
    args = parser.parse_args()

    try:
        df = parse_file(args.file_path)
        if df.empty:
            output = {'success': True, 'entries': [], 'message': 'File is empty', 'profile': 'generic'}
        else:
            entries, profile = extract_entries(df, args.bank_name)
            output = {'success': True, 'entries': entries, 'profile': profile}
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
