#!/usr/bin/env python3
"""Unit tests for Westpac PDF statement parsing helpers."""

from __future__ import annotations

import sys
import unittest
from decimal import Decimal
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from python_bank_pdf_parser import (  # noqa: E402
    cells_from_text_line,
    group_westpac_text_blocks,
    infer_sign_from_description,
    merge_westpac_table_rows,
    parse_row_cells,
    parse_text_block,
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
        self.assertEqual(entry["amount_debit"], 42.0)
        self.assertIsNone(entry["amount_credit"])
        self.assertEqual(entry["amount"], -42.0)
        self.assertEqual(entry["transaction_type"], "debit")
        self.assertIn("Number 587796", entry["description"])
        self.assertEqual(entry["balance"], 210473.77)

    def test_interest_row_parses_as_debit_when_columns_collapsed(self) -> None:
        cells = ["31/03/26", "Interest Payable On Account 587796", "9,234.15", "201,239.62"]

        entry, _, _, _ = parse_row_cells(cells, 2026, "2026-03-31", Decimal("210473.77"))
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["amount_debit"], 9234.15)
        self.assertIsNone(entry["amount_credit"])
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
        self.assertEqual(fee_entry["amount_debit"], 42.0)
        self.assertIsNone(fee_entry["amount_credit"])
        self.assertEqual(fee_entry["transaction_type"], "debit")
        self.assertIsNone(deposit_entry["amount_debit"])
        self.assertEqual(deposit_entry["amount_credit"], 2488.30)
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

    def test_continuation_line_does_not_treat_first_token_as_date(self) -> None:
        cells = cells_from_text_line("Number 587796 42.00 210,473.77")
        self.assertEqual(cells[0], "Number 587796")
        self.assertEqual(cells[1:], ["42.00", "210,473.77"])

    def test_double_spaced_date_description_does_not_index_error(self) -> None:
        """Westpac text often keeps date+desc in one cell when amounts use wide gaps."""
        block = "\n".join(
            [
                "31/03/26 Loan Service Fee Redirected From Account\t\t",
                "Number 587796\t\t42.00\t\t\t210,473.77",
            ]
        )
        # Also cover the exact double-space shape that previously crashed merge.
        wide = (
            "31/03/26 Loan Service Fee Redirected From Account\n"
            "Number 587796          42.00                    210,473.77"
        )

        for sample in (block, wide):
            entry, _, _, _ = parse_westpac_text_block(
                sample, 2026, None, Decimal("210515.77")
            )
            self.assertIsNotNone(entry, sample)
            assert entry is not None
            self.assertEqual(entry["amount"], -42.0)
            self.assertEqual(entry["transaction_type"], "debit")

    def test_cells_from_text_line_peels_embedded_slash_date(self) -> None:
        cells = cells_from_text_line(
            "31/03/26 Interest Payable On Account 587796          9,234.15          201,239.62"
        )
        self.assertEqual(cells[0], "31/03/26")
        self.assertIn("Interest Payable", cells[1])

    def test_footer_text_is_not_merged_into_transaction(self) -> None:
        lines = [
            "31/03/26 Loan Service Fee Redirected From Account",
            "Number 587796 42.00 210,473.77",
            "Please check all entries on this statement and promptly inform Westpac",
        ]
        blocks = group_westpac_text_blocks(lines)
        self.assertEqual(len(blocks), 1)
        self.assertNotIn("Please check", blocks[0])

    def test_sample_statement_text_recovers_fees_and_interest_signs(self) -> None:
        sample = "\n".join(
            [
                "WESTPAC BANKING CORPORATION",
                "DATE",
                "TRANSACTION DESCRIPTION",
                "DEBIT",
                "CREDIT",
                "BALANCE",
                "19/03/26 STATEMENT OPENING BALANCE 210,515.77",
                "31/03/26 Loan Service Fee Redirected From Account",
                "Number 587796 42.00 210,473.77",
                "31/03/26 Interest Payable On Account 587796 9,234.15 201,239.62",
                "01/04/26 Line Fee Redirected From Account Number",
                "587796 1,623.28 199,616.34",
                "02/04/26 Deposit Goldtrack Proper 3 Faulkiner St Cla 2,488.30 202,104.64",
                "30/04/26 Interest Payable On Account 587796 9,062.30 214,944.67",
            ]
        )

        entries, _, _, _ = parse_text_block(sample, 2026, None, None, use_westpac=True)
        by_key = {
            (entry["date"], entry.get("amount_debit"), entry.get("amount_credit")): entry
            for entry in entries
        }

        self.assertIn(("2026-03-31", 42.0, None), by_key)
        self.assertIn(("2026-03-31", 9234.15, None), by_key)
        self.assertIn(("2026-04-01", 1623.28, None), by_key)
        self.assertIn(("2026-04-02", None, 2488.30), by_key)
        self.assertIn(("2026-04-30", 9062.30, None), by_key)

        interest = by_key[("2026-03-31", 9234.15, None)]
        self.assertEqual(interest["transaction_type"], "debit")
        self.assertEqual(interest["amount"], -9234.15)
        for column in ("date", "description", "amount_debit", "amount_credit", "balance"):
            self.assertIn(column, interest)


if __name__ == "__main__":
    unittest.main()
