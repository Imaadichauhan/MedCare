<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

doLogout();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
