#!/usr/bin/env python3
"""Date parsing tests for bank statement PDF helpers."""

from __future__ import annotations

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from python_bank_pdf_parser import (  # noqa: E402
    extract_year_hint_from_text,
    parse_generic_text_block,
    parse_row_cells,
)


class StatementDateParsingTest(unittest.TestCase):
    def test_macquarie_statement_period_year_beats_financial_year(self) -> None:
        text = "\n".join(
            [
                "Offset Account Statement",
                "From 1 January 2026 to 30 June 2026",
                "2025/26 Annual interest summary for your tax return",
            ]
        )

        self.assertEqual(extract_year_hint_from_text(text), 2026)

    def test_macquarie_month_header_applies_to_day_month_rows(self) -> None:
        block = "\n".join(
            [
                "Jan 2026",
                "21 Jan Direct debit to account xx1849 6,041.29 45,894.56CR",
            ]
        )

        entry, year_hint, _, _ = parse_generic_text_block(block, 2026, None)
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["date"], "2026-01-21")
        self.assertEqual(year_hint, 2026)

    def test_westpac_year_hint_ignores_interest_rate_amounts(self) -> None:
        text = "\n".join(
            [
                "Statement Period",
                "19 March 2026 - 19 June 2026",
                "Effective Date $0 Over $1999 Over $9999",
            ]
        )

        self.assertEqual(extract_year_hint_from_text(text), 2026)

    def test_slash_dates_keep_explicit_year(self) -> None:
        cells = ["31/03/26", "Loan Service Fee", "42.00", "", "210,473.77"]
        entry, _, _, _ = parse_row_cells(cells, 1999, None, None)
        self.assertIsNotNone(entry)
        assert entry is not None
        self.assertEqual(entry["date"], "2026-03-31")


if __name__ == "__main__":
    unittest.main()
