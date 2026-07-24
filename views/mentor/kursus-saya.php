<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Mentor') {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/MentorController.php';

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

$database = new Database();
$controller = new MentorController($database);
$mentorId = $_SESSION['user_id'];

$mentor = $controller->getMentorData($mentorId);
$pageData = $controller->getCoursesPageData($mentorId);
$courses = $pageData['courses'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kursus Saya - Mentor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_kursus-saya.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
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
                <a href="/MindCraft-Project/views/mentor/kursus-saya.php" class="active">
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
                <h1>Kursus Saya</h1>
            </div>
            <div class="content-body">
                
                <div class="course-grid-container">
                    <?php if (empty($courses)): ?>
                        <div class="empty-state-container">
                            <i class="fas fa-book-open"></i>
                            <p>Anda belum memiliki kursus.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card">
                                <div class="card-thumbnail">
                                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="Thumbnail Kursus">
                                    <span class="card-status status-<?php echo strtolower(htmlspecialchars($course['status'] ?? 'draft')); ?>">
                                        <?php echo htmlspecialchars($course['status'] ?? 'Draft'); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <span class="card-category"><?php echo htmlspecialchars($course['category']); ?></span>
                                    <h3 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>

                                    <div class="card-details">
                                        <span><i class="fas fa-signal"></i> <?php echo htmlspecialchars($course['difficulty'] ?? 'N/A'); ?></span>
                                        <span><i class="fas fa-tag"></i> Rp <?php echo number_format($course['price'] ?? 0, 0, ',', '.'); ?></span>
                                    </div>

                                    <div class="card-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo number_format($course['student_count']); ?> Siswa</span>
                                        </div>
                                        <div class="meta-item">
                                            <a href="/MindCraft-Project/views/mentor/edit-course.php?id=<?php echo $course['course_id']; ?>" title="Edit Kursus">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div> 
        </main> 
    </div> 

    <script src="/MindCraft-Project/assets/js/mentor_dashboard.js"></script>
</body>
</html>