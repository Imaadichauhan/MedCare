<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in? send to their dashboard.
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . currentRole() . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND status = "active" LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // If logged in as doctor, also stash doctor_id for convenience
            if ($user['role'] === 'doctor') {
                $docStmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
                $docStmt->execute([$user['user_id']]);
                $doc = $docStmt->fetch();
                $_SESSION['doctor_id'] = $doc['doctor_id'] ?? null;
            }

            header('Location: ' . BASE_URL . '/' . $user['role'] . '/dashboard.php');
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — MediCare HMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lexend:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-visual">
        <h2>One system for every ward, ledger, and chart.</h2>
        <p>MediCare HMS keeps patients, doctors, beds, and billing in sync so your front desk never double-books and your pharmacy never runs blind.</p>
        <div class="login-stats">
            <div><strong>6</strong><span>Core modules</span></div>
            <div><strong>3</strong><span>Role-based panels</span></div>
            <div><strong>24/7</strong><span>Ward visibility</span></div>
        </div>
    </div>
    <div class="login-form-wrap">
        <div class="login-form-box">
            <div class="brand-row">
                <span class="cross" style="width:30px;height:30px;background:#0E6E6E;border-radius:8px;position:relative;display:inline-block;">
                    <span style="position:absolute;width:4px;height:15px;background:#fff;top:7px;left:13px;border-radius:1px;"></span>
                    <span style="position:absolute;width:15px;height:4px;background:#fff;top:13px;left:7px;border-radius:1px;"></span>
                </span>
                <strong style="font-family:var(--font-display);font-size:18px;">MediCare HMS</strong>
            </div>
            <h1>Welcome back</h1>
            <p class="login-sub">Sign in with your staff account to continue.</p>

            <?php if ($error): ?>
                <div class="alert danger">&#9888; <span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>
                <div class="field" style="margin-bottom:16px;">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="e.g. admin" required autofocus>
                </div>
                <div class="field" style="margin-bottom:20px;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Sign in</button>
            </form>

            <div class="demo-creds">
                <strong>Demo accounts</strong> (password for all: <code>Admin@123</code>)<br>
                Admin: <code>admin</code> &nbsp;·&nbsp; Doctor: <code>Aditya1</code> &nbsp;·&nbsp; Receptionist: <code>Aditya2</code>
            </div>
        </div>
    </div>
</div>
</body>
</html>
