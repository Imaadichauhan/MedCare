<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'receptionist']);

$pdo = getDB();

// Handle discharge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discharge'])) {
    verifyCsrf();
    $admissionId = (int) $_POST['admission_id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT bed_id FROM admissions WHERE admission_id = ?");
        $stmt->execute([$admissionId]);
        $bedId = $stmt->fetchColumn();

        $pdo->prepare("UPDATE admissions SET status='discharged', discharge_date=NOW() WHERE admission_id=?")->execute([$admissionId]);
        $pdo->prepare("UPDATE beds SET status='available' WHERE bed_id=?")->execute([$bedId]);

        $pdo->commit();
        setFlash('success', 'Patient discharged and bed marked available.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Could not process discharge.');
    }
    header('Location: list.php');
    exit;
}

$wards = $pdo->query("SELECT * FROM wards ORDER BY ward_name")->fetchAll();
$beds = $pdo->query("
    SELECT b.*, w.ward_name, w.ward_type,
           ad.admission_id, p.full_name AS patient_name, p.patient_code, ad.admission_date
    FROM beds b
    JOIN wards w ON w.ward_id = b.ward_id
    LEFT JOIN admissions ad ON ad.bed_id = b.bed_id AND ad.status = 'admitted'
    LEFT JOIN patients p ON p.patient_id = ad.patient_id
    ORDER BY w.ward_name, b.bed_number
")->fetchAll();

$bedsByWard = [];
foreach ($beds as $b) {
    $bedsByWard[$b['ward_name']][] = $b;
}

$pageTitle = 'Wards & Beds';
$activePage = 'wards';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div>
            <h1>Wards & Beds</h1>
            <div class="subtitle">Live occupancy across all wards.</div>
        </div>
        <?php if (currentRole() === 'admin'): ?>
            <a href="add_ward.php" class="btn btn-primary">+ Add Ward</a>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../includes/flash.php'; ?>

    <div class="stat-grid">
        <?php foreach ($wards as $w):
            $occupied = count(array_filter($bedsByWard[$w['ward_name']] ?? [], fn($b) => $b['status'] === 'occupied'));
            $total = count($bedsByWard[$w['ward_name']] ?? []);
        ?>
        <div class="stat-card">
            <div class="stat-icon <?= $w['ward_type'] === 'ICU' ? 'red' : 'teal' ?>">&#127968;</div>
            <div class="stat-label"><?= e($w['ward_name']) ?></div>
            <div class="stat-value"><?= $occupied ?>/<?= $total ?></div>
            <div class="text-muted" style="font-size:12.5px;margin-top:2px;">₹<?= number_format($w['charge_per_day'],0) ?>/day &middot; Floor <?= e($w['floor_no']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php foreach ($bedsByWard as $wardName => $wardBeds): ?>
        <div class="section-title"><span><?= e($wardName) ?></span></div>
        <div class="table-wrap" style="margin-bottom:24px;">
            <table>
                <thead><tr><th>Bed</th><th>Status</th><th>Patient</th><th>Admitted Since</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($wardBeds as $b): ?>
                        <tr>
                            <td><strong><?= e($b['bed_number']) ?></strong></td>
                            <td>
                                <?php $sMap = ['available'=>'success','occupied'=>'warning','maintenance'=>'neutral']; ?>
                                <span class="badge <?= $sMap[$b['status']] ?>"><?= e(ucfirst($b['status'])) ?></span>
                            </td>
                            <td><?= $b['patient_name'] ? e($b['patient_name']) . ' (' . e($b['patient_code']) . ')' : '—' ?></td>
                            <td><?= $b['admission_date'] ? e(date('d M Y, h:i A', strtotime($b['admission_date']))) : '—' ?></td>
                            <td class="actions-cell">
                                <?php if ($b['status'] === 'available'): ?>
                                    <a href="admit.php?bed_id=<?= $b['bed_id'] ?>" class="btn btn-secondary btn-sm">Admit Patient</a>
                                <?php elseif ($b['status'] === 'occupied'): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="admission_id" value="<?= $b['admission_id'] ?>">
                                        <button type="submit" name="discharge" class="btn btn-secondary btn-sm" data-confirm="Discharge this patient and free the bed?">Discharge</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
