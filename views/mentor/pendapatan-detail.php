<?php
session_start();
if (!isset($_SESSION['mentor_id'])) {
    header("Location: /MindCraft-Project/views/mentor/login.php");
    exit();
}

$mentorId = $_SESSION['mentor_id'];

// Include database connection
require_once __DIR__ . '/../../config/Database.php';
$database = new Database();
$db = $database->connect();

// Get filter parameters
$courseFilter = isset($_GET['course']) ? $_GET['course'] : 'all';
$periodFilter = isset($_GET['period']) ? $_GET['period'] : '30';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

/**
 * Get Detailed Earnings Data from Database
 */
function getDetailedEarningsData($db, $mentorId, $courseFilter, $periodFilter, $startDate, $endDate) {
    try {
        // Build conditions
        $conditions = ["e.mentor_id = :mentor_id"];
        $params = [':mentor_id' => $mentorId];
        
        if ($courseFilter !== 'all') {
            $conditions[] = "e.course_id = :course_id";
            $params[':course_id'] = $courseFilter;
        }
        
        $conditions[] = "e.created_at BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate . ' 00:00:00';
        $params[':end_date'] = $endDate . ' 23:59:59';
        
        $whereClause = implode(' AND ', $conditions);
        
        // Get summary metrics
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END) as total_earnings,
                AVG(CASE WHEN status = 'completed' THEN net_amount ELSE NULL END) as avg_earning,
                SUM(CASE WHEN status = 'completed' THEN platform_fee ELSE 0 END) as total_fees,
                MAX(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END) as highest_earning,
                MIN(CASE WHEN status = 'completed' AND net_amount > 0 THEN net_amount ELSE NULL END) as lowest_earning
            FROM earnings e
            WHERE {$whereClause}
        ");
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get daily earnings for chart
        $stmt = $db->prepare("
            SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END) as daily_earnings,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as daily_transactions
            FROM earnings e
            WHERE {$whereClause}
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->execute($params);
        $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get course breakdown
        $stmt = $db->prepare("
            SELECT 
                c.title as course_name,
                COUNT(e.id) as transaction_count,
                SUM(CASE WHEN e.status = 'completed' THEN e.net_amount ELSE 0 END) as total_earnings,
                AVG(CASE WHEN e.status = 'completed' THEN e.net_amount ELSE NULL END) as avg_earning
            FROM earnings e
            LEFT JOIN courses c ON e.course_id = c.id
            WHERE {$whereClause} AND e.transaction_type = 'course_sale'
            GROUP BY e.course_id, c.title
            ORDER BY total_earnings DESC
        ");
        $stmt->execute($params);
        $courseBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all transactions for table
        $stmt = $db->prepare("
            SELECT 
                e.*,
                c.title as course_title,
                u.username as student_name
            FROM earnings e
            LEFT JOIN courses c ON e.course_id = c.id
            LEFT JOIN users u ON e.student_id = u.id
            WHERE {$whereClause}
            ORDER BY e.created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'summary' => [
                'total_transactions' => (int)($summary['total_transactions'] ?? 0),
                'total_earnings' => (float)($summary['total_earnings'] ?? 0),
                'avg_earning' => (float)($summary['avg_earning'] ?? 0),
                'total_fees' => (float)($summary['total_fees'] ?? 0),
                'highest_earning' => (float)($summary['highest_earning'] ?? 0),
                'lowest_earning' => (float)($summary['lowest_earning'] ?? 0)
            ],
            'daily_data' => $dailyData,
            'course_breakdown' => $courseBreakdown,
            'transactions' => $transactions
        ];
        
    } catch (Exception $e) {
        error_log("Database detailed earnings error: " . $e->getMessage());
        return [
            'summary' => [
                'total_transactions' => 0,
                'total_earnings' => 0,
                'avg_earning' => 0,
                'total_fees' => 0,
                'highest_earning' => 0,
                'lowest_earning' => 0
            ],
            'daily_data' => [],
            'course_breakdown' => [],
            'transactions' => []
        ];
    }
}

/**
 * Get Courses for Filter Dropdown
 */
