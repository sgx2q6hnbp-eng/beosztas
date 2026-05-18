<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Napló – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body { background:#F8FAFC; }
        .table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:600; }
        .badge-create  { background:#dcfce7; color:#166534; }
        .badge-update  { background:#dbeafe; color:#1e40af; }
        .badge-delete  { background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>
<?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">📋 Változásnapló</h1>
        <span class="text-muted small"><?= number_format($total) ?> bejegyzés</span>
    </div>
    <?php if (empty($logs)): ?>
        <div class="text-center py-5 text-muted">
            <p>Nincs naplóbejegyzés.</p>
        </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Időpont</th>
                        <th>Módosította</th>
                        <th>Típus</th>
                        <th>Műszak ID</th>
                        <th>Előző érték</th>
                        <th>Új érték</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap text-muted small"><?= date('Y. m. d. H:i', strtotime($log['changed_at'])) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($log['changed_by_name'] ?? '–') ?></td>
                        <td>
                            <span class="badge badge-<?= $log['change_type'] ?>">
                                <?= match($log['change_type']) {
                                    'create' => '➕ Létrehozás',
                                    'update' => '✏️ Módosítás',
                                    'delete' => '🗑️ Törlés',
                                    default  => $log['change_type']
                                } ?>
                            </span>
                        </td>
                        <td><code>#<?= $log['shift_id'] ?></code></td>
                        <td>
                            <?php if ($log['old_value']): ?>
                                <small class="text-muted font-monospace"><?= htmlspecialchars(mb_strimwidth($log['old_value'], 0, 60, '…')) ?></small>
                            <?php else: ?>–<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['new_value']): ?>
                                <small class="text-muted font-monospace"><?= htmlspecialchars(mb_strimwidth($log['new_value'], 0, 60, '…')) ?></small>
                            <?php else: ?>–<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-transparent d-flex justify-content-center gap-1 py-2">
            <?php for ($p=1; $p<=$totalPages; $p++): ?>
                <a href="/admin/logs?page=<?= $p ?>"
                   class="btn btn-sm <?= $p===$page ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
