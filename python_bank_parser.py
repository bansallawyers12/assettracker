#!/usr/bin/env python3
"""
Bank Statement Parser for Asset Tracker
Parses Excel and CSV bank statement files and returns structured data
"""

import pandas as pd
import sys
import json
import re
from datetime import datetime
from typing import List, Dict, Any, Optional
import argparse

class BankStatementParser:
    def __init__(self):
        self.supported_formats = ['.xlsx', '.xls', '.csv']
        self.common_date_formats = [
            '%Y-%m-%d', '%d/%m/%Y', '%m/%d/%Y', '%d-%m-%Y', 
            '%Y/%m/%d', '%d.%m.%Y', '%m.%d.%Y'
        ]
        
    def parse_file(self, file_path: str, bank_name: str = None) -> Dict[str, Any]:
        """
        Parse bank statement file and return structured data
        """
        try:
            # Determine file type and read
            if file_path.endswith('.csv'):
                df = pd.read_csv(file_path)
            elif file_path.endswith(('.xlsx', '.xls')):
                df = pd.read_excel(file_path)
            else:
                raise ValueError(f"Unsupported file format. Supported: {self.supported_formats}")
            
            # Clean and standardize column names
            df.columns = df.columns.str.strip().str.lower()
            
            # Detect column mappings
            column_mapping = self._detect_columns(df.columns)
            
            if not column_mapping['date'] or not column_mapping['amount']:
                raise ValueError("Could not detect required columns (date and amount)")
            
            # Process the data
            entries = []
            for index, row in df.iterrows():
                try:
                    entry = self._process_row(row, column_mapping, bank_name)
                    if entry:
                        entries.append(entry)
                except Exception as e:
                    print(f"Warning: Skipping row {index + 1}: {str(e)}", file=sys.stderr)
                    continue
            
            return {
                'success': True,
                'entries': entries,
                'total_entries': len(entries),
                'column_mapping': column_mapping,
                'bank_name': bank_name or 'Unknown Bank'
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e),
                'entries': [],
                'total_entries': 0
            }
    
    def _detect_columns(self, columns: List[str]) -> Dict[str, Optional[str]]:
        """
        Detect which columns contain date, amount, description, etc.
        """
        mapping = {
            'date': None,
            'amount': None,
            'description': None,
            'transaction_type': None,
            'reference': None,
            'balance': None
        }
        
        # Common column name patterns
        patterns = {
            'date': [
                'date', 'transaction_date', 'value_date', 'posting_date',
                'trans_date', 'tran_date', 'txn_date'
            ],
            'amount': [
                'amount', 'transaction_amount', 'debit', 'credit', 'value',
                'trans_amount', 'txn_amount', 'amt'
            ],
            'description': [
                'description', 'narrative', 'details', 'particulars', 'memo',
                'transaction_details', 'trans_desc', 'txn_desc', 'remarks'
            ],
            'transaction_type': [
                'type', 'transaction_type', 'txn_type', 'trans_type',
                'dr_cr', 'debit_credit', 'flow'
            ],
            'reference': [
                'reference', 'ref', 'transaction_ref', 'txn_ref',
                'cheque_no', 'check_no', 'serial_no'
            ],
            'balance': [
                'balance', 'running_balance', 'closing_balance', 'bal'
            ]
        }
        
        for field, patterns_list in patterns.items():
            for col in columns:
                if any(pattern in col for pattern in patterns_list):
                    mapping[field] = col
                    break
        
        return mapping
    
    def _process_row(self, row: pd.Series, column_mapping: Dict[str, str], bank_name: str) -> Optional[Dict[str, Any]]:
        """
        Process a single row of bank statement data
        """
        try:
            # Extract date
            date_str = str(row[column_mapping['date']]).strip()
            if date_str == 'nan' or date_str == '':
                return None
                
            parsed_date = self._parse_date(date_str)
            if not parsed_date:
                return None
            
            # Extract amount
            amount_str = str(row[column_mapping['amount']]).strip()
            if amount_str == 'nan' or amount_str == '':
                return None
                
            amount = self._parse_amount(amount_str)
            if amount is None:
                return None
            
            # Extract description
            description = ''
            if column_mapping['description']:
                desc_str = str(row[column_mapping['description']]).strip()
                if desc_str != 'nan' and desc_str != '':
                    description = desc_str
            
            # Extract transaction type
            transaction_type = 'unknown'
            if column_mapping['transaction_type']:
                type_str = str(row[column_mapping['transaction_type']]).strip()
                if type_str != 'nan' and type_str != '':
                    transaction_type = self._normalize_transaction_type(type_str, amount)
            else:
                # Infer from amount
                transaction_type = 'credit' if amount >= 0 else 'debit'
            
            # Extract reference
            reference = ''
            if column_mapping['reference']:
                ref_str = str(row[column_mapping['reference']]).strip()
                if ref_str != 'nan' and ref_str != '':
                    reference = ref_str
            
            return {
                'date': parsed_date.strftime('%Y-%m-%d'),
                'amount': float(amount),
                'description': description,
                'transaction_type': transaction_type,
                'reference': reference,
                'bank_name': bank_name or 'Unknown Bank'
            }
            
        except Exception as e:
            raise Exception(f"Error processing row: {str(e)}")
    
    def _parse_date(self, date_str: str) -> Optional[datetime]:
        """
        Parse date string using common formats
        """
        # Clean the date string
        date_str = re.sub(r'[^\d/\-\.]', '', date_str)
        
        for fmt in self.common_date_formats:
            try:
                return datetime.strptime(date_str, fmt)
            except ValueError:
                continue
        
        # Try pandas date parsing as fallback
        try:
            return pd.to_datetime(date_str).to_pydatetime()
        except:
            return None
    
    def _parse_amount(self, amount_str: str) -> Optional[float]:
        """
        Parse amount string, handling various formats
        """
        # Remove common currency symbols and spaces
        amount_str = re.sub(r'[^\d\.\-\+]', '', amount_str)
        
        if not amount_str or amount_str == '':
            return None
        
        try:
            # Handle negative amounts in parentheses
            if amount_str.startswith('(') and amount_str.endswith(')'):
                amount_str = '-' + amount_str[1:-1]
            
            return float(amount_str)
        except ValueError:
            return None
    
    def _normalize_transaction_type(self, type_str: str, amount: float) -> str:
        """
        Normalize transaction type based on common patterns
        """
        type_str = type_str.lower().strip()
        
        # Common patterns
        if any(word in type_str for word in ['credit', 'cr', 'deposit', 'incoming']):
            return 'credit'
        elif any(word in type_str for word in ['debit', 'dr', 'withdrawal', 'outgoing']):
            return 'debit'
        elif any(word in type_str for word in ['transfer', 'tfr']):
            return 'transfer'
        elif any(word in type_str for word in ['fee', 'charge', 'commission']):
            return 'fee'
        else:
            # Default based on amount
            return 'credit' if amount >= 0 else 'debit'

def main():
    parser = argparse.ArgumentParser(description='Parse bank statement files')
    parser.add_argument('file_path', help='Path to the bank statement file')
    parser.add_argument('--bank-name', help='Name of the bank', default=None)
    parser.add_argument('--output', help='Output file path (JSON)', default=None)
    
    args = parser.parse_args()
    
    # Parse the file
    bank_parser = BankStatementParser()
    result = bank_parser.parse_file(args.file_path, args.bank_name)
    
    # Output result
    if args.output:
        with open(args.output, 'w') as f:
            json.dump(result, f, indent=2)
    else:
        print(json.dumps(result, indent=2))

if __name__ == '__main__':
    main()
