<?php
/**
 * Electricity charge calculator (vanilla PHP).
 *
 * Given a voltage, current and the current rate, calculates the power draw and the
 * energy consumed / charge incurred for every hour from 1 to 24 (hour 24 = per day).
 *
 * Formulas (verified against the reference example in calculater.pdf):
 *   Power (kW)       = Voltage (V) * Current (A) / 1000
 *   Energy (kWh) @ h = Power (kW) * Hour
 *   Total (RM)  @ h  = Energy (kWh) * (current rate / 100)
 */

/**
 * Calculate the electricity usage breakdown per hour for a full day.
 *
 * @param float $voltage     Voltage in volts (V).
 * @param float $current     Current in amperes (A).
 * @param float $currentRate Tariff in sen per kWh (e.g. 21.80).
 * @param int   $hours       Number of hours to tabulate (default 24).
 * @return array{power: float, rate: float, rows: array<int, array{hour:int, energy:float, total:float}>}
 */
function calculateElectricity($voltage, $current, $currentRate, $hours = 24)
{
    $power = ($voltage * $current) / 1000; // kW
    $rate  = $currentRate / 100;           // RM per kWh

    $rows = [];
    for ($hour = 1; $hour <= $hours; $hour++) {
        $energy = $power * $hour;      // kWh consumed after $hour hours
        $total  = $energy * $rate;     // RM
        $rows[] = [
            'hour'   => $hour,
            'energy' => $energy,
            'total'  => $total,
        ];
    }

    return [
        'power' => $power,
        'rate'  => $rate,
        'rows'  => $rows,
    ];
}

// --- Handle input ---------------------------------------------------------

$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$voltage     = isset($_POST['voltage'])      ? $_POST['voltage']      : '';
$current     = isset($_POST['current'])      ? $_POST['current']      : '';
$currentRate = isset($_POST['current_rate']) ? $_POST['current_rate'] : '';

$result = null;
if ($submitted && is_numeric($voltage) && is_numeric($current) && is_numeric($currentRate)) {
    $result = calculateElectricity((float) $voltage, (float) $current, (float) $currentRate);
}

/**
 * Trim trailing zeros from a fixed-precision number for tidy display
 * (e.g. 0.21800 -> "0.218", 0.06156 -> "0.06156").
 */
