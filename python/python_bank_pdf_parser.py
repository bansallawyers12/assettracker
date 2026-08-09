#!/usr/bin/env python3
"""
Parse Australian bank statement PDFs (CBA, NAB, generic) into transaction JSON for Laravel.
Usage: python_bank_pdf_parser.py <file_path> [--bank-name auto|cba|nab]
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import tempfile
from datetime import datetime
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any

DATE_FULL_TEXT_RE = re.compile(
    r"^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{2,4})$"
)
DATE_DAY_MONTH_RE = re.compile(r"^(\d{1,2})\s+([A-Za-z]{3})$")
DATE_SLASH_RE = re.compile(r"^(\d{1,2})/(\d{1,2})/(\d{2,4})$")

# Summary / non-transaction lines. Interest credits are real transactions and are kept.
SKIP_DESCRIPTION_PATTERNS = [
    re.compile(r"OPENING\s+BALANCE", re.I),
    re.compile(r"CLOSING\s+BALANCE", re.I),
    re.compile(r"BALANCE\s+CARRIED\s+FORWARD", re.I),
    re.compile(r"^TOTAL\s+(DEBITS?|CREDITS?)", re.I),
    re.compile(r"^Page\s+\d+", re.I),
]

HEADER_HINTS = {"date", "transaction", "description", "debit", "credit", "balance", "details"}

BANK_MARKERS = {
    "cba": ("COMMONWEALTH BANK", "COMMBANK", "NETBANK"),
    "nab": ("NATIONAL AUSTRALIA BANK", "NAB LIMITED", "NAB BUSINESS"),
}

MONEY_RE = re.compile(r"^-?\$?\d[\d,]*\.\d{2}$")


def detect_bank(text: str, requested: str) -> str:
    if requested in ("cba", "nab"):
        return requested

    upper = text.upper()
    for bank, markers in BANK_MARKERS.items():
        if any(marker in upper for marker in markers):
            return bank

    return "generic"


def should_skip_description(description: str) -> bool:
    cleaned = description.strip()
    if not cleaned:
        return False

    return any(pattern.search(cleaned) for pattern in SKIP_DESCRIPTION_PATTERNS)


def parse_amount(value: Any) -> Decimal | None:
    """Parse a money cell. Returns None when the cell is blank/not an amount."""
    if value is None:
        return None

    text = str(value).strip()
    if not text or text in {"-", "—", "–"}:
        return None

    negative = text.startswith("(") and text.endswith(")")
    cleaned = text.replace("$", "").replace(",", "").replace(" ", "")
    cleaned = cleaned.replace("(", "").replace(")", "")

    if not cleaned:
        return None

    try:
        amount = Decimal(cleaned)
    except InvalidOperation:
        return None

    return -abs(amount) if negative else amount


def normalize_year(year_part: str | int) -> int:
    year = int(year_part)
    if year < 100:
        year += 2000
    return year


def parse_date_token(token: str, year_hint: int | None) -> tuple[str | None, int | None]:
    token = token.strip()
    if not token:
        return None, year_hint

    match = DATE_FULL_TEXT_RE.match(token)
    if match:
        day, month, year_part = match.groups()
        year = normalize_year(year_part)
        try:
            parsed = datetime.strptime(f"{int(day)} {month.title()} {year}", "%d %b %Y")
            return parsed.strftime("%Y-%m-%d"), year
        except ValueError:
            return None, year_hint

    match = DATE_DAY_MONTH_RE.match(token)
    if match:
        day, month = match.groups()
        year = year_hint or datetime.now().year
        try:
            parsed = datetime.strptime(f"{int(day)} {month.title()} {year}", "%d %b %Y")
            return parsed.strftime("%Y-%m-%d"), year
        except ValueError:
            return None, year_hint

    match = DATE_SLASH_RE.match(token)
    if match:
        day, month, year_part = match.groups()
        year = normalize_year(year_part)
        try:
            parsed = datetime.strptime(f"{int(day)}/{int(month)}/{year}", "%d/%m/%Y")
            return parsed.strftime("%Y-%m-%d"), year
        except ValueError:
            return None, year_hint

    return None, year_hint


def adjust_year(date_iso: str, prev_iso: str | None, year_hint: int | None) -> tuple[str, int]:
    if not date_iso:
        return date_iso, year_hint or datetime.now().year

    current = datetime.strptime(date_iso, "%Y-%m-%d")
    year = year_hint or current.year
    candidate = current.replace(year=year)

    # Statement dates can wrap Dec -> Jan within one PDF.
    if prev_iso:
        prev = datetime.strptime(prev_iso, "%Y-%m-%d")
        if candidate < prev:
            year += 1
            candidate = current.replace(year=year)

    return candidate.strftime("%Y-%m-%d"), year


def normalize_row(cells: list[Any]) -> list[str]:
    return [str(cell or "").strip() for cell in cells]


def is_header_row(cells: list[str]) -> bool:
    joined = " ".join(cells).lower()
    hits = sum(1 for hint in HEADER_HINTS if hint in joined)
    return hits >= 2


def extract_year_hint_from_text(text: str) -> int | None:
    """Pull a year from opening-balance / statement-period style lines."""
    patterns = [
        re.compile(r"OPENING\s+BALANCE.*?(\d{4})", re.I | re.S),
        re.compile(r"Statement\s+period.*?(\d{4})", re.I | re.S),
        re.compile(r"\b\d{1,2}\s+[A-Za-z]{3}\s+(\d{4})\b"),
    ]
    for pattern in patterns:
        match = pattern.search(text)
        if not match:
            continue
        try:
            return int(match.group(1))
        except (TypeError, ValueError):
            continue
    return None


def build_entry(
    date_iso: str,
    description: str,
    debit: Decimal | None,
    credit: Decimal | None,
    balance: Decimal | None,
) -> dict[str, Any] | None:
    if should_skip_description(description):
        return None

    debit_val = Decimal("0") if debit is None else abs(debit)
    credit_val = Decimal("0") if credit is None else abs(credit)

    amount = Decimal("0")
    if debit_val != 0 and credit_val == 0:
        amount = -debit_val
    elif credit_val != 0 and debit_val == 0:
        amount = credit_val
    elif credit_val != 0 or debit_val != 0:
        amount = credit_val - debit_val

    description = description.strip()
    if amount == 0 and not description:
        return None

    transaction_type = "credit" if amount >= 0 else "debit"
    entry: dict[str, Any] = {
        "date": date_iso,
        "amount": float(amount.quantize(Decimal("0.01"))),
        "description": description or "Transaction",
        "transaction_type": transaction_type,
    }

    if balance is not None:
        entry["balance"] = float(balance.quantize(Decimal("0.01")))

    return entry


def split_leading_date(cells: list[str]) -> tuple[str | None, list[str]]:
    """Support rows where date and description share the first cell."""
    if not cells:
        return None, cells

    first = cells[0]
    full = DATE_FULL_TEXT_RE.match(first)
    if full and len(first) == len(full.group(0)):
        return first, cells[1:]

    day_month = DATE_DAY_MONTH_RE.match(first)
    if day_month and len(first) == len(day_month.group(0)):
        return first, cells[1:]

    slash = DATE_SLASH_RE.match(first)
    if slash and len(first) == len(slash.group(0)):
        return first, cells[1:]

    # "01 Jul Transfer to savings" style
    embedded = re.match(
        r"^(\d{1,2}\s+[A-Za-z]{3}(?:\s+\d{2,4})?)\s+(.+)$",
        first,
    )
    if embedded:
        rest = [embedded.group(2)] + cells[1:]
        return embedded.group(1), rest

    return None, cells


def look_like_money(value: str) -> bool:
    cleaned = value.strip().replace("(", "").replace(")", "")
    return bool(MONEY_RE.match(cleaned))


def money_values_from_cells(cells: list[str]) -> list[Decimal]:
    values: list[Decimal] = []
    for cell in cells:
        if not look_like_money(cell):
            continue
        amount = parse_amount(cell)
        if amount is not None:
            values.append(amount)
    return values


def disambiguate_amount_balance(
    amount: Decimal,
    balance: Decimal,
    last_balance: Decimal | None,
) -> tuple[Decimal | None, Decimal | None, Decimal]:
    """
    Compact PDF text often collapses blank debit/credit cells into:
    Date | Description | Amount | Balance
    Use the previous balance to recover the sign.
    """
    amount = abs(amount)
    if last_balance is not None:
        if abs((last_balance - amount) - balance) <= Decimal("0.01"):
            return amount, None, balance
        if abs((last_balance + amount) - balance) <= Decimal("0.01"):
            return None, amount, balance

    # Fallback without continuity: treat as debit (common for card spend lines).
    return amount, None, balance


def parse_row_cells(
    cells: list[str],
    year_hint: int | None,
    prev_date_iso: str | None,
    last_balance: Decimal | None = None,
) -> tuple[dict[str, Any] | None, int | None, str | None, Decimal | None]:
    if not cells or all(not cell for cell in cells):
        return None, year_hint, prev_date_iso, last_balance

    if is_header_row(cells):
        return None, year_hint, prev_date_iso, last_balance

    date_token, remainder = split_leading_date(cells)
    if not date_token:
        return None, year_hint, prev_date_iso, last_balance

    date_iso, year_hint = parse_date_token(date_token, year_hint)
    if not date_iso:
        return None, year_hint, prev_date_iso, last_balance

    date_iso, year_hint = adjust_year(date_iso, prev_date_iso, year_hint)

    joined_desc = " ".join(remainder).strip()
    money_vals = money_values_from_cells(remainder)

    # Capture year/balance from opening-balance rows, but do not emit them.
    if should_skip_description(joined_desc):
        next_balance = money_vals[-1] if money_vals else last_balance
        next_date = date_iso if "OPENING BALANCE" in joined_desc.upper() else prev_date_iso
        return None, year_hint, next_date, next_balance

    description = joined_desc
    debit: Decimal | None = None
    credit: Decimal | None = None
    balance: Decimal | None = None

    # Prefer explicit 5-column shaped rows when non-money description is first.
    if len(remainder) >= 4 and not look_like_money(remainder[0]):
        description = remainder[0]
        debit = parse_amount(remainder[1])
        credit = parse_amount(remainder[2])
        balance = parse_amount(remainder[3])
        # If credit/debit cells were blank and collapsed, remainder may actually be
        # desc + amount + balance with an extra trailing token — handled below.
        if debit is None and credit is None and len(money_vals) >= 2:
            debit, credit, balance = disambiguate_amount_balance(
                money_vals[0], money_vals[1], last_balance
            )
        elif (
            debit is not None
            and credit is not None
            and balance is None
            and len(money_vals) == 2
        ):
            # Misread amount/balance as debit/credit.
            debit, credit, balance = disambiguate_amount_balance(
                money_vals[0], money_vals[1], last_balance
            )
    elif len(money_vals) >= 3:
        # desc ... debit credit balance
        description = re.sub(
            r"(\$?\d[\d,]*\.\d{2}\s*)+$",
            "",
            joined_desc,
        ).strip()
        debit, credit, balance = money_vals[0], money_vals[1], money_vals[2]
        if debit == 0:
            debit = None
        if credit == 0:
            credit = None
    elif len(money_vals) == 2:
        description = re.sub(
            r"(\$?\d[\d,]*\.\d{2}\s*)+$",
            "",
            joined_desc,
        ).strip()
        debit, credit, balance = disambiguate_amount_balance(
            money_vals[0], money_vals[1], last_balance
        )
    elif len(money_vals) == 1:
        description = re.sub(
            r"(\$?\d[\d,]*\.\d{2}\s*)+$",
            "",
            joined_desc,
        ).strip()
        amount_cell = money_vals[0]
        debit = abs(amount_cell) if amount_cell < 0 else None
        credit = abs(amount_cell) if amount_cell > 0 else None
    else:
        return None, year_hint, prev_date_iso, last_balance

    entry = build_entry(date_iso, description, debit, credit, balance)
    if entry and balance is not None:
        last_balance = balance
    elif entry and last_balance is not None:
        last_balance = last_balance + Decimal(str(entry["amount"]))

    return entry, year_hint, date_iso if entry else prev_date_iso, last_balance


def parse_text_block(
    text: str,
    year_hint: int | None,
    prev_date_iso: str | None,
    last_balance: Decimal | None = None,
) -> tuple[list[dict[str, Any]], int | None, str | None, Decimal | None]:
    entries: list[dict[str, Any]] = []
    markers = [
        "Date\nTransaction\nDebit\nCredit\nBalance\n",
        "Date\nTransaction details\nDebit\nCredit\nBalance\n",
        "Date Transaction Debit Credit Balance",
        "Date Transaction details Debit Credit Balance",
    ]

    blocks: list[str] = [text]
    for marker in markers:
        if marker in text:
            blocks = [part for part in text.split(marker)[1:] if part.strip()]
            break

    for block in blocks:
        for raw_line in block.split("\n"):
            line = raw_line.strip()
            if not line:
                continue

            cells = [part.strip() for part in re.split(r"\s{2,}|\t", line) if part.strip()]
            if len(cells) < 2:
                # Fall back to single-space split for compact PDF text extractors.
                cells = line.split()
                if len(cells) < 3:
                    continue
                money_idxs = [i for i, part in enumerate(cells) if look_like_money(part)]
                if not money_idxs:
                    continue
                first_money = money_idxs[0]
                if DATE_FULL_TEXT_RE.match(" ".join(cells[:3])):
                    date_token = " ".join(cells[:3])
                    desc_start = 3
                elif DATE_DAY_MONTH_RE.match(" ".join(cells[:2])):
                    date_token = " ".join(cells[:2])
                    desc_start = 2
                else:
                    date_token = cells[0]
                    desc_start = 1
                description = " ".join(cells[desc_start:first_money]).strip()
                money_parts = cells[first_money:]
                cells = [date_token, description, *money_parts]

            entry, year_hint, prev_date_iso, last_balance = parse_row_cells(
                cells, year_hint, prev_date_iso, last_balance
            )
            if entry:
                entries.append(entry)

    return entries, year_hint, prev_date_iso, last_balance


def decrypt_pdf_if_needed(path: Path) -> tuple[Path | None, str | None]:
    """
    Return (path, error).
    If the PDF is encrypted with a non-empty password, return an error.
    """
    try:
        from pypdf import PdfReader, PdfWriter
    except ImportError:
        return path, None

    try:
        reader = PdfReader(str(path))
    except Exception as exc:  # noqa: BLE001 - surface PDF open errors cleanly
        return None, f"Unable to open PDF: {exc}"

    if not reader.is_encrypted:
        return path, None

    # Many bank PDFs are "encrypted" with an empty user password (print/copy restrictions).
    decrypt_result = reader.decrypt("")
    if decrypt_result == 0:
        return None, "PDF is password-protected. Export an unlocked statement PDF and try again."

    writer = PdfWriter()
    for page in reader.pages:
        writer.add_page(page)

    temp = tempfile.NamedTemporaryFile(delete=False, suffix=".pdf")
    temp.close()
    with open(temp.name, "wb") as handle:
        writer.write(handle)

    return Path(temp.name), None


def extract_entries(path: Path, bank_name: str) -> dict[str, Any]:
    try:
        import pdfplumber
    except ImportError:
        return {
            "success": False,
            "error": "pdfplumber is not installed. Run: pip install pdfplumber pypdf",
            "entries": [],
        }

    decrypted_path, decrypt_error = decrypt_pdf_if_needed(path)
    if decrypt_error:
        return {
            "success": False,
            "error": decrypt_error,
            "entries": [],
        }

    assert decrypted_path is not None

    entries: list[dict[str, Any]] = []
    year_hint: int | None = None
    prev_date_iso: str | None = None
    last_balance: Decimal | None = None
    full_text_parts: list[str] = []
    pages = 0

    try:
        with pdfplumber.open(str(decrypted_path)) as pdf:
            pages = len(pdf.pages)
            for page in pdf.pages:
                page_text = page.extract_text() or ""
                full_text_parts.append(page_text)

                for table in page.extract_tables() or []:
                    for row in table:
                        cells = normalize_row(row)
                        entry, year_hint, prev_date_iso, last_balance = parse_row_cells(
                            cells, year_hint, prev_date_iso, last_balance
                        )
                        if entry:
                            entries.append(entry)

            combined_text = "\n".join(full_text_parts)
            if year_hint is None:
                year_hint = extract_year_hint_from_text(combined_text)

            text_entries, year_hint, prev_date_iso, last_balance = parse_text_block(
                combined_text,
                year_hint,
                prev_date_iso,
                last_balance=None if entries else last_balance,
            )

            if not entries:
                entries = text_entries
    finally:
        if decrypted_path != path and decrypted_path.exists():
            decrypted_path.unlink(missing_ok=True)

    detected_bank = detect_bank("\n".join(full_text_parts), bank_name)

    # Deduplicate identical rows that table + text parsing may both capture
    seen: set[tuple[str, float, str]] = set()
    unique_entries: list[dict[str, Any]] = []
    for entry in entries:
        key = (entry["date"], float(entry["amount"]), entry["description"])
        if key in seen:
            continue
        seen.add(key)
        unique_entries.append(entry)

    return {
        "success": True,
        "entries": unique_entries,
        "metadata": {
            "detected_bank": detected_bank,
            "pages": pages,
            "entry_count": len(unique_entries),
            "parser": "python_bank_pdf_parser",
        },
    }


def emit(result: dict[str, Any], *, exit_code: int = 0) -> None:
    """Always emit JSON on stdout so Laravel can decode failures reliably."""
    print(json.dumps(result, indent=2))
    raise SystemExit(exit_code)


def main() -> None:
    parser = argparse.ArgumentParser(description="Parse bank statement PDF files")
    parser.add_argument("file_path", help="Path to bank statement PDF")
    parser.add_argument(
        "--bank-name",
        default="auto",
        help="Bank hint: auto, cba, nab",
    )
    args = parser.parse_args()

    path = Path(args.file_path)
    if not path.exists():
        emit(
            {"success": False, "error": f"File not found: {path}", "entries": []},
            exit_code=1,
        )

    if path.suffix.lower() != ".pdf":
        emit(
            {
                "success": False,
                "error": "Only PDF files are supported by this parser",
                "entries": [],
            },
            exit_code=1,
        )

    try:
        result = extract_entries(path, args.bank_name.lower().strip())
        emit(result, exit_code=0 if result.get("success") else 1)
    except Exception as exc:  # noqa: BLE001 - return structured error to Laravel
        emit(
            {"success": False, "error": str(exc), "entries": []},
            exit_code=1,
        )


if __name__ == "__main__":
    main()
