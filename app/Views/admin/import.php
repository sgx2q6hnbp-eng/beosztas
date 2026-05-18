<?php
require_once BASE_PATH . '/app/Models/User.php';
$csrf        = User::generateCsrfToken();
$success     = $_SESSION['success'] ?? null;
$error       = $_SESSION['error']   ?? null;
$importResult = $_SESSION['import_result'] ?? null;
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['import_result']);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Import – Munkabeosztás</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-light">

<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <!-- Oldal fejléc -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="p-2 rounded-3 bg-primary bg-opacity-10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#3B82F6" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </div>
                <div>
                    <h1 class="h4 mb-0 fw-bold">Excel Beosztás Import</h1>
                    <p class="text-muted small mb-0">Töltsd fel a kitöltött munkabeosztás sablont</p>
                </div>
            </div>

            <!-- Sikeres import visszajelzés -->
            <?php if ($success): ?>
            <div class="alert alert-success border-0 d-flex gap-3 align-items-start mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="mt-1 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <strong>Sikeres import!</strong><br>
                    <small><?= htmlspecialchars($success) ?></small>

                    <?php if (!empty($importResult['errors'])): ?>
                    <details class="mt-2">
                        <summary class="text-warning small fw-semibold" style="cursor:pointer;">
                            ⚠ <?= count($importResult['errors']) ?> figyelmeztetés
                        </summary>
                        <ul class="small mt-1 mb-0">
                            <?php foreach ($importResult['errors'] as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hibaüzenet -->
            <?php if ($error): ?>
            <div class="alert alert-danger border-0 mb-4">
                <strong>Hiba:</strong> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Feltöltő kártya -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Fájl feltöltése</h5>

                    <form method="POST" action="/admin/import" enctype="multipart/form-data" id="importForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <!-- Drag & drop zóna -->
                        <div class="upload-zone mb-3" id="dropZone">
                            <input type="file" name="excel_file" id="excelFile"
                                   accept=".xlsx" class="upload-input" required>
                            <div class="upload-content text-center py-4 px-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#94A3B8" stroke-width="1.2" class="mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                                <p class="mb-1 fw-semibold text-dark" id="dropText">
                                    Húzd ide a fájlt, vagy kattints a tallózáshoz
                                </p>
                                <p class="text-muted small mb-0">Csak .xlsx formátum, max. 5 MB</p>
                            </div>
                        </div>

                        <!-- Figyelmeztetés doboz -->
                        <div class="alert alert-warning border-0 py-2 mb-3 small">
                            <strong>⚠ Fontos:</strong> A manuálisan rögzített táppénzek,
                            szabadságok és hiányzások <strong>nem kerülnek felülírásra</strong>
                            az import során — azok megmaradnak.
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold" id="submitBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="me-1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                                Feltöltés és import indítása
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sablon letöltés -->
            <div class="card border-0 shadow-sm mb-4 border-start border-4 border-primary">
                <div class="card-body d-flex align-items-center justify-content-between gap-3 p-3">
                    <div>
                        <p class="mb-0 fw-semibold small">Nincs még sablonja?</p>
                        <p class="mb-0 text-muted small">Töltsd le az aktuális Excel sablont</p>
                    </div>
                    <a href="/admin/import/template" class="btn btn-outline-primary btn-sm flex-shrink-0">
                        Sablon letöltése
                    </a>
                </div>
            </div>

            <!-- Import előzmények -->
            <?php if (!empty($lastImports)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold">Utolsó importok</h6>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($lastImports as $log): ?>
                    <?php $val = json_decode($log['new_value'], true); ?>
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 small fw-semibold">
                                    <?= htmlspecialchars($val['file'] ?? 'ismeretlen fájl') ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    <?= htmlspecialchars($log['changed_by']) ?> &bull;
                                    <?= date('Y.m.d H:i', strtotime($log['changed_at'])) ?>
                                </p>
                            </div>
                            <div class="text-end small">
                                <span class="text-success">+<?= (int)($val['inserted'] ?? 0) ?></span>
                                <span class="text-muted mx-1">/</span>
                                <span class="text-primary">~<?= (int)($val['updated'] ?? 0) ?></span>
                                <span class="text-muted mx-1">/</span>
                                <span class="text-warning"><?= (int)($val['skipped'] ?? 0) ?> skip</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
.upload-zone {
    position: relative;
    border: 2px dashed #CBD5E1;
    border-radius: 12px;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.upload-zone:hover,
.upload-zone.dragover {
    border-color: #3B82F6;
    background: #EFF6FF;
}
.upload-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('excelFile');
const dropText  = document.getElementById('dropText');
const submitBtn = document.getElementById('submitBtn');

// Fájl kiválasztva → név megjelenítése
fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) {
        dropText.textContent = '✓ ' + fileInput.files[0].name;
        dropZone.style.borderColor = '#10B981';
        dropZone.style.background  = '#F0FDF4';
    }
});

// Drag & drop vizuális visszajelzés
['dragenter','dragover'].forEach(e =>
    dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('dragover'); })
);
['dragleave','drop'].forEach(e =>
    dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'))
);

// Betöltés visszajelzés
document.getElementById('importForm').addEventListener('submit', () => {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Feldolgozás...';
});
</script>
</body>
</html>
