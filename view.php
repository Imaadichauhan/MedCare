<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'doctor', 'receptionist']);

$pdo = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    setFlash('danger', 'Patient not found.');
    header('Location: list.php');
    exit;
}

$appointments = $pdo->prepare("
    SELECT a.*, u.full_name AS doctor_name FROM appointments a
    JOIN doctors d ON d.doctor_id = a.doctor_id
    JOIN users u ON u.user_id = d.user_id
    WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 10
");
$appointments->execute([$id]);
$appointments = $appointments->fetchAll();

$labTests = $pdo->prepare("
    SELECT lt.*, c.test_name, c.test_price FROM lab_tests lt
    JOIN lab_test_catalog c ON c.test_id = lt.test_id
    WHERE lt.patient_id = ? ORDER BY lt.test_date DESC LIMIT 10
");
$labTests->execute([$id]);
$labTests = $labTests->fetchAll();

$invoices = $pdo->prepare("SELECT * FROM invoices WHERE patient_id = ? ORDER BY created_at DESC LIMIT 10");
$invoices->execute([$id]);
$invoices = $invoices->fetchAll();

$admissions = $pdo->prepare("
    SELECT ad.*, w.ward_name, b.bed_number FROM admissions ad
    JOIN beds b ON b.bed_id = ad.bed_id
    JOIN wards w ON w.ward_id = b.ward_id
    WHERE ad.patient_id = ? ORDER BY ad.admission_date DESC LIMIT 5
");
$admissions->execute([$id]);
$admissions = $admissions->fetchAll();

$pageTitle = $patient['full_name'] . ' — Patient Profile';
$activePage = 'patients';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div>
            <h1><?= e($patient['full_name']) ?> <span class="text-muted" style="font-size:15px;">(<?= e($patient['patient_code']) ?>)</span></h1>
            <div class="subtitle"><?= e($patient['gender']) ?> &middot; Age <?= e($patient['age'] ?? '—') ?> &middot; <?= e($patient['blood_group'] ?? 'Blood group not recorded') ?></div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="list.php" class="btn btn-secondary">&larr; Back</a>
            <?php if (currentRole() !== 'doctor'): ?>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">Edit Patient</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/flash.php'; ?>

    <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div class="card">
            <h4 class="mt-0">Contact</h4>
            <p style="margin:0;font-size:14px;">📞 <?= e($patient['phone']) ?><br>
            ✉️ <?= e($patient['email'] ?: 'Not provided') ?><br>
            🏠 <?= e($patient['address'] ?: 'Not provided') ?></p>
        </div>
        <div class="card">
            <h4 class="mt-0">Emergency Contact</h4>
            <p style="margin:0;font-size:14px;"><?= e($patient['emergency_contact_name'] ?: 'Not provided') ?><br>
            <?= e($patient['emergency_contact_phone'] ?: '') ?></p>
        </div>
        <div class="card">
            <h4 class="mt-0">Quick Actions</h4>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="../appointments/add.php?patient_id=<?= $id ?>" class="btn btn-secondary btn-sm">+ Book Appointment</a>
                <a href="../billing/add.php?patient_id=<?= $id ?>" class="btn btn-secondary btn-sm">+ Create Invoice</a>
            </div>
        </div>
    </div>

    <div class="section-title"><span>Appointment History</span></div>
    <div class="table-wrap" style="margin-bottom:26px;">
        <table>
            <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$appointments): ?>
                <tr><td colspan="5" class="table-empty">No appointments recorded.</td></tr>
            <?php else: foreach ($appointments as $a): ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                    <td><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                    <td>Dr. <?= e($a['doctor_name']) ?></td>
                    <td><?= e($a['reason'] ?: '—') ?></td>
                    <td><span class="badge info"><?= e(ucfirst(str_replace('_',' ',$a['status']))) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-title"><span>Lab Tests</span></div>
    <div class="table-wrap" style="margin-bottom:26px;">
        <table>
            <thead><tr><th>Test</th><th>Date</th><th>Result</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$labTests): ?>
                <tr><td colspan="4" class="table-empty">No lab tests recorded.</td></tr>
            <?php else: foreach ($labTests as $t): ?>
                <tr>
                    <td><?= e($t['test_name']) ?></td>
                    <td><?= e(date('d M Y', strtotime($t['test_date']))) ?></td>
                    <td><?= e($t['result_value'] ?: 'Pending') ?></td>
                    <td><span class="badge <?= $t['status'] === 'completed' ? 'success' : 'warning' ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-title"><span>Ward Admissions</span></div>
    <div class="table-wrap" style="margin-bottom:26px;">
        <table>
            <thead><tr><th>Ward</th><th>Bed</th><th>Admitted</th><th>Discharged</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$admissions): ?>
                <tr><td colspan="5" class="table-empty">No ward admissions recorded.</td></tr>
            <?php else: foreach ($admissions as $ad): ?>
                <tr>
                    <td><?= e($ad['ward_name']) ?></td>
                    <td><?= e($ad['bed_number']) ?></td>
                    <td><?= e(date('d M Y', strtotime($ad['admission_date']))) ?></td>
                    <td><?= $ad['discharge_date'] ? e(date('d M Y', strtotime($ad['discharge_date']))) : '—' ?></td>
                    <td><span class="badge <?= $ad['status'] === 'admitted' ? 'warning' : 'success' ?>"><?= e(ucfirst($ad['status'])) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-title"><span>Billing History</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Paid</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$invoices): ?>
                <tr><td colspan="6" class="table-empty">No invoices recorded.</td></tr>
            <?php else: foreach ($invoices as $inv): ?>
                <tr>
                    <td><code><?= e($inv['invoice_no']) ?></code></td>
                    <td><?= e(date('d M Y', strtotime($inv['created_at']))) ?></td>
                    <td>₹<?= number_format($inv['total_amount'], 2) ?></td>
                    <td>₹<?= number_format($inv['amount_paid'], 2) ?></td>
                    <td>
                        <?php $pStatus = ['paid'=>'success','unpaid'=>'danger','partial'=>'warning']; ?>
                        <span class="badge <?= $pStatus[$inv['payment_status']] ?>"><?= e(ucfirst($inv['payment_status'])) ?></span>
                    </td>
                    <td><a href="../billing/view.php?id=<?= $inv['invoice_id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
