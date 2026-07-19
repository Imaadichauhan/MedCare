<?php
/**
 * Sidebar navigation. Include after determining $activePage (string)
 * for highlighting. Menu items adapt to currentRole().
 */
$role = currentRole();

$menus = [
    'admin' => [
        ['dashboard.php', 'dashboard', '&#9737;', 'Dashboard'],
        ['../modules/patients/list.php', 'patients', '&#128100;', 'Patients'],
        ['../modules/doctors/list.php', 'doctors', '&#129658;', 'Doctors'],
        ['../modules/appointments/list.php', 'appointments', '&#128197;', 'Appointments'],
        ['../modules/wards/list.php', 'wards', '&#127968;', 'Wards & Beds'],
        ['../modules/lab/list.php', 'lab', '&#129514;', 'Lab Tests'],
        ['../modules/pharmacy/list.php', 'pharmacy', '&#128138;', 'Pharmacy'],
        ['../modules/billing/list.php', 'billing', '&#128179;', 'Billing'],
        ['users.php', 'users', '&#128272;', 'Staff Accounts'],
    ],
    'doctor' => [
        ['dashboard.php', 'dashboard', '&#9737;', 'Dashboard'],
        ['../modules/appointments/list.php', 'appointments', '&#128197;', 'My Appointments'],
        ['../modules/patients/list.php', 'patients', '&#128100;', 'Patients'],
        ['../modules/lab/list.php', 'lab', '&#129514;', 'Lab Tests'],
        ['../modules/pharmacy/prescribe.php', 'prescribe', '&#128138;', 'Prescriptions'],
    ],
    'receptionist' => [
        ['dashboard.php', 'dashboard', '&#9737;', 'Dashboard'],
        ['../modules/patients/list.php', 'patients', '&#128100;', 'Patients'],
        ['../modules/appointments/list.php', 'appointments', '&#128197;', 'Appointments'],
        ['../modules/wards/list.php', 'wards', '&#127968;', 'Wards & Beds'],
        ['../modules/billing/list.php', 'billing', '&#128179;', 'Billing'],
    ],
];

$navItems = $menus[$role] ?? [];
$roleLabels = ['admin' => 'Administrator', 'doctor' => 'Doctor', 'receptionist' => 'Receptionist'];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="cross"></span>
        MediCare HMS
    </div>
    <div class="sidebar-role"><?= e($roleLabels[$role] ?? 'User') ?> Panel</div>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as [$href, $key, $icon, $label]): ?>
            <a href="<?= e($href) ?>" class="<?= ($activePage ?? '') === $key ? 'active' : '' ?>">
                <span class="icon"><?= $icon ?></span> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <div class="user-name"><?= e(currentUserName()) ?></div>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-link">Log out</a>
    </div>
</aside>
<button id="sidebarToggle" class="mobile-toggle" aria-label="Toggle menu" style="position:fixed;top:14px;left:14px;z-index:150;">&#9776;</button>
