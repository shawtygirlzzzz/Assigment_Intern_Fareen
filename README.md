# ⚡ Electricity Charge Calculator

A small web app that calculates electricity **power**, **energy**, and **total charge** from a given
voltage, current, and tariff rate. Enter the three values and it tabulates consumption and cost for
every hour from 1 to 24 — so hour 1 is the rate **per hour**, and hour 24 is the rate **per day** —
alongside a chart of how energy and cost accumulate across the day.

Written in **plain (vanilla) PHP**, with no framework and no build step.

**Author:** Fareen Nathrah binti Yusri

## Running it

You only need PHP installed. From the project root:

```
php -S localhost:8000
```

Then open <http://localhost:8000>.

To check it against the worked example from the assignment, enter:

| Field | Value |
| --- | --- |
| Voltage (V) | `19` |
| Current (A) | `3.24` |
| Current Rate (sen/kWh) | `21.80` |

This gives a power of **0.06156 kW** at a rate of **0.218 RM/kWh**, and a per-day (hour 24) total of
**1.47744 kWh** costing **RM 0.32**.

## Formulas

```
Power (kW)       = Voltage (V) * Current (A) / 1000
Energy (kWh) @ h = Power (kW) * Hour
Total (RM) @ h   = Energy (kWh) * (current rate / 100)
```

**A note on these:** the formulas as written in the brief ([`BRIEF.md`](BRIEF.md)) do not agree with
the worked example in [`calculater.pdf`](calculater.pdf). The brief gives `Power (Wh) = V * A` and
`Energy = Power * Hour * 1000`, but the example divides power by 1000 and does not multiply energy by
1000 — for 19 V and 3.24 A it shows 0.06156 kW, which is `19 * 3.24 / 1000`, and at hour 24 it shows
1.47744 kWh rather than a figure a thousand times larger.

I implemented what the example computes, since it is internally consistent and produces the sensible
kW/kWh units the table is labelled with. Every value in the example reproduces exactly.

## Implementation notes

- Everything lives in a single [`index.php`](index.php) — markup, styles, and logic.
- The calculation is wrapped in a dedicated `calculateElectricity()` function, as the brief requires,
  and is kept free of any output or form handling so it can be reused or tested on its own.
- Totals are rounded to 2 decimal places for display only; the underlying values are not rounded.
- Chart.js and the Inter webfont are loaded from a CDN, so the chart needs an internet connection.
  The calculator itself works fully offline.

## Reference

- Assignment brief: [`BRIEF.md`](BRIEF.md) · worked example: [`calculater.pdf`](calculater.pdf)
- TNB residential pricing & tariffs: <https://www.tnb.com.my/residential/pricing-tariffs>
