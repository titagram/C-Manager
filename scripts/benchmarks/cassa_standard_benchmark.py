#!/usr/bin/env python3
"""
Independent benchmark for the agreed Cassa Standard reference case.

Purpose:
- Validate expected cutting-plan behavior outside the Laravel app / Sail runtime.
- Provide a reproducible baseline while the new category optimizer is being implemented.

Current agreed assumptions (can be tweaked via constants below):
- Cassa standard WITHOUT coperchio
- Fondo esterno
- Elementi interni use length = W - 2*T
- No rotation
- Kerf configurable (default 0 for current implementation phase)

Dimensions are expressed in centimeters for readability and converted only where needed.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from math import ceil
from typing import List


@dataclass(frozen=True)
class BoardStock:
    length_cm: float
    width_cm: float
    thickness_cm: float

    @property
    def volume_m3(self) -> float:
        return (self.length_cm * self.width_cm * self.thickness_cm) / 1_000_000


@dataclass(frozen=True)
class PanelRequirement:
    name: str
    length_cm: float
    height_cm: float
    quantity: int


@dataclass(frozen=True)
class StripRequirement:
    source_panel: str
    length_cm: float
    quantity: int
    strips_per_panel: int


@dataclass
class PackedBoard:
    capacity_cm: float
    kerf_cm: float
    strips: List[float] = field(default_factory=list)
    remaining_cm: float = 0.0

    def __post_init__(self) -> None:
        self.remaining_cm = self.capacity_cm

    def can_fit(self, strip_length_cm: float) -> bool:
        required = strip_length_cm + (self.kerf_cm if self.strips else 0.0)
        return self.remaining_cm + 1e-9 >= required

    def add(self, strip_length_cm: float) -> None:
        required = strip_length_cm + (self.kerf_cm if self.strips else 0.0)
        if required > self.remaining_cm + 1e-9:
            raise ValueError("Strip does not fit in board")
        self.strips.append(strip_length_cm)
        self.remaining_cm = round(self.remaining_cm - required, 6)


def panel_to_strips(panel: PanelRequirement, board: BoardStock) -> StripRequirement:
    strips_per_panel = int(ceil(panel.height_cm / board.width_cm))
    return StripRequirement(
        source_panel=panel.name,
        length_cm=panel.length_cm,
        quantity=strips_per_panel * panel.quantity,
        strips_per_panel=strips_per_panel,
    )


def best_fit_decreasing_pack(strips: List[float], board_length_cm: float, kerf_cm: float) -> List[PackedBoard]:
    boards: List[PackedBoard] = []
    for strip in sorted(strips, reverse=True):
        best_index = -1
        best_remaining = float("inf")
        for i, b in enumerate(boards):
            if not b.can_fit(strip):
                continue
            required = strip + (kerf_cm if b.strips else 0.0)
            remaining = b.remaining_cm - required
            if remaining < best_remaining:
                best_remaining = remaining
                best_index = i
        if best_index == -1:
            board = PackedBoard(capacity_cm=board_length_cm, kerf_cm=kerf_cm)
            board.add(strip)
            boards.append(board)
        else:
            boards[best_index].add(strip)
    return boards


def build_cassa_standard_no_lid_panels(
    L_cm: float, W_cm: float, H_cm: float, T_cm: float, fondo_esterno: bool = True
) -> List[PanelRequirement]:
    short_length = W_cm - (2 * T_cm)  # internal rule for short walls
    if short_length <= 0:
        raise ValueError("Short wall length <= 0 after internal offset")

    fondo_length = L_cm
    fondo_height = W_cm
    if not fondo_esterno:
        fondo_length = L_cm - (2 * T_cm)
        fondo_height = W_cm - (2 * T_cm)

    return [
        PanelRequirement("Parete lunga esterna", length_cm=L_cm, height_cm=H_cm, quantity=2),
        PanelRequirement("Parete corta interna", length_cm=short_length, height_cm=H_cm, quantity=2),
        PanelRequirement("Fondo", length_cm=fondo_length, height_cm=fondo_height, quantity=1),
    ]


def summarize_case() -> None:
    # Agreed reference case
    board = BoardStock(length_cm=230, width_cm=25, thickness_cm=2)
    kerf_cm = 0.0
    L_cm, W_cm, H_cm = 100, 50, 100
    fondo_esterno = True

    panels = build_cassa_standard_no_lid_panels(L_cm, W_cm, H_cm, board.thickness_cm, fondo_esterno=fondo_esterno)
    strip_requirements = [panel_to_strips(p, board) for p in panels]

    strips: List[float] = []
    for req in strip_requirements:
        strips.extend([req.length_cm] * req.quantity)

    boards = best_fit_decreasing_pack(strips, board.length_cm, kerf_cm)

    net_volume_m3 = sum((p.length_cm * p.height_cm * board.thickness_cm * p.quantity) / 1_000_000 for p in panels)
    gross_volume_m3 = len(boards) * board.volume_m3
    scrap_volume_m3 = gross_volume_m3 - net_volume_m3

    print("=== Cassa Standard Benchmark ===")
    print(f"Case: {L_cm}x{W_cm}x{H_cm} cm | board {board.length_cm}x{board.width_cm}x{board.thickness_cm} cm")
    print(f"Assumptions: no coperchio, fondo_esterno={fondo_esterno}, no rotation, short internal=W-2T, kerf={kerf_cm} cm")
    print()

    print("Panel requirements:")
    for p in panels:
        print(f"- {p.name}: {p.quantity} x {p.length_cm}x{p.height_cm} cm")
    print()

    print("Strip requirements (derived from board width):")
    for r in strip_requirements:
        print(
            f"- {r.source_panel}: {r.quantity} strips x {r.length_cm} cm "
            f"(strips_per_panel={r.strips_per_panel})"
        )
    print()

    print(f"Packed boards: {len(boards)}")
    for i, b in enumerate(boards, start=1):
        def fmt_len(value: float) -> str:
            as_float = float(value)
            return str(int(as_float)) if as_float.is_integer() else f"{as_float:.2f}"

        strips_str = ", ".join(fmt_len(s) for s in b.strips)
        print(f"  Board {i}: [{strips_str}] | waste={b.remaining_cm:.2f} cm")
    print()

    print(f"Gross volume (boards): {gross_volume_m3:.6f} m3")
    print(f"Net volume (panels):   {net_volume_m3:.6f} m3")
    print(f"Scrap volume:          {scrap_volume_m3:.6f} m3")

    expected_boards = 7
    print()
    print(f"Expected boards (current agreed target): {expected_boards}")
    print(f"Benchmark result boards:                  {len(boards)}")
    if len(boards) != expected_boards:
        raise SystemExit("Unexpected board count for benchmark case.")


if __name__ == "__main__":
    summarize_case()
