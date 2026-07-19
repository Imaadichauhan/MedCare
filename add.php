<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['admin', 'receptionist']);

$pdo = getDB();
$errors = [];
$old = ['full_name' => '', 'gender' => 'Male', 'dob' => '', 'phone' => '', 'email' => '',
        'address' => '', 'blood_group' => '', 'emergency_contact_name' => '', 'emergency_contact_phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $old = array_merge($old, $_POST);

    if (trim($old['full_name']) === '') $errors[] = 'Full name is required.';
    if (trim($old['phone']) === '') $errors[] = 'Phone number is required.';
    if (!in_array($old['gender'], ['Male', 'Female', 'Other'], true)) $errors[] = 'Please select a valid gender.';

    $age = null;
    if (!empty($old['dob'])) {
        $dob = new DateTime($old['dob']);
        $age = $dob->diff(new DateTime())->y;
    }

    if (!$errors) {
        // Generate next patient code, e.g. PT-0001
        $count = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        $patientCode = 'PT-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO patients
                (patient_code, full_name, gender, dob, age, phone, email, address, blood_group,
                 emergency_contact_name, emergency_contact_phone, registered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $patientCode, trim($old['full_name']), $old['gender'], $old['dob'] ?: null, $age,
            trim($old['phone']), trim($old['email']) ?: null, trim($old['address']) ?: null,
            $old['blood_group'] ?: null, trim($old['emergency_contact_name']) ?: null,
            trim($old['emergency_contact_phone']) ?: null, currentUserId(),
        ]);

        setFlash('success', "Patient registered successfully with code {$patientCode}.");
        header('Location: list.php');
        exit;
    }
}

$pageTitle = 'Register Patient';
$activePage = 'patients';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div>
            <h1>Register New Patient</h1>
            <div class="subtitle">Add a patient record to the system.</div>
        </div>
        <a href="list.php" class="btn btn-secondary">&larr; Back to list</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert danger">
            &#9888;
            <span><?= e(implode(' ', $errors)) ?></span>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <?= csrfField() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
                </div>
                <div class="field">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $old['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?= e($old['dob']) ?>">
                </div>
                <div class="field">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group">
                        <option value="">Select</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= $old['blood_group'] === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
                </div>
                <div class="field full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?= e($old['address']) ?></textarea>
                </div>
                <div class="field">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?= e($old['emergency_contact_name']) ?>">
                </div>
                <div class="field">
                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?= e($old['emergency_contact_phone']) ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Register Patient</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
