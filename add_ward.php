<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pdo = getDB();
$errors = [];
$old = ['ward_name' => '', 'ward_type' => 'General', 'total_beds' => '', 'floor_no' => '', 'charge_per_day' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $old = array_merge($old, $_POST);

    if (trim($old['ward_name']) === '') $errors[] = 'Ward name is required.';
    if ((int)$old['total_beds'] <= 0) $errors[] = 'Total beds must be at least 1.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO wards (ward_name, ward_type, total_beds, floor_no, charge_per_day) VALUES (?,?,?,?,?)");
            $stmt->execute([trim($old['ward_name']), $old['ward_type'], (int)$old['total_beds'], trim($old['floor_no']) ?: null, (float)($old['charge_per_day'] ?: 0)]);
            $wardId = $pdo->lastInsertId();

            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $old['ward_name']), 0, 3)) ?: 'BED';
            $bedStmt = $pdo->prepare("INSERT INTO beds (ward_id, bed_number, status) VALUES (?,?, 'available')");
            for ($i = 1; $i <= (int)$old['total_beds']; $i++) {
                $bedStmt->execute([$wardId, $prefix . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)]);
            }
            $pdo->commit();
            setFlash('success', 'Ward added with ' . (int)$old['total_beds'] . ' beds.');
            header('Location: list.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Could not save ward: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Ward';
$activePage = 'wards';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div><h1>Add Ward</h1><div class="subtitle">Create a new ward and automatically generate its beds.</div></div>
        <a href="list.php" class="btn btn-secondary">&larr; Back</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert danger">&#9888; <span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="ward_name">Ward Name *</label>
                    <input type="text" id="ward_name" name="ward_name" value="<?= e($old['ward_name']) ?>" placeholder="e.g. General Ward B" required>
                </div>
                <div class="field">
                    <label for="ward_type">Ward Type</label>
                    <select id="ward_type" name="ward_type">
                        <?php foreach (['General','ICU','Private','Semi-Private','Emergency'] as $t): ?>
                            <option value="<?= $t ?>" <?= $old['ward_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="total_beds">Total Beds *</label>
                    <input type="number" id="total_beds" name="total_beds" value="<?= e($old['total_beds']) ?>" min="1" required>
                </div>
                <div class="field">
                    <label for="floor_no">Floor</label>
                    <input type="text" id="floor_no" name="floor_no" value="<?= e($old['floor_no']) ?>">
                </div>
                <div class="field">
                    <label for="charge_per_day">Charge per Day (₹)</label>
                    <input type="number" id="charge_per_day" name="charge_per_day" value="<?= e($old['charge_per_day']) ?>" min="0" step="0.01">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Ward</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
