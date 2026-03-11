#!/usr/bin/env python3
"""
Export a normalized golden fixture from BIFF analysis of legacy Excel files.

Source:
- excel_analysis/excel_biff_analysis.json

Output:
- tests/Fixtures/production/excel_legacy_golden_cases.json

The fixture is intentionally compact and focused on the legacy formula contract:
- per-row volume formula (A*B*C*D/10_000_000)
- total volume (E21 standard / E20 legacci)
- price and weight rollups (H13/D22 standard, H12/D21 legacci)
"""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "excel_analysis" / "excel_biff_analysis.json"
TARGET = ROOT / "tests" / "Fixtures" / "production" / "excel_legacy_golden_cases.json"


def to_numeric(value: Any) -> float | None:
    if isinstance(value, (int, float)):
        return float(value)

    if isinstance(value, str):
        text = value.strip()
        if text == "":
            return None
        try:
            return float(text.replace(",", "."))
        except ValueError:
            return None

    return None


def main() -> None:
    source_payload = json.loads(SOURCE.read_text(encoding="utf-8"))
    cases: list[dict[str, Any]] = []

    for filename, workbook in source_payload.items():
        foglio1 = (workbook.get("sheets") or {}).get("Foglio1") or {}
        nonempty_cells = foglio1.get("nonempty_cells") or []
        if not isinstance(nonempty_cells, list) or not nonempty_cells:
            continue

        cells: dict[str, Any] = {}
        for row in nonempty_cells:
            if not isinstance(row, dict):
                continue
            cell = row.get("cell")
            if isinstance(cell, str):
                cells[cell] = row.get("value")

        # Distinguish workbook families by where total formula lands.
        standard_total = to_numeric(cells.get("E21"))
        legacci_total = to_numeric(cells.get("E20"))

        if standard_total is not None and standard_total > 0:
            variant = "standard"
            row_start, row_end = 8, 20
            expected = {
                "volume_total_m3": standard_total,
                "price_per_m3": to_numeric(cells.get("H10")),
                "price_total": to_numeric(cells.get("H13")),
                "weight_kg_per_m3": to_numeric(cells.get("K10")),
                "weight_total_kg": to_numeric(cells.get("D22")),
            }
        elif legacci_total is not None and legacci_total > 0:
            variant = "legacci"
            row_start, row_end = 7, 19
            expected = {
                "volume_total_m3": legacci_total,
                "price_per_m3": to_numeric(cells.get("H9")),
                "price_total": to_numeric(cells.get("H12")),
                "weight_kg_per_m3": to_numeric(cells.get("K9")),
                "weight_total_kg": to_numeric(cells.get("D21")),
            }
        else:
            # Skip templates/blank workbooks not useful for formula regression.
            continue

        rows: list[dict[str, Any]] = []
        for row_index in range(row_start, row_end + 1):
            a = to_numeric(cells.get(f"A{row_index}")) or 0.0
            b = to_numeric(cells.get(f"B{row_index}")) or 0.0
            c = to_numeric(cells.get(f"C{row_index}")) or 0.0
            d = to_numeric(cells.get(f"D{row_index}")) or 0.0
            e = to_numeric(cells.get(f"E{row_index}")) or 0.0
            rows.append(
                {
                    "row": row_index,
                    "A_raw": a,
                    "B_raw": b,
                    "C_raw": c,
                    "D_raw": d,
                    "E_volume_m3": e,
                }
            )

        case_id = (
            filename.replace(".xls", "")
            .replace(" ", "_")
            .replace("-", "_")
            .replace("__", "_")
            .lower()
        )

        cases.append(
            {
                "id": case_id,
                "source": f"excel_analysis/excel_biff_analysis.json::{filename}::Foglio1",
                "variant": variant,
                "sheet_label": cells.get("A2"),
                "dimensions_cm": {
                    "L": to_numeric(cells.get("C2")),
                    "W": to_numeric(cells.get("D2")),
                    "H": to_numeric(cells.get("E2")),
                },
                "rows": rows,
                "expected": expected,
            }
        )

    payload = {
        "generated_from": str(SOURCE.relative_to(ROOT)),
        "cases": sorted(cases, key=lambda item: item["id"]),
    }

    TARGET.parent.mkdir(parents=True, exist_ok=True)
    TARGET.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    print(f"Exported {len(cases)} cases to {TARGET}")


if __name__ == "__main__":
    main()
