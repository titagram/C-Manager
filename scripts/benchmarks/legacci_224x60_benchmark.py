#!/usr/bin/env python3
"""
Independent benchmark for legacy legacci224x60 routine.

Scope:
- routine: legacci224x60
- stock board: 230x25x2 cm
- kerf: 0.0 cm
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import List


@dataclass(frozen=True)
class BoardStock:
    length_cm: float
    width_cm: float
    thickness_cm: float

    @property
    def volume_m3(self) -> float:
        return (self.length_cm * self.width_cm * self.thickness_cm) / 1_000_000


@dataclass
class PackedBoard:
    capacity_cm: float
    kerf_cm: float
    cuts_cm: List[float] = field(default_factory=list)
    remaining_cm: float = 0.0

    def __post_init__(self) -> None:
        self.remaining_cm = self.capacity_cm

    def can_fit(self, cut_cm: float) -> bool:
        required = cut_cm + (self.kerf_cm if self.cuts_cm else 0.0)
        return self.remaining_cm + 1e-9 >= required

    def add(self, cut_cm: float) -> None:
        required = cut_cm + (self.kerf_cm if self.cuts_cm else 0.0)
        if required > self.remaining_cm + 1e-9:
            raise ValueError("Cut does not fit")
        self.cuts_cm.append(cut_cm)
        self.remaining_cm = round(self.remaining_cm - required, 6)


def bfd_pack(lengths_cm: List[float], board_length_cm: float, kerf_cm: float) -> List[PackedBoard]:
    boards: List[PackedBoard] = []
    for cut in sorted(lengths_cm, reverse=True):
        best_idx = -1
        best_rem = float("inf")
        for i, board in enumerate(boards):
            if not board.can_fit(cut):
                continue
            required = cut + (kerf_cm if board.cuts_cm else 0.0)
            rem = board.remaining_cm - required
            if rem < best_rem:
                best_rem = rem
                best_idx = i
        if best_idx == -1:
            b = PackedBoard(capacity_cm=board_length_cm, kerf_cm=kerf_cm)
            b.add(cut)
            boards.append(b)
        else:
            boards[best_idx].add(cut)
    return boards


def expand_rows_to_cuts_cm() -> List[float]:
    # A/B/C/D legacy rows
    rows = [
        # A_length_cm, C_width_cm, D_qty
        (225.0, 55.0, 4),
        (90.0, 60.0, 4),
        (57.0, 40.0, 4),
    ]
    board_width_cm = 25.0
    cuts: List[float] = []

    for length_cm, width_cm, qty in rows:
        full = int(width_cm // board_width_cm)
        rem = width_cm - (full * board_width_cm)

        if full > 0:
            cuts.extend([length_cm] * (full * qty))
        if rem > 0.0001:
            cuts.extend([length_cm] * qty)

    return cuts


def main() -> None:
    board = BoardStock(length_cm=230.0, width_cm=25.0, thickness_cm=2.0)
    kerf_cm = 0.0
    cuts = expand_rows_to_cuts_cm()
    packed = bfd_pack(cuts, board.length_cm, kerf_cm)

    # Net based on row geometry (before strip split)
    net = (
        (225.0 * 55.0 * 2.0 * 4)
        + (90.0 * 60.0 * 2.0 * 4)
        + (57.0 * 40.0 * 2.0 * 4)
    ) / 1_000_000

    gross = len(packed) * board.volume_m3
    scrap = gross - net

    print("=== Legacci 224x60 Benchmark ===")
    print(f"Stock board: {board.length_cm}x{board.width_cm}x{board.thickness_cm} cm | kerf={kerf_cm} cm")
    print(f"Packed boards: {len(packed)}")
    for i, b in enumerate(packed, start=1):
        cuts_str = ", ".join(f"{x:.1f}" for x in b.cuts_cm)
        print(f"  Board {i}: [{cuts_str}] | waste={b.remaining_cm:.1f} cm")
    print(f"Gross volume (boards): {gross:.6f} m3")
    print(f"Net volume (pieces):   {net:.6f} m3")
    print(f"Scrap volume:          {scrap:.6f} m3")

    expected_boards = 20
    print(f"Expected boards:       {expected_boards}")
    print(f"Result boards:         {len(packed)}")
    if len(packed) != expected_boards:
        raise SystemExit("Unexpected board count for legacci224x60 benchmark.")


if __name__ == "__main__":
    main()