function getCoursesForFilter($db, $mentorId) {
    try {
        $stmt = $db->prepare("
            SELECT id, title 
            FROM courses 
            WHERE mentor_id = :mentor_id AND status = 'Published'
            ORDER BY title
        ");
        $stmt->execute([':mentor_id' => $mentorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting courses: " . $e->getMessage());
        return [];
    }
}

// Get detailed earnings data
$detailData = getDetailedEarningsData($db, $mentorId, $courseFilter, $periodFilter, $startDate, $endDate);

// Get courses list for filter
$courses = getCoursesForFilter($db, $mentorId);

// Get mentor name
$mentorName = '';
try {
    $stmt = $db->prepare("SELECT username FROM users WHERE id = :mentor_id");
    $stmt->execute([':mentor_id' => $mentorId]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
    $mentorName = $mentor['username'] ?? 'Mentor';
} catch (Exception $e) {
    error_log("Error getting mentor name: " . $e->getMessage());
    $mentorName = 'Mentor';
}

/**
 * Format Currency
 */
function formatCurrency($amount) {
    if ($amount >= 1000000) {
        return 'Rp ' . number_format($amount / 1000000, 1) . ' jt';
    } elseif ($amount >= 1000) {
        return 'Rp ' . number_format($amount / 1000, 0) . 'k';
    } else {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

/**
 * Format Date
 */
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

/**
 * Get Transaction Type Label
 */
function getTransactionTypeLabel($type) {
    $labels = [
        'course_sale' => 'Penjualan Kursus',
        'tip' => 'Tip dari Mentee',
        'bonus' => 'Bonus Platform',
        'refund' => 'Refund',
        'withdrawal' => 'Penarikan Dana'
    ];
    
    return $labels[$type] ?? 'Transaksi Lain';
}

/**
 * Get Status Badge Class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'completed' => 'status-completed',
        'pending' => 'status-pending',
        'cancelled' => 'status-cancelled'
    ];
    
    return $classes[$status] ?? 'status-pending';
}

/**
 * Get Payout Badge Class
 */
function getPayoutBadgeClass($status) {
    $classes = [
        'paid' => 'payout-paid',
        'pending' => 'payout-pending',
        'hold' => 'payout-hold'
    ];
    
    return $classes[$status] ?? 'payout-pending';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Detail Pendapatan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor-pendapatan-detail.css">
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
                <a href="/MindCraft-Project/views/mentor/dashboard.php">
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
                <a href="/MindCraft-Project/views/mentor/pendapatan.php" class="active">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="/MindCraft-Project/views/mentor/pendapatan.php">Pendapatan</a>
                    <span class="separator">›</span>
                    <span class="current">Detail Analitik</span>
                </div>
                <h1>Detail Analitik Pendapatan</h1>
                <p class="header-subtitle">Analisis mendalam tentang sumber pendapatan dan tren transaksi Anda</p>
            </div>
        </div>
        
        <div class="content-body">
            <!-- Advanced Filter Controls -->
            <div class="advanced-filter-section">
                <div class="filter-header">
                    <h3>Filter Analitik</h3>
                    <button id="resetFilters" class="btn-reset">Reset Filter</button>
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Kursus</label>
                        <div class="custom-select">
                            <select id="courseSelect" name="course">
                                <option value="all" <?php echo $courseFilter === 'all' ? 'selected' : ''; ?>>Semua Kursus</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course['id']); ?>" 
                                            <?php echo $courseFilter == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label>Periode</label>
                        <div class="custom-select">
                            <select id="periodSelect" name="period">
                                <option value="7" <?php echo $periodFilter === '7' ? 'selected' : ''; ?>>7 Hari Terakhir</option>
                                <option value="30" <?php echo $periodFilter === '30' ? 'selected' : ''; ?>>30 Hari Terakhir</option>
                                <option value="90" <?php echo $periodFilter === '90' ? 'selected' : ''; ?>>90 Hari Terakhir</option>
                                <option value="365" <?php echo $periodFilter === '365' ? 'selected' : ''; ?>>1 Tahun Terakhir</option>
                                <option value="custom" <?php echo $periodFilter === 'custom' ? 'selected' : ''; ?>>Rentang Kustom</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-group" id="dateRangeGroup" style="<?php echo $periodFilter === 'custom' ? '' : 'display: none;'; ?>">
                        <label>Dari Tanggal</label>
                        <input type="date" id="startDate" value="<?php echo $startDate; ?>" class="date-input">
                    </div>
                    
                    <div class="filter-group" id="dateRangeGroupEnd" style="<?php echo $periodFilter === 'custom' ? '' : 'display: none;'; ?>">
                        <label>Sampai Tanggal</label>
                        <input type="date" id="endDate" value="<?php echo $endDate; ?>" class="date-input">
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button id="applyFilters" class="btn btn-primary">Terapkan Filter</button>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics Cards -->
            <div class="summary-stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo formatCurrency($detailData['summary']['total_earnings']); ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>
                
                <div class="stat-card secondary">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $detailData['summary']['total_transactions']; ?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo formatCurrency($detailData['summary']['avg_earning']); ?></div>
                        <div class="stat-label">Rata-rata per Transaksi</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">🏦</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo formatCurrency($detailData['summary']['total_fees']); ?></div>
                        <div class="stat-label">Total Fee Platform</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- Daily Earnings Chart -->
                <div class="chart-section">
                    <div class="chart-header">
                        <h3>Tren Pendapatan Harian</h3>
                        <div class="chart-actions">
                            <button class="chart-toggle active" data-chart="earnings">Pendapatan</button>
                            <button class="chart-toggle" data-chart="transactions">Transaksi</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailyEarningsChart"></canvas>
                    </div>
                </div>
                
                <!-- Course Performance Chart -->
                <div class="chart-section">
                    <div class="chart-header">
                        <h3>Performa Kursus</h3>
                        <div class="chart-legend">
                            <span class="legend-item">
                                <span class="legend-color" style="background: #3A59D1;"></span>
                                Pendapatan
                            </span>
                            <span class="legend-item">
                                <span class="legend-color" style="background: #90C7F8;"></span>
                                Jumlah Transaksi
                            </span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="coursePerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Course Breakdown Table -->
            <div class="breakdown-section">
                <div class="section-header">
                    <h3>Breakdown Pendapatan per Kursus</h3>
                </div>
                
                <div class="breakdown-table-container">
                    <table class="breakdown-table">
                        <thead>
                            <tr>
                                <th>Nama Kursus</th>
                                <th data-sort="transactions">Jumlah Transaksi</th>
                                <th data-sort="earnings">Total Pendapatan</th>
                                <th data-sort="average">Rata-rata per Transaksi</th>
                                <th>Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detailData['course_breakdown'])): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
                                        <div style="font-weight: 500; margin-bottom: 8px;">Belum ada data breakdown</div>
                                        <div style="font-size: 13px;">Data akan muncul setelah ada transaksi dalam periode ini</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $totalEarnings = $detailData['summary']['total_earnings'];
                                foreach ($detailData['course_breakdown'] as $course): 
                                    $contribution = $totalEarnings > 0 ? ($course['total_earnings'] / $totalEarnings) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="course-info">
                                            <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="transaction-count"><?php echo $course['transaction_count']; ?></span>
                                    </td>
                                    <td class="text-right">
                                        <span class="earnings-amount"><?php echo formatCurrency($course['total_earnings']); ?></span>
                                    </td>
                                    <td class="text-right">
                                        <span class="average-amount"><?php echo formatCurrency($course['avg_earning']); ?></span>
                                    </td>
                                    <td>
                                        <div class="contribution-cell">
                                            <div class="contribution-bar">
                                                <div class="contribution-fill" style="width: <?php echo $contribution; ?>%;"></div>
                                            </div>
                                            <span class="contribution-percentage"><?php echo number_format($contribution, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Transactions Table -->
            <div class="transactions-detail-section">
                <div class="section-header">
                    <h3>Detail Transaksi</h3>
                    <div class="section-actions">
                    </div>
                </div>
                
                <div class="transactions-table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th data-sort="date">Tanggal</th>
                                <th>Jenis Transaksi</th>
                                <th>Student/Mentee</th>
                                <th data-sort="amount">Jumlah Kotor</th>
                                <th>Fee Platform</th>
                                <th data-sort="net">Jumlah Bersih</th>
                                <th>Status</th>
                                <th>Status Payout</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <?php if (empty($detailData['transactions'])): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: #718096;">
                                        <div style="font-size: 48px; margin-bottom: 16px;">💳</div>
                                        <div style="font-weight: 500; margin-bottom: 8px;">Belum ada transaksi</div>
                                        <div style="font-size: 13px;">Transaksi akan muncul setelah ada penjualan dalam periode ini</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($detailData['transactions'] as $transaction): ?>
                                <tr class="transaction-row">
                                    <td class="date-cell">
                                        <?php echo formatDate($transaction['created_at'], 'd M Y'); ?>
                                        <div class="time-small"><?php echo formatDate($transaction['created_at'], 'H:i'); ?></div>
                                    </td>
                                    <td>
                                        <div class="transaction-type">
                                            <span class="type-badge"><?php echo getTransactionTypeLabel($transaction['transaction_type']); ?></span>
                                            <?php if (!empty($transaction['course_title'])): ?>
                                                <div class="course-small"><?php echo htmlspecialchars($transaction['course_title']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($transaction['student_name'])): ?>
                                            <div class="student-info">
                                                <div class="student-avatar"><?php echo strtoupper(substr($transaction['student_name'], 0, 1)); ?></div>
                                                <span class="student-name"><?php echo htmlspecialchars($transaction['student_name']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-student">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount-cell">
                                        <?php if ($transaction['transaction_type'] === 'withdrawal'): ?>
                                            <span class="amount-negative">-<?php echo formatCurrency(abs($transaction['amount'])); ?></span>
                                        <?php else: ?>
                                            <span class="amount-positive"><?php echo formatCurrency($transaction['amount']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fee-cell">
                                        <?php if ($transaction['platform_fee'] > 0): ?>
                                            <span class="fee-amount"><?php echo formatCurrency($transaction['platform_fee']); ?></span>
                                            <div class="fee-percentage">(<?php echo number_format($transaction['commission_rate'], 0); ?>%)</div>
                                        <?php else: ?>
                                            <span class="no-fee">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="net-cell">
                                        <?php if ($transaction['transaction_type'] === 'withdrawal'): ?>
                                            <span class="net-negative">-<?php echo formatCurrency(abs($transaction['net_amount'])); ?></span>
                                        <?php else: ?>
                                            <span class="net-positive"><?php echo formatCurrency($transaction['net_amount']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadgeClass($transaction['status']); ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="payout-badge <?php echo getPayoutBadgeClass($transaction['payout_status']); ?>">
                                            <?php echo ucfirst($transaction['payout_status']); ?>
                                        </span>
                                        <?php if ($transaction['payout_date']): ?>
                                            <div class="payout-date"><?php echo formatDate($transaction['payout_date'], 'd M'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-container">
                    <div class="pagination-info">
                        Menampilkan 1-<?php echo count($detailData['transactions']); ?> dari <?php echo $detailData['summary']['total_transactions']; ?> transaksi
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled>‹ Sebelumnya</button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn">2</button>
                        <button class="pagination-btn">3</button>
                        <button class="pagination-btn">Selanjutnya ›</button>
                    </div>
                </div>
            </div>

            <!-- Analytics Insights -->
            <div class="insights-section">
                <div class="section-header">
                    <h3>💡 Insights & Rekomendasi</h3>
                    <p>Analisis otomatis berdasarkan data pendapatan Anda</p>
                </div>
                
                <div class="insights-grid">
                    <div class="insight-card positive">
                        <div class="insight-icon">📈</div>
                        <div class="insight-content">
                            <h4>Tren Positif</h4>
                            <p>Pendapatan Anda <?php echo $detailData['summary']['total_earnings'] > 0 ? 'mengalami peningkatan' : 'mulai berkembang'; ?>. <?php echo !empty($detailData['course_breakdown']) ? 'Kursus "' . htmlspecialchars($detailData['course_breakdown'][0]['course_name']) . '" menjadi kontributor utama.' : ''; ?></p>
                        </div>
                    </div>
                    
                    <div class="insight-card neutral">
                        <div class="insight-icon">⚡</div>
                        <div class="insight-content">
                            <h4>Optimisasi Harga</h4>
                            <p>Rata-rata transaksi Anda <?php echo formatCurrency($detailData['summary']['avg_earning']); ?>. Pertimbangkan untuk membuat paket premium untuk meningkatkan nilai transaksi.</p>
                        </div>
                    </div>
                    
                    <div class="insight-card warning">
                        <div class="insight-icon">💰</div>
                        <div class="insight-content">
                            <h4>Diversifikasi Pendapatan</h4>
                            <p>Fokuskan pada kursus dengan performa tinggi dan pertimbangkan untuk membuat konten serupa untuk meningkatkan portfolio Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.earningsDetailData = {
            summary: <?php echo json_encode($detailData['summary']); ?>,
            dailyData: <?php echo json_encode($detailData['daily_data']); ?>,
            courseBreakdown: <?php echo json_encode($detailData['course_breakdown']); ?>,
            transactions: <?php echo json_encode($detailData['transactions']); ?>,
            currentFilters: {
                course: '<?php echo $courseFilter; ?>',
                period: '<?php echo $periodFilter; ?>',
                startDate: '<?php echo $startDate; ?>',
                endDate: '<?php echo $endDate; ?>'
            }
        };

        // Filter controls functionality
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelect = document.getElementById('periodSelect');
            const dateRangeGroup = document.getElementById('dateRangeGroup');
            const dateRangeGroupEnd = document.getElementById('dateRangeGroupEnd');
            const applyFiltersBtn = document.getElementById('applyFilters');
            const resetFiltersBtn = document.getElementById('resetFilters');
            
            // Toggle date range inputs based on period selection
            periodSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    dateRangeGroup.style.display = 'flex';
                    dateRangeGroupEnd.style.display = 'flex';
                } else {
                    dateRangeGroup.style.display = 'none';
                    dateRangeGroupEnd.style.display = 'none';
                }
            });
            
            // Apply filters button
            applyFiltersBtn.addEventListener('click', function() {
                const course = document.getElementById('courseSelect').value;
                const period = document.getElementById('periodSelect').value;
                let startDate = document.getElementById('startDate').value;
                let endDate = document.getElementById('endDate').value;
                
                // Set default dates based on period if not custom
                if (period !== 'custom') {
                    const today = new Date();
                    endDate = today.toISOString().split('T')[0];
                    
                    const startDateObj = new Date();
                    startDateObj.setDate(today.getDate() - parseInt(period));
                    startDate = startDateObj.toISOString().split('T')[0];
                }
                
                // Build URL with parameters
                let url = window.location.pathname + `?course=${course}&period=${period}`;
                
                if (period === 'custom') {
                    url += `&start_date=${startDate}&end_date=${endDate}`;
                }
                
                window.location.href = url;
            });
            
            // Reset filters button
            resetFiltersBtn.addEventListener('click', function() {
                window.location.href = window.location.pathname;
            });
            
            // Initialize charts
            initializeCharts();
        });
        
        function initializeCharts() {
            // Daily Earnings Chart
            const dailyCtx = document.getElementById('dailyEarningsChart').getContext('2d');
            const dailyLabels = window.earningsDetailData.dailyData.map(item => item.date);
            const dailyEarningsData = window.earningsDetailData.dailyData.map(item => item.daily_earnings);
            const dailyTransactionsData = window.earningsDetailData.dailyData.map(item => item.daily_transactions);
            
            const dailyEarningsChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: dailyEarningsData,
                        borderColor: '#3A59D1',
                        backgroundColor: 'rgba(58, 89, 209, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Pendapatan: Rp ' + context.raw.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp' + (value / 1000000).toFixed(1) + 'jt';
                                    } else if (value >= 1000) {
                                        return 'Rp' + (value / 1000).toFixed(0) + 'k';
                                    }
                                    return 'Rp' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Toggle between earnings and transactions
            document.querySelectorAll('.chart-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    document.querySelectorAll('.chart-toggle').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const chartType = this.getAttribute('data-chart');
                    
                    if (chartType === 'earnings') {
                        dailyEarningsChart.data.datasets = [{
                            label: 'Pendapatan',
                            data: dailyEarningsData,
                            borderColor: '#3A59D1',
                            backgroundColor: 'rgba(58, 89, 209, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y'
                        }];
                        
                        dailyEarningsChart.options.scales.y.ticks.callback = function(value) {
                            if (value >= 1000000) {
                                return 'Rp' + (value / 1000000).toFixed(1) + 'jt';
                            } else if (value >= 1000) {
                                return 'Rp' + (value / 1000).toFixed(0) + 'k';
                            }
                            return 'Rp' + value;
                        };
                    } else {
                        dailyEarningsChart.data.datasets = [{
                            label: 'Transaksi',
                            data: dailyTransactionsData,
                            borderColor: '#059669',
                            backgroundColor: 'rgba(5, 150, 105, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y'
                        }];
                        
                        dailyEarningsChart.options.scales.y.ticks.callback = function(value) {
                            return value;
                        };
                    }
                    
                    dailyEarningsChart.update();
                });
            });
            
            // Course Performance Chart
            const courseCtx = document.getElementById('coursePerformanceChart').getContext('2d');
            const courseLabels = window.earningsDetailData.courseBreakdown.map(item => item.course_name);
            const courseEarningsData = window.earningsDetailData.courseBreakdown.map(item => item.total_earnings);
            const courseTransactionsData = window.earningsDetailData.courseBreakdown.map(item => item.transaction_count);
            
            const coursePerformanceChart = new Chart(courseCtx, {
                type: 'bar',
                data: {
                    labels: courseLabels,
                    datasets: [
                        {
                            label: 'Pendapatan',
                            data: courseEarningsData,
                            backgroundColor: '#3A59D1',
                            borderColor: '#3A59D1',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Transaksi',
                            data: courseTransactionsData,
                            backgroundColor: '#90C7F8',
                            borderColor: '#90C7F8',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label === 'Pendapatan') {
                                        label += ': Rp ' + context.raw.toLocaleString('id-ID');
                                    } else {
                                        label += ': ' + context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Pendapatan'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp' + (value / 1000000).toFixed(1) + 'jt';
                                    } else if (value >= 1000) {
                                        return 'Rp' + (value / 1000).toFixed(0) + 'k';
                                    }
                                    return 'Rp' + value;
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Transaksi'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>