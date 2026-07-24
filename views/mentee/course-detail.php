<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../landingpage/landingpage.php");
    exit();
}

require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
$db = $database->connect();

// Get course ID from URL
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get course details
$course = [];
$instructor = [];
$modules = [];
$is_enrolled = false;

try {
    // Fetch course details
    $stmt = $db->prepare("SELECT c.*, u.username as instructor_name 
                         FROM courses c 
                         JOIN users u ON c.mentor_id = u.id 
                         WHERE c.id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($course) {
        // Fetch instructor details
        $stmt = $db->prepare("SELECT u.username, mp.* 
                             FROM users u
                             LEFT JOIN mentor_profiles mp ON u.id = mp.user_id
                             WHERE u.id = ?");
        $stmt->execute([$course['mentor_id']]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch all modules and their lessons in one optimized query
        $stmt = $db->prepare("
            SELECT cm.id AS module_id, cm.title AS module_title, 
                   cm.order_index AS module_order,
                   cl.id AS lesson_id, cl.title AS lesson_title, 
                   cl.type AS lesson_type, cl.video_duration,
                   cl.is_free, cl.order_index AS lesson_order
            FROM course_modules cm
            LEFT JOIN course_lessons cl ON cm.id = cl.module_id
            WHERE cm.course_id = ?
            ORDER BY cm.order_index ASC, cl.order_index ASC
        ");
        $stmt->execute([$course_id]);
        
        // Organize modules and lessons data
        $modules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $moduleId = $row['module_id'];
            
            if (!isset($modules[$moduleId])) {
                $modules[$moduleId] = [
                    'id' => $moduleId,
                    'title' => $row['module_title'],
                    'order_index' => $row['module_order'],
                    'lessons' => []
                ];
            }
            
            if ($row['lesson_id']) {
                $modules[$moduleId]['lessons'][] = [
                    'id' => $row['lesson_id'],
                    'title' => $row['lesson_title'],
                    'type' => $row['lesson_type'],
                    'video_duration' => $row['video_duration'],
                    'is_free' => $row['is_free'],
                    'order_index' => $row['lesson_order']
                ];
            }
        }

        // Check if user is enrolled
        if (isset($_SESSION['user_id'])) {
            $stmt = $db->prepare("SELECT * FROM enrollments 
                                 WHERE student_id = ? AND course_id = ? 
                                 AND status = 'active'");
            $stmt->execute([$_SESSION['user_id'], $course_id]);
            $is_enrolled = $stmt->fetch() !== false;
        }
    }
} catch (PDOException $e) {
    $error = "Error fetching course details: " . $e->getMessage();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'] ?? 'Mentee';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title'] ?? 'Kursus'); ?> - MindCraft</title>
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/detail-kursus.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>

<body class="course-detail-page">
    <!-- Header -->
    <header class="course-header">
        <div class="container">
            <nav class="header-nav">
                <a href="kursus.php" class="logo">
                    <img src="../../assets/img/20250502_083014.png" alt="MindCraft Logo">
                    <span>MindCraft</span>
                </a>
                
                <div class="nav-links">
                    <a href="kursus.php" class="nav-link active"><i class="fas fa-book-open"></i> Kursus</a>
                    <a href="ai_assistant.php" class="nav-link"><i class="fas fa-robot"></i> MindBot</a>
                </div>
                
                <div class="user-menu">
                    <div class="user-avatar" id="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                        <div class="dropdown-menu" id="dropdown-menu">
                            <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
                            <a href="../landingpage/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </nav>
        </div>
    </header>

    <main class="course-main">
        <div class="container">
            <?php if (empty($course)): ?>
                <div class="empty-state animate__animated animate__fadeIn">
                    <img src="/MindCraft-Project/assets/img/empty-search.svg" alt="Kursus tidak ditemukan">
                    <h3>Kursus tidak ditemukan</h3>
                    <p>Kursus yang Anda cari tidak tersedia atau mungkin telah dihapus</p>
                    <a href="kursus.php" class="btn btn-primary">Kembali ke Katalog Kursus</a>
                </div>
            <?php else: ?>
                <!-- Course Hero Section -->
                <section class="course-hero animate__animated animate__fadeIn">
                    <div class="breadcrumb">
                        <a href="kursus.php">Kursus</a>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo htmlspecialchars($course['title']); ?></span>
                    </div>
                    
                    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                    
                    <div class="course-meta">
                        <div class="meta-item">
                            <span class="badge badge-level"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="badge badge-price <?php echo $course['is_premium'] ? 'premium' : 'free'; ?>">
                                <?php echo $course['is_premium'] ? 'Premium' : 'Gratis'; ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo number_format($course['total_enrollments'] ?? 0); ?> Siswa</span>
                        </div>
                    </div>
                </section>

                <div class="course-content-grid">
                    <!-- Main Content -->
                    <div class="course-main-content">
                        <!-- Course Image -->
                        <div class="course-image-container animate__animated animate__fadeIn">
                            <div class="course-image">
                                <img src="<?php echo htmlspecialchars($course['cover_image'] ?? '/MindCraft-Project/assets/img/default-course.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <div class="image-overlay">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Course Tabs -->
                        <div class="course-tabs animate__animated animate__fadeIn">
                            <button class="course-tab-link active" data-tab="overview">
                                <i class="fas fa-info-circle"></i> Overview
                            </button>
                            <button class="course-tab-link" data-tab="curriculum">
                                <i class="fas fa-list-ul"></i> Kurikulum
                            </button>
                            <button class="course-tab-link" data-tab="instructor">
                                <i class="fas fa-chalkboard-teacher"></i> Instruktur
                            </button>
                        </div>
                        
                        <!-- Tab Contents -->
                        <div class="tab-contents">
                            <!-- Overview Tab -->
                            <div id="overview-content" class="course-tab-content active animate__animated animate__fadeIn">
                                <h2>Tentang Kursus Ini</h2>
                                <div class="course-description">
                                    <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                                </div>
                                
                                <?php if (!empty($course['what_you_learn'])): ?>
                                    <div class="what-you-learn card">
                                        <h3><i class="fas fa-graduation-cap"></i> Apa yang akan Anda pelajari</h3>
                                        <ul>
                                            <?php 
                                            $learn_items = explode("\n", $course['what_you_learn']);
                                            foreach ($learn_items as $item):
                                                if (trim($item)): ?>
                                                    <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($item)); ?></li>
                                                <?php endif;
                                            endforeach; 
                                            ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="course-details-grid">
                                    <?php if (!empty($course['requirements'])): ?>
                                        <div class="requirements card">
                                            <h3><i class="fas fa-tools"></i> Persyaratan</h3>
                                            <ul>
                                                <?php 
                                                $requirements = explode("\n", $course['requirements']);
                                                foreach ($requirements as $req):
                                                    if (trim($req)): ?>
                                                        <li><i class="fas fa-circle"></i> <?php echo htmlspecialchars(trim($req)); ?></li>
                                                    <?php endif;
                                                endforeach; 
                                                ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($course['target_audience'])): ?>
                                        <div class="audience card">
                                            <h3><i class="fas fa-users"></i> Untuk siapa kursus ini</h3>
                                            <ul>
                                                <?php 
                                                $audience = explode("\n", $course['target_audience']);
                                                foreach ($audience as $aud):
                                                    if (trim($aud)): ?>
                                                        <li><i class="fas fa-circle"></i> <?php echo htmlspecialchars(trim($aud)); ?></li>
                                                    <?php endif;
                                                endforeach; 
                                                ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Curriculum Tab -->
<div id="curriculum-content" class="course-tab-content">
                            <h2>Kurikulum Kursus</h2>
                            <div class="course-modules">
                                <?php if (empty($modules)): ?>
                                    <p class="empty-modules">Belum ada modul untuk kursus ini</p>
                                <?php else: ?>
                                    <?php foreach ($modules as $module): ?>
                                        <div class="module">
                                            <div class="module-header">
                                                <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                                                <span><?php echo count($module['lessons']); ?> Pelajaran</span>
                                            </div>
                                            <div class="module-lessons">
                                                <?php if (!empty($module['lessons'])): ?>
                                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                                        <div class="lesson <?php echo $is_enrolled || $lesson['is_free'] ? '' : 'locked'; ?>">
                                                            <div class="lesson-icon">
                                                                <?php if ($lesson['type'] === 'video'): ?>
                                                                    <i class="fas fa-play-circle"></i>
                                                                <?php elseif ($lesson['type'] === 'quiz'): ?>
                                                                    <i class="fas fa-question-circle"></i>
                                                                <?php elseif ($lesson['type'] === 'assignment'): ?>
                                                                    <i class="fas fa-tasks"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-file-alt"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="lesson-info">
                                                                <h4><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                                                <?php if ($lesson['type'] === 'video' && $lesson['video_duration']): ?>
                                                                    <span class="lesson-duration"><?php echo gmdate("i:s", $lesson['video_duration']); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if (!$is_enrolled && !$lesson['is_free']): ?>
                                                                <div class="lesson-lock">
                                                                    <i class="fas fa-lock"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p class="empty-lessons">Belum ada pelajaran dalam modul ini</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                                                    
                            <!-- Instructor Tab -->
                            <div id="instructor-content" class="course-tab-content animate__animated animate__fadeIn">
                                <h2>Tentang Instruktur</h2>
                                <div class="instructor-profile card">
                                    <div class="instructor-avatar">
                                    <img src="<?php echo !empty($instructor['profile_picture']) 
                                        ? htmlspecialchars($instructor['profile_picture']) 
                                        : 'https://randomuser.me/api/portraits/men/32.jpg'; ?>" 
                                        alt="<?php echo htmlspecialchars($instructor['username'] ?? 'Instruktur'); ?>">
                                </div>
                                    <div class="instructor-info">
                                        <div class="instructor-header">
                                            <h3><?php echo htmlspecialchars($instructor['username'] ?? 'Instruktur'); ?></h3>
                                            <span class="verified-badge">
                                                <i class="fas fa-check-circle"></i> Mentor Terverifikasi
                                            </span>
                                        </div>
                                        
                                        <?php if (!empty($instructor['specialization'])): ?>
                                            <p class="specialization"><?php echo htmlspecialchars($instructor['specialization']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($instructor['bio'])): ?>
                                            <div class="bio">
                                                <?php echo nl2br(htmlspecialchars($instructor['bio'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="instructor-stats">
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $instructor['experience_years'] ?? 0; ?>+</span>
                                                <span class="stat-label">Tahun Pengalaman</span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-number"><?php echo $course['total_enrollments'] ?? 0; ?></span>
                                                <span class="stat-label">Siswa</span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($instructor['website']) || !empty($instructor['linkedin']) || !empty($instructor['instagram']) || !empty($instructor['youtube'])): ?>
                                            <div class="instructor-social">
                                                <h4>Ikuti Instruktur:</h4>
                                                <div class="social-links">
                                                    <?php if (!empty($instructor['website'])): ?>
                                                        <a href="<?php echo htmlspecialchars($instructor['website']); ?>" target="_blank" class="social-link">
                                                            <i class="fas fa-globe"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($instructor['linkedin'])): ?>
                                                        <a href="<?php echo htmlspecialchars($instructor['linkedin']); ?>" target="_blank" class="social-link">
                                                            <i class="fab fa-linkedin"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($instructor['instagram'])): ?>
                                                        <a href="<?php echo htmlspecialchars($instructor['instagram']); ?>" target="_blank" class="social-link">
                                                            <i class="fab fa-instagram"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($instructor['youtube'])): ?>
                                                        <a href="<?php echo htmlspecialchars($instructor['youtube']); ?>" target="_blank" class="social-link">
                                                            <i class="fab fa-youtube"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reviews Tab -->
                            <div id="reviews-content" class="course-tab-content animate__animated animate__fadeIn">
                               
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="course-sidebar">
                        <div class="course-card sticky-card">
                            <div class="course-image">
                                <img src="<?php echo htmlspecialchars($course['cover_image'] ?? '/MindCraft-Project/assets/img/default-course.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <div class="course-badges">
                                    <span class="badge badge-level"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                                    <span class="badge badge-price <?php echo $course['is_premium'] ? 'premium' : 'free'; ?>">
                                        <?php echo $course['is_premium'] ? 'Premium' : 'Gratis'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="course-pricing">
                                <?php if ($course['is_premium']): ?>
                                    <div class="price">
                                        <span class="current-price">Rp <?php echo number_format($course['price'], 0, ',', '.'); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="price">
                                        <span class="current-price">Gratis</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="course-actions">
                                <?php if ($is_enrolled): ?>
                                    <a href="course-player.php?id=<?php echo $course_id; ?>" class="btn btn-primary btn-full">
                                        <i class="fas fa-play"></i> Lanjutkan Belajar
                                    </a>
                                <?php else: ?>
                                    <?php if ($course['is_premium']): ?>
                                        <form action="payment.php" method="post" id="payment-form">
                                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                            <input type="hidden" name="course_title" value="<?php echo htmlspecialchars($course['title']); ?>">
                                            <input type="hidden" name="course_price" value="<?php echo $course['price']; ?>">
                                            <button type="submit" class="btn btn-primary btn-full" id="enroll-btn">
                                                <i class="fas fa-shopping-cart"></i> Beli Kursus - Rp <?php echo number_format($course['price'], 0, ',', '.'); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-primary btn-full" id="enroll-btn" data-course-id="<?php echo $course_id; ?>">
                                            <i class="fas fa-user-plus"></i> Ikuti Kursus Gratis
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="course-includes">
                                <h4><i class="fas fa-check-circle"></i> Kursus ini mencakup:</h4>
                                <ul>
                                    <li><i class="fas fa-video"></i> <?php echo $course['total_lessons'] ?? 0; ?> jam video sesuai permintaan</li>
                                    <li><i class="fas fa-file-alt"></i> Materi yang dapat diunduh</li>
                                    <li><i class="fas fa-mobile-alt"></i> Akses seumur hidup</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="course-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="../../assets/img/20250502_083014.png" alt="MindCraft Logo">
                    <p class="footer-tagline">Platform Belajar Digital Modern</p>
                </div>
                <p class="footer-copyright">
                    &copy; 2025 MindCraft. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    <script src="/MindCraft-Project/assets/js/course-detail.js"></script>
    <script>
        // Initialize course detail functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabLinks = document.querySelectorAll('.course-tab-link');
            const tabContents = document.querySelectorAll('.course-tab-content');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab + '-content').classList.add('active');
                    
                    // Add animation to the content
                    document.getElementById(targetTab + '-content').classList.add('animate__fadeIn');
                });
            });
            
            // Module accordion
            const moduleHeaders = document.querySelectorAll('.module-header');
            moduleHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const module = this.closest('.module');
                    module.classList.toggle('open');
                    
                    // Toggle chevron icon
                    const chevron = this.querySelector('.fa-chevron-down');
                    if (chevron) {
                        chevron.classList.toggle('rotate');
                    }
                });
            });
            
// User menu toggle for mobile
            const userAvatar = document.getElementById('user-avatar');
            const dropdownMenu = document.getElementById('dropdown-menu');
            
            if (userAvatar) {
                userAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (dropdownMenu) {
                    dropdownMenu.style.display = 'none';
                }
            });
        });
            
            // Check wishlist status on page load
            function checkWishlist() {
                const courseId = <?php echo $course_id; ?>;
                
                fetch(`/MindCraft-Project/api/wishlist.php?course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.in_wishlist) {
                            const wishlistBtn = document.getElementById('wishlist-btn');
                            if (wishlistBtn) {
                                wishlistBtn.innerHTML = '<i class="fas fa-heart"></i> Dalam Wishlist';
                                wishlistBtn.classList.add('active');
                            }
                        }
                    })
                    .catch(error => console.error('Error checking wishlist:', error));
            }
            
            checkWishlist();
        });
    </script>
</body>
</html>