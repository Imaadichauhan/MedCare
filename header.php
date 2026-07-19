<?php
/**
 * Page head. Expects $pageTitle to be set before including.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'MediCare HMS') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lexend:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<style>
.mobile-toggle{display:none;border:none;background:var(--color-primary);color:#fff;width:40px;height:40px;border-radius:8px;font-size:18px;cursor:pointer;}
@media (max-width:900px){.mobile-toggle{display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;}}
</style>
</head>
<body>
<div class="app-shell">
