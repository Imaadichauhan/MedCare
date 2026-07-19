<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$pdo = getDB();
$errors = [];
$old = ['medicine_name' => '', 'category' => '', 'manufacturer' => '', 'unit_price' => '', 'stock_qty' => '', 'reorder_level' => '10', 'expiry_date' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $old = array_merge($old, $_POST);

    if (trim($old['medicine_name']) === '') $errors[] = 'Medicine name is required.';
    if ($old['unit_price'] === '' || (float)$old['unit_price'] < 0) $errors[] = 'Please enter a valid price.';

    if (!$errors) {
        $pdo->prepare("
            INSERT INTO medicines (medicine_name, category, manufacturer, unit_price, stock_qty, reorder_level, expiry_date)
            VALUES (?,?,?,?,?,?,?)
        ")->execute([
            trim($old['medicine_name']), trim($old['category']) ?: null, trim($old['manufacturer']) ?: null,
            (float)$old['unit_price'], (int)($old['stock_qty'] ?: 0), (int)($old['reorder_level'] ?: 10),
            $old['expiry_date'] ?: null,
        ]);
        setFlash('success', 'Medicine added to inventory.');
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Add Medicine';
$activePage = 'pharmacy';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div><h1>Add Medicine</h1><div class="subtitle">Add a new medicine to the pharmacy inventory.</div></div>
        <a href="list.php" class="btn btn-secondary">&larr; Back</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert danger">&#9888; <span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="field full">
                    <label for="medicine_name">Medicine Name *</label>
                    <input type="text" id="medicine_name" name="medicine_name" value="<?= e($old['medicine_name']) ?>" placeholder="e.g. Paracetamol 500mg" required>
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?= e($old['category']) ?>" placeholder="e.g. Analgesic">
                </div>
                <div class="field">
                    <label for="manufacturer">Manufacturer</label>
                    <input type="text" id="manufacturer" name="manufacturer" value="<?= e($old['manufacturer']) ?>">
                </div>
                <div class="field">
                    <label for="unit_price">Unit Price (₹) *</label>
                    <input type="number" id="unit_price" name="unit_price" value="<?= e($old['unit_price']) ?>" min="0" step="0.01" required>
                </div>
                <div class="field">
                    <label for="stock_qty">Stock Quantity</label>
                    <input type="number" id="stock_qty" name="stock_qty" value="<?= e($old['stock_qty']) ?>" min="0">
                </div>
                <div class="field">
                    <label for="reorder_level">Reorder Level</label>
                    <input type="number" id="reorder_level" name="reorder_level" value="<?= e($old['reorder_level']) ?>" min="0">
                    <div class="hint">An alert shows when stock falls to or below this number.</div>
                </div>
                <div class="field">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?= e($old['expiry_date']) ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Medicine</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
