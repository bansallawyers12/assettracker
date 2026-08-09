#!/usr/bin/env python3
"""PDF test-page fixed columns apply to every bank layout, not only Westpac."""

from __future__ import annotations

import sys
import unittest
from decimal import Decimal
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from python_bank_pdf_parser import FIXED_COLUMNS, build_entry, parse_row_cells  # noqa: E402


class FixedColumnsAllBanksTest(unittest.TestCase):
    def assert_fixed_columns(self, entry: dict) -> None:
        for column in FIXED_COLUMNS:
            self.assertIn(column, entry)

    def test_build_entry_always_emits_fixed_columns(self) -> None:
        entry = build_entry(
            "2026-04-02",
            "Deposit Facey",
            None,
            Decimal("100.00"),
            Decimal("500.00"),
        )
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assert_fixed_columns(entry)
        self.assertIsNone(entry["amount_debit"])
        self.assertEqual(entry["amount_credit"], 100.0)

    def test_cba_style_five_column_row(self) -> None:
        cells = ["02 Apr 2026", "Direct Credit Salary", "", "1,200.00", "3,400.00"]
        entry, _, _, _ = parse_row_cells(cells, 2026, None, Decimal("2200.00"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assert_fixed_columns(entry)
        self.assertIsNone(entry["amount_debit"])
        self.assertEqual(entry["amount_credit"], 1200.0)
        self.assertEqual(entry["balance"], 3400.0)

    def test_nab_style_debit_row(self) -> None:
        cells = ["15/04/26", "EFTPOS Purchase", "45.50", "", "1,154.50"]
        entry, _, _, _ = parse_row_cells(cells, 2026, None, Decimal("1200.00"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assert_fixed_columns(entry)
        self.assertEqual(entry["amount_debit"], 45.5)
        self.assertIsNone(entry["amount_credit"])

    def test_cba_overdrawn_dr_balance_keeps_debit_sign(self) -> None:
        """Balance moving 3,368.35DR -> 3,517.35DR is a debit, not a credit."""
        cells = ["01Jun", "DirectDebit RAMSFRANCHI", "149.00", "3,517.35DR"]
        entry, _, _, last_balance = parse_row_cells(
            cells, 2026, "2026-06-01", Decimal("-3368.35")
        )
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["amount_debit"], 149.0)
        self.assertIsNone(entry["amount_credit"])
        self.assertEqual(entry["balance"], -3517.35)
        self.assertEqual(last_balance, Decimal("-3517.35"))

    def test_macquarie_collapsed_amount_balance(self) -> None:
        cells = ["01 Jul 26", "Direct Debit Loan Payment", "850.00", "44,150.00CR"]
        entry, _, _, _ = parse_row_cells(cells, 2026, None, Decimal("45000.00"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assert_fixed_columns(entry)
        self.assertEqual(entry["amount_debit"], 850.0)
        self.assertIsNone(entry["amount_credit"])
        self.assertEqual(entry["balance"], 44150.0)


if __name__ == "__main__":
    unittest.main()
