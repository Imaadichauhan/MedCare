<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('receptionist');

$pdo = getDB();

$todayAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$availableBeds = $pdo->query("SELECT COUNT(*) FROM beds WHERE status='available'")->fetchColumn();
$unpaidToday = $pdo->query("SELECT COUNT(*) FROM invoices WHERE payment_status != 'paid' AND DATE(created_at) = CURDATE()")->fetchColumn();

$todaySchedule = $pdo->query("
    SELECT a.*, p.full_name AS patient_name, p.patient_code, u.full_name AS doctor_name
    FROM appointments a
    JOIN patients p ON p.patient_id = a.patient_id
    JOIN doctors d ON d.doctor_id = a.doctor_id
    JOIN users u ON u.user_id = d.user_id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time
")->fetchAll();

$pageTitle = 'Reception Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="topbar">
        <div>
            <h1>Front Desk — <?= e(date('d M Y')) ?></h1>
            <div class="subtitle">Today's appointments and quick actions.</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="../modules/patients/add.php" class="btn btn-secondary">+ Register Patient</a>
            <a href="../modules/appointments/add.php" class="btn btn-primary">+ Book Appointment</a>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/flash.php'; ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon teal">&#128197;</div>
            <div class="stat-label">Today's Appointments</div>
            <div class="stat-value"><?= number_format($todayAppts) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">&#128100;</div>
            <div class="stat-label">Total Patients</div>
            <div class="stat-value"><?= number_format($totalPatients) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#127968;</div>
            <div class="stat-label">Beds Available</div>
            <div class="stat-value"><?= number_format($availableBeds) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">&#128179;</div>
            <div class="stat-label">Unpaid Invoices Today</div>
            <div class="stat-value"><?= number_format($unpaidToday) ?></div>
        </div>
    </div>

    <div class="section-title"><span>Today's Appointment Board</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$todaySchedule): ?>
                <tr><td colspan="4" class="table-empty">No appointments booked for today yet.</td></tr>
            <?php else: foreach ($todaySchedule as $a): ?>
                <tr>
                    <td><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                    <td><?= e($a['patient_name']) ?> <span class="text-muted" style="font-size:12px;">(<?= e($a['patient_code']) ?>)</span></td>
                    <td>Dr. <?= e($a['doctor_name']) ?></td>
                    <td>
                        <?php $statusMap = ['scheduled' => 'info', 'completed' => 'success', 'cancelled' => 'danger', 'no_show' => 'warning']; ?>
                        <span class="badge <?= $statusMap[$a['status']] ?? 'neutral' ?>"><?= e(ucfirst(str_replace('_',' ',$a['status']))) ?></span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
