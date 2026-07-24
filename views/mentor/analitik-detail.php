<?php
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
    $mentorName = $mentor['username'] ?? $_SESSION['username'] ?? 'Mentor';
    
    // Filter parameters
    $selectedCourse = isset($_GET['course']) ? $_GET['course'] : 'all';
    $selectedPeriod = isset($_GET['period']) ? $_GET['period'] : '30';

    // Get analytics data from database
    $detailData = $controller->getAnalyticsDetailData($mentorId, $selectedCourse, $selectedPeriod);
    
    // Extract data for template
    $totalMentees = $detailData['totalMentees'] ?? 0;
    $activeMentees = $detailData['activeMentees'] ?? 0;
    $completionRate = $detailData['completionRate'] ?? 0;
    $avgTimeSpent = $detailData['avgTimeSpent'] ?? 0;
    $courseEngagement = $detailData['courseEngagement'] ?? [];
    $weeklyActivity = $detailData['weeklyActivity'] ?? array_fill(0, 7, 0);
    $menteeProgress = $detailData['menteeProgress'] ?? [];

    // Get courses from database
    $courses = $database->fetchAll("
        SELECT id, title 
        FROM courses 
        WHERE mentor_id = ?
        ORDER BY title
    ", [$mentorId]);

} catch (Exception $e) {
    error_log("Analytics detail page error: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat memuat data analitik detail.";
    
    // Set default empty values 
    $totalMentees = 0;
    $activeMentees = 0;
    $completionRate = 0;
    $avgTimeSpent = 0;
    $courseEngagement = [];
    $weeklyActivity = array_fill(0, 7, 0);
    $menteeProgress = [];
    $courses = [];
    $selectedCourse = 'all';
    $selectedPeriod = '30';
}

// Helper function for status badge
function getProgressStatus($progress) {
    if ($progress >= 80) return ['Excellent', 'status-excellent'];
    if ($progress >= 60) return ['Good', 'status-good'];
    return ['Need Support', 'status-support'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Detail Keterlibatan Mentee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor-analitik-detail.css">
     <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
</head>
<body>
    <header class="top-header">
        <div class="logo">MindCraft</div>
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-right">
            <div class="header-profile">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($mentorName, 0, 1)); ?>
                </div>
                <span class="profile-name"><?php echo htmlspecialchars($mentorName); ?></span>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
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

        <main class="main-content">
            <div class="content-header">
                <div class="header-content">
                    <div class="breadcrumb">
                        <a href="/MindCraft-Project/views/mentor/analitik.php" class="breadcrumb-link">Analitik</a>
                        <span class="breadcrumb-separator">></span>
                        <span class="breadcrumb-current">Detail Keterlibatan Mentee</span>
                    </div>
                    <h1>Detail Keterlibatan Mentee</h1>
                </div>
            </div>
            
            <div class="content-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Controls -->
                <div class="filter-controls">
                    <div class="filter-group">
                        <label for="courseSelect" class="control-label">
                            <i class="fas fa-book"></i> Kursus
                        </label>
                        <div class="custom-select">
                            <select id="courseSelect" name="course">
                                <option value="all" <?php echo $selectedCourse === 'all' ? 'selected' : ''; ?>>Semua Kursus</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course['id']); ?>" <?php echo $selectedCourse == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label for="periodSelect" class="control-label">
                            <i class="fas fa-calendar"></i> Periode
                        </label>
                        <div class="custom-select">
                            <select id="periodSelect" name="period">
                                <option value="7" <?php echo $selectedPeriod === '7' ? 'selected' : ''; ?>>7 Hari Terakhir</option>
                                <option value="30" <?php echo $selectedPeriod === '30' ? 'selected' : ''; ?>>30 Hari Terakhir</option>
                                <option value="90" <?php echo $selectedPeriod === '90' ? 'selected' : ''; ?>>90 Hari Terakhir</option>
                            </select>
                        </div>
                    </div>
                    
                    <button class="filter-btn" id="applyFilters">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                </div>

                <!-- Overview Cards -->
                <div class="overview-grid">
                    <div class="overview-card fade-in-up" style="animation-delay: 0.1s;">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-title">Total Mentee</div>
                            <div class="card-number"><?php echo number_format($totalMentees); ?></div>
                            <div class="card-subtitle">Terdaftar aktif</div>
                        </div>
                    </div>
                    
                    <div class="overview-card fade-in-up" style="animation-delay: 0.2s;">
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-title">Mentee Aktif</div>
                            <div class="card-number"><?php echo number_format($activeMentees); ?></div>
                            <div class="card-subtitle">7 hari terakhir</div>
                        </div>
                    </div>
                    
                    <div class="overview-card fade-in-up" style="animation-delay: 0.3s;">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-title">Tingkat Penyelesaian</div>
                            <div class="card-number"><?php echo $completionRate; ?>%</div>
                            <div class="card-subtitle">Rata-rata semua kursus</div>
                        </div>
                    </div>
                    
                    <div class="overview-card fade-in-up" style="animation-delay: 0.4s;">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-title">Waktu Belajar</div>
                            <div class="card-number"><?php echo $avgTimeSpent; ?> min</div>
                            <div class="card-subtitle">Rata-rata per sesi</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <!-- Weekly Activity Chart -->
                    <div class="chart-card fade-in-up" style="animation-delay: 0.5s;">
                        <div class="chart-header">
                            <h3><i class="fas fa-calendar-week"></i> Aktivitas Mingguan Mentee</h3>
                            <p>Jumlah mentee aktif per hari dalam 7 hari terakhir</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="weeklyActivityChart"></canvas>
                        </div>
                    </div>

                    <!-- Course Engagement Chart -->
                    <div class="chart-card fade-in-up" style="animation-delay: 0.6s;">
                        <div class="chart-header">
                            <h3><i class="fas fa-book-open"></i> Keterlibatan per Kursus</h3>
                            <p>Tingkat engagement dan completion rate setiap kursus</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="courseEngagementChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Mentee Progress Table -->
                <div class="progress-section fade-in-up" style="animation-delay: 0.7s;">
                    <div class="section-header">
                        <h3><i class="fas fa-user-graduate"></i> Progress Individual Mentee</h3>
                        <p>Daftar mentee dengan progress dan aktivitas terbaru</p>
                    </div>
                    
                    <div class="progress-table-container">
                        <?php if (count($menteeProgress) > 0): ?>
                        <table class="progress-table">
                            <thead>
                                <tr>
                                    <th>Nama Mentee</th>
                                    <th>Kursus</th>
                                    <th>Progress</th>
                                    <th>Terakhir Aktif</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menteeProgress as $mentee): ?>
                                <tr>
                                    <td>
                                        <div class="mentee-info">
                                            <div class="mentee-avatar"><?php echo strtoupper(substr($mentee['name'], 0, 1)); ?></div>
                                            <span class="mentee-name"><?php echo htmlspecialchars($mentee['name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="course-badge"><?php echo htmlspecialchars($mentee['course']); ?></span>
                                    </td>
                                    <td>
                                        <div class="progress-cell">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $mentee['progress']; ?>%"></div>
                                            </div>
                                            <span class="progress-text"><?php echo $mentee['progress']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="last-active"><?php echo htmlspecialchars($mentee['lastActive']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        list($statusText, $statusClass) = getProgressStatus($mentee['progress']);
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h3>Belum Ada Data Mentee</h3>
                            <p>Data progress mentee akan muncul setelah ada siswa yang mendaftar kursus Anda.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-section fade-in-up" style="animation-delay: 0.8s;">
                    <button class="action-btn primary" id="exportData">
                        <i class="fas fa-file-export"></i>
                        Export Data Analytics
                    </button>
                    <button class="action-btn secondary" id="refreshData">
                        <i class="fas fa-sync-alt"></i>
                        Refresh Data
                    </button>
                </div>
            </div> 
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script>
        // Pass PHP data to JavaScript
        window.detailData = {
            weeklyActivity: <?php echo json_encode($weeklyActivity); ?>,
            courseEngagement: <?php echo json_encode($courseEngagement); ?>,
            weekLabels: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
        };

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Weekly Activity Chart
            const weeklyCtx = document.getElementById('weeklyActivityChart').getContext('2d');
            const weeklyChart = new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: detailData.weekLabels,
                    datasets: [{
                        label: 'Aktivitas Mentee',
                        data: detailData.weeklyActivity,
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1,
                        borderRadius: 4
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
                                    return context.parsed.y + ' mentee aktif';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Course Engagement Chart
            if (detailData.courseEngagement.length > 0) {
                const engagementCtx = document.getElementById('courseEngagementChart').getContext('2d');
                const engagementChart = new Chart(engagementCtx, {
                    type: 'radar',
                    data: {
                        labels: detailData.courseEngagement.map(course => course.title),
                        datasets: [
                            {
                                label: 'Tingkat Engagement',
                                data: detailData.courseEngagement.map(course => course.engagement),
                                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                                borderColor: 'rgba(102, 126, 234, 1)',
                                borderWidth: 2,
                                pointBackgroundColor: 'rgba(102, 126, 234, 1)'
                            },
                            {
                                label: 'Completion Rate',
                                data: detailData.courseEngagement.map(course => course.completionRate),
                                backgroundColor: 'rgba(118, 75, 162, 0.2)',
                                borderColor: 'rgba(118, 75, 162, 1)',
                                borderWidth: 2,
                                pointBackgroundColor: 'rgba(118, 75, 162, 1)'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                angleLines: {
                                    display: true
                                },
                                suggestedMin: 0,
                                suggestedMax: 100
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.r + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Filter functionality
            const applyFilters = function() {
                const params = new URLSearchParams();
                if (courseSelect.value !== 'all') params.set('course', courseSelect.value);
                if (periodSelect.value !== '30') params.set('period', periodSelect.value);
                
                window.location.search = params.toString();
            };

            const courseSelect = document.getElementById('courseSelect');
            const periodSelect = document.getElementById('periodSelect');
            const applyFiltersBtn = document.getElementById('applyFilters');
            
            applyFiltersBtn.addEventListener('click', applyFilters);
            
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Export data button
            document.getElementById('exportData').addEventListener('click', function() {
                alert('Fitur export data akan segera tersedia!');
            });
            
            // Refresh data button
            document.getElementById('refreshData').addEventListener('click', function() {
                window.location.reload();
            });
        });
    </script>
</body>
</html>