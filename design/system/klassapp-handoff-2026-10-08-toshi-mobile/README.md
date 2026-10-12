# Toshi on phones and tablets: handoff, 2026-10-08

- `HANDOFF.md`: four PRs, smallest first:
  - **T1** launcher and bottom safe zone;
  - **T2** preview sheet;
  - **T3** setup guide;
  - **T4** assistant (early access).

  **T1 and T2 can ship before 17 October.** It also covers what the current phone experience gets wrong, and open questions.
- `concept/index.html`: every screen and state at 375, 414 and 768. `concept/toshi.html` is the click-through.
- `concept/measure.html`: the automatic check: sideways scroll, 44px targets, the bottom safe zone, the keyboard, focus and Back.
- `design-system/`: the tokens (including the new bottom safe-zone tokens) and the K mark.

**Not browser-measured yet:** run `measure.html` from a local web server and paste its results into T1 and T2. Contrast is calculated from hex.
