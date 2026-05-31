<?php
$monthNames = ['','Január','Február','Március','Április','Május','Június','Július','Augusztus','Szeptember','Október','November','December'];
$hunDays    = ['1'=>'Hétfő','2'=>'Kedd','3'=>'Szerda','4'=>'Csütörtök','5'=>'Péntek','6'=>'Szombat','7'=>'Vasárnap'];
$statusLabels = [
    'active'       => '',
    'sick'         => '🤒 Táppénz',
    'vacation'     => '🌴 Szabadság',
    'absence'      => '❌ Hiányzás',
    'swap_pending' => '🔄 Csere folyamatban',
];
$prevMonth = $month===1 ? 12 : $month-1; $prevYear = $month===1 ? $year-1 : $year;
$nextMonth = $month===12 ? 1 : $month+1; $nextYear = $month===12 ? $year+1 : $year;
$today     = date('Y-m-d');
$userName  = $_SESSION['user']['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saját beosztásom – <?= $monthNames[$month] ?> <?= $year ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        /* ===== KÉPERNYŐS NÉZET ===== */
        body { background:#F8FAFC; }
        .page-header { background:#0C4E54; color:#fff; padding:1.2rem 1rem; }
        .page-header .month-title { font-size:1.35rem; font-weight:700; }
        .page-header .user-name   { font-size:.85rem; opacity:.7; }
        .nav-btn { color:#fff; opacity:.8; text-decoration:none; font-size:1.3rem; padding:0 .5rem; }
        .nav-btn:hover { opacity:1; color:#fff; }

        .day-card { border-radius:10px; padding:.85rem 1rem; margin-bottom:.5rem;
                    box-shadow:0 1px 3px rgba(0,0,0,.07); border-left:5px solid #cbd5e1;
                    background:#fff; transition:.15s; }
        .day-card:hover { box-shadow:0 3px 10px rgba(0,0,0,.1); }
        .day-card.is-today { border-left-color:#3B82F6 !important; background:#eff6ff; }

        /* Típusonként szín */
        .day-card.type-work     { border-left-color:#0C4E54; }
        .day-card.type-rest     { border-left-color:#16a34a; background:#f0fdf4; }
        .day-card.type-sunday   { border-left-color:#dc2626; background:#fff1f2; }
        .day-card.type-holiday  { border-left-color:#f59e0b; background:#fffbeb; }
        .day-card.type-sick     { border-left-color:#ef4444; background:#fef2f2; }
        .day-card.type-vacation { border-left-color:#8b5cf6; background:#f5f3ff; }

        .day-num  { font-size:1.05rem; font-weight:700; min-width:28px; color:#1e293b; }
        .day-name { font-size:.78rem; color:#64748b; min-width:72px; }
        .day-tag  { font-size:.75rem; font-weight:700; padding:.2em .6em; border-radius:5px; }
        .tag-work     { background:#0C4E54; color:#fff; }
        .tag-rest     { background:#16a34a; color:#fff; }
        .tag-sunday   { background:#dc2626; color:#fff; }
        .tag-holiday  { background:#f59e0b; color:#fff; }
        .tag-sick     { background:#ef4444; color:#fff; }
        .tag-vacation { background:#8b5cf6; color:#fff; }

        .day-detail { font-size:.82rem; color:#475569; margin-top:.3rem; }
        .day-plate  { font-family:monospace; font-size:.82rem; color:#334155; font-weight:600; }
        .overtime-badge { font-size:.68rem; background:#dc2626; color:#fff;
                          padding:.15em .5em; border-radius:4px; margin-left:.3rem; }

        /* Jelmagyarázat */
        .legend { display:flex; flex-wrap:wrap; gap:.5rem 1rem; padding:.75rem 1rem;
                  background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.07); }
        .leg-item { display:flex; align-items:center; gap:.35rem; font-size:.75rem; color:#64748b; }
        .leg-dot  { width:12px; height:12px; border-radius:3px; flex-shrink:0; }

        /* ===== NYOMTATÁS: heti rácsos A4 ===== */
        @media print {
            body { background:#fff !important; }
            .no-print { display:none !important; }
            .screen-only { display:none !important; }
            .print-only { display:block !important; }

            .print-page { width:794px; margin:0 auto; font-family:Arial,sans-serif; }
            .print-header { background:#0C4E54 !important; -webkit-print-color-adjust:exact;
                            print-color-adjust:exact; color:#fff; padding:22px 28px 16px;
                            display:flex; justify-content:space-between; align-items:center; }
            .print-header .ph-name  { font-size:19px; font-weight:700; }
            .print-header .ph-meta  { font-size:11px; opacity:.7; margin-top:3px; }
            .print-header .ph-month { font-size:24px; font-weight:700; }

            .print-daynames { display:grid; grid-template-columns:repeat(7,1fr);
                              background:#0a3c41 !important; -webkit-print-color-adjust:exact;
                              print-color-adjust:exact; }
            .print-daynames div { text-align:center; color:rgba(255,255,255,.65) !important;
                                  font-size:9.5px; font-weight:700; text-transform:uppercase;
                                  letter-spacing:.07em; padding:6px 0; }
            .print-daynames .sun { color:#fca5a5 !important; }

            .print-calendar { padding:8px 12px 0; }
            .print-week-row { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-bottom:4px; }
            .print-cell { border-radius:6px; min-height:100px; padding:7px 6px; font-size:10px; }
            .print-cell-empty { min-height:100px; }
            .pc-work    { background:#f8fafc !important; border:1px solid #e2e8f0;
                          -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .pc-rest    { background:#f0fdf4 !important; border:1px solid #bbf7d0;
                          -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .pc-sunday  { background:#fff1f2 !important; border:1px solid #fecdd3;
                          -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .pc-holiday { background:#fffbeb !important; border:1px solid #fde68a;
                          -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .pc-num     { font-size:17px; font-weight:700; line-height:1; margin-bottom:5px; }
            .pc-tag     { display:inline-block; font-size:9px; font-weight:700; padding:2px 6px;
                          border-radius:4px; margin-bottom:4px; color:#fff !important;
                          -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .pct-work    { background:#0C4E54 !important; }
            .pct-rest    { background:#16a34a !important; }
            .pct-sunday  { background:#dc2626 !important; }
            .pct-holiday { background:#f59e0b !important; }
            .pc-detail  { font-size:9px; color:#475569; margin-top:2px;
                          white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
            .pc-plate   { font-family:monospace; color:#334155; }

            .print-summary { margin:10px 12px 0; display:flex; border:1px solid #e2e8f0; border-radius:7px; overflow:hidden; }
            .ps-item { flex:1; padding:10px 8px; text-align:center; border-right:1px solid #e2e8f0; }
            .ps-item:last-child { border-right:none; }
            .ps-val { font-size:20px; font-weight:700; color:#0C4E54; }
            .ps-lbl { font-size:9px; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-top:2px; }
        }
        @media screen { .print-only { display:none !important; } }
    </style>
</head>
<body>

<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>

<!-- ===== KÉPERNYŐS NÉZET ===== -->
<div class="screen-only">

    <!-- Fejléc -->
    <div class="page-header no-print">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="month-title"><?= $monthNames[$month] ?> <?= $year ?></div>
                <div class="user-name"><?= htmlspecialchars($userName) ?> – Saját beosztásom</div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <a href="/my-schedule?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="nav-btn">&#8592;</a>
                <a href="/my-schedule?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="nav-btn">&#8594;</a>
                <button class="btn btn-sm ms-2" style="background:#fff;color:#0C4E54;font-weight:700;"
                        onclick="window.print()">🖨️ Nyomtatás</button>
            </div>
        </div>
    </div>

    <div class="container py-3" style="max-width:600px">

        <!-- Jelmagyarázat -->
        <div class="legend mb-3 no-print">
            <div class="leg-item"><div class="leg-dot" style="background:#0C4E54"></div> Munkanap</div>
            <div class="leg-item"><div class="leg-dot" style="background:#16a34a"></div> Pihenőnap</div>
            <div class="leg-item"><div class="leg-dot" style="background:#dc2626"></div> Vasárnap</div>
            <div class="leg-item"><div class="leg-dot" style="background:#f59e0b"></div> Ünnepnap</div>
            <div class="leg-item"><div class="leg-dot" style="background:#ef4444"></div> Táppénz</div>
            <div class="leg-item"><div class="leg-dot" style="background:#8b5cf6"></div> Szabadság</div>
        </div>

        <!-- Napi kártyák -->
        <?php for ($day = 1; $day <= $daysInMonth; $day++):
            $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dowNum   = date('N', strtotime($dateStr));
            $isToday  = ($dateStr === $today);
            $shift    = $shiftMap[$dateStr] ?? null;
            $isHoliday= isset($holidays[$dateStr]);
            $isSunday = ($dowNum == 7);

            if ($shift) {
                $type = match($shift['status']) {
                    'sick'         => 'sick',
                    'vacation'     => 'vacation',
                    'absence'      => 'sick',
                    default        => 'work',
                };
                if ($isHoliday) $type = 'holiday';
            } elseif ($isHoliday) {
                $type = 'holiday';
            } elseif ($isSunday) {
                $type = 'sunday';
            } else {
                $type = 'rest';
            }
        ?>
        <div class="day-card type-<?= $type ?><?= $isToday ? ' is-today' : '' ?>">
            <div class="d-flex align-items-center gap-2">
                <span class="day-num"><?= $day ?></span>
                <span class="day-name"><?= $hunDays[$dowNum] ?></span>
                <?php if ($type === 'work'): ?>
                    <span class="day-tag tag-work"><?= substr($shift['start_time'],0,5) ?>–<?= substr($shift['end_time'],0,5) ?></span>
                    <?php if ($shift['is_overtime']): ?><span class="overtime-badge">⚡ Túlóra</span><?php endif; ?>
                <?php elseif ($type === 'sick'): ?>
                    <span class="day-tag tag-sick">🤒 Táppénz</span>
                <?php elseif ($type === 'vacation'): ?>
                    <span class="day-tag tag-vacation">🌴 Szabadság</span>
                <?php elseif ($type === 'holiday'): ?>
                    <span class="day-tag tag-holiday">🎉 <?= htmlspecialchars($holidays[$dateStr]) ?></span>
                <?php elseif ($type === 'sunday'): ?>
                    <span class="day-tag tag-sunday">Vasárnap</span>
                <?php else: ?>
                    <span class="day-tag tag-rest">Pihenőnap</span>
                <?php endif; ?>
                <?php if ($isToday): ?>
                    <span class="badge bg-primary ms-auto" style="font-size:.65rem">Ma</span>
                <?php endif; ?>
            </div>
            <?php if ($shift && $type === 'work'): ?>
            <div class="day-detail d-flex flex-wrap gap-3 mt-1">
                <?php if (!empty($shift['location'])): ?>
                    <span>📍 <?= htmlspecialchars($shift['location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($shift['license_plate'])): ?>
                    <span class="day-plate">🚗 <?= htmlspecialchars($shift['license_plate']) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>

    </div>
</div>

<!-- ===== NYOMTATÁSI NÉZET (A4 heti rács) ===== -->
<?php
// Heti rács előkészítés
$calDays = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $ds    = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $dow   = (int)date('N', strtotime($ds));
    $shift = $shiftMap[$ds] ?? null;
    $isH   = isset($holidays[$ds]);
    if ($shift) {
        $type = match($shift['status']) {
            'sick','absence' => 'sick',
            'vacation'       => 'vacation',
            default          => 'work',
        };
        if ($isH) $type = 'holiday';
    } elseif ($isH) { $type = 'holiday'; }
    elseif ($dow == 7) { $type = 'sunday'; }
    else { $type = 'rest'; }
    $calDays[] = ['day'=>$d,'dow'=>$dow,'ds'=>$ds,'shift'=>$shift,'type'=>$type,'holiday'=>$holidays[$ds] ?? null];
}
$firstDow = (int)date('N', strtotime($firstDay));
$weeks = []; $week = array_fill(0,7,null);
foreach ($calDays as $cd) {
    $col = $cd['dow'] - 1;
    $week[$col] = $cd;
    if ($cd['dow'] == 7) { $weeks[] = $week; $week = array_fill(0,7,null); }
}
if (array_filter($week)) $weeks[] = $week;

$workCount = 0; $restCount = 0; $totalH = 0;
foreach ($calDays as $cd) {
    if ($cd['type']==='work') {
        $workCount++;
        $s = $cd['shift'];
        if ($s) {
            $mins = (strtotime($s['end_time']) - strtotime($s['start_time'])) / 60;
            $totalH += round($mins / 60, 1);
        }
    } elseif (in_array($cd['type'],['rest','sunday','holiday'])) {
        $restCount++;
    }
}
?>
<div class="print-only">
<div class="print-page">
    <div class="print-header">
        <div>
            <div class="ph-name"><?= htmlspecialchars($userName) ?></div>
            <div class="ph-meta">Saját beosztásom</div>
        </div>
        <div style="text-align:right">
            <div class="ph-month"><?= $monthNames[$month] ?></div>
            <div style="font-size:11px;opacity:.6"><?= $year ?></div>
        </div>
    </div>
    <div class="print-daynames">
        <div>Hétfő</div><div>Kedd</div><div>Szerda</div>
        <div>Csütörtök</div><div>Péntek</div><div>Szombat</div>
        <div class="sun">Vasárnap</div>
    </div>
    <div class="print-calendar">
    <?php foreach ($weeks as $wk): ?>
    <div class="print-week-row">
        <?php for ($col=0; $col<7; $col++):
            $cd = $wk[$col];
            if ($cd === null) { echo '<div class="print-cell-empty"></div>'; continue; }
            $t = $cd['type'];
            $pcClass = match($t) {
                'work'=>'pc-work','rest'=>'pc-rest','sunday'=>'pc-sunday',
                'holiday'=>'pc-holiday','sick'=>'pc-rest','vacation'=>'pc-rest',
                default=>'pc-rest'
            };
            $tagClass = match($t) {
                'work'=>'pct-work','rest'=>'pct-rest','sunday'=>'pct-sunday',
                'holiday'=>'pct-holiday',default=>'pct-rest'
            };
        ?>
        <div class="print-cell <?= $pcClass ?>">
            <div class="pc-num" style="color:<?= $t==='sunday'?'#dc2626':($t==='holiday'?'#b45309':'#1e293b') ?>">
                <?= $cd['day'] ?>
            </div>
            <?php if ($t === 'work' && $cd['shift']): ?>
                <span class="pc-tag <?= $tagClass ?>">
                    <?= substr($cd['shift']['start_time'],0,5) ?>–<?= substr($cd['shift']['end_time'],0,5) ?>
                </span>
                <?php if (!empty($cd['shift']['location'])): ?>
                <div class="pc-detail">📍 <?= htmlspecialchars($cd['shift']['location']) ?></div>
                <?php endif; ?>
                <?php if (!empty($cd['shift']['license_plate'])): ?>
                <div class="pc-detail pc-plate">🚗 <?= htmlspecialchars($cd['shift']['license_plate']) ?></div>
                <?php endif; ?>
            <?php elseif ($t === 'holiday'): ?>
                <span class="pc-tag <?= $tagClass ?>">Ünnepnap</span>
                <div class="pc-detail"><?= htmlspecialchars($cd['holiday']) ?></div>
            <?php elseif ($t === 'sunday'): ?>
                <span class="pc-tag <?= $tagClass ?>">Vasárnap</span>
            <?php else: ?>
                <span class="pc-tag <?= $tagClass ?>">Pihenőnap</span>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <div class="print-summary">
        <div class="ps-item"><div class="ps-val"><?= $workCount ?></div><div class="ps-lbl">Munkanap</div></div>
        <div class="ps-item"><div class="ps-val"><?= $restCount ?></div><div class="ps-lbl">Pihenőnap</div></div>
        <div class="ps-item"><div class="ps-val"><?= $totalH ?></div><div class="ps-lbl">Munkaóra</div></div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
