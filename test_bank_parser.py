#!/usr/bin/env python3
"""
Test script for the bank statement parser
"""

import json
import sys
import os

# Add the current directory to Python path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from python_bank_parser import BankStatementParser

def test_parser():
    """Test the bank statement parser with sample data"""
    
    # Create a sample CSV file for testing
    sample_csv = """Date,Description,Amount,Type
2024-01-15,Office Supplies Purchase,-150.00,Debit
2024-01-16,Client Payment,2500.00,Credit
2024-01-17,Bank Transfer Out,-500.00,Debit
2024-01-18,Interest Earned,25.50,Credit
2024-01-19,ATM Withdrawal,-100.00,Debit"""
    
    # Write sample file
    with open('test_statement.csv', 'w') as f:
        f.write(sample_csv)
    
    # Test the parser
    parser = BankStatementParser()
    result = parser.parse_file('test_statement.csv', 'Test Bank')
    
    print("Parser Test Results:")
    print("=" * 50)
    print(json.dumps(result, indent=2))
    
    # Clean up
    os.remove('test_statement.csv')
    
    return result['success']

if __name__ == '__main__':
    success = test_parser()
    if success:
        print("\n✅ Parser test passed!")
    else:
        print("\n❌ Parser test failed!")
        sys.exit(1)
