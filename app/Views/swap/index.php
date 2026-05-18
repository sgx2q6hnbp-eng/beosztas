<?php
$statusLabels  = ['pending'=>'Függő','accepted'=>'Elfogadva','rejected'=>'Elutasítva','approved'=>'Jóváhagyva','cancelled'=>'Visszavonva'];
$statusClasses = ['pending'=>'warning text-dark','accepted'=>'info','rejected'=>'danger','approved'=>'success','cancelled'=>'secondary'];
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$csrf    = User::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Műszakcsere</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background:#F8FAFC; }
        .table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:600; }
        .shift-select { border:2px solid #e2e8f0; border-radius:8px; padding:.6rem .9rem; cursor:pointer; transition:.15s; }
        .shift-select:hover { border-color:#8B5CF6; background:#F5F3FF; }
        .shift-select.selected { border-color:#8B5CF6; background:#EDE9FE; }
        .fleet-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:5px; }
        .section-title { font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:.75rem; }
        .incoming-card { border-left:4px solid #8B5CF6; background:#F5F3FF; border-radius:0 8px 8px 0; padding:1rem 1.25rem; }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4" style="max-width:860px">

    <div class="mb-4">
        <h1 class="h4 fw-bold mb-0">🔄 Műszakcsere</h1>
        <p class="text-muted small mb-0">Cseréld el műszakodat egy kollégáéval</p>
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

    <!-- Bejövő kérelmek -->
    <?php if (!empty($incomingSwaps)): ?>
    <div class="mb-4">
        <div class="section-title">📬 Bejövő csere kérelmek <span class="badge bg-danger"><?= count($incomingSwaps) ?></span></div>
        <?php foreach ($incomingSwaps as $sw): ?>
        <div class="incoming-card mb-2">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span class="fw-semibold"><?= htmlspecialchars($sw['requester_name']) ?></span>
                    <span class="text-muted small"> cserét kér:</span><br>
                    <small>
                        Az ő műszakja: <strong><?= htmlspecialchars($sw['their_date']) ?></strong>
                        <?= substr($sw['their_start'],0,5) ?>–<?= substr($sw['their_end'],0,5) ?>
                        &nbsp;↔️&nbsp;
                        A te műszakod: <strong><?= htmlspecialchars($sw['my_date']) ?></strong>
                        <?= substr($sw['my_start'],0,5) ?>–<?= substr($sw['my_end'],0,5) ?>
                    </small>
                    <?php if ($sw['message']): ?>
                        <br><small class="text-muted fst-italic">"<?= htmlspecialchars($sw['message']) ?>"</small>
                    <?php endif; ?>
                </div>
                <?php if ($sw['status'] === 'pending'): ?>
                <div class="d-flex gap-2 mt-2 mt-sm-0">
                    <form method="POST" action="/swap/accept">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= (int)$sw['id'] ?>">
                        <button class="btn btn-sm btn-success">✓ Elfogadom</button>
                    </form>
                    <form method="POST" action="/swap/decline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= (int)$sw['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">✗ Elutasítom</button>
                    </form>
                </div>
                <?php else: ?>
                <?php if ($sw['status'] === 'pending'): ?>
                <div class="d-flex gap-2 mt-2 mt-sm-0">
                    <form method="POST" action="/swap/accept">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= (int)$sw['id'] ?>">
                        <button class="btn btn-sm btn-success">&#10003; Elfogadom</button>
                    </form>
                    <form method="POST" action="/swap/decline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id" value="<?= (int)$sw['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">&#10007; Elutasítom</button>
                    </form>
                </div>
                <?php else: ?>
                <span class="badge bg-<?= $statusClasses[$sw['status']] ?>"><?= $statusLabels[$sw['status']] ?></span>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Új csere kérelem form -->
    <?php if (!empty($myShifts) && !empty($otherShifts)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-3">Új csere kérelem</h2>
            <form method="POST" action="/swap/store" id="swapForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="requester_shift_id" id="requesterShiftId" value="">
                <input type="hidden" name="target_shift_id"    id="targetShiftId"    value="">

                <div class="row g-4">
                    <!-- Saját műszakok -->
                    <div class="col-md-6">
                        <div class="fw-semibold small mb-2">1️⃣ Melyik műszakodat adod?</div>
                        <div style="max-height:280px;overflow-y:auto;" class="d-flex flex-column gap-2">
                        <?php foreach ($myShifts as $s): ?>
                            <div class="shift-select" data-id="<?= $s['id'] ?>" data-side="mine"
                                 onclick="selectShift(this,'mine')">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="fleet-dot" style="background:<?= htmlspecialchars($s['color']??'#64748b') ?>"></span>
                                    <span class="fw-semibold small"><?= htmlspecialchars($s['shift_date']) ?></span>
                                </div>
                                <small class="text-muted"><?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?>
                                    <?php if ($s['location']): ?> · <?= htmlspecialchars($s['location']) ?><?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Másik dolgozó műszakai -->
                    <div class="col-md-6">
                        <div class="fw-semibold small mb-2">2️⃣ Melyik műszakot kéred?</div>
                        <div style="max-height:280px;overflow-y:auto;" class="d-flex flex-column gap-2">
                        <?php foreach ($otherShifts as $s): ?>
                            <div class="shift-select" data-id="<?= $s['id'] ?>" data-side="theirs"
                                 onclick="selectShift(this,'theirs')">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="fleet-dot" style="background:<?= htmlspecialchars($s['color']??'#64748b') ?>"></span>
                                    <span class="fw-semibold small"><?= htmlspecialchars($s['employee_name']) ?></span>
                                    <span class="text-muted small ms-1"><?= htmlspecialchars($s['shift_date']) ?></span>
                                </div>
                                <small class="text-muted"><?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?>
                                    <?php if ($s['location']): ?> · <?= htmlspecialchars($s['location']) ?><?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Üzenet -->
                <div class="mt-3 mb-3">
                    <label for="message" class="form-label fw-semibold small">
                        Üzenet <span class="text-muted fw-normal">(opcionális)</span>
                    </label>
                    <input type="text" id="message" name="message" class="form-control"
                           maxlength="255" placeholder="Pl. nyaralás miatt kérem a cserét...">
                </div>

                <button type="submit" class="btn btn-purple fw-semibold px-4"
                        id="submitBtn" disabled
                        style="background:#8B5CF6;color:#fff;border:none;">
                    🔄 Csere kérelem küldése
                </button>
                <small class="text-muted ms-2" id="selectHint">Válassz ki mindkét műszakot!</small>
            </form>
        </div>
    </div>
    <?php elseif (empty($myShifts)): ?>
    <div class="alert alert-info">Nincs jövőbeli aktív műszakod, amelyet cserélhetnél.</div>
    <?php endif; ?>

    <!-- Saját korábbi kérelmek -->
    <?php if (!empty($mySwaps)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="section-title">Korábbi csere kérelmeim</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Kivel</th><th>Az én műszakom</th><th>Az ő műszakja</th><th>Státusz</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mySwaps as $sw): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($sw['target_name']) ?></td>
                            <td class="text-nowrap small"><?= htmlspecialchars($sw['my_date']) ?> <?= substr($sw['my_start'],0,5) ?>–<?= substr($sw['my_end'],0,5) ?></td>
                            <td class="text-nowrap small"><?= htmlspecialchars($sw['their_date']) ?> <?= substr($sw['their_start'],0,5) ?>–<?= substr($sw['their_end'],0,5) ?></td>
                            <td><span class="badge bg-<?= $statusClasses[$sw['status']] ?>"><?= $statusLabels[$sw['status']] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectShift(el, side) {
    document.querySelectorAll('[data-side="' + side + '"]').forEach(e => {
        e.classList.remove('selected');
    });
    el.classList.add('selected');
    if (side === 'mine') {
        document.getElementById('requesterShiftId').value = el.dataset.id;
    } else {
        document.getElementById('targetShiftId').value = el.dataset.id;
    }
    checkReady();
}

function checkReady() {
    const mine   = document.getElementById('requesterShiftId').value;
    const theirs = document.getElementById('targetShiftId').value;
    const btn    = document.getElementById('submitBtn');
    const hint   = document.getElementById('selectHint');
    if (mine && theirs) {
        btn.disabled = false;
        hint.textContent = '✅ Mindkét műszak kiválasztva';
        hint.className = 'text-success ms-2 small';
    } else {
        btn.disabled = true;
        hint.textContent = 'Válassz ki mindkét műszakot!';
        hint.className = 'text-muted ms-2 small';
    }
}
</script>
</body>
</html>
