"""
Extract headword + definition (text before the example bullet) from
Merriam-Webster's Vocabulary Builder PDF (second edition layout).

Usage (from project root):
  python scripts/extract_mw_vocabulary_builder.py Merriam-Webster-s-Vocabulary-Builder.pdf

Writes database/data/mw_vocabulary_builder_reference.json

Each entry is only {"word", "definition"} — do not add difficulty_level (not used by the app).
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

try:
    from pypdf import PdfReader
except ImportError:
    print("Install pypdf: pip install pypdf", file=sys.stderr)
    sys.exit(1)

# Headword: lowercase word, optional hyphens, optional short multi-word phrase (e.g. "a fortiori")
_HEADWORD = (
    r"^([a-z][a-z]*(?:-[a-z]+)*(?:\s+[a-z][a-z]*(?:-[a-z]+)*){0,3})\s*\n\s*\n\s*"
)
# Definition runs until example bullet on its own line
_DEF = r"((?:(?!\n•).)+?)(?=\n•|\Z)"

_ENTRY = re.compile(_HEADWORD + _DEF, re.MULTILINE | re.DOTALL)

def _page_is_quiz_sheet(text: str) -> bool:
    """Skip quiz-answer pages; do not use broad substrings (etymology repeats them)."""
    t = text.strip()[:800]
    if "Choose the closest" in t or "Choose the best" in t:
        return True
    if "Match the following" in t:
        return True
    if re.match(r"^Quiz\s+\d+-\d+", text.strip()):
        return True
    # Answer key lines like "1.e 2.g 3.b"
    if re.search(r"^\d+\.[a-z]\s+\d+\.[a-z]", text.strip(), re.MULTILINE):
        return True
    return False

# Reject headwords that are clearly not dictionary entries
_BAD_HEADWORDS = frozenset(
    {
        "the",
        "and",
        "for",
        "with",
        "from",
        "that",
        "this",
        "when",
        "they",
        "them",
        "your",
        "into",
        "also",
        "only",
        "just",
        "like",
        "such",
        "some",
        "very",
        "much",
        "more",
        "most",
        "many",
        "each",
        "both",
        "than",
        "then",
        "there",
        "these",
        "those",
        "what",
        "which",
        "while",
        "where",
        "after",
        "before",
        "about",
        "under",
        "over",
        "again",
        "here",
        "even",
    }
)


def _normalize_ws(s: str) -> str:
    return re.sub(r"\s+", " ", s.strip())


def extract_page(text: str) -> list[tuple[str, str]]:
    if not text or len(text) < 30:
        return []
    if _page_is_quiz_sheet(text):
        return []
    out: list[tuple[str, str]] = []
    for m in _ENTRY.finditer(text):
        word = _normalize_ws(m.group(1))
        definition = _normalize_ws(m.group(2))
        if not word or not definition:
            continue
        if len(definition) < 18:
            continue
        # Definition should read like a definition, not a quiz line
        if definition.lower().startswith(("a.", "b.", "c.", "d.", "e.", "f.", "g.", "h.")):
            continue
        lw = word.lower()
        if lw in _BAD_HEADWORDS:
            continue
        if not definition[0].isupper() and not definition.startswith("("):
            continue
        out.append((word, definition))
    return out


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument(
        "pdf",
        nargs="?",
        default="Merriam-Webster-s-Vocabulary-Builder.pdf",
        help="Path to Merriam-Webster Vocabulary Builder PDF",
    )
    ap.add_argument(
        "-o",
        "--output",
        default="database/data/mw_vocabulary_builder_reference.json",
        help="Output JSON path (relative to project root unless absolute)",
    )
    args = ap.parse_args()

    root = Path(__file__).resolve().parents[1]
    pdf_path = Path(args.pdf)
    if not pdf_path.is_absolute():
        pdf_path = root / pdf_path
    if not pdf_path.is_file():
        print(f"PDF not found: {pdf_path}", file=sys.stderr)
        return 1

    out_path = Path(args.output)
    if not out_path.is_absolute():
        out_path = root / out_path

    reader = PdfReader(str(pdf_path))
    merged: dict[str, str] = {}
    for page in reader.pages:
        raw = page.extract_text() or ""
        for word, definition in extract_page(raw):
            prev = merged.get(word.lower())
            if prev is None or len(definition) > len(prev):
                merged[word.lower()] = definition

    items = [
        {"word": k, "definition": v}
        for k, v in sorted(merged.items(), key=lambda x: x[0])
    ]
    for e in items:
        if set(e) != {"word", "definition"}:
            raise ValueError(f"unexpected entry keys: {e!r}")

    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_text(
        json.dumps(
            {
                "source": "Merriam-Webster's Vocabulary Builder (PDF extract; definition text is the lemma gloss before the example bullet)",
                "pdf": str(pdf_path.name),
                "entry_count": len(items),
                "entries": items,
            },
            indent=2,
            ensure_ascii=False,
        )
        + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {len(items)} entries to {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
