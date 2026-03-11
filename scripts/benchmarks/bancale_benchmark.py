#!/usr/bin/env python3
"""
Independent benchmark for legacy bancale-family routines.

Scope:
- routines: bancale, perimetrale
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
    cuts: List[float] = field(default_factory=list)
    remaining_cm: float = 0.0

    def __post_init__(self) -> None:
        self.remaining_cm = self.capacity_cm

    def can_fit(self, cut_cm: float) -> bool:
        required = cut_cm + (self.kerf_cm if self.cuts else 0.0)
        return self.remaining_cm + 1e-9 >= required

    def add(self, cut_cm: float) -> None:
        required = cut_cm + (self.kerf_cm if self.cuts else 0.0)
        if required > self.remaining_cm + 1e-9:
            raise ValueError("Cut does not fit")
        self.cuts.append(cut_cm)
        self.remaining_cm = round(self.remaining_cm - required, 6)


def legacy_d8(width_cm: float) -> int:
    return max(0, int(round(width_cm / 10.0)))


def legacy_d9(length_cm: float) -> int:
    return 4 if length_cm > 199 else 3


def bfd_pack(lengths_cm: List[float], board_length_cm: float, kerf_cm: float) -> List[PackedBoard]:
    boards: List[PackedBoard] = []
    for cut in sorted(lengths_cm, reverse=True):
        best_idx = -1
        best_rem = float("inf")
        for i, board in enumerate(boards):
            if not board.can_fit(cut):
                continue
            required = cut + (kerf_cm if board.cuts else 0.0)
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


def main() -> None:
    board = BoardStock(length_cm=230.0, width_cm=25.0, thickness_cm=2.0)
    kerf_cm = 0.0

    # Case 1: bancale
    L_cm, W_cm = 84.0, 43.0
    d8 = legacy_d8(W_cm)
    d9 = legacy_d9(L_cm)

    # A8=L qty D8, A9=W qty D9
    cuts = ([L_cm] * d8) + ([W_cm] * d9)
    packed = bfd_pack(cuts, board.length_cm, kerf_cm)

    # C8=C9=10 cm, stock thickness=2 cm
    net = ((L_cm * 10 * 2 * d8) + (W_cm * 10 * 2 * d9)) / 1_000_000
    gross = len(packed) * board.volume_m3
    scrap = gross - net

    print("=== Bancale Benchmark ===")
    print(f"Case: {L_cm}x{W_cm} cm | board {board.length_cm}x{board.width_cm}x{board.thickness_cm} cm")
    print(f"Legacy quantities: D8={d8}, D9={d9}")
    print(f"Packed boards: {len(packed)}")
    for i, b in enumerate(packed, start=1):
        cuts_str = ", ".join(f"{x:.1f}" for x in b.cuts)
        print(f"  Board {i}: [{cuts_str}] | waste={b.remaining_cm:.1f} cm")
    print(f"Gross volume (boards): {gross:.6f} m3")
    print(f"Net volume (pieces):   {net:.6f} m3")
    print(f"Scrap volume:          {scrap:.6f} m3")

    expected_boards = 3
    print(f"Expected boards:       {expected_boards}")
    print(f"Result boards:         {len(packed)}")
    if len(packed) != expected_boards:
        raise SystemExit("Unexpected board count for bancale benchmark.")

    print()

    # Case 2: perimetrale
    L_cm, W_cm, H_cm = 190.0, 120.0, 80.0
    d8 = 2
    d9 = 2
    d10 = 7  # W=120 -> class 120..159
    d11 = 6  # L=190 -> class 180..249

    per_rows = [
        # A, C, D
        (L_cm + 5.0, H_cm, d8),
        (W_cm, H_cm, d9),
        (L_cm + 5.0, 8.0, d10),
        (W_cm + 5.0, 8.0, d11),
    ]

    cuts = []
    for a, c, d in per_rows:
        full = int(c // board.width_cm)
        rem = c - full * board.width_cm
        if full > 0:
            cuts.extend([a] * (full * d))
        if rem > 0.0001:
            cuts.extend([a] * d)

    packed = bfd_pack(cuts, board.length_cm, kerf_cm)
    net = sum((a * c * board.thickness_cm * d) for a, c, d in per_rows) / 1_000_000
    gross = len(packed) * board.volume_m3
    scrap = gross - net

    print("=== Perimetrale Benchmark ===")
    print(f"Case: {L_cm}x{W_cm}x{H_cm} cm | board {board.length_cm}x{board.width_cm}x{board.thickness_cm} cm")
    print(f"Legacy quantities: D8={d8}, D9={d9}, D10={d10}, D11={d11}")
    print(f"Packed boards: {len(packed)}")
    for i, b in enumerate(packed, start=1):
        cuts_str = ", ".join(f"{x:.1f}" for x in b.cuts)
        print(f"  Board {i}: [{cuts_str}] | waste={b.remaining_cm:.1f} cm")
    print(f"Gross volume (boards): {gross:.6f} m3")
    print(f"Net volume (pieces):   {net:.6f} m3")
    print(f"Scrap volume:          {scrap:.6f} m3")

    expected_boards = 29
    print(f"Expected boards:       {expected_boards}")
    print(f"Result boards:         {len(packed)}")
    if len(packed) != expected_boards:
        raise SystemExit("Unexpected board count for perimetrale benchmark.")


if __name__ == "__main__":
    main()
