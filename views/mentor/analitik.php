<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Mentor') {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../../config/Database.php';

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->connect();
    
    $mentorId = $_SESSION['user_id'];
    
    // Get mentor name
    $query = "SELECT username FROM users WHERE id = :mentor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);
    $mentorName = $mentor['username'] ?? 'Mentor';
    
    // Get total courses
    $query = "SELECT COUNT(*) as total_courses FROM courses WHERE mentor_id = :mentor_id AND status = 'Published'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCourses = $result['total_courses'] ?? 0;
    
    // Get total students (mentees)
    $query = "SELECT COUNT(DISTINCT e.student_id) as total_mentees 
              FROM enrollments e
              JOIN courses c ON e.course_id = c.id
              WHERE c.mentor_id = :mentor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalMentees = $result['total_mentees'] ?? 0;
    
    // Get total earnings (completed transactions only)
    $query = "SELECT SUM(net_amount) as total_earnings 
              FROM earnings 
              WHERE mentor_id = :mentor_id AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalEarnings = $result['total_earnings'] ?? 0;
    
    // Get total modules
    $query = "SELECT COUNT(*) as total_modules 
              FROM course_modules cm
              JOIN courses c ON cm.course_id = c.id
              WHERE c.mentor_id = :mentor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalModules = $result['total_modules'] ?? 0;
    
    // Get monthly earnings data for chart
    $query = "SELECT 
                  DATE_FORMAT(created_at, '%Y-%m') as month,
                  SUM(net_amount) as monthly_revenue
              FROM earnings
              WHERE mentor_id = :mentor_id 
                AND status = 'completed'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $monthlyEarnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent enrollments as activities
    $query = "SELECT 
                  c.title as course_title,
                  u.username as student_name,
                  e.enrollment_date,
                  CONCAT('Pendaftaran baru untuk kursus ', c.title, ' oleh ', u.username) as message,
                  DATE_FORMAT(e.enrollment_date, '%d %b %Y %H:%i') as formatted_date
              FROM enrollments e
              JOIN courses c ON e.course_id = c.id
              JOIN users u ON e.student_id = u.id
              WHERE c.mentor_id = :mentor_id
              ORDER BY e.enrollment_date DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Set default values
    $mentorName = $_SESSION['username'] ?? 'Mentor';
    $totalCourses = $totalMentees = $totalEarnings = $totalModules = 0;
    $monthlyEarnings = [];
    $recentActivities = [];
}

function formatRupiah($number) {
    if ($number >= 1000000) {
        return 'Rp ' . number_format($number / 1000000, 1) . ' jt';
    } elseif ($number >= 1000) {
        return 'Rp ' . number_format($number / 1000, 0) . 'k';
    }
    return 'Rp ' . number_format($number);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik Kinerja - MindCraft</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor-analitik.css">
</head>
<body>
    <div class="dashboard-container">
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
                    <a href="/MindCraft-Project/views/mentor/pendapatan.php">
                        <i class="fas fa-wallet"></i>
                        <span>Pendapatan</span>
                    </a>
                </li>
                <li>
                    <a href="/MindCraft-Project/views/mentor/analitik.php" class="active">
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
            <!-- Header -->
            <header class="main-header">
                <h1>Analitik Kinerja</h1>
            </header>

            <!-- Welcome Message -->
            <div class="welcome-message">
                <p>Selamat datang di pusat data kinerja Anda, <strong><?php echo htmlspecialchars($mentorName); ?></strong>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                    <div class="stat-info">
                        <h3>Total Kursus</h3>
                        <p><?php echo number_format($totalCourses); ?></p>
                        <span>Kursus Aktif</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3>Total Mentee</h3>
                        <p><?php echo number_format($totalMentees); ?></p>
                        <span>Siswa Terdaftar</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-info">
                        <h3>Total Pendapatan</h3>
                        <p><?php echo formatRupiah($totalEarnings); ?></p>
                        <span>Pendapatan Bersih</span>
                    </div>
                </div>
            </div>

            <!-- Summary Bar -->
            <div class="summary-bar">
                <div class="summary-item">
                    <i class="fas fa-cubes"></i>
                    <div>
                        <span>Total Modul</span>
                        <h4><?php echo number_format($totalModules); ?></h4>
                    </div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <span>Periode</span>
                        <h4>12 Bulan</h4>
                    </div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <span>Kursus Terlaris</span>
                        <h4><?php 
                            // Query untuk mendapatkan kursus dengan pendapatan tertinggi
                            $query = "SELECT c.title, SUM(e.net_amount) as total_earnings
                                      FROM earnings e
                                      JOIN courses c ON e.course_id = c.id
                                      WHERE e.mentor_id = :mentor_id AND e.status = 'completed'
                                      GROUP BY e.course_id
                                      ORDER BY total_earnings DESC
                                      LIMIT 1";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':mentor_id', $mentorId);
                            $stmt->execute();
                            $bestCourse = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $bestCourse ? htmlspecialchars(substr($bestCourse['title'], 0, 15) . (strlen($bestCourse['title']) > 15 ? '...' : '')) : 'Tidak ada';
                        ?></h4>
                    </div>
                </div>
            </div>

            <!-- Charts and Activities -->
            <div class="content-grid">
                <!-- Revenue Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Grafik Pendapatan (12 Bulan Terakhir)</h3>
                        <div class="chart-legend">
                            <span><i class="fas fa-square" style="color: #4F46E5;"></i> Pendapatan Bulanan</span>
                        </div>
                    </div>
                    <canvas id="revenueChart"></canvas>
                </div>

                <!-- Recent Activities -->
                <div class="activities-container">
                    <h3>Aktivitas Terbaru</h3>
                    <?php if (!empty($recentActivities)): ?>
                        <div class="activities-list">
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><?php echo htmlspecialchars($activity['message']); ?></p>
                                        <small><?php echo $activity['formatted_date']; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-pie"></i>
                            <p>Belum ada aktivitas terbaru</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const monthlyData = <?php echo json_encode($monthlyEarnings); ?>;
            
            const labels = monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleString('id-ID', { month: 'short' });
            });
            
            const dataValues = monthlyData.map(item => item.monthly_revenue);
            
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pendapatan Bulanan',
                        data: dataValues,
                        backgroundColor: 'rgba(79, 70, 229, 0.7)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value/1000) + 'k';
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
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
        });
    </script>
</body>
</html>