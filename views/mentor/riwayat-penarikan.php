<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

$mentorId = $_SESSION['user_id'];

// Include database connection
require_once __DIR__ . '/../../config/Database.php';
$database = new Database();
$db = $database->connect();

// Function to format currency
function format_rupiah($number) { 
    $number = $number ?? 0; 
    return 'Rp ' . number_format($number, 0, ',', '.'); 
}

// Get withdrawal history
try {
    $stmt = $db->prepare("
        SELECT 
            id as payout_id,
            reference_id,
            amount,
            net_amount,
            withdrawal_method,
            withdrawal_account,
            description,
            payout_status as status,
            payout_date,
            created_at
        FROM earnings
        WHERE mentor_id = ? AND transaction_type = 'withdrawal'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$mentorId]);
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error getting withdrawal history: " . $e->getMessage());
    $payouts = [];
}

// Include header
$page_title = "Riwayat Penarikan";
$page_css = "mentor_riwayat-penarikan.css";
include_once __DIR__ . '/../../templates/header_mentor.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Dashboard Mentor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
</head>
<body>
    <!-- Top Header -->
    <header class="top-header">
        <div class="logo">MindCraft</div>
        <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
    </header>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-menu">
                <li><a href="/MindCraft-Project/views/mentor/dashboard.php" class="active">Dashboard</a></li>
                <li><a href="/MindCraft-Project/views/mentor/kursus-saya.php">Kursus Saya</a></li>
                <li><a href="/MindCraft-Project/views/mentor/buat-kursus-baru.php">Buat Kursus Baru</a></li>
                <li><a href="/MindCraft-Project/views/mentor/pendapatan.php">Pendapatan</a></li>
                <li><a href="/MindCraft-Project/views/mentor/analitik.php">Analitik</a></li>
                <li><a href="/MindCraft-Project/views/mentor/pengaturan.php">Pengaturan</a></li>
                <li><a href="/MindCraft-Project/views/mentor/logout.php">Logout</a></li>
            </ul>
        </aside>
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="/MindCraft-Project/views/mentor/pendapatan.php">Pendapatan</a>
                    <span class="separator">›</span>
                    <span class="current">Riwayat Penarikan</span>
                </div>
                <div class="header-main">
                    <div class="header-info">
                        <h1>Riwayat Penarikan</h1>
                        <p class="header-subtitle">Lihat semua transaksi penarikan dana Anda</p>
                    </div>
                    <div class="header-actions">
                        <a href="/MindCraft-Project/views/mentor/tarik-dana.php" class="btn btn-primary">
                            Tarik Dana Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-body">
            <div class="history-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Penarikan</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Akun Tujuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payouts)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <h3>Belum ada riwayat penarikan dana</h3>
                                    <p>Anda belum melakukan penarikan dana. Mulai tarik dana Anda sekarang.</p>
                                    <a href="/MindCraft-Project/views/mentor/tarik-dana.php" class="btn btn-primary">
                                        Tarik Dana
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payouts as $payout): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($payout['reference_id']); ?></td>
                                    <td><?php echo date('d F Y, H:i', strtotime($payout['created_at'])); ?></td>
                                    <td><?php echo format_rupiah(abs($payout['amount'])); ?></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $payout['withdrawal_method'])); ?></td>
                                    <td><?php echo htmlspecialchars($payout['withdrawal_account']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars(strtolower($payout['status'])); ?>">
                                            <?php echo htmlspecialchars(ucfirst($payout['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="/MindCraft-Project/assets/js/mentor_riwayat-penarikan.js"></script>
<?php include_once __DIR__ . '/../../templates/footer.php'; ?>