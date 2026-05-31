<?php
$monthNames = ['','Január','Február','Március','Április','Május','Június','Július','Augusztus','Szeptember','Október','November','December'];
$dayNames   = ['H','K','Sze','Cs','P','Szo','V'];
$daysInMonth    = (int)date('t', strtotime($firstDay));
$firstDayOfWeek = (int)date('N', strtotime($firstDay));
$prevMonth = $month===1 ? 12 : $month-1; $prevYear = $month===1 ? $year-1 : $year;
$nextMonth = $month===12 ? 1 : $month+1; $nextYear = $month===12 ? $year+1 : $year;
$today = date('Y-m-d');
$hunDays = ['1'=>'Hétfő','2'=>'Kedd','3'=>'Szerda','4'=>'Csütörtök','5'=>'Péntek','6'=>'Szombat','7'=>'Vasárnap'];
$statusLabels = ['active'=>'','sick'=>'🤒 Táppénz','vacation'=>'🌴 Szabadság','swap_pending'=>'🔄 Csere folyamatban','absence'=>'❌ Hiányzás'];
$isAdmin = (($_SESSION['user']['role'] ?? '') === 'admin');
$currentUserName = $_SESSION['user']['name'] ?? '';
$currentUserId   = (int)($_SESSION['user']['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beosztás – <?= $monthNames[$month] ?> <?= $year ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background:#F8FAFC; }
        .calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
        .cal-dayname { text-align:center; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; padding:.4rem 0; }
        .cal-cell { background:#fff; border-radius:8px; min-height:90px; padding:6px; box-shadow:0 1px 2px rgba(0,0,0,.05); font-size:.78rem; cursor:pointer; transition:.15s; }
        .cal-cell:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-1px); }
        .cal-cell--empty { background:transparent; box-shadow:none; cursor:default; pointer-events:none; }
        .cal-cell--empty:hover { transform:none; box-shadow:none; }
        .cal-cell--no-shift { cursor:default; }
        .cal-cell--no-shift:hover { box-shadow:0 1px 2px rgba(0,0,0,.05); transform:none; }
        .cal-cell--admin-empty { cursor:pointer; }
        .cal-cell--admin-empty:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-1px); }
        .cal-cell--today { border:2px solid #3B82F6; }
        .cal-day-num { display:block; font-weight:700; color:#1e293b; margin-bottom:3px; }
        .cal-cell--today .cal-day-num { color:#3B82F6; }
        .cal-shift { padding:2px 5px; border-radius:4px; margin-bottom:2px; background:#f1f5f9; }
        .cal-shift-name { display:block; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:.72rem; }
        .cal-shift-time { display:block; color:#64748b; font-size:.68rem; }
        .cal-more { font-size:.68rem; color:#3B82F6; font-weight:600; margin-top:2px; }
        .cal-add-hint { font-size:.65rem; color:#94a3b8; margin-top:4px; text-align:center; }
        .cal-my-shift { background:#dbeafe !important; border-left-color:#3B82F6 !important; }
        .cal-cell--dimmed { opacity:.35; }
        .modal-shift-row { border-left:4px solid #64748b; background:#f8fafc; border-radius:0 8px 8px 0; padding:.6rem 1rem; margin-bottom:.5rem; }
        .modal-shift-row .emp-name { font-weight:700; color:#1e293b; }
        .modal-shift-row .emp-detail { font-size:.8rem; color:#64748b; }
        .fleet-pill { display:inline-block; padding:.15em .55em; border-radius:4px; font-size:.7rem; font-weight:700; color:#fff; margin-right:.3rem; }
        #myShiftToggle.active { background:#0C4E54; color:#fff; border-color:#0C4E54; }
        @media(max-width:576px){ .cal-cell{min-height:55px;padding:3px;} .cal-shift-name{display:none;} }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 fw-bold mb-0">📅 Beosztás</h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if (!$isAdmin): ?>
            <button id="myShiftToggle" class="btn btn-outline-secondary btn-sm" onclick="toggleMyShifts()">
                👤 Csak az enyém
            </button>
            <?php endif; ?>
            <a href="/schedule?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-outline-secondary btn-sm">← Előző</a>
            <span class="fw-bold px-1"><?= $monthNames[$month] ?> <?= $year ?></span>
            <a href="/schedule?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-outline-secondary btn-sm">Következő →</a>
        </div>
    </div>
    <div class="card border-0 shadow-sm p-3">
        <div class="calendar-grid mb-1">
            <?php foreach ($dayNames as $d): ?><div class="cal-dayname"><?= $d ?></div><?php endforeach; ?>
        </div>
        <div class="calendar-grid" id="calendarGrid">
            <?php for ($i=1; $i<$firstDayOfWeek; $i++): ?>
                <div class="cal-cell cal-cell--empty"></div>
            <?php endfor; ?>
            <?php for ($day=1; $day<=$daysInMonth; $day++):
                $dateStr   = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $isToday   = ($dateStr === $today);
                $dayShifts = $shiftsByDate[$dateStr] ?? [];
                $hasShifts = !empty($dayShifts);
                $maxShow   = 3;
                $showShifts = array_slice($dayShifts, 0, $maxShow);
                $moreCount  = count($dayShifts) - $maxShow;
                $dowNum     = date('N', strtotime($dateStr));
                $hasMyShift = false;
                foreach ($dayShifts as $s) {
                    if ((int)$s['user_id'] === $currentUserId) { $hasMyShift = true; break; }
                }
                $clickable = $hasShifts || $isAdmin;
                $extraClass = '';
                if (!$hasShifts && $isAdmin) $extraClass = 'cal-cell--admin-empty';
                if (!$hasShifts && !$isAdmin) $extraClass = 'cal-cell--no-shift';
            ?>
                <div class="cal-cell <?= $isToday ? 'cal-cell--today' : '' ?> <?= $extraClass ?>"
                     data-date="<?= $dateStr ?>"
                     data-has-my-shift="<?= $hasMyShift ? '1' : '0' ?>"
                     <?php if ($clickable): ?>
                         onclick="openDayModal('<?= $dateStr ?>', '<?= $hunDays[$dowNum] ?>', <?= $day ?>)"
                     <?php endif; ?>>
                    <span class="cal-day-num"><?= $day ?></span>
                    <?php foreach ($showShifts as $s): ?>
                        <div class="cal-shift <?= ((int)$s['user_id'] === $currentUserId) ? 'cal-my-shift' : '' ?>"
                             style="border-left:3px solid <?= htmlspecialchars($s['color'] ?? '#64748b') ?>">
                            <span class="cal-shift-name"><?= htmlspecialchars($s['employee_name']) ?></span>
                            <?php if (!empty($s['license_plate'])): ?>
                            <span class="cal-shift-time"><?= htmlspecialchars($s['license_plate']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($moreCount > 0): ?>
                        <div class="cal-more">+<?= $moreCount ?> további</div>
                    <?php endif; ?>
                    <?php if (!$hasShifts && $isAdmin): ?>
                        <div class="cal-add-hint">+ hozzáad</div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Napi részletek modal -->
<div class="modal fade" id="dayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#0C4E54; color:#fff;">
                <h5 class="modal-title fw-bold" id="dayModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body" id="dayModalBody"></div>
            <?php if ($isAdmin): ?>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-sm btn-success" onclick="openAddShiftForm()">+ Beosztás hozzáadása</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Beosztás hozzáadása modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#0C4E54;color:#fff;">
                <h5 class="modal-title fw-bold">➕ Beosztás hozzáadása</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="addShiftDate">
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Dátum</label>
                    <input type="text" class="form-control form-control-sm" id="addShiftDateDisplay" readonly style="background:#f8fafc;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Dolgozó</label>
                    <select class="form-select" id="addShiftUser">
                        <option value="">-- Válassz --</option>
                        <?php
                        $uStmt = (Database::getInstance())->prepare("SELECT id, name FROM users WHERE is_active=1 AND role='employee' ORDER BY name");
                        $uStmt->execute();
                        foreach ($uStmt->fetchAll() as $u):
                        ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col">
                        <label class="form-label fw-semibold">Kezdés</label>
                        <input type="time" class="form-control" id="addShiftStart" value="06:00">
                    </div>
                    <div class="col">
                        <label class="form-label fw-semibold">Befejezés</label>
                        <input type="time" class="form-control" id="addShiftEnd" value="18:00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Helyszín</label>
                    <input type="text" class="form-control" id="addShiftLocation" placeholder="pl. Budapest">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rendszám</label>
                    <input type="text" class="form-control" id="addShiftPlate" placeholder="pl. ABC-123">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Mégse</button>
                <button class="btn btn-success btn-sm" id="addShiftSubmitBtn" onclick="submitAddShift()">Mentés</button>
            </div>
        </div>
    </div>
</div>

<!-- Beosztás szerkesztése modal -->
<div class="modal fade" id="editShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#1d4ed8;color:#fff;">
                <h5 class="modal-title fw-bold">✏️ Beosztás szerkesztése</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editShiftId">
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Dolgozó</label>
                    <input type="text" class="form-control form-control-sm" id="editShiftName" readonly style="background:#f8fafc;">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold small mb-1">Dátum</label>
                    <input type="text" class="form-control form-control-sm" id="editShiftDateDisplay" readonly style="background:#f8fafc;">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col">
                        <label class="form-label fw-semibold">Kezdés</label>
                        <input type="time" class="form-control" id="editShiftStart">
                    </div>
                    <div class="col">
                        <label class="form-label fw-semibold">Befejezés</label>
                        <input type="time" class="form-control" id="editShiftEnd">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Helyszín</label>
                    <input type="text" class="form-control" id="editShiftLocation" placeholder="pl. Budapest">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rendszám</label>
                    <input type="text" class="form-control" id="editShiftPlate" placeholder="pl. ABC-123">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Státusz</label>
                    <select class="form-select" id="editShiftStatus">
                        <option value="active">Aktív</option>
                        <option value="sick">🤒 Táppénz</option>
                        <option value="vacation">🌴 Szabadság</option>
                        <option value="absence">❌ Hiányzás</option>
                        <option value="swap_pending">🔄 Csere folyamatban</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Mégse</button>
                <button class="btn btn-primary btn-sm" id="editShiftSubmitBtn" onclick="submitEditShift()">💾 Mentés</button>
            </div>
        </div>
    </div>
</div>

<!-- Shift adatok JS-nek -->
<script>
const shiftsByDate   = <?php
    $jsData = [];
    foreach ($shiftsByDate as $date => $dayShifts) {
        foreach ($dayShifts as $s) {
            $jsData[$date][] = [
                'name'     => $s['employee_name'],
                'fleet'    => $s['fleet_name'] ?? '',
                'color'    => $s['color'] ?? '#64748b',
                'start'    => substr($s['start_time'], 0, 5),
                'end'      => substr($s['end_time'], 0, 5),
                'plate'    => $s['license_plate'] ?? '',
                'location' => $s['location'] ?? '',
                'status'   => $s['status'] ?? 'active',
                'id'       => (int)$s['id'],
                'overtime' => (bool)($s['is_overtime'] ?? false),
                'user_id'  => (int)$s['user_id'],
            ];
        }
    }
    echo json_encode($jsData, JSON_UNESCAPED_UNICODE);
?>;

const statusLabels   = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>;
const monthNames     = <?= json_encode($monthNames, JSON_UNESCAPED_UNICODE) ?>;
const isAdmin        = <?= json_encode($isAdmin) ?>;
const currentUserId  = <?= json_encode($currentUserId) ?>;
const currentUserName = <?= json_encode($currentUserName, JSON_UNESCAPED_UNICODE) ?>;

// --- Saját beosztás szűrő ---
let myShiftOnly = false;

function toggleMyShifts() {
    myShiftOnly = !myShiftOnly;
    const btn = document.getElementById('myShiftToggle');
    btn.classList.toggle('active', myShiftOnly);
    btn.textContent = myShiftOnly ? '👥 Mindenki' : '👤 Csak az enyém';
    document.querySelectorAll('#calendarGrid .cal-cell[data-date]').forEach(cell => {
        const hasMyShift = cell.dataset.hasMyShift === '1';
        if (myShiftOnly) {
            cell.classList.toggle('cal-cell--dimmed', !hasMyShift);
        } else {
            cell.classList.remove('cal-cell--dimmed');
        }
    });
}

// --- Nap modal ---
function openDayModal(dateStr, dayName, dayNum) {
    currentModalDate = dateStr;
    let shifts = shiftsByDate[dateStr] || [];
    if (myShiftOnly) shifts = shifts.filter(s => s.user_id === currentUserId);

    const parts = dateStr.split('-');
    const title = dayName + ', ' + parts[0] + '. ' + monthNames[parseInt(parts[1])] + ' ' + dayNum + '.';
    document.getElementById('dayModalTitle').textContent = title;

    let html = '';
    if (shifts.length === 0) {
        html = '<p class="text-muted text-center py-3">Ezen a napon nincs beosztás.</p>';
        if (isAdmin) html += '<p class="text-center"><button class="btn btn-sm btn-outline-success" onclick="openAddShiftForm()">➕ Beosztás hozzáadása</button></p>';
    } else {
        html = '<div class="small text-muted mb-3">' + shifts.length + ' beosztott dolgozó</div>';
        shifts.forEach(s => {
            const isMine = s.user_id === currentUserId;

            const overtimeBtn = isAdmin
                ? `<button class="btn btn-xs btn-outline-warning ms-1"
                      style="font-size:.65rem;padding:1px 7px;border-radius:4px;"
                      data-id="${s.id}" data-state="${s.overtime ? '1' : '0'}"
                      onclick="toggleOvertime(this)">
                      ${s.overtime ? '⚡ Túlóra' : '+ Túlóra'}
                   </button>`
                : (s.overtime ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">⚡ Túlóra</span>' : '');

            const statusBadge = s.status !== 'active'
                ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">' + (statusLabels[s.status] || s.status) + '</span>'
                : '';

            const plate    = s.plate    ? '<span class="me-2">🚗 ' + s.plate    + '</span>' : '';
            const location = s.location ? '<span>📍 ' + s.location + '</span>' : '';

            const editBtn = isAdmin
                ? `<button class="btn btn-xs btn-outline-primary ms-1"
                      style="font-size:.62rem;padding:1px 7px;border-radius:4px;"
                      onclick="openEditShiftForm(${s.id}, '${s.name.replace(/'/g,"&#39;")}', '${dateStr}', '${s.start}', '${s.end}', '${(s.location||'').replace(/'/g,"&#39;")}', '${(s.plate||'').replace(/'/g,"&#39;")}', '${s.status}')">
                      ✏️ Szerk.
                   </button>`
                : '';

            const deleteBtn = isAdmin
                ? `<button class="btn btn-xs btn-outline-danger ms-1"
                      style="font-size:.62rem;padding:1px 7px;border-radius:4px;"
                      onclick="deleteShift(${s.id}, this)">🗑 Törlés</button>`
                : '';

            html += `
            <div class="modal-shift-row" style="border-left-color:${s.color}${isMine ? ';background:#eff6ff' : ''}" data-shift-id="${s.id}">
                <div class="emp-name" style="${s.overtime ? 'color:#dc2626;font-weight:700;' : ''}">
                    <span class="fleet-pill" style="background:${s.color}">${s.fleet}</span>
                    ${s.name}${isMine ? ' <span class="badge bg-primary ms-1" style="font-size:.6rem">Én</span>' : ''}
                    ${statusBadge} ${overtimeBtn} ${editBtn} ${deleteBtn}
                </div>
                <div class="emp-detail mt-1">${plate}${location}</div>
            </div>`;
        });
    }

    document.getElementById('dayModalBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('dayModal')).show();
}

let currentModalDate = null;

function openAddShiftForm() {
    const date = currentModalDate;
    document.getElementById('addShiftDate').value = date;
    const parts = date.split('-');
    document.getElementById('addShiftDateDisplay').value =
        parts[0] + '. ' + monthNames[parseInt(parts[1])] + ' ' + parseInt(parts[2]) + '.';
    document.getElementById('addShiftUser').value = '';
    document.getElementById('addShiftStart').value = '06:00';
    document.getElementById('addShiftEnd').value = '18:00';
    document.getElementById('addShiftLocation').value = '';
    document.getElementById('addShiftPlate').value = '';

    const dayModal = bootstrap.Modal.getInstance(document.getElementById('dayModal'));
    if (dayModal) dayModal.hide();
    setTimeout(() => new bootstrap.Modal(document.getElementById('addShiftModal')).show(), 300);
}

function openEditShiftForm(id, name, dateStr, start, end, location, plate, status) {
    document.getElementById('editShiftId').value           = id;
    document.getElementById('editShiftName').value         = name;
    document.getElementById('editShiftStart').value        = start;
    document.getElementById('editShiftEnd').value          = end;
    document.getElementById('editShiftLocation').value     = location;
    document.getElementById('editShiftPlate').value        = plate;
    document.getElementById('editShiftStatus').value       = status;

    const parts = dateStr.split('-');
    document.getElementById('editShiftDateDisplay').value =
        parts[0] + '. ' + monthNames[parseInt(parts[1])] + ' ' + parseInt(parts[2]) + '.';

    const dayModal = bootstrap.Modal.getInstance(document.getElementById('dayModal'));
    if (dayModal) dayModal.hide();
    setTimeout(() => new bootstrap.Modal(document.getElementById('editShiftModal')).show(), 300);
}

function submitEditShift() {
    const id       = document.getElementById('editShiftId').value;
    const start    = document.getElementById('editShiftStart').value;
    const end      = document.getElementById('editShiftEnd').value;
    const location = document.getElementById('editShiftLocation').value;
    const plate    = document.getElementById('editShiftPlate').value;
    const status   = document.getElementById('editShiftStatus').value;

    const btn = document.getElementById('editShiftSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Mentés...';

    fetch('/schedule/edit', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `shift_id=${id}&start_time=${start}&end_time=${end}&location=${encodeURIComponent(location)}&license_plate=${encodeURIComponent(plate)}&status=${status}`
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = '💾 Mentés';
        if (data.success) {
            // Frissíti a JS adatot is, hogy modal újranyitáskor helyes legyen
            for (const date in shiftsByDate) {
                shiftsByDate[date].forEach(s => {
                    if (s.id == id) {
                        s.start    = data.start_time;
                        s.end      = data.end_time;
                        s.location = data.location;
                        s.plate    = data.license_plate;
                        s.status   = data.status;
                    }
                });
            }
            bootstrap.Modal.getInstance(document.getElementById('editShiftModal'))?.hide();
            setTimeout(() => location.reload(), 250);
        } else {
            alert(data.message || 'Hiba történt.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = '💾 Mentés';
        alert('Hálózati hiba.');
    });
}

function submitAddShift() {
    const date   = document.getElementById('addShiftDate').value;
    const userId = document.getElementById('addShiftUser').value;
    const start  = document.getElementById('addShiftStart').value;
    const end    = document.getElementById('addShiftEnd').value;
    const loc    = document.getElementById('addShiftLocation').value;
    const plate  = document.getElementById('addShiftPlate').value;

    if (!userId) { alert('Válassz dolgozót!'); return; }

    const btn = document.getElementById('addShiftSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Mentés...';

    fetch('/schedule/add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `user_id=${userId}&shift_date=${date}&start_time=${start}&end_time=${end}&location=${encodeURIComponent(loc)}&license_plate=${encodeURIComponent(plate)}`
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Mentés';
        if (data.success) {
            const s = data.shift;
            if (!shiftsByDate[date]) shiftsByDate[date] = [];
            shiftsByDate[date].push({
                name: s.employee_name, fleet: s.fleet_name, color: s.color,
                start: s.start_time ? s.start_time.substring(0,5) : '06:00',
                end: s.end_time ? s.end_time.substring(0,5) : '18:00',
                plate: s.license_plate || '', location: s.location || '',
                status: s.status || 'active', id: s.id, overtime: false, user_id: s.user_id,
            });
            bootstrap.Modal.getInstance(document.getElementById('addShiftModal'))?.hide();
            setTimeout(() => location.reload(), 300);
        } else {
            alert(data.message || 'Hiba történt.');
        }
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Mentés'; alert('Hálózati hiba.'); });
}

function deleteShift(shiftId, btn) {
    if (!confirm('Biztosan törlöd ezt a beosztást?')) return;
    fetch('/schedule/delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'shift_id=' + shiftId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.closest('.modal-shift-row').remove();
            for (const date in shiftsByDate) {
                shiftsByDate[date] = shiftsByDate[date].filter(s => s.id != shiftId);
            }
            const body = document.getElementById('dayModalBody');
            if (!body.querySelector('.modal-shift-row')) {
                body.innerHTML = '<p class="text-muted text-center py-3">Ezen a napon nincs beosztás.</p>';
                if (isAdmin) body.innerHTML += '<p class="text-center"><button class="btn btn-sm btn-outline-success" onclick="openAddShiftForm()">➕ Beosztás hozzáadása</button></p>';
            }
        } else { alert(data.message || 'Hiba történt.'); }
    })
    .catch(() => alert('Hálózati hiba.'));
}

function toggleOvertime(btn) {
    const shiftId = btn.dataset.id;
    fetch('/schedule/overtime', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'shift_id=' + shiftId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const isNow = data.is_overtime;
            btn.dataset.state = isNow ? '1' : '0';
            btn.textContent   = isNow ? '⚡ Túlóra' : '+ Túlóra';
            btn.classList.toggle('btn-warning', isNow);
            btn.classList.toggle('btn-outline-warning', !isNow);
            for (const date in shiftsByDate) {
                shiftsByDate[date].forEach(s => { if (s.id == shiftId) s.overtime = isNow; });
            }
            const empNameDiv = btn.closest('.modal-shift-row').querySelector('.emp-name');
            if (empNameDiv) {
                empNameDiv.style.color      = isNow ? '#dc2626' : '';
                empNameDiv.style.fontWeight = isNow ? '700' : '';
            }
        } else { alert(data.message || 'Hiba történt.'); }
    })
    .catch(() => alert('Hálózati hiba.'));
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
