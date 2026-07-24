<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../landingpage/landingpage.php");
    exit();
}

require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
$db = $database->connect();

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lesson_id = isset($_GET['lesson']) ? intval($_GET['lesson']) : 0;

$stmt = $db->prepare("SELECT * FROM enrollments 
                     WHERE student_id = ? AND course_id = ? 
                     AND status = 'active'");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    die("Anda belum terdaftar dalam kursus ini atau akses ditolak.");
}

$stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Kursus tidak ditemukan");
}

$stmt = $db->prepare("
    SELECT cm.id AS module_id, cm.title AS module_title, cm.order_index AS module_order,
           cl.id AS lesson_id, cl.title AS lesson_title, cl.type AS lesson_type,
           cl.video_url, cl.video_duration, cl.description, cl.content,
           cl.file_path, cl.order_index AS lesson_order
    FROM course_modules cm
    LEFT JOIN course_lessons cl ON cm.id = cl.module_id
    WHERE cm.course_id = ?
    ORDER BY cm.order_index ASC, cl.order_index ASC
");
$stmt->execute([$course_id]);

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
            'video_url' => $row['video_url'],
            'video_duration' => $row['video_duration'],
            'description' => $row['description'],
            'content' => $row['content'],
            'file_path' => $row['file_path'],
            'order_index' => $row['lesson_order']
        ];
    }
}

