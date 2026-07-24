<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Mentor') {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/MentorController.php';
$database = new Database();
$db = $database->connect();

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
}

// Function to format currency
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Get mentor ID
$mentorId = $_SESSION['user_id'];

// Query for total revenue (all completed course earnings)
$query = "SELECT SUM(net_amount) as total_revenue 
          FROM earnings 
          WHERE mentor_id = :mentor_id 
          AND status = 'completed'
          AND transaction_type = 'course_sale'";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Query for total withdrawals
$query = "SELECT SUM(amount) as total_withdrawals 
          FROM withdrawals 
          WHERE mentor_id = :mentor_id 
          AND status = 'completed'";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$totalWithdrawals = $stmt->fetch(PDO::FETCH_ASSOC)['total_withdrawals'] ?? 0;

// Calculate available balance (total revenue - total withdrawals)
$availableBalance = $totalRevenue - $totalWithdrawals;
if ($availableBalance < 0) $availableBalance = 0;

// Query for total course sales count
$query = "SELECT COUNT(*) as total_sales 
          FROM earnings 
          WHERE mentor_id = :mentor_id 
          AND transaction_type = 'course_sale' 
          AND status = 'completed'";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;

// Query for recent earnings (last 5 transactions)
$query = "SELECT e.*, c.title as course_title, 
                 t.customer_name as student_name,
                 t.transaction_date as payment_date
          FROM earnings e
          LEFT JOIN courses c ON e.course_id = c.id
          LEFT JOIN transactions t ON e.reference_id = t.transaction_id
          WHERE e.mentor_id = :mentor_id
          AND e.status = 'completed'
          AND e.transaction_type = 'course_sale'
          ORDER BY e.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$recentEarnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query for monthly earnings data for chart
$query = "SELECT 
            MONTH(e.created_at) as month, 
            SUM(e.net_amount) as monthly_earnings
          FROM earnings e
          WHERE e.mentor_id = :mentor_id
          AND e.status = 'completed'
          AND e.transaction_type = 'course_sale'
          AND YEAR(e.created_at) = YEAR(CURRENT_DATE())
          GROUP BY MONTH(e.created_at)
          ORDER BY MONTH(e.created_at)";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$monthlyEarningsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query for mentor's payout settings
$query = "SELECT minimum_payout, payout_method FROM mentor_settings WHERE mentor_id = :mentor_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$payoutSettings = $stmt->fetch(PDO::FETCH_ASSOC);
$minimumWithdrawal = $payoutSettings['minimum_payout'] ?? 100000;

// Prepare chart data
$chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$chartEarnings = array_fill(0, 12, 0);

foreach ($monthlyEarningsData as $data) {
    $monthIndex = $data['month'] - 1;
    $chartEarnings[$monthIndex] = $data['monthly_earnings'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendapatan - Mentor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_pendapatan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #e0e7ff;
            --primary-dark: #4f46e5;
            --primary-extra-light: #f5f7ff;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --secondary: #8b5cf6;
            --success: #10b981;
            --success-light: #d1fae5;
            --success-dark: #059669;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --warning-dark: #d97706;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --danger-dark: #dc2626;
            --info: #3b82f6;
            --info-light: #dbeafe;
            --info-dark: #2563eb;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --gray-950: #030712;
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --shadow-primary: 0 4px 14px -2px rgba(99, 102, 241, 0.3);
            --shadow-primary-lg: 0 10px 25px -5px rgba(79, 70, 229, 0.3);
        }

       /* Base Styles */
body {
  font-family: "Inter", sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f5f7fa;
  color: #333;
}

/* Header Styles */
.top-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo {
  font-size: 1.5rem;
  font-weight: 700;
  color: #3a59d1;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.header-notification {
  position: relative;
  cursor: pointer;
  font-size: 1.2rem;
  color: #64748b;
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background-color: #dc2626;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: bold;
}

.header-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.profile-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #3a59d1;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.profile-name {
  font-weight: 500;
}

/* Sidebar Styles */
.sidebar {
  width: 250px;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  background-color: #fff;
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
  padding-top: 70px;
  transition: transform 0.3s ease;
  z-index: 90;
}

.sidebar.open {
  transform: translateX(0);
}

.sidebar-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.sidebar-title {
  font-weight: 600;
  color: #64748b;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.sidebar-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-menu li a {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  color: #64748b;
  text-decoration: none;
  transition: all 0.2s ease;
}

.sidebar-menu li a:hover {
  background-color: #f1f5f9;
  color: #3a59d1;
}

.sidebar-menu li a.active {
  background-color: #e0e7ff;
  color: #3a59d1;
  border-left: 3px solid #3a59d1;
}

