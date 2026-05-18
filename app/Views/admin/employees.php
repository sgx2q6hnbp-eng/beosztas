<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolgozok - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background: #F8FAFC; }
        .table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; }
        .fleet-badge { display: inline-block; padding: .25em .65em; border-radius: 6px; font-size: .78rem; font-weight: 600; color: #fff; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .modal-header { background: #01696F; color: #fff; }
        .modal-header .btn-close { filter: invert(1); }
        .emp-number { font-family: monospace; font-size: .85rem; color: #0C4E54; font-weight: 700; }
        .password-wrapper { position: relative; }
        .password-wrapper .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 0; }
        .modal-header-edit { background: #0C4E54; color: #fff; }
        .modal-header-delete { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4">

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">Dolgozok</h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><?= count($employees) ?> fo</span>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">+ Uj dolgozo</button>
        </div>
    </div>

    <?php if (empty($employees)): ?>
        <div class="text-center py-5 text-muted">
            <p>Nincs meg dolgozo felvive.</p>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">+ Elso dolgozo hozzaadasa</button>
        </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Torzsszam</th>
                        <th>Nev</th>
                        <th>E-mail</th>
                        <th>Telefon</th>
                        <th>Flotta</th>
                        <th>Szerepkor</th>
                        <th>Statusz</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><span class="emp-number"><?= htmlspecialchars($emp['employee_number'] ?? '-') ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($emp['name']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($emp['email']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($emp['phone'] ?? '-') ?></td>
                        <td><?php if ($emp['fleet_name']): ?>
                            <span class="fleet-badge" style="background:<?= htmlspecialchars($emp['color'] ?? '#64748b') ?>"><?= htmlspecialchars($emp['fleet_name']) ?></span>
                        <?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                        <td><span class="badge <?= $emp['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>"><?= htmlspecialchars($emp['role']) ?></span></td>
                        <td><?php if ($emp['is_active']): ?>
                            <span class="status-dot bg-success me-1"></span><span class="small text-success">Aktiv</span>
                        <?php else: ?>
                            <span class="status-dot bg-secondary me-1"></span><span class="small text-muted">Inaktiv</span>
                        <?php endif; ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode($emp)) ?>)">
                                Szerkeszt
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="openDeleteModal(<?= (int)$emp['id'] ?>, '<?= htmlspecialchars(addslashes($emp['name'])) ?>')">
                                Torles
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Uj dolgozo modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Uj dolgozo felvitele</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/admin/employees/store" novalidate autocomplete="off" id="addEmployeeForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Torzsszam <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" name="employee_number" placeholder="pl. T-0042" required maxlength="20">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Teljes nev <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="pl. Kovacs Janos" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail cim <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="kovacs@pelda.hu" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telefon</label>
                            <input type="tel" class="form-control" name="phone" placeholder="+36 30 123 4567" maxlength="20">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Jelszo <span class="text-danger">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control pe-5" id="add_password" name="password" placeholder="Min. 4 karakter" required minlength="4">
                                <button type="button" class="toggle-pw" onclick="togglePw('add_password', this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Flotta</label>
                            <select class="form-select" name="fleet_id">
                                <option value="">- Nincs -</option>
                                <?php foreach ($fleets as $fleet): ?>
                                    <option value="<?= (int)$fleet['id'] ?>"><?= htmlspecialchars($fleet['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Szerepkor <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="employee" selected>Dolgozo</option>
                                <option value="admin">Adminisztrator</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" checked>
                                <label class="form-check-label">Aktiv felhasznalo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Megse</button>
                    <button type="submit" class="btn btn-success">Dolgozo mentese</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Szerkesztes modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-edit">
                <h5 class="modal-title">Dolgozo szerkesztese</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <form method="POST" action="/admin/employees/update" novalidate autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Torzsszam <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" name="employee_number" id="edit_employee_number" required maxlength="20">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Teljes nev <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail cim <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="edit_email" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telefon</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" maxlength="20">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Uj jelszo <span class="text-muted fw-normal">(ures = nem valtozik)</span></label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control pe-5" id="edit_password" name="password" placeholder="Min. 4 karakter">
                                <button type="button" class="toggle-pw" onclick="togglePw('edit_password', this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Flotta</label>
                            <select class="form-select" name="fleet_id" id="edit_fleet_id">
                                <option value="">- Nincs -</option>
                                <?php foreach ($fleets as $fleet): ?>
                                    <option value="<?= (int)$fleet['id'] ?>"><?= htmlspecialchars($fleet['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Szerepkor <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="employee">Dolgozo</option>
                                <option value="admin">Adminisztrator</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                <label class="form-check-label">Aktiv felhasznalo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Megse</button>
                    <button type="submit" class="btn btn-primary">Valtozasok mentese</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Torles modal -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-delete">
                <h5 class="modal-title">Dolgozo torlese</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-1">Biztosan inaktiválod ezt a dolgozot?</p>
                <p class="fw-bold" id="delete_name_display"></p>
                <p class="text-muted small">Az adatok megmaradnak, de a dolgozo nem tud bejelentkezni.</p>
            </div>
            <form method="POST" action="/admin/employees/delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Megse</button>
                    <button type="submit" class="btn btn-danger">Inaktival</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw(id, btn) {
    const i = document.getElementById(id);
    const show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    btn.innerHTML = show
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

function openEditModal(emp) {
    document.getElementById('edit_id').value             = emp.id;
    document.getElementById('edit_employee_number').value = emp.employee_number || '';
    document.getElementById('edit_name').value           = emp.name || '';
    document.getElementById('edit_email').value          = emp.email || '';
    document.getElementById('edit_phone').value          = emp.phone || '';
    document.getElementById('edit_fleet_id').value       = emp.fleet_id || '';
    document.getElementById('edit_role').value           = emp.role || 'employee';
    document.getElementById('edit_is_active').checked   = emp.is_active == 1;
    document.getElementById('edit_password').value      = '';
    new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
}

function openDeleteModal(id, name) {
    document.getElementById('delete_id').value          = id;
    document.getElementById('delete_name_display').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteEmployeeModal')).show();
}

document.getElementById('addEmployeeModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('addEmployeeForm').reset();
});
</script>
</body>
</html>