$current_lesson = null;
if ($lesson_id > 0) {
    $stmt = $db->prepare("SELECT * FROM course_lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $current_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current_lesson) {
        $stmt = $db->prepare("INSERT INTO course_progress 
                            (student_id, course_id, lesson_id, progress, last_accessed) 
                            VALUES (?, ?, ?, 100, NOW())
                            ON DUPLICATE KEY UPDATE 
                            progress = GREATEST(progress, 100), 
                            last_accessed = NOW()");
        $stmt->execute([$_SESSION['user_id'], $course_id, $lesson_id]);
    }
}

// kalkulasi progres kursus
$stmt = $db->prepare("SELECT AVG(progress) as overall_progress 
                     FROM course_progress 
                     WHERE student_id = ? AND course_id = ?");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$progress_data = $stmt->fetch(PDO::FETCH_ASSOC);
$course_progress = $progress_data['overall_progress'] ?? 0;

//mengambil total pelajaran
$stmt = $db->prepare("SELECT COUNT(*) as total_lessons FROM course_lessons cl
                     JOIN course_modules cm ON cl.module_id = cm.id
                     WHERE cm.course_id = ?");
$stmt->execute([$course_id]);
$total_lessons = $stmt->fetch(PDO::FETCH_ASSOC)['total_lessons'];

// mengambil total pelajaran selesai
$stmt = $db->prepare("SELECT COUNT(*) as completed_lessons FROM course_progress
                     WHERE student_id = ? AND course_id = ? AND progress = 100");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$completed_lessons = $stmt->fetch(PDO::FETCH_ASSOC)['completed_lessons'];

// menghitung progres presentasi
$progress_percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 1) : 0;

$prev_lesson_id = null;
$next_lesson_id = null;
if ($current_lesson) {
    $found_current = false;
    $prev_lesson = null;
    
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if ($found_current && !$next_lesson_id) {
                $next_lesson_id = $lesson['id'];
                break 2;
            }
            
            if ($lesson['id'] == $current_lesson['id']) {
                $found_current = true;
                if ($prev_lesson) {
                    $prev_lesson_id = $prev_lesson['id'];
                }
            } else {
                $prev_lesson = $lesson;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - MindCraft</title>
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/course-player.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

</head>
<body>
    <div class="course-player-container">
        <!-- Floating decorative shapes -->
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        
        <!-- Mobile FAB -->
        <div class="fab" id="mobileFab">
            <i class="fas fa-book-open"></i>
        </div>
        
        <!-- Sidebar -->
        <div class="course-sidebar" id="courseSidebar">
            <div class="sidebar-header">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <div>
                    <a href="kursus.php" class="btn-back-to-courses" title="Kembali ke Daftar Kursus">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="progress-card">
                <div class="progress-header">
                    <span class="progress-title">Progress Anda</span>
                    <span class="progress-percentage"><?php echo $progress_percentage; ?>%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <div class="progress-details">
                    <span><?php echo $completed_lessons; ?> dari <?php echo $total_lessons; ?> pelajaran</span>
                    <span><?php echo round($course_progress, 1); ?>% selesai</span>
                </div>
            </div>
            
            <div class="course-modules">
                <?php 
                $stmt = $db->prepare("SELECT lesson_id FROM course_progress 
                                     WHERE student_id = ? AND course_id = ? AND progress = 100");
                $stmt->execute([$_SESSION['user_id'], $course_id]);
                $completed_lessons_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($modules as $module): 
                    $has_active_lesson = false;
                    foreach ($module['lessons'] as $lesson) {
                        if ($current_lesson && $lesson['id'] == $current_lesson['id']) {
                            $has_active_lesson = true;
                            break;
                        }
                    }
                ?>
                    <div class="module <?php echo $has_active_lesson ? 'active' : ''; ?>">
                        <div class="module-header">
                            <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                            <i class="fas fa-chevron-down module-toggle <?php echo $has_active_lesson ? '' : 'collapsed'; ?>"></i>
                        </div>
                        <ul class="lessons" style="<?php echo $has_active_lesson ? 'display: block;' : 'display: none;' ?>">
                            <?php foreach ($module['lessons'] as $lesson): 
                                $is_active = $current_lesson && $lesson['id'] == $current_lesson['id'];
                                $is_completed = in_array($lesson['id'], $completed_lessons_ids);
                            ?>
                                <li class="lesson <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>">
                                    <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>">
                                        <i class="fas fa-check-circle lesson-check"></i>
                                        <span class="lesson-icon">
                                            <?php 
                                            switch($lesson['type']) {
                                                case 'video': echo '<i class="fas fa-play"></i>'; break;
                                                case 'quiz': echo '<i class="fas fa-question-circle"></i>'; break;
                                                case 'assignment': echo '<i class="fas fa-tasks"></i>'; break;
                                                case 'download': echo '<i class="fas fa-download"></i>'; break;
                                                default: echo '<i class="fas fa-file-alt"></i>';
                                            }
                                            ?>
                                        </span>
                                        <span class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                        <?php if ($lesson['type'] === 'video' && $lesson['video_duration']): ?>
                                            <span class="duration"><?php echo gmdate("i:s", $lesson['video_duration']); ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="course-content">
            <?php if ($current_lesson): ?>
                <div class="lesson-card animate__animated animate__fadeIn">
                    <div class="lesson-header">
                        <div class="breadcrumb">
                            <a href="?id=<?php echo $course_id; ?>">Kursus</a>
                            <i class="fas fa-chevron-right"></i>
                            <span><?php echo htmlspecialchars($current_lesson['title']); ?></span>
                        </div>
                        <h2><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                        <div class="lesson-meta">
                            <div class="meta-item">
                                <span class="meta-icon"><i class="fas fa-book-open"></i></span>
                                <span><?php echo ucfirst($current_lesson['type']); ?></span>
                            </div>
                            <?php if ($current_lesson['type'] === 'video' && $current_lesson['video_duration']): ?>
                                <div class="meta-item">
                                    <span class="meta-icon"><i class="fas fa-clock"></i></span>
                                    <span><?php echo gmdate("i:s", $current_lesson['video_duration']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <span class="meta-icon"><i class="fas fa-check-circle"></i></span>
                                <span><?php echo $progress_percentage; ?>% Selesai</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lesson-body">
                        <?php if ($current_lesson['type'] === 'video' && $current_lesson['video_url']): ?>
                            <div class="video-container">
                                <?php if (strpos($current_lesson['video_url'], 'youtube.com') !== false || strpos($current_lesson['video_url'], 'youtu.be') !== false): ?>
                                    <?php 
                                    $video_id = '';
                                    $url = $current_lesson['video_url'];
                                    
                                    if (preg_match('%(?:youtube(?:nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
                                        $video_id = $match[1];
                                    }
                                    ?>
                                    <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>?rel=0&showinfo=0" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen></iframe>
                                <?php else: ?>
                                    <video controls width="100%" id="lessonVideo">
                                        <source src="<?php echo htmlspecialchars($current_lesson['video_url']); ?>" type="video/mp4">
                                        Browser Anda tidak mendukung pemutaran video.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($current_lesson['description']): ?>
                            <div class="content-section">
                                <h3>Deskripsi</h3>
                                <div class="text-content">
                                    <p><?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($current_lesson['content']): ?>
                            <div class="content-section">
                                <?php if ($current_lesson['type'] !== 'video'): ?>
                                    <h3>Materi Pembelajaran</h3>
                                <?php endif; ?>
                                <div class="text-content">
                                    <?php echo nl2br(htmlspecialchars($current_lesson['content'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($current_lesson['type'] === 'quiz'): ?>
                            <div class="activity-card animate__animated animate__fadeIn">
                                <h3><span class="icon"><i class="fas fa-question-circle"></i></span> Kuis</h3>
                                <p>Uji pemahaman Anda dengan mengerjakan kuis ini. Anda bisa mencoba berkali-kali sampai merasa puas dengan hasilnya.</p>
                                <button class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-play btn-icon"></i>
                                    Mulai Kuis
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($current_lesson['type'] === 'assignment'): ?>
                            <div class="activity-card animate__animated animate__fadeIn">
                                <h3><span class="icon"><i class="fas fa-tasks"></i></span> Tugas</h3>
                                <p>Kerjakan tugas berikut untuk menerapkan apa yang telah Anda pelajari. Jangan lupa submit sebelum deadline!</p>
                                <button class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-upload btn-icon"></i>
                                    Upload Tugas
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($current_lesson['type'] === 'download' && $current_lesson['file_path']): ?>
                            <div class="download-card animate__animated animate__fadeIn">
                                <h3><span class="icon"><i class="fas fa-download"></i></span> File Materi</h3>
                                <p>Download materi pembelajaran berikut untuk dipelajari offline atau sebagai referensi tambahan.</p>
                                <a href="<?php echo htmlspecialchars($current_lesson['file_path']); ?>" 
                                   class="btn btn-primary" 
                                   download
                                   style="margin-top: 1rem;">
                                    <i class="fas fa-download btn-icon"></i>
                                    Unduh Sekarang
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="navigation-buttons">
                        <?php if ($prev_lesson_id): ?>
                            <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $prev_lesson_id; ?>" class="btn btn-outline">
                                <i class="fas fa-arrow-left btn-icon"></i>
                                Pelajaran Sebelumnya
                            </a>
                        <?php else: ?>
                            <a href="kursus.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left btn-icon"></i>
                                Kembali ke Kursus
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_lesson_id): ?>
                            <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $next_lesson_id; ?>" class="btn btn-primary">
                                Pelajaran Selanjutnya
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="kursus.php" class="btn btn-primary">
                                Selesaikan Kursus
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="welcome-screen animate__animated animate__fadeIn">
                    <div class="welcome-illustration">
                        <img src="/MindCraft-Project/assets/img/course-welcome.svg" alt="Selamat Belajar">
                    </div>
                    <h2>Selamat Datang di <?php echo htmlspecialchars($course['title']); ?></h2>
                    <p>Anda telah menyelesaikan <?php echo $progress_percentage; ?>% dari kursus ini. Mari lanjutkan perjalanan belajar Anda dengan memilih pelajaran dari menu sidebar.</p>
                    <?php if (!empty($modules) && !empty($modules[array_key_first($modules)]['lessons'])): ?>
                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $modules[array_key_first($modules)]['lessons'][0]['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-play btn-icon"></i>
                            Mulai Belajar Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle module collapse/expand with animation
        document.querySelectorAll('.module-header').forEach(header => {
            header.addEventListener('click', () => {
                const module = header.parentElement;
                const lessons = module.querySelector('.lessons');
                const toggle = header.querySelector('.module-toggle');
                
                if (lessons.style.display === 'none') {
                    lessons.style.display = 'block';
                    const height = lessons.scrollHeight;
                    lessons.style.maxHeight = '0';
                    setTimeout(() => {
                        lessons.style.maxHeight = `${height}px`;
                    }, 10);
                    toggle.classList.remove('collapsed');
                    module.classList.add('active');
                } else {
                    lessons.style.maxHeight = `${lessons.scrollHeight}px`;
                    setTimeout(() => {
                        lessons.style.maxHeight = '0';
                    }, 10);
                    setTimeout(() => {
                        lessons.style.display = 'none';
                    }, 300);
                    toggle.classList.add('collapsed');
                    module.classList.remove('active');
                }
            });
        });
        
        // Initialize modules
        document.addEventListener('DOMContentLoaded', () => {
            const modules = document.querySelectorAll('.module');
            
            modules.forEach(module => {
                const hasActiveLesson = module.querySelector('.lesson.active') !== null;
                if (hasActiveLesson) {
                    const lessons = module.querySelector('.lessons');
                    const toggle = module.querySelector('.module-toggle');
                    lessons.style.display = 'block';
                    lessons.style.maxHeight = `${lessons.scrollHeight}px`;
                    toggle.classList.remove('collapsed');
                    module.classList.add('active');
                    
                    // Scroll to active lesson
                    const activeLesson = module.querySelector('.lesson.active');
                    if (activeLesson) {
                        setTimeout(() => {
                            activeLesson.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);
                    }
                }
            });
            
            // Mobile sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileFab = document.getElementById('mobileFab');
            const courseSidebar = document.getElementById('courseSidebar');
            
            if (sidebarToggle && courseSidebar) {
                sidebarToggle.addEventListener('click', () => {
                    courseSidebar.classList.remove('active');
                });
            }
            
            if (mobileFab && courseSidebar) {
                mobileFab.addEventListener('click', () => {
                    courseSidebar.classList.add('active');
                });
            }
            
            // Track video progress
            const videoElement = document.getElementById('lessonVideo');
            if (videoElement) {
                let progressInterval;
                let lastSavedTime = 0;
                
                const trackProgress = () => {
                    const currentTime = videoElement.currentTime;
                    const duration = videoElement.duration;
                    
                    if (duration > 0) {
                        const progress = (currentTime / duration) * 100;
                        
                        // Only save progress if it's increased by at least 5% or video is completed
                        if (progress - lastSavedTime >= 5 || progress >= 95) {
                            saveVideoProgress(progress);
                            lastSavedTime = progress;
                        }
                    }
                };
                
                const saveVideoProgress = (progress) => {
                    const formData = new FormData();
                    formData.append('student_id', <?php echo $_SESSION['user_id']; ?>);
                    formData.append('course_id', <?php echo $course_id; ?>);
                    formData.append('lesson_id', <?php echo $current_lesson['id']; ?>);
                    formData.append('progress', Math.min(progress, 100));
                    
                    fetch('save_progress.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (!response.ok) {
                            console.error('Failed to save progress');
                        }
                        return response.text();
                    }).then(data => {
                        // Update progress UI if needed
                        if (progress >= 95) {
                            const currentLessonLink = document.querySelector('.lesson.active a');
                            if (currentLessonLink) {
                                currentLessonLink.closest('.lesson').classList.add('completed');
                                
                                // Update progress bar
                                const progressBar = document.querySelector('.progress-bar');
                                const progressPercentage = document.querySelector('.progress-percentage');
                                const progressDetails = document.querySelectorAll('.progress-details span');
                                
                                // Calculate new progress
                                const completedLessons = parseInt(progressDetails[0].textContent.split(' ')[0]) + 1;
                                const totalLessons = parseInt(progressDetails[0].textContent.split(' ')[2]);
                                const newPercentage = Math.round((completedLessons / totalLessons) * 100);
                                
                                // Update UI
                                progressBar.style.width = `${newPercentage}%`;
                                progressPercentage.textContent = `${newPercentage}%`;
                                progressDetails[0].textContent = `${completedLessons} dari ${totalLessons} pelajaran`;
                            }
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                    });
                };
                
                videoElement.addEventListener('play', () => {
                    progressInterval = setInterval(trackProgress, 5000); // Track every 5 seconds
                });
                
                videoElement.addEventListener('pause', () => {
                    clearInterval(progressInterval);
                });
                
                videoElement.addEventListener('ended', () => {
                    clearInterval(progressInterval);
                    saveVideoProgress(100); // Mark as completed when video ends
                });
            }
        });
    </script>
</body>
</html>