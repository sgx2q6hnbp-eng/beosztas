<?php
$leaveTypeLabels = ['vacation'=>'Szabadság','sick'=>'Táppénz','unpaid'=>'Fizetés nélküli','other'=>'Egyéb'];
$leaveTypeIcons  = ['vacation'=>'🌴','sick'=>'🤒','unpaid'=>'💼','other'=>'📋'];
$statusLabels    = ['pending'=>'Függő','approved'=>'Jóváhagyva','rejected'=>'Elutasítva'];
$statusClasses   = ['pending'=>'warning text-dark','approved'=>'success','rejected'=>'danger'];
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$csrf    = User::generateCsrfToken();
$today   = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+1 year'));
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szabadságkérelem</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background:#F8FAFC; }
        .table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:600; }
        .type-card { border:2px solid #e2e8f0; border-radius:10px; padding:1rem; cursor:pointer; transition:.15s; text-align:center; }
        .type-card:hover { border-color:#3B82F6; background:#EFF6FF; }
        .type-card input[type=radio]:checked ~ & { border-color:#3B82F6; }
        .type-label input:checked + .type-card { border-color:#3B82F6; background:#EFF6FF; }
        .days-badge { font-size:1.5rem; font-weight:700; color:#3B82F6; }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4" style="max-width:760px">

    <div class="mb-4">
        <h1 class="h4 fw-bold mb-0">🌿 Szabadságkérelem</h1>
        <p class="text-muted small mb-0">Adj be új kérelmet vagy tekintsd meg a korábbiakat</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            ✅ <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            ⚠️ <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Kérelem form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-3">Új kérelem benyújtása</h2>
            <form method="POST" action="/leave/store" id="leaveForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <!-- Típus választó -->
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Típus</label>
                    <div class="row g-2">
                        <?php foreach ($leaveTypeLabels as $val => $label): ?>
                        <div class="col-6 col-sm-3">
                            <label class="type-label d-block">
                                <input type="radio" name="leave_type" value="<?= $val ?>"
                                       class="d-none" <?= $val==='vacation'?'checked':'' ?>>
                                <div class="type-card">
                                    <div class="fs-4 mb-1"><?= $leaveTypeIcons[$val] ?></div>
                                    <div class="small fw-semibold"><?= $label ?></div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dátumok -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label for="start_date" class="form-label fw-semibold small">Kezdő dátum</label>
                        <input type="date" id="start_date" name="start_date"
                               class="form-control" required
                               min="<?= $today ?>" max="<?= $maxDate ?>"
                               value="<?= $today ?>">
                    </div>
                    <div class="col-sm-5">
                        <label for="end_date" class="form-label fw-semibold small">Záró dátum</label>
                        <input type="date" id="end_date" name="end_date"
                               class="form-control" required
                               min="<?= $today ?>" max="<?= $maxDate ?>"
                               value="<?= $today ?>">
                    </div>
                    <div class="col-sm-2 d-flex flex-column justify-content-end">
                        <div class="text-center pb-1">
                            <div class="days-badge" id="daysCount">1</div>
                            <div class="text-muted" style="font-size:.72rem;">nap</div>
                        </div>
                    </div>
                </div>

                <!-- Indoklás -->
                <div class="mb-4">
                    <label for="reason" class="form-label fw-semibold small">
                        Indoklás <span class="text-muted fw-normal">(opcionális)</span>
                    </label>
                    <textarea id="reason" name="reason" class="form-control"
                              rows="2" maxlength="500"
                              placeholder="Pl. nyaralás, orvosi vizsgálat..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary fw-semibold px-4">
                    📨 Kérelem beküldése
                </button>
            </form>
        </div>
    </div>

    <!-- Korábbi kérelmek -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Korábbi kérelmeim</h2>
            <?php if (empty($leaves)): ?>
                <div class="text-center py-4 text-muted">
                    <div class="fs-1 mb-2">🌿</div>
                    <p class="mb-0 small">Még nem adtál be kérelmet.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Típus</th><th>Kezdés</th><th>Befejezés</th><th>Napok</th><th>Státusz</th><th>Megjegyzés</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leaves as $l):
                        $days = (int)((strtotime($l['end_date']) - strtotime($l['start_date'])) / 86400) + 1;
                    ?>
                        <tr>
                            <td><?= ($leaveTypeIcons[$l['leave_type']] ?? '') . ' ' . ($leaveTypeLabels[$l['leave_type']] ?? $l['leave_type']) ?></td>
                            <td class="text-nowrap"><?= htmlspecialchars($l['start_date']) ?></td>
                            <td class="text-nowrap"><?= htmlspecialchars($l['end_date']) ?></td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold"><?= $days ?> nap</span></td>
                            <td><span class="badge bg-<?= $statusClasses[$l['status']] ?? 'secondary' ?>"><?= $statusLabels[$l['status']] ?? $l['status'] ?></span></td>
                            <td class="text-muted small"><?= $l['admin_note'] ? htmlspecialchars($l['admin_note']) : '–' ?></td>
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
// Típus kártya kijelölés
document.querySelectorAll('.type-label input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.type-card').forEach(c => {
            c.style.borderColor = '#e2e8f0';
            c.style.background  = '';
        });
        if (radio.checked) {
            const card = radio.nextElementSibling;
            card.style.borderColor = '#3B82F6';
            card.style.background  = '#EFF6FF';
        }
    });
    // Kezdeti állapot
    if (radio.checked) {
        radio.nextElementSibling.style.borderColor = '#3B82F6';
        radio.nextElementSibling.style.background  = '#EFF6FF';
    }
});

// Napok számítása
function calcDays() {
    const s = document.getElementById('start_date').value;
    const e = document.getElementById('end_date').value;
    if (s && e && e >= s) {
        const diff = (new Date(e) - new Date(s)) / 86400000 + 1;
        document.getElementById('daysCount').textContent = diff;
    }
}
document.getElementById('start_date').addEventListener('change', function() {
    const ed = document.getElementById('end_date');
    if (ed.value < this.value) ed.value = this.value;
    calcDays();
});
document.getElementById('end_date').addEventListener('change', calcDays);
calcDays();
</script>
</body>
</html>
