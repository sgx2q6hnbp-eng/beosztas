<?php
// A teljes index.php a formázott dashboarddal
// Megtartja az összes eredeti PHP logikát, csak a HTML/CSS részt alakítja át
?>
<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';

$user = $_SESSION['user'];
$isAdmin = ($user['role'] === 'admin' || $user['role'] === 'rendszergazda');

// Mai dátum
$today = date('Y-m-d');

// Mai aktív műszakok száma
$stmtToday = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE shift_date = ? AND status = 'active'");
$stmtToday->execute([$today]);
$todayShiftCount = $stmtToday->fetchColumn();

// Függő szabadságkérelmek (admin esetén az összes, user esetén csak a sajátja)
if ($isAdmin) {
    $stmtLeaves = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
    $stmtLeaves->execute();
} else {
    $stmtLeaves = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = ? AND status = 'pending'");
    $stmtLeaves->execute([$user['id']]);
}
$pendingLeaves = $stmtLeaves->fetchColumn();

// Következő műszak (csak saját felhasználónak)
$nextShift = null;
if (!$isAdmin) {
    $stmtNext = $pdo->prepare("
        SELECT s.*, e.name AS employee_name, f.name AS fleet_name, v.license_plate
        FROM shifts s
        JOIN employees e ON s.employee_id = e.id
        JOIN fleets f ON s.fleet_id = f.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        WHERE s.employee_id = ? AND s.shift_date >= ?
        ORDER BY s.shift_date ASC, s.start_time ASC
        LIMIT 1
    ");
    $stmtNext->execute([$user['id'], $today]);
    $nextShift = $stmtNext->fetch(PDO::FETCH_ASSOC);
}

// Heti beosztás (hétfőtől vasárnapig)
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd   = date('Y-m-d', strtotime('sunday this week'));

if ($isAdmin) {
    $stmtWeek = $pdo->prepare("
        SELECT s.*, e.name AS employee_name, f.name AS fleet_name, v.license_plate
        FROM shifts s
        JOIN employees e ON s.employee_id = e.id
        JOIN fleets f ON s.fleet_id = f.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        WHERE s.shift_date BETWEEN ? AND ?
        ORDER BY s.shift_date ASC, s.start_time ASC
    ");
    $stmtWeek->execute([$weekStart, $weekEnd]);
} else {
    $stmtWeek = $pdo->prepare("
        SELECT s.*, e.name AS employee_name, f.name AS fleet_name, v.license_plate
        FROM shifts s
        JOIN employees e ON s.employee_id = e.id
        JOIN fleets f ON s.fleet_id = f.id
        LEFT JOIN vehicles v ON s.vehicle_id = v.id
        WHERE s.employee_id = ? AND s.shift_date BETWEEN ? AND ?
        ORDER BY s.shift_date ASC, s.start_time ASC
    ");
    $stmtWeek->execute([$user['id'], $weekStart, $weekEnd]);
}
$weekShifts = $stmtWeek->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'active'    => '<span class="badge status-approved">Aktív</span>',
    'cancelled' => '<span class="badge status-rejected">Lemondva</span>',
    'pending'   => '<span class="badge status-pending">Függőben</span>',
];

$fleetDayNames = ['Mon'=>'H','Tue'=>'K','Wed'=>'Sz','Thu'=>'Cs','Fri'=>'P','Sat'=>'Sz','Sun'=>'V'];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Munkabeosztás</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/main-2.css">
    <style>
        body {
            background-color: #F8FAFC;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        /* ── Dashboard fejléc sáv ── */
        .dashboard-header {
            background: linear-gradient(135deg, #1E3A5F 0%, #1E40AF 60%, #1d4ed8 100%);
            padding: 2rem 0 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            pointer-events: none;
        }
        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: 5%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            pointer-events: none;
        }
        .dashboard-header .greeting {
            color: #fff;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .dashboard-header .date-label {
            color: rgba(255,255,255,0.65);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* ── KPI kártyák (lebegnek a header fölé) ── */
        .kpi-row {
            margin-top: -2rem;
            position: relative;
            z-index: 10;
        }
        .kpi-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(30,58,95,0.10), 0 1px 4px rgba(0,0,0,0.06);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: box-shadow 0.2s, transform 0.2s;
            border: 1px solid rgba(255,255,255,0.8);
            height: 100%;
        }
        .kpi-card:hover {
            box-shadow: 0 8px 28px rgba(30,58,95,0.14), 0 2px 6px rgba(0,0,0,0.07);
            transform: translateY(-2px);
        }
        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .kpi-icon.blue  { background: #EFF6FF; color: #1D4ED8; }
        .kpi-icon.green { background: #F0FDF4; color: #16A34A; }
        .kpi-icon.indigo{ background: #EEF2FF; color: #4338CA; }
        .kpi-icon.amber { background: #FFFBEB; color: #D97706; }
        .kpi-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1E293B;
            line-height: 1;
        }
        .kpi-label {
            font-size: 0.78rem;
            color: #64748B;
            font-weight: 500;
            margin-top: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ── Következő műszak kártya ── */
        .next-shift-card {
            background: linear-gradient(135deg, #EFF6FF 0%, #F0FDF4 100%);
            border: 1px solid #BFDBFE;
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .next-shift-badge {
            background: linear-gradient(135deg, #1E3A5F, #1E40AF);
            color: #fff;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            text-align: center;
            min-width: 60px;
            flex-shrink: 0;
        }
        .next-shift-badge .month { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.8; }
        .next-shift-badge .day   { font-size: 1.5rem; font-weight: 800; line-height: 1; }

        /* ── Szekció fejlécek ── */
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1E293B;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title .bi {
            color: #3B82F6;
        }
        .section-divider {
            border: none;
            border-top: 1px solid #E2E8F0;
            margin: 0;
        }

        /* ── Táblázat stílus ── */
        .shifts-table {
            font-size: 0.875rem;
        }
        .shifts-table thead th {
            background: #F1F5F9;
            color: #475569;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #E2E8F0;
            padding: 0.75rem 1rem;
            white-space: nowrap;
        }
        .shifts-table tbody td {
            padding: 0.85rem 1rem;
            color: #334155;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }
        .shifts-table tbody tr:last-child td { border-bottom: none; }
        .shifts-table tbody tr:hover td { background: #F8FAFC; }
        .date-col { color: #1E293B; font-weight: 600; }
        .time-col { font-family: 'Courier New', monospace; font-weight: 600; color: #1D4ED8; }

        /* ── Üres állapot ── */
        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .empty-state .bi {
            font-size: 2.5rem;
            color: #CBD5E1;
            display: block;
            margin-bottom: 1rem;
        }
        .empty-state h6 { color: #475569; font-weight: 600; }
        .empty-state p  { color: #94A3B8; font-size: 0.875rem; }

        /* ── Áttekintés link gomb ── */
        .btn-outline-primary-soft {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1D4ED8;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.18s;
        }
        .btn-outline-primary-soft:hover {
            background: #DBEAFE;
            border-color: #93C5FD;
            color: #1E40AF;
            text-decoration: none;
        }

        /* ── Badge státuszok ── */
        .badge { font-size: 0.72rem; font-weight: 600; border-radius: 6px; padding: 0.3em 0.65em; }
        .status-approved { background: #D1FAE5; color: #065F46; }
        .status-rejected { background: #FEE2E2; color: #991B1B; }
        .status-pending  { background: #FEF3C7; color: #92400E; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<!-- ── Dashboard fejléc ── -->
<div class="dashboard-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="greeting">
                    👋 Üdvözöllek, <?= htmlspecialchars($user['name']) ?>!
                </div>
                <div class="date-label">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('Y. F j., l') ?>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <span class="badge" style="background:rgba(255,255,255,0.15);color:#fff;font-size:0.8rem;padding:0.5em 1em;border-radius:20px;backdrop-filter:blur(4px);">
                <i class="bi bi-shield-check me-1"></i>Rendszergazda
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Fő tartalom ── -->
<div class="container pb-5">

    <!-- KPI kártyák -->
    <div class="row g-3 kpi-row mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="kpi-value"><?= $todayShiftCount ?></div>
                    <div class="kpi-label">Mai aktív műszak</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon <?= $pendingLeaves > 0 ? 'amber' : 'green' ?>">
                    <i class="bi bi-<?= $pendingLeaves > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-value"><?= $pendingLeaves ?></div>
                        <?php if ($pendingLeaves > 0): ?>
                        <a href="szabadsag/" class="btn-outline-primary-soft ms-auto">
                            Áttekintés <i class="bi bi-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="kpi-label">Függő szabadságkérelem</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon indigo"><i class="bi bi-people"></i></div>
                <div>
                    <div class="kpi-value"><?= count($weekShifts) ?></div>
                    <div class="kpi-label">Heti műszak összesen</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <?php if ($nextShift): ?>
            <div class="next-shift-card h-100">
                <div class="next-shift-badge">
                    <div class="month"><?= date('M', strtotime($nextShift['shift_date'])) ?></div>
                    <div class="day"><?= date('d', strtotime($nextShift['shift_date'])) ?></div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-700 text-dark" style="font-weight:700;font-size:0.9rem;">Következő műszakod</div>
                    <div class="text-secondary" style="font-size:0.82rem;margin-top:0.15rem;">
                        <i class="bi bi-clock me-1"></i><?= htmlspecialchars($nextShift['start_time']) ?> – <?= htmlspecialchars($nextShift['end_time']) ?>
                    </div>
                    <?php if (!empty($nextShift['location'])): ?>
                    <div class="text-secondary" style="font-size:0.82rem;">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($nextShift['location']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="kpi-card">
                <div class="kpi-icon green"><i class="bi bi-sun"></i></div>
                <div>
                    <div class="kpi-value" style="font-size:1rem;color:#16A34A;">Szabad</div>
                    <div class="kpi-label">Nincs köv. műszak</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Heti beosztás táblázat -->
    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3 px-4" style="border-color:#E2E8F0!important;">
            <span class="section-title">
                <i class="bi bi-table"></i>
                Heti beosztás
                <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-weight:600;font-size:0.72rem;border-radius:6px;margin-left:0.25rem;">
                    <?= date('m. d.', strtotime($weekStart)) ?> – <?= date('m. d.', strtotime($weekEnd)) ?>
                </span>
            </span>
            <?php if ($isAdmin): ?>
            <a href="beosztás/" class="btn-outline-primary-soft">
                <i class="bi bi-grid-3x3-gap"></i> Teljes beosztás
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($weekShifts)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h6>Erre a hétre nincs rögzített műszak</h6>
            <p>A beosztás még nem töltötték fel, vagy ezen a héten nincs beosztásod.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table shifts-table mb-0">
                <thead>
                    <tr>
                        <th>Dátum</th>
                        <?php if ($isAdmin): ?><th>Dolgozó</th><?php endif; ?>
                        <th>Flotta</th>
                        <th>Kezdés</th>
                        <th>Befejezés</th>
                        <th>Helyszín</th>
                        <th>Rendszám</th>
                        <th>Státusz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weekShifts as $shift): ?>
                    <tr>
                        <td class="date-col">
                            <?= date('m. d.', strtotime($shift['shift_date'])) ?>
                            <span class="text-muted" style="font-size:0.75rem;font-weight:400;">
                                (<?= date('D', strtotime($shift['shift_date'])) ?>)
                            </span>
                        </td>
                        <?php if ($isAdmin): ?>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                      style="width:28px;height:28px;background:#EFF6FF;color:#1D4ED8;font-size:0.7rem;font-weight:700;flex-shrink:0;">
                                    <?= strtoupper(substr($shift['employee_name'], 0, 1)) ?>
                                </span>
                                <?= htmlspecialchars($shift['employee_name']) ?>
                            </div>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php
                                $fleetName = htmlspecialchars($shift['fleet_name']);
                                $badgeClass = (stripos($shift['fleet_name'], 'I.') !== false || stripos($shift['fleet_name'], '1') !== false)
                                    ? 'badge-fleet1' : 'badge-fleet2';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $fleetName ?></span>
                        </td>
                        <td class="time-col"><?= htmlspecialchars($shift['start_time']) ?></td>
                        <td class="time-col"><?= htmlspecialchars($shift['end_time']) ?></td>
                        <td>
                            <?php if (!empty($shift['location'])): ?>
                            <i class="bi bi-geo-alt text-muted me-1"></i><?= htmlspecialchars($shift['location']) ?>
                            <?php else: ?>
                            <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($shift['license_plate'])): ?>
                            <code style="background:#F1F5F9;padding:0.2em 0.5em;border-radius:4px;font-size:0.8rem;color:#334155;">
                                <?= htmlspecialchars($shift['license_plate']) ?>
                            </code>
                            <?php else: ?>
                            <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $statusLabels[$shift['status']] ?? htmlspecialchars($shift['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
