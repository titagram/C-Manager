#!/usr/bin/env python3
"""
Independent benchmark for Gabbia SP20 legacy routines.

Purpose:
- Keep a reproducible baseline outside Laravel/Sail while gabbia v2 evolves.
- Validate legacy row generation (A/B/C/D) plus downstream 1D BFD packing.

Current benchmark scope:
- Routines: gabbiasp20, gabbiasp20fondo4
- Case: LxWxH = 84x43x55 cm
- Stock board: 230x25x2 cm
- Kerf: 0.0 cm
- numero_pezzi: 1

Notes:
- Column B (section) differs on row 8 for fondo4 (40mm instead of 25mm).
- Current packing baseline uses normalized row pieces from Excel preview:
  each row contributes length/width/qty pieces and is then packed in 1D by length.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from math import ceil
from typing import Dict, List, Tuple


@dataclass(frozen=True)
class BoardStock:
    length_cm: float
    width_cm: float
    thickness_cm: float

    @property
    def length_mm(self) -> float:
        return self.length_cm * 10.0

    @property
    def volume_m3(self) -> float:
        return (self.length_cm * self.width_cm * self.thickness_cm) / 1_000_000


@dataclass(frozen=True)
class RowRequirement:
    row: int
    a_length_cm: float
    b_section_mm: float
    c_width_cm: float
    d_qty_per_unit: int
    d_qty_total: int


@dataclass(frozen=True)
class Piece:
    row: int
    length_mm: float
    width_mm: float
    quantity: int


@dataclass
class PackedBoard:
    capacity_mm: float
    kerf_mm: float
    cuts_mm: List[float] = field(default_factory=list)
    remaining_mm: float = 0.0

    def __post_init__(self) -> None:
        self.remaining_mm = self.capacity_mm

    def can_fit(self, length_mm: float) -> bool:
        required = length_mm + (self.kerf_mm if self.cuts_mm else 0.0)
        return self.remaining_mm + 1e-9 >= required

    def add(self, length_mm: float) -> None:
        required = length_mm + (self.kerf_mm if self.cuts_mm else 0.0)
        if required > self.remaining_mm + 1e-9:
            raise ValueError("Piece does not fit")
        self.cuts_mm.append(length_mm)
        self.remaining_mm = round(self.remaining_mm - required, 6)


def qty_from_height_cm(height_cm: float) -> int:
    if height_cm < 40:
        return 4
    if height_cm <= 64:
        return 6
    if height_cm <= 99:
        return 8
    if height_cm <= 119:
        return 10
    if height_cm <= 144:
        return 12
    if height_cm <= 179:
        return 14
    if height_cm <= 209:
        return 16
    return 18


def legacy_d8_width_qty(width_cm: float) -> int:
    return max(0, int(ceil((width_cm / 10.0) + 0.5)))


def legacy_quantities(L_cm: float, W_cm: float, H_cm: float) -> Dict[str, int]:
    h_qty = qty_from_height_cm(H_cm)
    is_long = L_cm >= 200.0
    return {
        "D8": legacy_d8_width_qty(W_cm),
        "D9": h_qty,
        "D10": h_qty,
        "D11": 4 if is_long else 3,
        "D12": 16 if is_long else 14,
        "D13": 4 if is_long else 3,
    }


def build_rows_and_pieces(
    routine: str, L_cm: float, W_cm: float, H_cm: float, numero_pezzi: int
) -> Tuple[List[RowRequirement], List[Piece], Dict[str, int]]:
    is_fondo4 = routine == "gabbiasp20fondo4"
    q = legacy_quantities(L_cm, W_cm, H_cm)

    a = {
        8: L_cm + 4.0,
        9: L_cm + 8.0,
        10: W_cm,
        11: W_cm + 4.0,
        12: H_cm + 12.0,
        13: W_cm + 8.0,
    }
    b = {8: 40.0 if is_fondo4 else 25.0, 9: 20.0, 10: 20.0, 11: 40.0, 12: 25.0, 13: 25.0}
    c = {8: 10.0, 9: 8.0, 10: 8.0, 11: 10.0, 12: 8.0, 13: 8.0}
    d = {8: q["D8"], 9: q["D9"], 10: q["D10"], 11: q["D11"], 12: q["D12"], 13: q["D13"]}

    rows: List[RowRequirement] = []
    pieces: List[Piece] = []

    for row in [8, 9, 10, 11, 12, 13]:
        qty_per_unit = max(0, int(d[row]))
        qty_total = qty_per_unit * max(1, int(numero_pezzi))

        rows.append(
            RowRequirement(
                row=row,
                a_length_cm=round(a[row], 4),
                b_section_mm=b[row],
                c_width_cm=c[row],
                d_qty_per_unit=qty_per_unit,
                d_qty_total=qty_total,
            )
        )

        if qty_total <= 0:
            continue

        pieces.append(
            Piece(
                row=row,
                length_mm=round(a[row] * 10.0, 4),
                width_mm=round(c[row] * 10.0, 4),
                quantity=qty_total,
            )
        )

    return rows, pieces, q


def best_fit_decreasing_pack(pieces: List[Piece], board_length_mm: float, kerf_mm: float) -> List[PackedBoard]:
    lengths: List[float] = []
    for piece in pieces:
        lengths.extend([piece.length_mm] * piece.quantity)
    lengths.sort(reverse=True)

    boards: List[PackedBoard] = []
    for length in lengths:
        best_index = -1
        best_remaining = float("inf")

        for i, board in enumerate(boards):
            if not board.can_fit(length):
                continue
            required = length + (kerf_mm if board.cuts_mm else 0.0)
            remaining = board.remaining_mm - required
            if remaining < best_remaining:
                best_remaining = remaining
                best_index = i

        if best_index == -1:
            b = PackedBoard(capacity_mm=board_length_mm, kerf_mm=kerf_mm)
            b.add(length)
            boards.append(b)
        else:
            boards[best_index].add(length)

    return boards


def net_volume_m3(pieces: List[Piece], thickness_cm: float) -> float:
    thickness_mm = thickness_cm * 10.0
    total_mm3 = 0.0
    for piece in pieces:
        total_mm3 += piece.length_mm * piece.width_mm * thickness_mm * piece.quantity
    return total_mm3 / 1_000_000_000.0


def run_case(routine: str, board: BoardStock, L_cm: float, W_cm: float, H_cm: float, kerf_cm: float) -> None:
    rows, pieces, quantities = build_rows_and_pieces(
        routine=routine, L_cm=L_cm, W_cm=W_cm, H_cm=H_cm, numero_pezzi=1
    )
    packed = best_fit_decreasing_pack(pieces, board.length_mm, kerf_cm * 10.0)

    gross = len(packed) * board.volume_m3
    net = net_volume_m3(pieces, board.thickness_cm)
    scrap = gross - net

    print(f"=== Gabbia Benchmark: {routine} ===")
    print(
        f"Case: {L_cm}x{W_cm}x{H_cm} cm | board {board.length_cm}x{board.width_cm}x{board.thickness_cm} cm | kerf={kerf_cm} cm"
    )
    print(f"Legacy quantities: {quantities}")
    print("Rows:")
    for row in rows:
        print(
            f"- r{row.row}: A={row.a_length_cm}cm B={row.b_section_mm}mm C={row.c_width_cm}cm "
            f"D={row.d_qty_per_unit} (tot={row.d_qty_total})"
        )
    print(f"Packed boards: {len(packed)}")
    for i, b in enumerate(packed, start=1):
        cuts_cm = ", ".join(f"{c / 10:.1f}" for c in b.cuts_mm)
        print(f"  Board {i}: [{cuts_cm}] | waste={b.remaining_mm / 10:.1f} cm")
    print(f"Gross volume (boards): {gross:.6f} m3")
    print(f"Net volume (pieces):   {net:.6f} m3")
    print(f"Scrap volume:          {scrap:.6f} m3")

    expected_boards = 12
    print(f"Expected boards:       {expected_boards}")
    print(f"Result boards:         {len(packed)}")
    if len(packed) != expected_boards:
        raise SystemExit(f"Unexpected board count for {routine}: {len(packed)} != {expected_boards}")
    print()


def main() -> None:
    board = BoardStock(length_cm=230.0, width_cm=25.0, thickness_cm=2.0)
    L_cm, W_cm, H_cm = 84.0, 43.0, 55.0
    kerf_cm = 0.0

    run_case("gabbiasp20", board, L_cm, W_cm, H_cm, kerf_cm)
    run_case("gabbiasp20fondo4", board, L_cm, W_cm, H_cm, kerf_cm)


if __name__ == "__main__":
    main()
