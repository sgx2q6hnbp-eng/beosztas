<?php
$monthNames = ['','Január','Február','Március','Április','Május','Június','Július','Augusztus','Szeptember','Október','November','December'];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ünnepnapok – <?= $year ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4" style="max-width:700px">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h4 fw-bold mb-0">🗓️ Ünnepnapok kezelése</h1>
        <div class="d-flex gap-2 align-items-center">
            <a href="/admin/holidays?year=<?= $year-1 ?>" class="btn btn-outline-secondary btn-sm">← <?= $year-1 ?></a>
            <span class="fw-bold"><?= $year ?></span>
            <a href="/admin/holidays?year=<?= $year+1 ?>" class="btn btn-outline-secondary btn-sm"><?= $year+1 ?> →</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Hozzáadás form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">➕ Új ünnepnap hozzáadása</h6>
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold">Dátum</label>
                    <input type="date" class="form-control form-control-sm" id="newDate"
                           min="<?= $year ?>-01-01" max="<?= $year ?>-12-31"
                           value="<?= $year ?>-01-01">
                </div>
                <div class="col-sm-5">
                    <label class="form-label small fw-semibold">Megnevezés</label>
                    <input type="text" class="form-control form-control-sm" id="newName" placeholder="pl. Nemzeti ünnep">
                </div>
                <div class="col-sm-3">
                    <button class="btn btn-success btn-sm w-100" onclick="addHoliday()">Hozzáadás</button>
                </div>
            </div>
            <div id="addError" class="text-danger small mt-2" style="display:none"></div>
        </div>
    </div>

    <!-- Lista -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($holidays)): ?>
                <p class="text-muted text-center py-4">Még nincs rögzített ünnepnap <?= $year ?>-ben.</p>
            <?php else: ?>
            <table class="table table-hover mb-0" id="holidayTable">
                <thead class="table-light">
                    <tr>
                        <th>Dátum</th>
                        <th>Nap</th>
                        <th>Megnevezés</th>
                        <th class="text-end">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $hunDays = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];
                foreach ($holidays as $h):
                    $dow = (int)date('N', strtotime($h['holiday_date'])) - 1;
                ?>
                <tr data-id="<?= $h['id'] ?>">
                    <td class="fw-semibold"><?= htmlspecialchars($h['holiday_date']) ?></td>
                    <td><span class="badge bg-secondary"><?= $hunDays[$dow] ?></span></td>
                    <td>
                        <span class="holiday-name"><?= htmlspecialchars($h['name']) ?></span>
                        <input type="text" class="form-control form-control-sm holiday-name-edit d-none"
                               value="<?= htmlspecialchars($h['name']) ?>">
                    </td>
                    <td class="text-end">
                        <button class="btn btn-xs btn-outline-primary btn-sm me-1" style="font-size:.75rem"
                                onclick="editHoliday(this)">✏️ Szerk.</button>
                        <button class="btn btn-xs btn-outline-danger btn-sm" style="font-size:.75rem"
                                onclick="deleteHoliday(this)">🗑 Törlés</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($holidays)): ?>
    <table class="table table-hover mb-0 d-none" id="holidayTable"><tbody></tbody></table>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addHoliday() {
    const date = document.getElementById('newDate').value;
    const name = document.getElementById('newName').value.trim();
    const err  = document.getElementById('addError');
    if (!date || !name) { err.textContent = 'Töltsd ki mindkét mezőt!'; err.style.display='block'; return; }
    err.style.display = 'none';

    fetch('/admin/holidays/store', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `holiday_date=${date}&name=${encodeURIComponent(name)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#holidayTable tbody');
            const dow   = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];
            const d     = new Date(date);
            const dayName = dow[(d.getDay()+6)%7];
            const tr = document.createElement('tr');
            tr.dataset.id = data.id;
            tr.innerHTML = `
                <td class="fw-semibold">${data.date}</td>
                <td><span class="badge bg-secondary">${dayName}</span></td>
                <td>
                    <span class="holiday-name">${data.name}</span>
                    <input type="text" class="form-control form-control-sm holiday-name-edit d-none" value="${data.name}">
                </td>
                <td class="text-end">
                    <button class="btn btn-xs btn-outline-primary btn-sm me-1" style="font-size:.75rem" onclick="editHoliday(this)">✏️ Szerk.</button>
                    <button class="btn btn-xs btn-outline-danger btn-sm" style="font-size:.75rem" onclick="deleteHoliday(this)">🗑 Törlés</button>
                </td>`;
            tbody.appendChild(tr);
            document.querySelector('#holidayTable').classList.remove('d-none');
            document.getElementById('newName').value = '';
        } else { err.textContent = data.message; err.style.display='block'; }
    })
    .catch(() => { err.textContent = 'Hálózati hiba.'; err.style.display='block'; });
}

function deleteHoliday(btn) {
    if (!confirm('Biztosan törlöd ezt az ünnepnapot?')) return;
    const tr = btn.closest('tr');
    const id = tr.dataset.id;
    fetch('/admin/holidays/delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => { if (data.success) tr.remove(); else alert(data.message); })
    .catch(() => alert('Hálózati hiba.'));
}

function editHoliday(btn) {
    const tr       = btn.closest('tr');
    const nameSpan = tr.querySelector('.holiday-name');
    const nameInput= tr.querySelector('.holiday-name-edit');
    const isEditing= !nameInput.classList.contains('d-none');

    if (isEditing) {
        // Mentés
        const newName = nameInput.value.trim();
        if (!newName) return;
        fetch('/admin/holidays/update', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `id=${tr.dataset.id}&name=${encodeURIComponent(newName)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                nameSpan.textContent = newName;
                nameInput.classList.add('d-none');
                nameSpan.classList.remove('d-none');
                btn.textContent = '✏️ Szerk.';
                btn.classList.replace('btn-primary','btn-outline-primary');
            } else alert(data.message);
        })
        .catch(() => alert('Hálózati hiba.'));
    } else {
        nameInput.classList.remove('d-none');
        nameSpan.classList.add('d-none');
        nameInput.focus();
        btn.textContent = '💾 Mentés';
        btn.classList.replace('btn-outline-primary','btn-primary');
    }
}
</script>
</body>
</html>
