<?php
/**
 * admin/ab-tests.php — Dashboard A/B testing sederhana:
 * daftar test + impresi/konversi per varian.
 */
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
cekLogin();

$pageTitle = 'A/B Testing';
$tests = db()->query("SELECT t.*, (SELECT COUNT(*) FROM ab_variants v WHERE v.test_name = t.test_name) AS variant_count FROM ab_tests t ORDER BY t.test_name")->fetchAll();
require_once 'includes/admin-header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-vector-pen me-2"></i>A/B Testing</h4>
    </div>

    <?php foreach ($tests as $t): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-semibold mb-0"><code><?= e($t['test_name']) ?></code></h6>
                <span class="badge <?= $t['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $t['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
            </div>
            <?php $results = abResults($t['test_name']); ?>
            <?php if (empty($results)): ?>
            <p class="text-muted small mb-0">Belum ada impresi.</p>
            <?php else: ?>
            <table class="table table-sm table-borderless mb-0" style="max-width: 480px;">
                <thead><tr><th>Varian</th><th>Impresi</th><th>Konversi</th><th>Rate</th></tr></thead>
                <tbody>
                <?php foreach ($results as $r): $rate = $r['impressions'] > 0 ? round(100 * $r['conversions'] / $r['impressions'], 1) : 0; ?>
                <tr>
                    <td><span class="badge bg-primary"><?= e($r['variant']) ?></span></td>
                    <td><?= (int)$r['impressions'] ?></td>
                    <td><?= (int)$r['conversions'] ?></td>
                    <td><?= $rate ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once 'includes/admin-footer.php'; ?>
