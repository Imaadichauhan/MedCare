<?php
/**
 * Auth Helpers
 * Session-based authentication and role guards.
 * Include this AFTER config/database.php in every protected page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Is anyone logged in? */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/** Get the logged-in user's role, or null */
function currentRole() {
    return $_SESSION['role'] ?? null;
}

/** Get the logged-in user's id, or null */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/** Get the logged-in user's display name */
function currentUserName() {
    return $_SESSION['full_name'] ?? 'User';
}

/**
 * Require login. If not logged in, redirect to the login page.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Require one of the given roles. If the user is logged in but
 * lacks permission, send them to their own dashboard instead of
 * silently failing.
 *
 * @param string|array $roles e.g. 'admin' or ['admin','doctor']
 */
function requireRole($roles) {
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array(currentRole(), $roles, true)) {
        header('Location: ' . BASE_URL . '/' . currentRole() . '/dashboard.php');
        exit;
    }
}

/** Log the user out and destroy the session */
function doLogout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/** Escape helper to keep view files tidy: e(<?= ... ?>) */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Simple flash-message helper using the session */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Generate and validate CSRF tokens for forms */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Invalid request (CSRF check failed). Please go back and try again.');
    }
}
