<?php
$leaveTypeLabels = ['vacation'=>'Szabadság','sick'=>'Táppénz','unpaid'=>'Fizetés nélküli','other'=>'Egyéb'];
$leaveTypeIcons  = ['vacation'=>'🌴','sick'=>'🤒','unpaid'=>'💼','other'=>'📋'];
$statusLabels    = ['pending'=>'Függő','approved'=>'Jóváhagyva','rejected'=>'Elutasítva'];
$statusClasses   = ['pending'=>'warning text-dark','approved'=>'success','rejected'=>'danger'];
$currentFilter   = $_GET['status'] ?? 'pending';
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$csrf    = User::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szabadságkérelmek – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background:#F8FAFC; }
        .table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:600; }
        .fleet-badge { display:inline-block; padding:.2em .55em; border-radius:5px; font-size:.75rem; font-weight:600; color:#fff; }
        .filter-tab { display:inline-block; padding:.35rem .85rem; border-radius:20px; font-size:.82rem; font-weight:600; text-decoration:none; color:#64748b; background:#fff; border:1px solid #e2e8f0; transition:.15s; }
        .filter-tab:hover { border-color:#3B82F6; color:#3B82F6; }
        .filter-tab--active { background:#3B82F6; color:#fff !important; border-color:#3B82F6; }
        .action-form { display:inline; }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 fw-bold mb-0">🌿 Szabadságkérelmek</h1>
        <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#adminStoreModal">
            + Távollét rögzítése
        </button>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach (['pending'=>'Függő','approved'=>'Jóváhagyva','rejected'=>'Elutasítva','all'=>'Összes'] as $val=>$label): ?>
                <a href="/admin/leaves?status=<?= $val ?>"
                   class="filter-tab <?= $currentFilter===$val ? 'filter-tab--active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2">
            ✅ <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2">
            ⚠️ <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($leaves)): ?>
        <div class="text-center py-5 text-muted"><p class="mb-0">Nincs megjeleníthető kérelem.</p></div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dolgozó</th><th>Flotta</th><th>Típus</th>
                        <th>Kezdés</th><th>Befejezés</th><th>Napok</th>
                        <th>Státusz</th><th>Benyújtva</th><th>Felülvizsgálta</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($leaves as $l):
                    $days = (int)((strtotime($l['end_date']) - strtotime($l['start_date'])) / 86400) + 1;
                ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($l['employee_name']) ?></td>
                        <td><?php if (!empty($l['fleet_name'])): ?>
                            <span class="fleet-badge bg-secondary"><?= htmlspecialchars($l['fleet_name']) ?></span>
                        <?php else: ?>–<?php endif; ?></td>
                        <td><?= ($leaveTypeIcons[$l['leave_type']] ?? '') . ' ' . ($leaveTypeLabels[$l['leave_type']] ?? $l['leave_type']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($l['start_date']) ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($l['end_date']) ?></td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold"><?= $days ?> nap</span></td>
                        <td><span class="badge bg-<?= $statusClasses[$l['status']] ?? 'secondary' ?>"><?= $statusLabels[$l['status']] ?? $l['status'] ?></span></td>
                        <td class="text-muted small"><?= date('m. d.', strtotime($l['created_at'])) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($l['reviewer_name'] ?? '–') ?></td>
                        <td>
                            <?php if ($l['status'] === 'pending'): ?>
                                <!-- Jóváhagyás -->
                                <form class="action-form" method="POST" action="/admin/leaves/review"
                                      onsubmit="return confirm('Jóváhagyod a kérelmet?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="leave_id"   value="<?= $l['id'] ?>">
                                    <input type="hidden" name="action"     value="approved">
                                    <input type="hidden" name="filter"     value="<?= $currentFilter ?>">
                                    <button type="submit" class="btn btn-success btn-sm">✅</button>
                                </form>
                                <!-- Elutasítás -->
                                <button type="button" class="btn btn-danger btn-sm ms-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectModal"
                                        data-id="<?= $l['id'] ?>"
                                        data-name="<?= htmlspecialchars($l['employee_name']) ?>">
                                    ❌
                                </button>
                            <?php else: ?>
                                <!-- Törlés elbírált kérelemnél -->
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteLeaveModal"
                                        data-id="<?= $l['id'] ?>"
                                        data-name="<?= htmlspecialchars($l['employee_name']) ?>"
                                        data-start="<?= htmlspecialchars($l['start_date']) ?>"
                                        data-end="<?= htmlspecialchars($l['end_date']) ?>"
                                        data-status="<?= htmlspecialchars($statusLabels[$l['status']] ?? $l['status']) ?>"
                                        title="Kérelem törlése">
                                    🗑
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Admin rögzítés modal -->
<div class="modal fade" id="adminStoreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/admin/leaves/store">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">📋 Távollét rögzítése</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Dolgozó</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">– Válassz dolgozót –</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Távollét típusa</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="">– Válassz típust –</option>
                            <option value="vacation">🌴 Szabadság</option>
                            <option value="sick">🤒 Táppénz</option>
                            <option value="unpaid">💼 Fizetés nélküli</option>
                            <option value="other">📋 Egyéb</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Kezdő dátum</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Befejező dátum</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Megjegyzés <span class="text-muted fw-normal">(opcionális)</span></label>
                        <input type="text" name="reason" class="form-control" maxlength="255" placeholder="Pl. nyári szabadság...">
                    </div>
                    <div class="alert alert-info py-2 small mb-0 mt-3">
                        ℹ️ Az admin által rögzített távollét azonnal <strong>jóváhagyott</strong> státuszba kerül, és az érintett műszakok automatikusan frissülnek.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Mégse</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Rögzítés</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Elutasítás modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/admin/leaves/review">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="leave_id"   id="rejectLeaveId" value="">
                <input type="hidden" name="action"     value="rejected">
                <input type="hidden" name="filter"     value="<?= $currentFilter ?>">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">❌ Kérelem elutasítása</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Dolgozó: <strong id="rejectEmployeeName"></strong>
                    </p>
                    <label for="adminNote" class="form-label fw-semibold small">
                        Indoklás <span class="text-muted fw-normal">(opcionális)</span>
                    </label>
                    <textarea id="adminNote" name="admin_note" class="form-control"
                              rows="3" maxlength="500"
                              placeholder="Pl. ütközik más szabadsággal..."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Mégse</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold">Elutasítás megerősítése</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Törlés megerősítő modal -->
<div class="modal fade" id="deleteLeaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/admin/leaves/delete">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="leave_id" id="deleteLeaveId" value="">
                <input type="hidden" name="filter"   value="<?= $currentFilter ?>">
                <div class="modal-header border-0" style="background:#dc2626;color:#fff;">
                    <h5 class="modal-title fw-bold">🗑 Kérelem törlése</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Biztosan törlöd az alábbi kérelmet?</p>
                    <div class="bg-light rounded p-3 small">
                        <div><strong>Dolgozó:</strong> <span id="deleteEmployeeName"></span></div>
                        <div><strong>Időszak:</strong> <span id="deleteLeaveRange"></span></div>
                        <div><strong>Státusz:</strong> <span id="deleteLeaveStatus"></span></div>
                    </div>
                    <div class="alert alert-warning py-2 small mt-3 mb-0">
                        ⚠️ Ha a kérelem <strong>jóváhagyott</strong> volt, az érintett műszakok státusza <strong>visszaáll aktívra</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Mégse</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold">Igen, törlöm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('rejectModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('rejectLeaveId').value = btn.dataset.id;
    document.getElementById('rejectEmployeeName').textContent = btn.dataset.name;
    document.getElementById('adminNote').value = '';
});

document.getElementById('deleteLeaveModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('deleteLeaveId').value = btn.dataset.id;
    document.getElementById('deleteEmployeeName').textContent = btn.dataset.name;
    document.getElementById('deleteLeaveRange').textContent = btn.dataset.start + ' – ' + btn.dataset.end;
    document.getElementById('deleteLeaveStatus').textContent = btn.dataset.status;
});
</script>
</body>
</html>