.sidebar-menu li a i {
  width: 20px;
  text-align: center;
}

.sidebar-footer {
  position: absolute;
  bottom: 0;
  width: 100%;
  padding: 1rem;
  text-align: center;
  font-size: 0.75rem;
  color: #94a3b8;
  border-top: 1px solid #e2e8f0;
}


        .main-content {
            margin-left: 280px;
            padding: 2rem;
            margin-top: 70px;
            transition: all 0.3s ease;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .content-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-withdraw {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-primary);
        }

        .btn-withdraw:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-primary-lg);
        }

        .btn-withdraw:active {
            transform: translateY(0);
        }

        .revenue-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background-color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .summary-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            z-index: 1;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-primary-lg);
        }

        .summary-card .icon {
            width: 56px;
            height: 56px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .summary-card:hover .icon {
            transform: scale(1.05);
        }

        .summary-card .icon.balance {
            background-color: var(--primary);
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.25);
        }

        .summary-card .icon.revenue {
            background-color: var(--success);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.25);
        }

        .summary-card .icon.withdrawal {
            background-color: var(--danger);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.25);
        }

        .summary-card .details .title {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin: 0 0 0.5rem 0;
            font-weight: 500;
        }

        .summary-card .details .value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 0.5rem 0;
            line-height: 1.2;
            transition: color 0.3s ease;
        }

        .summary-card:hover .value {
            color: var(--primary);
        }

        .summary-card .details .info {
            font-size: 0.75rem;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .chart-container {
            position: relative;
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .chart-container:hover {
            box-shadow: var(--shadow-md);
        }

        .chart-header {
            padding: 1rem 1.5rem 0;
        }

        .chart-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .chart-header h3::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 16px;
            background: var(--primary-gradient);
            border-radius: 2px;
            margin-right: 0.75rem;
        }

        .chart-wrapper {
            height: 320px;
            padding: 0.5rem 1rem 1rem;
            position: relative;
        }

        .transaction-history-card {
            background-color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .transaction-history-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
        }

        .transaction-history-card h3::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 16px;
            background: var(--primary-gradient);
            border-radius: 2px;
            margin-right: 0.75rem;
        }

        .transaction-table-wrapper {
            overflow-x: auto;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .transaction-table th {
            background-color: var(--gray-50);
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            border-bottom: 1px solid var(--gray-200);
        }

        .transaction-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.875rem;
            color: var(--gray-700);
            white-space: nowrap;
        }

        .transaction-table tr:last-child td {
            border-bottom: none;
        }

        .amount.positive {
            color: var(--success-dark);
            font-weight: 600;
        }

        .btn-detail {
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            background-color: var(--primary-light);
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-detail:hover {
            background-color: #d1d5f9;
            border-color: #b8bdf2;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray-400);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-200);
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
            color: var(--gray-600);
            font-weight: 600;
        }

        .empty-state p {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .transaction-table tr {
            transition: all 0.15s ease;
        }

        .transaction-table tr:hover {
            background-color: var(--gray-50);
        }

        .transaction-table tr:hover td {
            color: var(--gray-800);
        }

        @media (max-width: 1024px) {
            .main-content {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .revenue-summary-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
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

    <main class="main-content">
        <div class="content-header">
            <h1>Pendapatan</h1>
            <button class="btn-withdraw" id="withdrawBtn">
                <i class="fas fa-wallet"></i> Tarik Dana
            </button>
        </div>

        <div class="content-body">
            <!-- Summary Cards -->
            <div class="revenue-summary-cards">
                <div class="summary-card fade-in-up">
                    <div class="icon balance">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="details">
                        <div class="title">Saldo Tersedia</div>
                        <div class="value"><?php echo formatRupiah($availableBalance); ?></div>
                        <div class="info">Dapat ditarik sekarang</div>
                    </div>
                </div>
                
                <div class="summary-card fade-in-up" style="animation-delay: 0.1s;">
                    <div class="icon revenue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="details">
                        <div class="title">Total Pendapatan</div>
                        <div class="value"><?php echo formatRupiah($totalRevenue); ?></div>
                        <div class="info">Sejak bergabung</div>
                    </div>
                </div>
                
                <div class="summary-card fade-in-up" style="animation-delay: 0.2s;">
                    <div class="icon revenue">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="details">
                        <div class="title">Total Penjualan</div>
                        <div class="value"><?php echo number_format($totalSales, 0, ',', '.'); ?></div>
                        <div class="info">Kursus terjual</div>
                    </div>
                </div>
                
                <div class="summary-card fade-in-up" style="animation-delay: 0.3s;">
                    <div class="icon withdrawal">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="details">
                        <div class="title">Total Penarikan</div>
                        <div class="value"><?php echo formatRupiah($totalWithdrawals); ?></div>
                        <div class="info">Dana ditarik</div>
                    </div>
                </div>
            </div>

            <!-- Earnings Chart -->
            <div class="chart-container fade-in-up" style="animation-delay: 0.4s;">
                <div class="chart-header">
                    <h3>Pendapatan Bulanan</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="earningsChart"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="transaction-history-card fade-in-up" style="animation-delay: 0.5s;">
                <h3>Riwayat Pendapatan Terbaru</h3>
                <div class="transaction-table-wrapper">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Siswa</th>
                                <th style="text-align: right;">Jumlah</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentEarnings)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem;">
                                        <div class="empty-state">
                                            <i class="fas fa-coins"></i>
                                            <h3>Belum ada riwayat pendapatan</h3>
                                            <p>Anda akan melihat riwayat pendapatan di sini setelah kursus Anda mulai terjual</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentEarnings as $earning): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($earning['payment_date'])); ?></td>
                                        <td>
                                            <?php 
                                                $description = "Pendapatan dari ";
                                                if ($earning['course_title']) {
                                                    $description .= "Kursus: " . htmlspecialchars($earning['course_title']);
                                                } else {
                                                    $description .= "Sumber lain";
                                                }
                                                echo $description;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($earning['student_name'] ?? 'N/A'); ?></td>
                                        <td class="amount positive" style="text-align: right;">
                                            + <?php echo formatRupiah($earning['net_amount']); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="/MindCraft-Project/views/mentor/pendapatan-detail.php?id=<?php echo $earning['id']; ?>" class="btn-detail">
                                                Lihat Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="/MindCraft-Project/assets/js/mentor_dashboard.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize earnings chart
            const chartCanvas = document.getElementById('earningsChart');
            if (chartCanvas) {
                const ctx = chartCanvas.getContext('2d');
                
                const earningsChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Pendapatan',
                            data: <?php echo json_encode($chartEarnings); ?>,
                            backgroundColor: '#4F46E5',
                            borderColor: '#3A59D1',
                            borderWidth: 0,
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 40
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
                                backgroundColor: 'rgba(58, 89, 209, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#3305BC',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: false,
                                titleFont: {
                                    family: 'Inter',
                                    size: 13,
                                    weight: '500'
                                },
                                bodyFont: {
                                    family: 'Inter',
                                    size: 12,
                                    weight: '400'
                                },
                                callbacks: {
                                    title: function(context) {
                                        return context[0].label;
                                    },
                                    label: function(context) {
                                        return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#718096',
                                    font: {
                                        family: 'Inter',
                                        size: 12,
                                        weight: '400'
                                    },
                                    padding: 8
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.06)',
                                    drawBorder: false,
                                    lineWidth: 1
                                },
                                ticks: {
                                    color: '#718096',
                                    font: {
                                        family: 'Inter',
                                        size: 12,
                                        weight: '400'
                                    },
                                    padding: 8,
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                                        } else if (value >= 1000) {
                                            return 'Rp ' + (value / 1000).toFixed(0) + 'k';
                                        } else {
                                            return 'Rp ' + value;
                                        }
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1500,
                            easing: 'easeOutQuart'
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }

            // Withdrawal button functionality
            const withdrawBtn = document.getElementById('withdrawBtn');
            if (withdrawBtn) {
                withdrawBtn.addEventListener('click', function() {
                    const availableBalance = <?php echo $availableBalance; ?>;
                    const minimumWithdrawal = <?php echo $minimumWithdrawal; ?>;
                    
                    if (availableBalance < minimumWithdrawal) {
                        alert('Minimum penarikan adalah ' + formatCurrency(minimumWithdrawal) + '. Saldo Anda saat ini: ' + formatCurrency(availableBalance));
                        return;
                    }
                    
                    window.location.href = '/MindCraft-Project/views/mentor/tarik-dana.php';
                });
            }

            // Format currency function
            function formatCurrency(amount) {
                if (amount >= 1000000) {
                    return 'Rp ' + (amount / 1000000).toFixed(1) + ' jt';
                } else if (amount >= 1000) {
                    return 'Rp ' + (amount / 1000).toFixed(0) + 'k';
                } else {
                    return 'Rp ' + amount.toLocaleString('id-ID');
                }
            }

            // Initialize animations
            setTimeout(() => {
                const elements = document.querySelectorAll('.fade-in-up');
                elements.forEach((element, index) => {
                    element.style.opacity = '0';
                    element.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 300);
        });
    </script>
</body>
</html>