<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Csere kérelmek - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background: #F8FAFC; }
        .table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; }
        .fleet-badge { display: inline-block; padding: .2em .6em; border-radius: 5px; font-size: .75rem; font-weight: 600; color: #fff; }
        .swap-arrow { color: #01696F; font-size: 1.2rem; font-weight: 700; }
        .status-pending  { background: #FEF9C3; color: #854D0E; }
        .status-accepted { background: #DCFCE7; color: #166534; }
        .status-approved { background: #D1FAE5; color: #065F46; }
        .status-rejected { background: #FEE2E2; color: #991B1B; }
        .status-cancelled{ background: #F1F5F9; color: #64748B; }
        .shift-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: .5rem .75rem; font-size: .85rem; }
        .shift-box .date { font-weight: 700; color: #0C4E54; }
        .shift-box .time { color: #64748b; font-size: .78rem; }
        .section-title { font-size: .7rem; text-transform: uppercase; letter-spacing: .1em; color: #94A3B8; font-weight: 700; margin-bottom: .5rem; }
        .modal-header-approve { background: #01696F; color: #fff; }
        .modal-header-reject  { background: #dc3545; color: #fff; }
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Csere kérelmek</h1>
        <?php if (count($pending) > 0): ?>
            <span class="badge bg-warning text-dark fs-6"><?= count($pending) ?> függő kérelem</span>
        <?php endif; ?>
    </div>

    <!-- FÜGGŐ KÉRELMEK -->
    <h2 class="h6 section-title">Jóváhagyásra vár</h2>

    <?php if (empty($pending)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center text-muted py-5">
                <p class="mb-0">Nincs függő csere kérelem.</p>
            </div>
        </div>
    <?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ($pending as $s): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                        <!-- Kérelmező műszakja -->
                        <div class="flex-grow-1">
                            <div class="section-title">Kérelmező</div>
                            <div class="fw-semibold mb-1"><?= htmlspecialchars($s['requester_name']) ?></div>
                            <div class="shift-box">
                                <?php if ($s['req_fleet']): ?>
                                    <span class="fleet-badge mb-1" style="background:<?= htmlspecialchars($s['req_color'] ?? '#64748b') ?>"><?= htmlspecialchars($s['req_fleet']) ?></span>
                                <?php endif; ?>
                                <div class="date"><?= htmlspecialchars($s['req_date']) ?></div>
                                <div class="time"><?= htmlspecialchars($s['req_start']) ?> – <?= htmlspecialchars($s['req_end']) ?></div>
                            </div>
                        </div>

                        <div class="swap-arrow px-2">&#8644;</div>

                        <!-- Célpont műszakja -->
                        <div class="flex-grow-1">
                            <div class="section-title">Célpont</div>
                            <div class="fw-semibold mb-1"><?= htmlspecialchars($s['target_name']) ?></div>
                            <div class="shift-box">
                                <?php if ($s['tgt_fleet']): ?>
                                    <span class="fleet-badge mb-1" style="background:<?= htmlspecialchars($s['tgt_color'] ?? '#64748b') ?>"><?= htmlspecialchars($s['tgt_fleet']) ?></span>
                                <?php endif; ?>
                                <div class="date"><?= htmlspecialchars($s['tgt_date']) ?></div>
                                <div class="time"><?= htmlspecialchars($s['tgt_start']) ?> – <?= htmlspecialchars($s['tgt_end']) ?></div>
                            </div>
                        </div>

                        <!-- Státusz + üzenet + gombok -->
                        <div class="text-end" style="min-width:180px">
                            <span class="badge status-<?= $s['status'] ?> mb-2 d-inline-block px-3 py-2">
                                <?= $s['status'] === 'pending' ? 'Függő' : 'Elfogadva (dolgozo)' ?>
                            </span>
                            <?php if ($s['message']): ?>
                                <div class="text-muted small mb-2 fst-italic">"<?= htmlspecialchars($s['message']) ?>"</div>
                            <?php endif; ?>
                            <div class="text-muted small mb-3"><?= date('Y.m.d H:i', strtotime($s['created_at'])) ?></div>
                            <div class="d-flex gap-2 justify-content-end">
                                <button class="btn btn-sm btn-success"
                                    onclick="confirmApprove(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes($s['requester_name'])) ?>', '<?= htmlspecialchars(addslashes($s['target_name'])) ?>')">
                                    Jovahagyas
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmReject(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes($s['requester_name'])) ?>')">
                                    Elutasitas
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- LEZÁRT KÉRELMEK -->
    <?php if (!empty($closed)): ?>
    <h2 class="h6 section-title mt-4">Lezart kérelmek (utolso 50)</h2>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kérelmező</th>
                        <th>Celpontja</th>
                        <th>Datum</th>
                        <th>Státusz</th>
                        <th>Elbírálta</th>
                        <th>Elbírálás ideje</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($closed as $s): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($s['requester_name']) ?></td>
                        <td><?= htmlspecialchars($s['target_name']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($s['req_date']) ?> &#8644; <?= htmlspecialchars($s['tgt_date']) ?></td>
                        <td>
                            <span class="badge status-<?= $s['status'] ?> px-2 py-1">
                                <?php
                                echo match($s['status']) {
                                    'approved'  => 'Jovahagyva',
                                    'rejected'  => 'Elutasitva',
                                    'cancelled' => 'Torolve',
                                    default     => $s['status']
                                };
                                ?>
                            </span>
                        </td>
                        <td class="small"><?= htmlspecialchars($s['reviewed_by_name'] ?? '-') ?></td>
                        <td class="small text-muted"><?= $s['reviewed_at'] ? date('Y.m.d H:i', strtotime($s['reviewed_at'])) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Jóváhagyás modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-approve">
                <h5 class="modal-title">Csere jovahagyasa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-1">Biztosan jovahagyod a cseret?</p>
                <p class="fw-bold" id="approve_names"></p>
                <p class="text-muted small">A ket dolgozo muszakjai felcserelodnek.</p>
            </div>
            <form method="POST" action="/admin/swaps/approve">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="id" id="approve_id">
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Megse</button>
                    <button type="submit" class="btn btn-success">Jovahagyas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Elutasítás modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-reject">
                <h5 class="modal-title">Csere elutasitasa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-1">Biztosan elutasitod ezt a csere kérelmet?</p>
                <p class="fw-bold" id="reject_name"></p>
                <p class="text-muted small">A muszakok visszaallnak aktiv statusra.</p>
            </div>
            <form method="POST" action="/admin/swaps/reject">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="id" id="reject_id">
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Megse</button>
                    <button type="submit" class="btn btn-danger">Elutasitas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmApprove(id, req, tgt) {
    document.getElementById('approve_id').value = id;
    document.getElementById('approve_names').textContent = req + ' ↔ ' + tgt;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function confirmReject(id, name) {
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_name').textContent = name + ' kérelme';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
</body>
</html>