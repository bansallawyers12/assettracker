#!/usr/bin/env python3
"""
Parse Australian bank statement PDFs (CBA, NAB, Macquarie, Westpac, generic) into transaction JSON for Laravel.
Usage: python_bank_pdf_parser.py <file_path> [--bank-name auto|cba|nab|macquarie|westpac]
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
DATE_ISO_RE = re.compile(r"^(\d{4})-(\d{2})-(\d{2})$")
# CBA PDFs often collapse spaces: "15Mar", "04Mar 2026OPENINGBALANCE", "03Jun2026CLOSING"
DATE_DDMMM_RE = re.compile(r"^(\d{1,2})([A-Za-z]{3})(\d{4})?$")
DATE_DDMMM_SPACE_YEAR_RE = re.compile(
    r"^(\d{1,2})([A-Za-z]{3})\s+(\d{4})(.*)$"
)
# Macquarie section headers: "Jan 2026", "Feb 2026"
MONTH_YEAR_HEADER_RE = re.compile(
    r"^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})$",
    re.I,
)

# Canonical statement row columns. Every parsed entry fills these fields.
FIXED_COLUMNS = (
    "date",
    "description",
    "amount_debit",
    "amount_credit",
    "balance",
)
SKIP_DESCRIPTION_PATTERNS = [
    re.compile(r"OPENING\s*BALANCE", re.I),
    re.compile(r"CLOSING\s*BALANCE", re.I),
    re.compile(r"BALANCE\s+CARRIED\s+FORWARD", re.I),
    re.compile(r"^TOTAL\s+(DEBITS?|CREDITS?)", re.I),
    re.compile(r"^Page\s+\d+", re.I),
    re.compile(r"^BROUGHT\s+FORWARD\b", re.I),
    re.compile(r"PLEASE\s+NOTE\s+YOUR\s+CURRENT\s+DEBIT\s+INTEREST", re.I),
    re.compile(r"^OPENING\s+INTEREST\s+RATE\b", re.I),
    re.compile(r"^CLOSING\s+INTEREST\s+RATE\b", re.I),
    re.compile(r"^TRANSACTION\s+SUMMARY\b", re.I),
    re.compile(r"^TRANSACTIONTYPE\b", re.I),
    re.compile(r"\bUNIT\s+ELECTRONIC\s+(?:CREDITS?|DEBITS?)\b", re.I),
    re.compile(r"\bFEE\s*CHARGED\b", re.I),
    re.compile(r"\bACCOUNTE?FEE\b", re.I),
]

HEADER_HINTS = {"date", "transaction", "description", "debit", "credit", "balance", "details"}

BANK_MARKERS = {
    "cba": ("COMMONWEALTH BANK", "COMMBANK", "NETBANK"),
    "nab": ("NATIONAL AUSTRALIA BANK", "NAB LIMITED", "NAB BUSINESS"),
    "macquarie": ("MACQUARIE BANK", "MACQUARIE", "MACQUARIE GROUP"),
    "westpac": ("WESTPAC", "WESTPAC BANKING CORPORATION"),
}

# Macquarie PDFs often append CR/DR to money cells, e.g. 45,894.56CR
MONEY_RE = re.compile(r"^-?\$?\d[\d,]*\.\d{2}(?:\s*(?:CR|DR))?$", re.I)
TRAILING_MONEY_CHUNK_RE = re.compile(
    r"(?:\s+\$?-?\d[\d,]*\.\d{2}(?:\s*(?:CR|DR))?)+$",
    re.I,
)
DEBIT_HINT_RE = re.compile(
    r"\b(direct\s+debit|withdrawal|payment\s+to|transfer\s+to|purchase|pos\b|eftpos|debit"
    r"|interest\s+payable|loan\s+service\s+fee|line\s+fee|redirected\s+from\s+account)\b",
    re.I,
)
CREDIT_HINT_RE = re.compile(
    r"\b(deposit|salary|transfer\s+from|refund|credit\s+to|direct\s+credit"
    r"|interest\s+(?:earned|paid|credited))\b",
    re.I,
)
# Westpac wraps long narrations onto a second line (often just "Number 123" / account digits).
WESTPAC_CONTINUATION_DESC_RE = re.compile(
    r"^(?:Number\s+)?\d{3,}(?:\s+\d+)*$",
    re.I,
)


def detect_bank(text: str, requested: str) -> str:
    if requested in ("cba", "nab", "macquarie", "westpac"):
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
    upper = text.upper()
    # DR on a money token means debit/outflow; CR on balances is Macquarie credit-balance notation.
    force_debit = bool(re.search(r"\s*DR$", upper))
    cleaned = re.sub(r"\s*(CR|DR)$", "", upper, flags=re.I)
    cleaned = cleaned.replace("$", "").replace(",", "").replace(" ", "")
    cleaned = cleaned.replace("(", "").replace(")", "")

    if not cleaned:
        return None

    try:
        amount = Decimal(cleaned)
    except InvalidOperation:
        return None

    if negative or force_debit:
        return -abs(amount)

    return amount


def normalize_year(year_part: str | int) -> int:
    year = int(year_part)
    if year < 100:
        year += 2000
    return year


def is_plausible_statement_year(year: int) -> bool:
    current = datetime.now().year
    return 1990 <= year <= current + 1


def parse_date_token(
    token: str, year_hint: int | None
) -> tuple[str | None, int | None, bool]:
    token = token.strip()
    if not token:
        return None, year_hint, False

    match = DATE_FULL_TEXT_RE.match(token)
    if match:
        day, month, year_part = match.groups()
        year = normalize_year(year_part)
        try:
            parsed = datetime.strptime(f"{int(day)} {month.title()} {year}", "%d %b %Y")
            return parsed.strftime("%Y-%m-%d"), year, True
        except ValueError:
            return None, year_hint, False

    match = DATE_DAY_MONTH_RE.match(token)
    if match:
        day, month = match.groups()
        year = year_hint or datetime.now().year
        try:
            parsed = datetime.strptime(f"{int(day)} {month.title()} {year}", "%d %b %Y")
            return parsed.strftime("%Y-%m-%d"), year, False
        except ValueError:
            return None, year_hint, False

    match = DATE_DDMMM_SPACE_YEAR_RE.match(token)
    if match:
        day, month, year_part, _rest = match.groups()
        year = int(year_part)
        try:
            parsed = datetime.strptime(f"{int(day)} {month.title()} {year}", "%d %b %Y")
            return parsed.strftime("%Y-%m-%d"), year, True
        except ValueError:
            return None, year_hint, False

    match = DATE_DDMMM_RE.match(token)
    if match:
        day, month, year_part = match.groups()
        year = int(year_part) if year_part else (year_hint or datetime.now().year)
        try:
            parsed = datetime.strptime(f"{int(day)} {month.title()} {year}", "%d %b %Y")
            return parsed.strftime("%Y-%m-%d"), year, bool(year_part)
        except ValueError:
            return None, year_hint, False

    match = DATE_SLASH_RE.match(token)
    if match:
        day, month, year_part = match.groups()
        year = normalize_year(year_part)
        try:
            parsed = datetime.strptime(f"{int(day)}/{int(month)}/{year}", "%d/%m/%Y")
            return parsed.strftime("%Y-%m-%d"), year, True
        except ValueError:
            return None, year_hint, False

    match = DATE_ISO_RE.match(token)
    if match:
        year, month, day = match.groups()
        try:
            parsed = datetime(int(year), int(month), int(day))
            return parsed.strftime("%Y-%m-%d"), int(year), True
        except ValueError:
            return None, year_hint, False

    return None, year_hint, False


def adjust_year(
    date_iso: str,
    prev_iso: str | None,
    year_hint: int | None,
    *,
    explicit_year: bool = False,
) -> tuple[str, int]:
    if not date_iso:
        return date_iso, year_hint or datetime.now().year

    current = datetime.strptime(date_iso, "%Y-%m-%d")
    if explicit_year:
        return date_iso, current.year

    year = year_hint if year_hint and is_plausible_statement_year(year_hint) else current.year
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
    if row_has_leading_date(cells):
        return False
    joined = " ".join(cells).lower()
    hits = sum(
        1 for hint in HEADER_HINTS if re.search(rf"\b{re.escape(hint)}\b", joined)
    )
    return hits >= 2


def extract_year_hint_from_text(text: str) -> int | None:
    """Pull a year from statement-period headers (avoid FY / amount false positives)."""
    patterns = [
        re.compile(r"From\s+\d{1,2}\s+\w+\s+(\d{4})\s+to\b", re.I),
        re.compile(r"Statement\s+Period\s+\d{1,2}\s+\w+\s+(\d{4})\b", re.I | re.S),
        re.compile(r"Statement\s+starts\s+\d{1,2}\s+\w+\s+(\d{4})\b", re.I),
        re.compile(r"Statement\s*\n?\s*Period\s+\d{1,2}\w{3}(\d{4})\b", re.I),
        re.compile(r"\bPeriod\s+\d{1,2}\w{3}(\d{4})\b", re.I),
        re.compile(r"OPENING\s+BALANCE.*?(\d{4})", re.I | re.S),
        re.compile(r"\b\d{1,2}\s+[A-Za-z]{3}\s+(\d{4})\b"),
    ]
    for pattern in patterns:
        match = pattern.search(text)
        if not match:
            continue
        try:
            year = int(match.group(1))
        except (TypeError, ValueError):
            continue
        if is_plausible_statement_year(year):
            return year
    return None


def money_or_none(value: Decimal | None) -> float | None:
    """Format a money amount for output; blank/zero becomes null."""
    if value is None:
        return None
    quantized = abs(value).quantize(Decimal("0.01"))
    if quantized == 0:
        return None
    return float(quantized)


def balance_or_none(value: Decimal | None) -> float | None:
    """Keep the balance sign: DR/overdrawn balances are negative."""
    if value is None:
        return None
    return float(value.quantize(Decimal("0.01")))


def build_entry(
    date_iso: str,
    description: str,
    debit: Decimal | None,
    credit: Decimal | None,
    balance: Decimal | None,
) -> dict[str, Any] | None:
    """
    Build one statement row using fixed columns:
    date | description | amount_debit | amount_credit | balance

    Also includes derived `amount` (credit positive / debit negative) and
    `transaction_type` for Laravel consumers.
    """
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
    if amount == 0 and debit_val == 0 and credit_val == 0 and balance is None:
        return None
    if amount == 0 and not description:
        return None

    transaction_type = "credit" if amount >= 0 else "debit"
    entry: dict[str, Any] = {
        "date": date_iso,
        "description": description or "Transaction",
        "amount_debit": money_or_none(debit_val),
        "amount_credit": money_or_none(credit_val),
        "balance": balance_or_none(balance),
        # Derived helpers kept for existing Laravel import paths.
        "amount": float(amount.quantize(Decimal("0.01"))),
        "transaction_type": transaction_type,
    }

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

    ddmmm = DATE_DDMMM_RE.match(first)
    if ddmmm and len(first) == len(ddmmm.group(0)):
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

    # CBA: "15Mar TransferToAjayBansal" or "04Mar 2026OPENINGBALANCE"
    embedded_ddmmm = re.match(
        r"^(\d{1,2}[A-Za-z]{3}(?:\s+\d{4})?)\s+(.+)$",
        first,
    )
    if embedded_ddmmm:
        rest = [embedded_ddmmm.group(2)] + cells[1:]
        return embedded_ddmmm.group(1), rest

    # Westpac often keeps "31/03/26 Loan Service Fee..." in one cell.
    embedded_slash = re.match(
        r"^(\d{1,2}/\d{1,2}/\d{2,4})\s+(.+)$",
        first,
    )
    if embedded_slash:
        rest = [embedded_slash.group(2)] + cells[1:]
        return embedded_slash.group(1), rest

    return None, cells


def look_like_money(value: str) -> bool:
    cleaned = value.strip().replace("(", "").replace(")", "")
    cleaned = re.sub(r"\s+(CR|DR)$", r"\1", cleaned, flags=re.I)
    return bool(MONEY_RE.match(cleaned))


def strip_trailing_money(description: str) -> str:
    """Remove trailing amount/balance tokens from a description (Macquarie compact lines)."""
    return TRAILING_MONEY_CHUNK_RE.sub("", description).strip()


def money_values_from_cells(cells: list[str]) -> list[Decimal]:
    """Extract money values keeping their sign (DR suffix and parentheses are negative)."""
    values: list[Decimal] = []
    for cell in cells:
        if not look_like_money(cell):
            continue
        amount = parse_amount(cell)
        if amount is not None:
            values.append(amount)
    # Also pull CR/DR money glued inside a longer description cell.
    if not values:
        for cell in cells:
            for match in re.finditer(r"\$?-?\d[\d,]*\.\d{2}(?:CR|DR)?", cell, flags=re.I):
                amount = parse_amount(match.group(0))
                if amount is not None:
                    values.append(amount)
    return values


def glue_money_suffix(text: str) -> str:
    """Attach detached CR/DR markers to their amount: '607.06 Dr' -> '607.06Dr'."""
    return re.sub(r"(\.\d{2})\s+(CR|DR)\b", r"\1\2", text, flags=re.I)


def money_values_from_text(text: str) -> list[Decimal]:
    text = glue_money_suffix(text)
    values: list[Decimal] = []
    for match in re.finditer(r"\$?-?\d[\d,]*\.\d{2}(?:CR|DR)?", text, flags=re.I):
        amount = parse_amount(match.group(0))
        if amount is not None:
            values.append(amount)
    return values


def infer_sign_from_description(description: str) -> str | None:
    if description.strip().lower().startswith("return"):
        return "credit"
    if DEBIT_HINT_RE.search(description):
        return "debit"
    if CREDIT_HINT_RE.search(description):
        return "credit"
    return None


def row_has_leading_date(cells: list[str]) -> bool:
    date_token, _ = split_leading_date(cells)
    return date_token is not None


def line_starts_with_date(line: str) -> bool:
    line = line.strip()
    if not line:
        return False

    first = line.split()[0]
    if DATE_SLASH_RE.match(first):
        return True
    if DATE_DAY_MONTH_RE.match(first):
        return True
    if DATE_FULL_TEXT_RE.match(first):
        return True
    if DATE_DDMMM_RE.match(first):
        return True

    if DATE_SLASH_RE.match(line):
        return True
    if DATE_DAY_MONTH_RE.match(" ".join(line.split()[:2])):
        return True
    if DATE_DDMMM_RE.match(line.split()[0]):
        return True

    # CBA: "04Mar 2026OPENINGBALANCE" — date token is first word
    return bool(re.match(r"^\d{1,2}[A-Za-z]{3}", line))


def is_westpac_continuation_text(line: str) -> bool:
    """True when a non-dated line is a Westpac wrap fragment, not footer/boilerplate."""
    line = line.strip()
    if not line or line_starts_with_date(line):
        return False
    if is_header_row([line]) or should_skip_description(line):
        return False

    if money_values_from_text(line):
        return True

    # Keep short account/reference wraps; reject prose footers ("Please check...").
    if len(line) > 48:
        return False

    return bool(WESTPAC_CONTINUATION_DESC_RE.match(line))


def is_westpac_continuation_row(cells: list[str]) -> bool:
    """Westpac wraps long descriptions onto a second row without a leading date."""
    if not cells or row_has_leading_date(cells):
        return False
    if is_header_row(cells):
        return False

    joined = " ".join(cell for cell in cells if cell).strip()
    if not joined or should_skip_description(joined):
        return False

    if money_values_from_cells(cells) or money_values_from_text(joined):
        return True

    return is_westpac_continuation_text(joined)


def ensure_westpac_row_width(cells: list[str]) -> list[str]:
    """Pad/truncate to date, description, debit, credit, balance."""
    row = list(cells[:5])
    while len(row) < 5:
        row.append("")
    return row


def normalize_westpac_row_width(cells: list[str]) -> list[str]:
    """Expand a Westpac row to date, description, debit, credit, balance."""
    cells = normalize_row(cells)
    date_token, remainder = split_leading_date(cells)
    if not date_token:
        return ensure_westpac_row_width(cells)

    row = ["", "", "", "", ""]
    row[0] = date_token

    if len(remainder) >= 4 and not look_like_money(remainder[0]):
        row[1] = remainder[0]
        row[2] = remainder[1] if look_like_money(remainder[1]) else ""
        row[3] = remainder[2] if len(remainder) > 2 and look_like_money(remainder[2]) else ""
        row[4] = remainder[3] if len(remainder) > 3 and look_like_money(remainder[3]) else ""
        return row

    desc_parts = [cell for cell in remainder if cell and not look_like_money(cell)]
    money_parts = [cell for cell in remainder if look_like_money(cell)]
    row[1] = " ".join(desc_parts)

    if len(money_parts) >= 3:
        row[2], row[3], row[4] = money_parts[0], money_parts[1], money_parts[2]
    elif len(money_parts) == 2:
        amount, balance = money_parts[0], money_parts[1]
        if infer_sign_from_description(row[1]) == "credit":
            row[3] = amount
        else:
            row[2] = amount
        row[4] = balance
    elif len(money_parts) == 1:
        row[4] = money_parts[0]

    return row


def merge_westpac_continuation(prev: list[str], cont: list[str]) -> list[str]:
    """Merge a wrapped Westpac description continuation into the previous row."""
    prev = ensure_westpac_row_width(normalize_westpac_row_width(prev))
    cont = normalize_row(cont)

    while cont and not cont[0]:
        cont = cont[1:]

    if len(cont) == 1 and not row_has_leading_date(cont):
        expanded = cells_from_text_line(cont[0])
        if expanded and not row_has_leading_date(expanded):
            cont = expanded

    desc_parts = [cell for cell in cont if cell and not look_like_money(cell)]
    if desc_parts:
        extra = " ".join(desc_parts).strip()
        prev[1] = f"{prev[1]} {extra}".strip() if prev[1] else extra

    money_parts = [cell for cell in cont if look_like_money(cell)]
    if len(money_parts) >= 2:
        amount, balance = money_parts[0], money_parts[-1]
        if not prev[2] and not prev[3]:
            if infer_sign_from_description(prev[1]) == "credit":
                prev[3] = amount
            else:
                prev[2] = amount
        if not prev[4]:
            prev[4] = balance
    elif len(money_parts) == 1:
        if not prev[4]:
            prev[4] = money_parts[0]

    return prev


def merge_westpac_table_rows(rows: list[list[Any]]) -> list[list[str]]:
    merged: list[list[str]] = []
    for row in rows:
        cells = normalize_row(row)
        if not any(cells):
            continue
        if is_header_row(cells):
            continue
        if row_has_leading_date(cells):
            merged.append(normalize_westpac_row_width(cells))
            continue
        if merged and is_westpac_continuation_row(cells):
            merged[-1] = merge_westpac_continuation(merged[-1], cells)

    return merged


def cells_from_text_line(line: str) -> list[str]:
    line = glue_money_suffix(line)
    cells = [part.strip() for part in re.split(r"\s{2,}|\t", line) if part.strip()]
    if len(cells) >= 2:
        date_token, remainder = split_leading_date(cells)
        if date_token:
            return [date_token, *remainder]
        return cells

    if line_starts_with_date(line):
        date_token = extract_leading_date_from_line(line)
        if date_token:
            rest = line[len(date_token) :].strip()
            if not rest:
                return [date_token]
            money_idxs = [index for index, part in enumerate(rest.split()) if look_like_money(part)]
            if money_idxs:
                parts = rest.split()
                first_money = money_idxs[0]
                description = " ".join(parts[:first_money]).strip()
                money_parts = parts[first_money:]
                return [date_token, description, *money_parts]
            return [date_token, rest]

    parts = line.split()
    if len(parts) < 2:
        return [line] if line else []

    money_idxs = [index for index, part in enumerate(parts) if look_like_money(part)]
    if not money_idxs:
        if line_starts_with_date(line):
            date_token = extract_leading_date_from_line(line) or parts[0]
            remainder = line[len(date_token) :].strip() if date_token else " ".join(parts[1:])
            return [date_token, remainder] if remainder else [date_token]
        return [line]

    first_money = money_idxs[0]
    money_parts = parts[first_money:]

    # Continuation lines (no leading date) keep description tokens before amounts.
    if not line_starts_with_date(line):
        description = " ".join(parts[:first_money]).strip()
        return [description, *money_parts] if description else money_parts

    date_token = extract_leading_date_from_line(line) or parts[0]
    description = " ".join(parts[1:first_money]).strip()
    if date_token in description:
        description = description.replace(date_token, "", 1).strip()
    return [date_token, description, *money_parts]


def extract_leading_date_from_line(line: str) -> str | None:
    line = line.strip()
    if not line:
        return None

    glued = re.match(r"^(\d{1,2})([A-Za-z]{3})(\d{4})", line)
    if glued:
        return f"{glued.group(1)}{glued.group(2)}{glued.group(3)}"

    parts = line.split()
    if not parts:
        return None

    first = parts[0]
    if DATE_SLASH_RE.match(first):
        return first
    if DATE_FULL_TEXT_RE.match(" ".join(parts[:3])):
        return " ".join(parts[:3])
    if DATE_DAY_MONTH_RE.match(" ".join(parts[:2])):
        return " ".join(parts[:2])

    match = DATE_DDMMM_SPACE_YEAR_RE.match(line)
    if match:
        return f"{match.group(1)}{match.group(2)} {match.group(3)}"

    if DATE_DDMMM_RE.match(first):
        return first

    return None


def is_month_year_header(line: str) -> tuple[int | None, int | None]:
    match = MONTH_YEAR_HEADER_RE.match(line.strip())
    if not match:
        return None, None
    month_name, year_part = match.groups()
    try:
        month_num = datetime.strptime(month_name.title(), "%b").month
        return int(year_part), month_num
    except ValueError:
        return None, None


def is_continuation_text_line(line: str) -> bool:
    """True when a non-dated line continues the previous transaction row."""
    line = line.strip()
    if not line or line_starts_with_date(line):
        return False
    if is_header_row([line]) or should_skip_description(line):
        return False
    if is_month_year_header(line)[0] is not None:
        return False
    if re.search(r"^(Statement|AccountNumber|Transaction Summary|TransactionType)\b", line, re.I):
        return False

    if money_values_from_text(line):
        return True

    if is_westpac_continuation_text(line):
        return True

    # CBA/NAB wrapped narration before amounts on the next physical line.
    if len(line) <= 80 and not re.search(r"\bPage\s+\d+\b", line, flags=re.I):
        return True

    return False


def merge_continuation_row(prev: list[str], cont: list[str]) -> list[str]:
    """Merge a wrapped description/amount continuation into the previous row."""
    prev = normalize_row(prev)
    cont = normalize_row(cont)

    while cont and not cont[0]:
        cont = cont[1:]

    if len(cont) == 1 and not row_has_leading_date(cont):
        expanded = cells_from_text_line(cont[0])
        if expanded and not row_has_leading_date(expanded):
            cont = expanded

    desc_parts = [cell for cell in cont if cell and not look_like_money(cell)]
    if desc_parts:
        extra = " ".join(desc_parts).strip()
        if not prev:
            prev = ["", extra]
        else:
            while len(prev) < 2:
                prev.append("")
            prev[1] = f"{prev[1]} {extra}".strip() if prev[1] else extra

    money_parts = [cell for cell in cont if look_like_money(cell)]
    if money_parts:
        while len(prev) < 5:
            prev.append("")
        if len(money_parts) >= 3:
            prev[2], prev[3], prev[4] = money_parts[0], money_parts[1], money_parts[2]
        elif len(money_parts) == 2:
            amount, balance = money_parts[0], money_parts[1]
            if not prev[2] and not prev[3]:
                if infer_sign_from_description(prev[1]) == "credit":
                    prev[3] = amount
                else:
                    prev[2] = amount
            else:
                # NAB prints two sub-charges in one dated row; the balance moves
                # by their sum, so accumulate into the existing amount cell.
                slot = 2 if prev[2] else 3
                existing = parse_amount(prev[slot])
                extra_amount = parse_amount(amount)
                if existing is not None and extra_amount is not None:
                    prev[slot] = str(abs(existing) + abs(extra_amount))
            if not prev[4]:
                prev[4] = balance
        elif len(money_parts) == 1 and not prev[4]:
            prev[4] = money_parts[0]

    return prev


def group_transaction_text_blocks(lines: list[str]) -> list[str]:
    blocks: list[str] = []
    current: list[str] = []

    for raw_line in lines:
        line = raw_line.strip()
        if not line:
            continue
        if is_month_year_header(line)[0] is not None:
            if current:
                blocks.append("\n".join(current))
            current = [line]
            continue
        if line_starts_with_date(line):
            if current:
                blocks.append("\n".join(current))
            current = [line]
        elif current and is_continuation_text_line(line):
            current.append(line)

    if current:
        blocks.append("\n".join(current))

    return blocks


def parse_generic_text_block(
    block: str,
    year_hint: int | None,
    prev_date_iso: str | None,
    last_balance: Decimal | None = None,
) -> tuple[dict[str, Any] | None, int | None, str | None, Decimal | None]:
    lines = [line.strip() for line in block.split("\n") if line.strip()]
    if not lines:
        return None, year_hint, prev_date_iso, last_balance

    start_idx = 0
    for index, line in enumerate(lines):
        header_year, _ = is_month_year_header(line)
        if header_year is not None:
            year_hint = header_year
            continue
        if line_starts_with_date(line):
            start_idx = index
            break
    else:
        return None, year_hint, prev_date_iso, last_balance

    row_cells = cells_from_text_line(lines[start_idx])
    row = list(row_cells)
    for line in lines[start_idx + 1 :]:
        header_year, _ = is_month_year_header(line)
        if header_year is not None:
            year_hint = header_year
            continue
        row = merge_continuation_row(row, cells_from_text_line(line))

    return parse_row_cells(row, year_hint, prev_date_iso, last_balance)


def group_westpac_text_blocks(lines: list[str]) -> list[str]:
    blocks: list[str] = []
    current: list[str] = []

    for raw_line in lines:
        line = raw_line.strip()
        if not line:
            continue
        if line_starts_with_date(line):
            if current:
                blocks.append("\n".join(current))
            current = [line]
        elif current and is_westpac_continuation_text(line):
            current.append(line)

    if current:
        blocks.append("\n".join(current))

    return blocks


def parse_westpac_text_block(
    block: str,
    year_hint: int | None,
    prev_date_iso: str | None,
    last_balance: Decimal | None = None,
) -> tuple[dict[str, Any] | None, int | None, str | None, Decimal | None]:
    lines = [line.strip() for line in block.split("\n") if line.strip()]
    if not lines:
        return None, year_hint, prev_date_iso, last_balance

    row = normalize_westpac_row_width(cells_from_text_line(lines[0]))
    for line in lines[1:]:
        row = merge_westpac_continuation(row, cells_from_text_line(line))

    return parse_row_cells(row, year_hint, prev_date_iso, last_balance)


def should_use_westpac_layout(bank_hint: str, text: str) -> bool:
    if bank_hint == "westpac":
        return True

    return detect_bank(text, "auto") == "westpac"


def disambiguate_amount_balance(
    amount: Decimal,
    balance: Decimal,
    last_balance: Decimal | None,
    description: str = "",
) -> tuple[Decimal | None, Decimal | None, Decimal]:
    """
    Compact PDF text often collapses blank debit/credit cells into:
    Date | Description | Amount | Balance
    Use the previous balance / narration hints to recover the sign.
    Balances stay signed so DR/overdrawn arithmetic works (e.g. 280.25 -> -3,368.35).
    """
    amount = abs(amount)
    if last_balance is not None:
        if abs((last_balance - amount) - balance) <= Decimal("0.01"):
            return amount, None, balance
        if abs((last_balance + amount) - balance) <= Decimal("0.01"):
            return None, amount, balance

    hint = infer_sign_from_description(description)
    if hint == "debit":
        return amount, None, balance
    if hint == "credit":
        return None, amount, balance
    # Default: treat as outflow (Macquarie loan direct debits, card spend).
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

    date_iso, year_hint, explicit_year = parse_date_token(date_token, year_hint)
    if not date_iso:
        return None, year_hint, prev_date_iso, last_balance

    date_iso, year_hint = adjust_year(
        date_iso, prev_date_iso, year_hint, explicit_year=explicit_year
    )

    joined_desc = " ".join(remainder).strip()
    money_vals = money_values_from_cells(remainder)
    if len(money_vals) < 2:
        # Macquarie often keeps amount+balanceCR inside the description cell.
        money_vals = money_values_from_text(joined_desc)

    # Capture year/balance from opening-balance rows, but do not emit them.
    if should_skip_description(joined_desc):
        next_balance = money_vals[-1] if money_vals else last_balance
        upper = joined_desc.upper()
        next_date = date_iso if "OPENING" in upper and "BALANCE" in upper else prev_date_iso
        if "BROUGHT" in upper and "FORWARD" in upper and money_vals:
            # Brought-forward rows put the balance first; signs come from CR/DR suffixes.
            next_balance = money_vals[0]
        return None, year_hint, next_date, next_balance

    description = strip_trailing_money(joined_desc)
    debit: Decimal | None = None
    credit: Decimal | None = None
    balance: Decimal | None = None

    # Prefer explicit 5-column shaped rows when non-money description is first.
    if len(remainder) >= 4 and not look_like_money(remainder[0]):
        description = strip_trailing_money(remainder[0])
        if (
            not remainder[2].strip()
            and look_like_money(remainder[1])
            and look_like_money(remainder[3])
            and len(money_vals) >= 2
        ):
            debit, credit, balance = disambiguate_amount_balance(
                money_vals[0], money_vals[1], last_balance, description
            )
        else:
            debit = parse_amount(remainder[1])
            credit = parse_amount(remainder[2])
            # Balance keeps its sign (DR/overdrawn is negative).
            balance = parse_amount(remainder[3])
            if debit is not None:
                debit = abs(debit)
            if credit is not None:
                credit = abs(credit)
            # If credit/debit cells were blank and collapsed, remainder may actually be
            # desc + amount + balance with an extra trailing token — handled below.
            if debit is None and credit is None and len(money_vals) >= 2:
                debit, credit, balance = disambiguate_amount_balance(
                    money_vals[0], money_vals[1], last_balance, description
                )
            elif (
                debit is not None
                and credit is not None
                and balance is None
                and len(money_vals) == 2
            ):
                # Misread amount/balance as debit/credit.
                debit, credit, balance = disambiguate_amount_balance(
                    money_vals[0], money_vals[1], last_balance, description
                )
    elif (
        len(remainder) == 3
        and not look_like_money(remainder[0])
        and look_like_money(remainder[1])
        and look_like_money(remainder[2])
        and len(money_vals) >= 2
    ):
        description = strip_trailing_money(remainder[0])
        debit, credit, balance = disambiguate_amount_balance(
            money_vals[0], money_vals[1], last_balance, description
        )
    elif len(money_vals) >= 3:
        # desc ... debit credit balance (balance keeps its sign)
        debit, credit, balance = abs(money_vals[0]), abs(money_vals[1]), money_vals[2]
        if debit == 0:
            debit = None
        if credit == 0:
            credit = None
    elif len(money_vals) == 2:
        debit, credit, balance = disambiguate_amount_balance(
            money_vals[0], money_vals[1], last_balance, description
        )
    elif len(money_vals) == 1:
        amount_cell = abs(money_vals[0])
        hint = infer_sign_from_description(description)
        if hint == "credit":
            debit, credit = None, amount_cell
        else:
            # Debit hint or unknown → outflow (Macquarie loan direct debits).
            debit, credit = amount_cell, None
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
    *,
    use_westpac: bool = False,
) -> tuple[list[dict[str, Any]], int | None, str | None, Decimal | None]:
    entries: list[dict[str, Any]] = []
    markers = [
        "Date\nTransaction Description\nDebit\nCredit\nBalance\n",
        "DATE\nTRANSACTION DESCRIPTION\nDEBIT\nCREDIT\nBALANCE\n",
        "Date\nTransaction\nDebit\nCredit\nBalance\n",
        "Date\nTransaction details\nDebit\nCredit\nBalance\n",
        "Date\nTransaction details\nAmount\nBalance\n",
        "Date Transaction Description Debit Credit Balance",
        "Date Transaction Debit Credit Balance",
        "Date Transaction details Debit Credit Balance",
        "Date Transaction details Amount Balance",
        "Date Particulars Debits Credits Balance",
    ]

    blocks: list[str] = [text]
    for marker in markers:
        if marker in text:
            blocks = [part for part in text.split(marker)[1:] if part.strip()]
            break

    for block in blocks:
        lines = [raw_line.strip() for raw_line in block.split("\n") if raw_line.strip()]

        if use_westpac:
            for westpac_block in group_westpac_text_blocks(lines):
                entry, year_hint, prev_date_iso, last_balance = parse_westpac_text_block(
                    westpac_block, year_hint, prev_date_iso, last_balance
                )
                if entry:
                    entries.append(entry)
            continue

        for generic_block in group_transaction_text_blocks(lines):
            entry, year_hint, prev_date_iso, last_balance = parse_generic_text_block(
                generic_block, year_hint, prev_date_iso, last_balance
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
            # Detect bank from full text first so Westpac wrap-merge applies on page 1 tables.
            for page in pdf.pages:
                full_text_parts.append(page.extract_text() or "")

            combined_text = "\n".join(full_text_parts)
            use_westpac = should_use_westpac_layout(bank_name, combined_text)
            year_hint = extract_year_hint_from_text(combined_text)

            for page in pdf.pages:
                for table in page.extract_tables() or []:
                    try:
                        table_rows = merge_westpac_table_rows(table) if use_westpac else table
                    except (IndexError, ValueError, TypeError):
                        table_rows = table
                    for row in table_rows:
                        cells = normalize_row(row) if not use_westpac else row
                        try:
                            entry, year_hint, prev_date_iso, last_balance = parse_row_cells(
                                cells, year_hint, prev_date_iso, last_balance
                            )
                        except (IndexError, ValueError, TypeError):
                            continue
                        if entry:
                            entries.append(entry)

            try:
                text_entries, year_hint, prev_date_iso, last_balance = parse_text_block(
                    combined_text,
                    year_hint,
                    prev_date_iso,
                    last_balance=None if entries else last_balance,
                    use_westpac=use_westpac,
                )
            except (IndexError, ValueError, TypeError):
                text_entries = []

            # Westpac text parsing recovers wrapped fee rows that table extract often drops.
            if use_westpac and text_entries and (
                not entries or len(text_entries) >= len(entries)
            ):
                entries = text_entries
            elif not entries:
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
            "columns": list(FIXED_COLUMNS),
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
        help="Bank hint: auto, cba, nab, macquarie, westpac",
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
        import traceback

        emit(
            {
                "success": False,
                "error": f"{exc}",
                "entries": [],
                "metadata": {"traceback": traceback.format_exc()},
            },
            exit_code=1,
        )


if __name__ == "__main__":
    main()
