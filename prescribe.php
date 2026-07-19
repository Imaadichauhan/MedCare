<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['doctor', 'admin']);

$pdo = getDB();
$patients = $pdo->query("SELECT patient_id, full_name, patient_code FROM patients ORDER BY full_name")->fetchAll();
$medicines = $pdo->query("SELECT medicine_id, medicine_name, stock_qty, unit_price FROM medicines ORDER BY medicine_name")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $patientId = $_POST['patient_id'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $medIds = $_POST['medicine_id'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $durations = $_POST['duration_days'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (!$patientId) $errors[] = 'Please select a patient.';
    if (!$medIds || !array_filter($medIds)) $errors[] = 'Please add at least one medicine.';

    $doctorId = $_SESSION['doctor_id'] ?? null;
    if (!$doctorId) $errors[] = 'No doctor profile linked to this account.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, notes) VALUES (?,?,?)");
            $stmt->execute([$patientId, $doctorId, $notes ?: null]);
            $prescriptionId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_id, dosage, duration_days, quantity) VALUES (?,?,?,?,?)");
            $stockStmt = $pdo->prepare("UPDATE medicines SET stock_qty = stock_qty - ? WHERE medicine_id = ? AND stock_qty >= ?");

            foreach ($medIds as $i => $medId) {
                if (!$medId) continue;
                $qty = max(1, (int) ($quantities[$i] ?? 1));
                $itemStmt->execute([$prescriptionId, $medId, trim($dosages[$i] ?? ''), (int)($durations[$i] ?? 0) ?: null, $qty]);
                $stockStmt->execute([$qty, $medId, $qty]);
            }

            $pdo->commit();
            setFlash('success', 'Prescription saved and stock updated.');
            header('Location: prescribe.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Could not save prescription: ' . $e->getMessage();
        }
    }
}

// Recent prescriptions by this doctor
$doctorId = $_SESSION['doctor_id'] ?? 0;
$recent = $pdo->prepare("
    SELECT pr.prescription_id, pr.prescribed_date, p.full_name AS patient_name, p.patient_code,
           GROUP_CONCAT(m.medicine_name SEPARATOR ', ') AS meds
    FROM prescriptions pr
    JOIN patients p ON p.patient_id = pr.patient_id
    LEFT JOIN prescription_items pi ON pi.prescription_id = pr.prescription_id
    LEFT JOIN medicines m ON m.medicine_id = pi.medicine_id
    WHERE pr.doctor_id = ?
    GROUP BY pr.prescription_id
    ORDER BY pr.prescribed_date DESC LIMIT 10
");
$recent->execute([$doctorId]);
$recent = $recent->fetchAll();

$pageTitle = 'Write Prescription';
$activePage = 'prescribe';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div><h1>Write Prescription</h1><div class="subtitle">Prescribe medicines — stock is deducted automatically.</div></div>
    </div>

    <?php if ($errors): ?>
        <div class="alert danger">&#9888; <span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>
    <?php include __DIR__ . '/../../includes/flash.php'; ?>

    <div class="card" style="margin-bottom:28px;">
        <form method="POST" action="">
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="field full">
                    <label for="patient_id">Patient *</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= e($p['full_name']) ?> (<?= e($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h4 style="margin-top:24px;">Medicines</h4>
            <div id="medicineRows">
                <div class="med-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;margin-bottom:10px;align-items:end;">
                    <div class="field" style="margin:0;">
                        <label>Medicine</label>
                        <select name="medicine_id[]">
                            <option value="">Select</option>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['medicine_id'] ?>"><?= e($m['medicine_name']) ?> (<?= $m['stock_qty'] ?> in stock)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Dosage</label>
                        <input type="text" name="dosage[]" placeholder="1-0-1">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Days</label>
                        <input type="number" name="duration_days[]" min="1" placeholder="5">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Qty</label>
                        <input type="number" name="quantity[]" min="1" value="1">
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" data-remove-row>✕</button>
                </div>
            </div>
            <template id="medicineRowTemplate">
                <div class="med-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;margin-bottom:10px;align-items:end;">
                    <div class="field" style="margin:0;">
                        <label>Medicine</label>
                        <select name="medicine_id[]">
                            <option value="">Select</option>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['medicine_id'] ?>"><?= e($m['medicine_name']) ?> (<?= $m['stock_qty'] ?> in stock)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Dosage</label>
                        <input type="text" name="dosage[]" placeholder="1-0-1">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Days</label>
                        <input type="number" name="duration_days[]" min="1" placeholder="5">
                    </div>
                    <div class="field" style="margin:0;">
                        <label>Qty</label>
                        <input type="number" name="quantity[]" min="1" value="1">
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" data-remove-row>✕</button>
                </div>
            </template>
            <button type="button" id="addMedicineRow" class="btn btn-secondary btn-sm">+ Add another medicine</button>

            <div class="field full" style="margin-top:20px;">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Any additional instructions for the patient..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Prescription</button>
            </div>
        </form>
    </div>

    <div class="section-title"><span>Your Recent Prescriptions</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Patient</th><th>Medicines</th><th>Date</th></tr></thead>
            <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="3" class="table-empty">No prescriptions written yet.</td></tr>
                <?php else: foreach ($recent as $r): ?>
                    <tr>
                        <td><?= e($r['patient_name']) ?> (<?= e($r['patient_code']) ?>)</td>
                        <td><?= e($r['meds'] ?: '—') ?></td>
                        <td><?= e(date('d M Y', strtotime($r['prescribed_date']))) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
