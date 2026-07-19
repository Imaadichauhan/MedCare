<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'receptionist']);

$pdo = getDB();
$bedId = (int) ($_GET['bed_id'] ?? $_POST['bed_id'] ?? 0);

$bedStmt = $pdo->prepare("SELECT b.*, w.ward_name, w.charge_per_day FROM beds b JOIN wards w ON w.ward_id = b.ward_id WHERE b.bed_id = ?");
$bedStmt->execute([$bedId]);
$bed = $bedStmt->fetch();

if (!$bed || $bed['status'] !== 'available') {
    setFlash('danger', 'That bed is not available for admission.');
    header('Location: list.php');
    exit;
}

$patients = $pdo->query("SELECT patient_id, full_name, patient_code FROM patients ORDER BY full_name")->fetchAll();
$doctors = $pdo->query("SELECT d.doctor_id, u.full_name FROM doctors d JOIN users u ON u.user_id = d.user_id ORDER BY u.full_name")->fetchAll();

$errors = [];
$old = ['patient_id' => '', 'doctor_id' => '', 'reason' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $old = array_merge($old, $_POST);

    if (!$old['patient_id']) $errors[] = 'Please select a patient.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO admissions (patient_id, bed_id, doctor_id, reason) VALUES (?,?,?,?)");
            $stmt->execute([$old['patient_id'], $bedId, $old['doctor_id'] ?: null, trim($old['reason']) ?: null]);
            $pdo->prepare("UPDATE beds SET status='occupied' WHERE bed_id=?")->execute([$bedId]);
            $pdo->commit();
            setFlash('success', "Patient admitted to bed {$bed['bed_number']}.");
            header('Location: list.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Could not complete admission.';
        }
    }
}

$pageTitle = 'Admit Patient';
$activePage = 'wards';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div>
            <h1>Admit Patient — Bed <?= e($bed['bed_number']) ?></h1>
            <div class="subtitle"><?= e($bed['ward_name']) ?> &middot; ₹<?= number_format($bed['charge_per_day'],0) ?>/day</div>
        </div>
        <a href="list.php" class="btn btn-secondary">&larr; Back</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert danger">&#9888; <span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="bed_id" value="<?= $bedId ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="patient_id">Patient *</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= e($p['full_name']) ?> (<?= e($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="doctor_id">Attending Doctor</label>
                    <select id="doctor_id" name="doctor_id">
                        <option value="">Select doctor</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['doctor_id'] ?>">Dr. <?= e($d['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full">
                    <label for="reason">Reason for Admission</label>
                    <textarea id="reason" name="reason" placeholder="e.g. Post-surgery observation, monitoring..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Admit Patient</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
