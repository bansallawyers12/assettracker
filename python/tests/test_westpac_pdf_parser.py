#!/usr/bin/env python3
"""Unit tests for Westpac PDF statement parsing helpers."""

from __future__ import annotations

import sys
import unittest
from decimal import Decimal
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from python_bank_pdf_parser import (  # noqa: E402
    infer_sign_from_description,
    merge_westpac_table_rows,
    parse_row_cells,
    parse_westpac_text_block,
)


class WestpacPdfParserTest(unittest.TestCase):
    def test_interest_payable_is_debit_not_credit(self) -> None:
        self.assertEqual(
            infer_sign_from_description("Interest Payable On Account 587796"),
            "debit",
        )

    def test_deposit_is_credit(self) -> None:
        self.assertEqual(
            infer_sign_from_description("Deposit Goldtrack Proper 3 Faulkiner St Cla"),
            "credit",
        )

    def test_merges_wrapped_fee_rows_from_table(self) -> None:
        rows = [
            ["31/03/26", "Loan Service Fee Redirected From Account"],
            ["Number 587796", "42.00", "", "210,473.77"],
        ]

        merged = merge_westpac_table_rows(rows)
        self.assertEqual(len(merged), 1)

        entry, _, _, _ = parse_row_cells(merged[0], 2026, None, Decimal("210515.77"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["amount"], -42.0)
        self.assertEqual(entry["transaction_type"], "debit")
        self.assertIn("Number 587796", entry["description"])
        self.assertEqual(entry["balance"], 210473.77)

    def test_interest_row_parses_as_debit_when_columns_collapsed(self) -> None:
        cells = ["31/03/26", "Interest Payable On Account 587796", "9,234.15", "201,239.62"]

        entry, _, _, _ = parse_row_cells(cells, 2026, "2026-03-31", Decimal("210473.77"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["amount"], -9234.15)
        self.assertEqual(entry["transaction_type"], "debit")

    def test_explicit_debit_and_credit_columns_are_honoured(self) -> None:
        fee_cells = [
            "31/03/26",
            "Loan Service Fee Redirected From Account Number 587796",
            "42.00",
            "",
            "210,473.77",
        ]
        deposit_cells = [
            "02/04/26",
            "Deposit Goldtrack Proper 3 Faulkiner St Cla",
            "",
            "2,488.30",
            "202,104.64",
        ]

        fee_entry, _, _, last_balance = parse_row_cells(
            fee_cells, 2026, None, Decimal("210515.77")
        )
        deposit_entry, _, _, _ = parse_row_cells(
            deposit_cells, 2026, "2026-03-31", last_balance
        )

        self.assertIsNotNone(fee_entry)
        self.assertIsNotNone(deposit_entry)
        assert fee_entry is not None and deposit_entry is not None
        self.assertEqual(fee_entry["transaction_type"], "debit")
        self.assertEqual(deposit_entry["transaction_type"], "credit")
        self.assertEqual(deposit_entry["amount"], 2488.30)

    def test_merges_single_cell_wrapped_continuation(self) -> None:
        rows = [
            ["31/03/26", "Loan Service Fee Redirected From Account"],
            ["Number 587796 42.00 210,473.77"],
        ]

        merged = merge_westpac_table_rows(rows)
        self.assertEqual(len(merged), 1)

        entry, _, _, _ = parse_row_cells(merged[0], 2026, None, Decimal("210515.77"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["amount"], -42.0)
        self.assertIn("587796", entry["description"])

    def test_parses_wrapped_text_block(self) -> None:
        block = "\n".join(
            [
                "31/03/26 Loan Service Fee Redirected From Account",
                "Number 587796 42.00 210,473.77",
            ]
        )

        entry, _, _, _ = parse_westpac_text_block(block, 2026, None, Decimal("210515.77"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["amount"], -42.0)
        self.assertIn("Loan Service Fee", entry["description"])
        self.assertIn("587796", entry["description"])


if __name__ == "__main__":
    unittest.main()
