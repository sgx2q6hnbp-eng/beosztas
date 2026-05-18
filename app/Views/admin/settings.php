<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beállítások</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>body{background:#F8FAFC;}</style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4" style="max-width:600px;">
    <h1 class="h4 fw-bold mb-4">⚙️ Beállítások</h1>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="POST" action="/admin/settings">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">📧 E-mail értesítések</h5>

                <?php
                $vals = [];
                foreach ($settings as $s) $vals[$s['key_name']] = $s['value'];
                $globalOn = ($vals['mail_enabled'] ?? '0') === '1';
                ?>

                <!-- Globális kapcsoló -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded mb-3"
                     style="background:<?= $globalOn ? '#dcfce7' : '#fee2e2' ?>;">
                    <div>
                        <div class="fw-bold">Globális e-mail értesítések</div>
                        <div class="small text-muted">Ha ki van kapcsolva, semmilyen e-mail nem megy ki</div>
                    </div>
                    <div class="form-check form-switch ms-3 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="mail_enabled" name="mail_enabled" value="1"
                               style="width:3rem;height:1.5rem;"
                               <?= $globalOn ? 'checked' : '' ?>
                               onchange="toggleSubSettings(this.checked)">
                    </div>
                </div>

                <!-- Típusonkénti kapcsolók -->
                <div id="subSettings" <?= !$globalOn ? 'style="opacity:.4;pointer-events:none;"' : '' ?>>
                    <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Típusonként</div>
                    <?php
                    $items = [
                        'mail_leave_new_admin'          => ['label' => 'Új szabadságkérelem', 'desc' => 'Admin kap értesítést, ha dolgozó kérelmet ad be'],
                        'mail_leave_reviewed_employee'  => ['label' => 'Szabadságkérelem elbírálva', 'desc' => 'Dolgozó kap értesítést az elbírálás eredményéről'],
                        'mail_swap_new'                 => ['label' => 'Új műszakcsere kérelem', 'desc' => 'Célszemély kap értesítést új csere kérelemről'],
                        'mail_swap_reviewed'            => ['label' => 'Műszakcsere elbírálva', 'desc' => 'Kérelmező kap értesítést az elbírálás eredményéről'],
                    ];
                    foreach ($items as $key => $item):
                        $checked = ($vals[$key] ?? '0') === '1';
                    ?>
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div>
                            <div class="fw-semibold" style="font-size:.9rem;"><?= $item['label'] ?></div>
                            <div class="small text-muted"><?= $item['desc'] ?></div>
                        </div>
                        <div class="form-check form-switch ms-3 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="<?= $key ?>" value="1"
                                   <?= $checked ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">💾 Mentés</button>
        <a href="/dashboard" class="btn btn-outline-secondary ms-2">Vissza</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSubSettings(enabled) {
    const sub = document.getElementById('subSettings');
    sub.style.opacity = enabled ? '1' : '.4';
    sub.style.pointerEvents = enabled ? '' : 'none';
    const card = document.querySelector('.d-flex.align-items-center.justify-content-between.p-3');
    if (card) card.style.background = enabled ? '#dcfce7' : '#fee2e2';
}
</script>
</body>
</html>
