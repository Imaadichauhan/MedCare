<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDB();

// Toggle active/inactive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verifyCsrf();
    $userId = (int) $_POST['user_id'];
    if ($userId !== currentUserId()) { // can't deactivate yourself
        $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE user_id = ?")->execute([$userId]);
        setFlash('success', 'Account status updated.');
    } else {
        setFlash('danger', "You can't deactivate your own account.");
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();

$errors = [];
$old = ['username' => '', 'full_name' => '', 'email' => '', 'phone' => '', 'role' => 'receptionist'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    verifyCsrf();
    $old = array_merge($old, $_POST);

    if (trim($old['username']) === '') $errors[] = 'Username is required.';
    if (trim($old['full_name']) === '') $errors[] = 'Full name is required.';
    if (!in_array($old['role'], ['admin', 'receptionist'], true)) $errors[] = 'Use the Doctors page to add doctor accounts.';

    if (!$errors) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check->execute([trim($old['username'])]);
        if ($check->fetchColumn() > 0) $errors[] = 'That username is already taken.';
    }

    if (!$errors) {
        $hash = password_hash('Staff@123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?,?,?,?,?,?)")
            ->execute([trim($old['username']), $hash, $old['role'], trim($old['full_name']), trim($old['email']) ?: null, trim($old['phone']) ?: null]);
        setFlash('success', 'Staff account created. Default password is Staff@123.');
        header('Location: users.php');
        exit;
    }
}

$pageTitle = 'Staff Accounts';
$activePage = 'users';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div><h1>Staff Accounts</h1><div class="subtitle">Manage login accounts for admins and receptionists. Doctor accounts are managed from the Doctors page.</div></div>
        <button class="btn btn-primary" data-modal-open="addUserModal">+ Add Staff Account</button>
    </div>

    <?php if ($errors): ?>
        <div class="alert danger">&#9888; <span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>
    <?php include __DIR__ . '/../includes/flash.php'; ?>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Phone</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['full_name']) ?></td>
                    <td><code><?= e($u['username']) ?></code></td>
                    <td><span class="badge info"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td><?= e($u['email'] ?: '—') ?></td>
                    <td><?= e($u['phone'] ?: '—') ?></td>
                    <td><span class="badge <?= $u['status'] === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                    <td class="actions-cell">
                        <?php if ($u['user_id'] != currentUserId()): ?>
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                            <button type="submit" name="toggle_status" class="btn btn-secondary btn-sm" data-confirm="<?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this account?">
                                <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:12.5px;">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="addUserModal">
        <div class="modal-box">
            <h3 class="mt-0">Add Staff Account</h3>
            <form method="POST">
                <?= csrfField() ?>
                <div class="field" style="margin-bottom:14px;">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required>
                </div>
                <div class="field" style="margin-bottom:14px;">
                    <label>Username *</label>
                    <input type="text" name="username" value="<?= e($old['username']) ?>" required>
                </div>
                <div class="field" style="margin-bottom:14px;">
                    <label>Role *</label>
                    <select name="role">
                        <option value="receptionist" <?= $old['role'] === 'receptionist' ? 'selected' : '' ?>>Receptionist</option>
                        <option value="admin" <?= $old['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="field" style="margin-bottom:14px;">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($old['email']) ?>">
                </div>
                <div class="field" style="margin-bottom:6px;">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?= e($old['phone']) ?>">
                </div>
                <div class="hint" style="margin-bottom:14px;">Default password will be <code>Staff@123</code>.</div>
                <div class="form-actions">
                    <button type="submit" name="add_user" class="btn btn-primary">Create Account</button>
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