function tidy($value, $decimals = 5)
{
    $s = number_format($value, $decimals, '.', '');
    return strpos($s, '.') !== false ? rtrim(rtrim($s, '0'), '.') : $s;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>⚡ Electricity Charge Calculator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg1: #6a11cb;
            --bg2: #2575fc;
            --accent: #ffd23f;
            --ink: #1a1a2e;
            --card: rgba(255, 255, 255, 0.95);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--ink);
            background: linear-gradient(135deg, var(--bg1) 0%, var(--bg2) 100%);
            background-attachment: fixed;
            padding: 40px 16px;
        }
        .shell { max-width: 960px; margin: 0 auto; }
        .hero { text-align: center; color: #fff; margin-bottom: 28px; }
        .hero h1 { font-size: 2.2rem; font-weight: 800; margin: 0 0 6px; letter-spacing: -0.5px; }
        .hero p { margin: 0; opacity: 0.85; font-weight: 500; }
        .panel {
            background: var(--card);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(8px);
        }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
        .field label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        .field .hint { font-weight: 400; color: #6b7280; font-size: 0.78rem; }
        .field input {
            width: 100%;
            padding: 13px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1.05rem;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input:focus {
            outline: none;
            border-color: var(--bg2);
            box-shadow: 0 0 0 4px rgba(37, 117, 252, 0.15);
        }
        .actions { text-align: center; margin-top: 22px; }
        .btn {
            display: inline-block;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 1.02rem;
            line-height: 1.2;
            text-decoration: none;
            padding: 13px 40px;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, var(--bg1), var(--bg2));
            box-shadow: 0 8px 20px rgba(37, 117, 252, 0.4);
            transition: transform 0.12s, box-shadow 0.12s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(37, 117, 252, 0.5); }
        .btn:active { transform: translateY(0); }
        .btn-ghost {
            background: #fff; color: var(--bg2); border: 2px solid #e5e7eb;
            box-shadow: none; margin-left: 8px; padding: 11px 26px;
        }
        .alert {
            margin-top: 18px; padding: 14px 16px; border-radius: 12px;
            background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 500;
        }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
        @media (max-width: 640px) { .stats { grid-template-columns: 1fr; } }
        .stat {
            border-radius: 16px; padding: 20px; color: #fff;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
        }
        .stat.power { background: linear-gradient(135deg, #f7971e, #ffd200); color: #4a2c00; }
        .stat.rate  { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .stat.daily { background: linear-gradient(135deg, #8e2de2, #4a00e0); }
        .stat .label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; font-weight: 600; }
        .stat .value { font-size: 1.9rem; font-weight: 800; margin-top: 4px; }
        .stat .value small { font-size: 0.95rem; font-weight: 600; opacity: 0.85; }
        .section-title { font-weight: 700; margin: 6px 0 14px; font-size: 1.05rem; }
        .chart-wrap { position: relative; height: 320px; margin-bottom: 26px; }
        .table-scroll { overflow-x: auto; border-radius: 14px; border: 1px solid #eef0f4; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        thead th {
            background: #f8fafc; text-align: left; padding: 12px 16px;
            font-weight: 700; color: #475569; position: sticky; top: 0;
        }
        tbody td { padding: 11px 16px; border-top: 1px solid #f1f5f9; }
        tbody tr:hover { background: #f8faff; }
        tbody tr.is-day { background: #fef9e7; font-weight: 700; }
        tbody tr.is-day:hover { background: #fdf3d0; }
        .pill { display: inline-block; font-size: 0.72rem; font-weight: 700; color: #92610a; background: var(--accent); padding: 2px 8px; border-radius: 999px; margin-left: 6px; }
        .divider { height: 1px; background: #eef0f4; margin: 26px 0; border: none; }
    </style>
</head>
<body>
<div class="shell">
    <div class="hero">
        <h1>⚡ Electricity Charge Calculator</h1>
        <p>Estimate power, energy &amp; cost per hour and per day</p>
    </div>

    <div class="panel">
        <form method="post" action="" id="calcForm">
            <div class="grid">
                <div class="field">
                    <label for="voltage">Voltage <span class="hint">(V)</span></label>
                    <input type="number" step="any" min="0" id="voltage" name="voltage"
                           placeholder="e.g. 19"
                           value="<?php echo htmlspecialchars($voltage); ?>" required>
                </div>
                <div class="field">
                    <label for="current">Current <span class="hint">(A)</span></label>
                    <input type="number" step="any" min="0" id="current" name="current"
                           placeholder="e.g. 3.24"
                           value="<?php echo htmlspecialchars($current); ?>" required>
                </div>
                <div class="field">
                    <label for="current_rate">Current Rate <span class="hint">(sen/kWh)</span></label>
                    <input type="number" step="any" min="0" id="current_rate" name="current_rate"
                           placeholder="e.g. 21.80"
                           value="<?php echo htmlspecialchars($currentRate); ?>" required>
                </div>
            </div>
            <div class="actions">
                <button type="submit" class="btn">Calculate</button>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-ghost">Reset</a>
            </div>
        </form>

        <?php if ($submitted && $result === null): ?>
            <div class="alert">⚠️ Please enter valid numbers for voltage, current and current rate.</div>
        <?php endif; ?>

        <?php if ($result !== null):
            $daily = $result['rows'][count($result['rows']) - 1]; ?>
            <hr class="divider">

            <div class="stats">
                <div class="stat power">
                    <div class="label">Power</div>
                    <div class="value"><?php echo tidy($result['power']); ?> <small>kW</small></div>
                </div>
                <div class="stat rate">
                    <div class="label">Rate</div>
                    <div class="value"><?php echo tidy($result['rate']); ?> <small>RM/kWh</small></div>
                </div>
                <div class="stat daily">
                    <div class="label">Total / Day (24h)</div>
                    <div class="value">RM <?php echo number_format($daily['total'], 2); ?></div>
                </div>
            </div>

            <div class="section-title">📈 Energy &amp; cost across 24 hours</div>
            <div class="chart-wrap">
                <canvas id="usageChart"></canvas>
            </div>

            <div class="section-title">📋 Hourly breakdown</div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hour</th>
                            <th>Energy (kWh)</th>
                            <th>Total (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['rows'] as $i => $row): ?>
                            <tr class="<?php echo $row['hour'] === 24 ? 'is-day' : ''; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <?php echo $row['hour']; ?>
                                    <?php if ($row['hour'] === 1): ?><span class="pill">per hour</span><?php endif; ?>
                                    <?php if ($row['hour'] === 24): ?><span class="pill">per day</span><?php endif; ?>
                                </td>
                                <td><?php echo tidy($row['energy']); ?></td>
                                <td><?php echo round($row['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <script>
                const rows = <?php echo json_encode($result['rows']); ?>;
                const labels  = rows.map(r => r.hour);
                const energy  = rows.map(r => r.energy);
                const cost    = rows.map(r => r.total);

                new Chart(document.getElementById('usageChart'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Energy (kWh)',
                                data: energy,
                                borderColor: '#2575fc',
                                backgroundColor: 'rgba(37,117,252,0.12)',
                                fill: true,
                                tension: 0.3,
                                yAxisID: 'y',
                                pointRadius: 3,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Cost (RM)',
                                data: cost,
                                borderColor: '#8e2de2',
                                backgroundColor: 'rgba(142,45,226,0.10)',
                                fill: true,
                                tension: 0.3,
                                yAxisID: 'y1',
                                pointRadius: 3,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    title: items => 'Hour ' + items[0].label,
                                    label: ctx => ctx.dataset.label + ': ' +
                                        (ctx.datasetIndex === 1 ? 'RM ' : '') +
                                        ctx.parsed.y.toFixed(ctx.datasetIndex === 1 ? 2 : 5) +
                                        (ctx.datasetIndex === 0 ? ' kWh' : '')
                                }
                            }
                        },
                        scales: {
                            x: { title: { display: true, text: 'Hour' } },
                            y: {
                                type: 'linear', position: 'left',
                                title: { display: true, text: 'Energy (kWh)' }
                            },
                            y1: {
                                type: 'linear', position: 'right',
                                grid: { drawOnChartArea: false },
                                title: { display: true, text: 'Cost (RM)' }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
