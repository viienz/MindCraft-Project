<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect function with absolute path
function redirect($url) {
    $base_url = '/MindCraft-Project';
    header("Location: " . $base_url . $url);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

// Check if user is a mentor
if ($_SESSION['user_type'] !== 'Mentor') {
    redirect('/auth/unauthorized.php');
}

// Set mentor_id from user_id
$_SESSION['mentor_id'] = $_SESSION['user_id'];

// Include database connection and controller
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/MentorController.php';

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

try {
    // Initialize database and controller
    $database = new Database();
    $controller = new MentorController($database);
    
    $mentorId = $_SESSION['mentor_id'];
    
    // Get mentor data
    $mentor = $controller->getMentorData($mentorId);
    
    // Set mentor name
    $mentorName = $mentor['username'] ?? $_SESSION['username'] ?? 'Mentor';
    
    // Get dashboard data
    $dashboardData = $controller->getDashboardData($mentorId);
    
    // Extract data
    $newRegistrations = $dashboardData['newRegistrations'] ?? 0;
    $unreadMessages = $dashboardData['unreadMessages'] ?? 0;
    $consistencyIncrease = $dashboardData['consistencyIncrease'] ?? 0;

    $totalCourses = $dashboardData['totalCourses'] ?? 0;
    $totalMentees = $dashboardData['totalMentees'] ?? 0;

    $moduleCount = $dashboardData['moduleCount'] ?? 0;
    $totalLessons = $dashboardData['totalLessons'] ?? 0;
    $formattedTotalEarnings = $dashboardData['formattedTotalEarnings'] ?? 'Rp 0';
    $formattedAvailableBalance = $dashboardData['formattedAvailableBalance'] ?? 'Rp 0';

    $monthlyRegistrations = $dashboardData['monthlyRegistrations'] ?? array_fill(0, 7, 0);
    $recentActivities = $dashboardData['recentActivities'] ?? [];

    // Get recent transactions
    $recentTransactions = $controller->getRecentTransactions($mentorId, 5);

    // Get top performing courses
    $topCourses = $controller->getTopPerformingCourses($mentorId, 3);

    // Get revenue data from database
    $revenueData = $controller->getRevenuePageData($mentorId);
    $totalRevenue = $revenueData['total_revenue'] ?? 0;
    $totalWithdrawals = $revenueData['total_withdrawals'] ?? 0;
    $availableBalance = $revenueData['available_balance'] ?? 0;

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    
    // Set default values if error occurs
    $mentorName = $_SESSION['username'] ?? 'Mentor';
    $newRegistrations = 0;
    $unreadMessages = 0;
    $consistencyIncrease = 0;
    $totalCourses = 0;
    $totalMentees = 0;
    $moduleCount = 0;
    $totalLessons = 0;
    $formattedTotalEarnings = 'Rp 0';
    $formattedAvailableBalance = 'Rp 0';
    $monthlyRegistrations = array_fill(0, 7, 0);
    $recentActivities = [];
    $recentTransactions = [];
    $topCourses = [];
    $totalRevenue = 0;
    $totalWithdrawals = 0;
    $availableBalance = 0;
}


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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
</head>
<body>
    <header class="top-header">
        <div class="logo">MindCraft</div>
        <div class="header-right">
            <div class="header-profile">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($mentorName, 0, 1)); ?>
                </div>
                <span class="profile-name"><?php echo htmlspecialchars($mentorName); ?></span>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">   
        <ul class="sidebar-menu">
            <li>
                <a href="/MindCraft-Project/views/mentor/dashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/kursus-saya.php">
                    <i class="fas fa-book"></i>
                    <span>Kursus Saya</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/buat-kursus-baru.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Buat Kursus Baru</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/pendapatan.php">
                    <i class="fas fa-wallet"></i>
                    <span>Pendapatan</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/analitik.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Analitik</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/pengaturan.php">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="sidebar-version">v1.0.0</div>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header">
            <h1>Dashboard Mentor</h1>
            <div class="breadcrumb">
                <span>Home</span> / <span class="active">Dashboard</span>
            </div>
        </div>
        
        <div class="content-body">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Welcome Banner -->
            <div class="welcome-banner fade-in-up">
                <div class="welcome-content">
                    <div class="welcome-title">
                        <h2>Selamat datang kembali, <?php echo htmlspecialchars($mentorName); ?>!</h2>
                        <p class="welcome-text">
                            <?php if ($newRegistrations > 0 || $unreadMessages > 0): ?>
                                Anda memiliki 
                                <?php if ($newRegistrations > 0): ?>
                                    <span class="highlight"><?php echo $newRegistrations; ?> pendaftaran baru</span>
                                    <?php if ($unreadMessages > 0): ?> dan <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($unreadMessages > 0): ?>
                                    <span class="highlight"><?php echo $unreadMessages; ?> pesan</span> yang belum dibaca
                                <?php endif; ?>.
                            <?php else: ?>
                                Semua terlihat terkini! Tidak ada notifikasi baru untuk saat ini.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="welcome-stats">
                        <div class="stat-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <span>Konsistensi</span>
                                <strong>
                                    <?php if ($consistencyIncrease > 0): ?>
                                        +<?php echo $consistencyIncrease; ?>%
                                    <?php elseif ($consistencyIncrease < 0): ?>
                                        <?php echo $consistencyIncrease; ?>%
                                    <?php else: ?>
                                        0%
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <span>Mentee Baru</span>
                                <strong><?php echo $newRegistrations; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="welcome-illustration">
                    <img src="/MindCraft-Project/assets/img/dashboard-illustration.svg" alt="Dashboard Illustration">
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Total Kursus</div>
                        <div class="stat-number"><?php echo number_format($totalCourses); ?></div>
                        <div class="stat-label">Kursus</div>
                        <div class="stat-badge">
                            <?php if ($totalCourses >= 10): ?>
                                <i class="fas fa-fire"></i> POPULER
                            <?php elseif ($totalCourses > 0): ?>
                                <i class="fas fa-seedling"></i> BERKEMBANG
                            <?php else: ?>
                                <i class="fas fa-rocket"></i> MULAI
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Total Mentee</div>
                        <div class="stat-number"><?php echo number_format($totalMentees); ?></div>
                        <div class="stat-label">Mentee</div>
                        <div class="stat-badge">
                            <?php 
                            $growthRate = $totalMentees > 0 ? min(15, max(5, floor($totalMentees / 10))) : 0;
                            echo $growthRate > 0 ? "<i class='fas fa-arrow-up'></i> " . $growthRate . "% MTM" : "<i class='fas fa-rocket'></i> MULAI";
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-icon" style="background-color: #e0f2fe; color: #0369a1;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-title">Saldo Tersedia</div>
                        <div class="stat-number">Rp <?php echo number_format($availableBalance, 0, ',', '.'); ?></div>
                        <div class="stat-label">Dapat ditarik</div>
                        <div class="stat-badge" style="background-color: #f0f9ff; color: #0369a1;">
                            <i class="fas fa-coins"></i> SALDO
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Bar -->
            <div class="summary-bar fade-in-up" style="animation-delay: 0.5s;">
                <div class="summary-item">
                    <div class="summary-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-title">Total Pendapatan</div>
                        <div class="summary-value">Rp <?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon" style="background-color: #fee2e2; color: #b91c1c;">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-title">Total Penarikan</div>
                        <div class="summary-value">Rp <?php echo number_format($totalWithdrawals, 0, ',', '.'); ?></div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-title">Modul</div>
                        <div class="summary-value"><?php echo number_format($moduleCount); ?></div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-title">Total Pelajaran</div>
                        <div class="summary-value"><?php echo number_format($totalLessons); ?></div>
                    </div>
                </div>
            </div>

            <!-- Bottom Grid -->
            <div class="bottom-grid">
                <div class="activity-card fade-in-up" style="animation-delay: 0.7s;">
                    <div class="card-header">
                        <h3 class="card-title">Aktivitas Terbaru</h3>
                        <a href="#" class="view-all">Lihat Semua</a>
                    </div>
                    <div class="activity-list">
                        <?php if (!empty($recentActivities)): ?>
                            <?php foreach (array_slice($recentActivities, 0, 5) as $index => $activity): ?>
                            <div class="activity-item">
                                <div class="activity-avatar" style="background: linear-gradient(135deg, 
                                    <?php 
                                    $colors = ['#3A59D1', '#9333EA', '#059669', '#DC2626', '#EA580C']; 
                                    echo $colors[$index % count($colors)]; 
                                    ?>, 
                                    <?php 
                                    $lightColors = ['#90C7F8', '#C4B5FD', '#6EE7B7', '#FCA5A5', '#FDBA74']; 
                                    echo $lightColors[$index % count($lightColors)]; 
                                    ?>);">
                                    <?php echo htmlspecialchars($activity['avatar']); ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <strong><?php echo htmlspecialchars($activity['user']); ?></strong> 
                                        <?php echo htmlspecialchars($activity['action']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo htmlspecialchars($activity['time']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-alt"></i>
                                <p>Belum ada aktivitas terbaru</p>
                                <small>Mulai buat kursus untuk melihat aktivitas</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chart-card fade-in-up" style="animation-delay: 0.8s;">
                    <div class="card-header">
                        <h3 class="card-title">Jumlah Pendaftaran</h3>
                        <div class="chart-period">
                            <select id="chartPeriod">
                                <option value="7days">7 Hari Terakhir</option>
                                <option value="30days">30 Hari Terakhir</option>
                                <option value="90days">90 Hari Terakhir</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <?php if (!empty($monthlyRegistrations) && array_sum($monthlyRegistrations) > 0): ?>
                            <canvas id="registrationChart"></canvas>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-pie"></i>
                                <p>Belum ada data pendaftaran</p>
                                <small>Chart akan muncul setelah ada pendaftaran</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Courses Section -->
            <?php if (!empty($topCourses)): ?>
            <div class="top-courses fade-in-up" style="animation-delay: 0.9s;">
                <h3 class="section-title">Kursus Terbaik Anda</h3>
                <div class="courses-grid">
                    <?php foreach ($topCourses as $course): ?>
                    <div class="course-card">
                        <div class="course-image" style="background-image: url('<?php echo htmlspecialchars($course['cover_image'] ?: '/MindCraft-Project/assets/images/course-placeholder.jpg'); ?>');">
                            <?php if (!empty($course['avg_rating'])): ?>
                            <div class="course-rating">
                                <i class="fas fa-star"></i>
                                <?php echo number_format($course['avg_rating'], 1); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="course-content">
                            <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                            <div class="course-stats">
                                <div class="stat">
                                    <i class="fas fa-users"></i>
                                    <?php echo number_format($course['total_enrollments'] ?? 0); ?> Mentee
                                </div>
                                <div class="stat">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <?php echo $course['formatted_total_earnings'] ?? 'Rp 0'; ?>
                                </div>
                            </div>
                            <a href="/MindCraft-Project/views/mentor/kursus-saya.php?id=<?php echo $course['id']; ?>" class="view-course">
                                Lihat Kursus
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions fade-in-up" style="animation-delay: 1s;">
                <h3 class="section-title">Aksi Cepat</h3>
                <div class="actions-grid">
                    <a href="/MindCraft-Project/views/mentor/buat-kursus-baru.php" class="action-card">
                        <div class="action-icon" style="background-color: #3A59D1;">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-content">
                            <h4>Buat Kursus Baru</h4>
                            <p>Mulai kursus baru Anda sekarang</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="/MindCraft-Project/views/mentor/kursus-saya.php" class="action-card">
                        <div class="action-icon" style="background-color: #9333EA;">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="action-content">
                            <h4>Kelola Kursus</h4>
                            <p>Edit atau hapus kursus Anda</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="/MindCraft-Project/views/mentor/pendapatan.php" class="action-card">
                        <div class="action-icon" style="background-color: #059669;">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="action-content">
                            <h4>Lihat Pendapatan</h4>
                            <p>Periksa detail penghasilan Anda</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="/MindCraft-Project/views/mentor/pengaturan.php" class="action-card">
                        <div class="action-icon" style="background-color: #DC2626;">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="action-content">
                            <h4>Pengaturan Akun</h4>
                            <p>Perbarui profil dan preferensi</p>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div> 
    </main> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/MindCraft-Project/assets/js/mentor_dashboard.js"></script>
<script>
    // Pass PHP data to JavaScript
    window.dashboardData = {
        monthlyRegistrations: <?php echo json_encode(array_values($monthlyRegistrations)); ?>,
        labels: ['<?php echo date('D', strtotime('-6 days')); ?>', 
                 '<?php echo date('D', strtotime('-5 days')); ?>', 
                 '<?php echo date('D', strtotime('-4 days')); ?>', 
                 '<?php echo date('D', strtotime('-3 days')); ?>', 
                 '<?php echo date('D', strtotime('-2 days')); ?>', 
                 '<?php echo date('D', strtotime('-1 days')); ?>', 
                 '<?php echo date('D'); ?>'],
        hasData: <?php echo array_sum($monthlyRegistrations) > 0 ? 'true' : 'false'; ?>,
        topCourses: <?php echo json_encode($topCourses); ?>
    };

    // Mobile menu toggle functionality
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>
</body>
</html>