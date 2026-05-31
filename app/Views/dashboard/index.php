<?php
$statusLabels = ['active'=>'Aktív','sick'=>'Táppénz','vacation'=>'Szabadság','absence'=>'Hiányzás','swap_pending'=>'Csere folyamatban'];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Munkabeosztás</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background: #F8FAFC; }
        .stat-card { border-radius:12px; border:none; box-shadow:0 1px 3px rgba(0,0,0,.08); transition:transform .15s; }
        .stat-card:hover { transform:translateY(-2px); }
        .stat-icon { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .stat-value { font-size:2rem; font-weight:700; line-height:1.1; }
        .stat-label { font-size:.78rem; color:#64748b; text-transform:uppercase; letter-spacing:.05em; font-weight:600; }
        .fleet-badge { display:inline-block; padding:.25em .65em; border-radius:6px; font-size:.78rem; font-weight:600; color:#fff; }
        .table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:600; border-bottom:2px solid #e2e8f0; }
        .table td { vertical-align:middle; font-size:.9rem; }
        .next-shift-card { background:linear-gradient(135deg,#1E3A5F,#1E40AF); color:#fff; border-radius:12px; }
        .section-title { font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .week-nav .btn { font-size:.8rem; }
        .btn-my-schedule { background:#0C4E54; color:#fff; border:none; }
        .btn-my-schedule:hover { background:#0a3c41; color:#fff; }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4">

    <!-- Fejléc -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4 pb-3 border-bottom">
        <div>
            <h1 class="h4 fw-bold mb-0">Üdvözöllek, <?= htmlspecialchars($user['name']) ?>! 👋</h1>
            <p class="text-muted small mb-0"><?= date('Y. F j., l') ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!$isAdmin): ?>
            <a href="/my-schedule" class="btn btn-sm btn-my-schedule">📅 Saját beosztásom</a>
            <?php endif; ?>
            <a href="/schedule" class="btn btn-primary btn-sm">📋 Teljes beosztás</a>
        </div>
    </div>

    <!-- Stat kártyák -->
    <div class="row g-3 mb-4">

        <!-- Mai műszakok -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#3B82F6" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Mai műszakok</div>
                        <div class="stat-value text-primary"><?= $todayShiftCount ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Függő kérelmek -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning bg-opacity-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#F59E0B" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Függő kérelmek</div>
                        <div class="stat-value text-warning"><?= $pendingLeaves ?></div>
                        <?php if ($pendingLeaves > 0): ?>
                            <a href="/admin/leaves" class="small text-warning">Áttekintés →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Következő műszak -->
        <?php if ($nextShift): ?>
        <div class="col-12 col-lg-<?= $isAdmin ? '6' : '9' ?>">
            <div class="next-shift-card p-3 h-100 d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:rgba(255,255,255,.15)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="small fw-semibold text-uppercase mb-1" style="font-size:.72rem;letter-spacing:.06em;opacity:.7;">Következő műszakod</div>
                    <div class="fw-bold fs-5"><?= date('Y. m. d. (D)', strtotime($nextShift['shift_date'])) ?></div>
                    <div class="small" style="opacity:.8;">
                        🕐 <?= htmlspecialchars(substr($nextShift['start_time'],0,5)) ?> – <?= htmlspecialchars(substr($nextShift['end_time'],0,5)) ?>
                        <?php if ($nextShift['location']): ?> &nbsp;📍 <?= htmlspecialchars($nextShift['location']) ?><?php endif; ?>
                        <?php if ($nextShift['license_plate']): ?> &nbsp;🚗 <?= htmlspecialchars($nextShift['license_plate']) ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Heti beosztás táblázat -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <!-- Szűrősáv -->
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                <div class="btn-group btn-group-sm" id="fleetFilter">
                    <button class="btn btn-outline-primary active" data-fleet="">Összes flotta</button>
                    <button class="btn btn-outline-primary" data-fleet="I. Flotta">I. Flotta</button>
                    <button class="btn btn-outline-primary" data-fleet="II. Flotta">II. Flotta</button>
                </div>
                <select class="form-select form-select-sm w-auto" id="statusFilter">
                    <option value="">Minden státusz</option>
                    <option value="active">Aktív</option>
                    <option value="sick">Táppénz</option>
                    <option value="vacation">Szabadság</option>
                    <option value="absence">Hiányzás</option>
                    <option value="swap_pending">Csere folyamatban</option>
                </select>
                <input type="text" class="form-control form-control-sm w-auto" id="nameFilter" placeholder="🔍 Dolgozó neve...">
                <span class="ms-auto small text-muted" id="filterCount"></span>
            </div>

            <!-- Hét navigáció -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 week-nav">
                <div class="section-title mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#3B82F6" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    Heti beosztás
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold" style="font-size:.72rem;">
                        <?= date('m. d.', strtotime($weekStart)) ?> – <?= date('m. d.', strtotime($weekEnd)) ?>
                        <?php if ($isCurrentWeek): ?><span class="ms-1 badge bg-success" style="font-size:.65rem;">aktuális</span><?php endif; ?>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="/dashboard?week=<?= $prevWeek ?>" class="btn btn-outline-secondary btn-sm">← Előző hét</a>
                    <?php if (!$isCurrentWeek): ?>
                        <a href="/dashboard" class="btn btn-outline-primary btn-sm">Ma</a>
                    <?php endif; ?>
                    <a href="/dashboard?week=<?= $nextWeek ?>" class="btn btn-outline-secondary btn-sm">Következő hét →</a>
                </div>
            </div>

            <?php if (empty($weekShifts)): ?>
                <div class="text-center py-5 text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="mb-2 opacity-25 d-block mx-auto">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    <p class="mb-0">Erre a hétre nincs rögzített műszak.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sortable" data-col="0" style="cursor:pointer;">Dátum <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="1" style="cursor:pointer;">Dolgozó <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="2" style="cursor:pointer;">Flotta <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="3" style="cursor:pointer;">Kezdés <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="4" style="cursor:pointer;">Befejezés <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="5" style="cursor:pointer;">Helyszín <span class="sort-icon">↕</span></th>
                            <th>Rendszám</th>
                            <th class="sortable" data-col="7" style="cursor:pointer;">Státusz <span class="sort-icon">↕</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sc = ['active'=>'success','sick'=>'danger','vacation'=>'primary','absence'=>'warning','swap_pending'=>'secondary'];
                    foreach ($weekShifts as $shift): ?>
                        <tr data-fleet="<?= htmlspecialchars($shift['fleet_name']) ?>"
                            data-status="<?= htmlspecialchars($shift['status']) ?>"
                            data-name="<?= htmlspecialchars(mb_strtolower($shift['employee_name'])) ?>">
                            <td class="fw-semibold text-nowrap" data-val="<?= $shift['shift_date'] ?>"><?= date('m. d. (D)', strtotime($shift['shift_date'])) ?></td>
                            <td data-val="<?= htmlspecialchars($shift['employee_name']) ?>"><?= htmlspecialchars($shift['employee_name']) ?></td>
                            <td data-val="<?= htmlspecialchars($shift['fleet_name']) ?>">
                                <span class="fleet-badge" style="background:<?= htmlspecialchars($shift['color'] ?? '#64748b') ?>">
                                    <?= htmlspecialchars($shift['fleet_name']) ?>
                                </span>
                            </td>
                            <td data-val="<?= htmlspecialchars(substr($shift['start_time'],0,5)) ?>"><?= htmlspecialchars(substr($shift['start_time'],0,5)) ?></td>
                            <td data-val="<?= htmlspecialchars(substr($shift['end_time'],0,5)) ?>"><?= htmlspecialchars(substr($shift['end_time'],0,5)) ?></td>
                            <td data-val="<?= htmlspecialchars($shift['location'] ?? '') ?>"><?= htmlspecialchars($shift['location'] ?? '–') ?></td>
                            <td data-val="<?= htmlspecialchars($shift['license_plate'] ?? '') ?>"><code><?= htmlspecialchars($shift['license_plate'] ?? '–') ?></code></td>
                            <td data-val="<?= $shift['status'] ?>">
                                <span class="badge rounded-pill bg-<?= $sc[$shift['status']] ?? 'secondary' ?>">
                                    <?= $statusLabels[$shift['status']] ?? $shift['status'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const tbody  = document.querySelector('.table tbody');
    const rows   = () => Array.from(tbody ? tbody.querySelectorAll('tr') : []);
    let sortCol  = -1, sortAsc = true;

    function applyFilters() {
        const fleet  = document.querySelector('#fleetFilter .active')?.dataset.fleet ?? '';
        const status = document.getElementById('statusFilter')?.value ?? '';
        const name   = (document.getElementById('nameFilter')?.value ?? '').toLowerCase().trim();
        let visible  = 0;
        rows().forEach(tr => {
            const ok = (!fleet  || tr.dataset.fleet  === fleet)
                    && (!status || tr.dataset.status === status)
                    && (!name   || tr.dataset.name.includes(name));
            tr.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        const fc = document.getElementById('filterCount');
        if (fc) fc.textContent = visible + ' sor';
    }

    document.getElementById('fleetFilter')?.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#fleetFilter button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        });
    });

    document.getElementById('statusFilter')?.addEventListener('change', applyFilters);
    document.getElementById('nameFilter')?.addEventListener('input', applyFilters);

    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = parseInt(th.dataset.col);
            if (sortCol === col) { sortAsc = !sortAsc; }
            else { sortCol = col; sortAsc = true; }
            document.querySelectorAll('th.sortable .sort-icon').forEach(i => i.textContent = '↕');
            th.querySelector('.sort-icon').textContent = sortAsc ? '↑' : '↓';
            const sorted = rows().sort((a, b) => {
                const aVal = a.cells[col]?.dataset.val ?? '';
                const bVal = b.cells[col]?.dataset.val ?? '';
                return sortAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            sorted.forEach(tr => tbody.appendChild(tr));
            applyFilters();
        });
    });

    applyFilters();
})();
</script>
</body>
</html>
