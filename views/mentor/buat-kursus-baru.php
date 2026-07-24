<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/MentorController.php';

$database = new Database();
$db = $database->connect();
$mentorController = new MentorController($database);

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

$mentorId = $_SESSION['user_id'];
$mentor = $mentorController->getMentorData($mentorId);

$error = '';
$categories = [];
$languages = ['Bahasa Indonesia', 'English', 'Other'];

try {
    $stmt = $db->query("SELECT id, name FROM course_categories WHERE is_active = 1 ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal memuat kategori: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '';
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '';
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING) ?? '';
    $difficulty = filter_input(INPUT_POST, 'difficulty', FILTER_SANITIZE_STRING) ?? 'Pemula';
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?? 0;
    $status = isset($_POST['publish']) ? 'Published' : 'Draft';
    $requirements = filter_input(INPUT_POST, 'requirements', FILTER_SANITIZE_STRING) ?? '';
    $whatYouLearn = filter_input(INPUT_POST, 'what_you_learn', FILTER_SANITIZE_STRING) ?? '';
    $targetAudience = filter_input(INPUT_POST, 'target_audience', FILTER_SANITIZE_STRING) ?? '';
    $language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_STRING) ?? 'Bahasa Indonesia';
    $durationHours = filter_input(INPUT_POST, 'duration_hours', FILTER_VALIDATE_INT) ?? 0;
    $allowReviews = isset($_POST['allow_reviews']) ? 1 : 0;
    $sendNotifications = isset($_POST['send_notifications']) ? 1 : 0;
    $autoCertificate = isset($_POST['auto_certificate']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;

    if (empty($title)) $error = "Judul kursus harus diisi";
    elseif (empty($description)) $error = "Deskripsi kursus harus diisi";
    elseif (empty($category)) $error = "Kategori kursus harus dipilih";
    elseif (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Gambar sampul harus diupload";
    }

    if (empty($error)) {
        $coverImage = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '/MindCraft-Project/uploads/course-covers/';
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
            
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['cover_image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = "Format file tidak didukung. Hanya JPG, PNG, dan WEBP yang diperbolehkan.";
            } else {
                $fileExt = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '_' . time() . '.' . $fileExt;
                $fullPath = $uploadPath . $fileName;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $fullPath)) {
                    $coverImage = $uploadDir . $fileName;
                } else {
                    $error = "Gagal mengupload gambar sampul";
                }
            }
        }
        
        if (empty($error)) {
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("INSERT INTO courses (
                    mentor_id, title, slug, category, difficulty, description, 
                    cover_image, price, is_premium, allow_reviews, send_notifications, 
                    auto_certificate, status, featured, duration_hours, total_lessons, 
                    language, requirements, what_you_learn, target_audience, 
                    total_enrollments, avg_rating, total_reviews, view_count, 
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    0, 0.00, 0, 0, 
                    NOW(), NOW()
                )");
                
                $slug = strtolower(str_replace(' ', '-', $title));
                $isPremium = ($price > 0) ? 1 : 0;
                
                $stmt->execute([
                    $mentorId, $title, $slug, $category, $difficulty, $description,
                    $coverImage, $price, $isPremium, $allowReviews, $sendNotifications,
                    $autoCertificate, $status, $featured, $durationHours, 0,
                    $language, $requirements, $whatYouLearn, $targetAudience
                ]);
                
                $courseId = $db->lastInsertId();
                $totalLessons = 0;
                
                if (!empty($_POST['module_titles'])) {
                    foreach ($_POST['module_titles'] as $moduleIndex => $moduleTitle) {
                        $moduleTitle = trim(filter_var($moduleTitle, FILTER_SANITIZE_STRING));
                        if (empty($moduleTitle)) continue;
                        
                        $moduleStmt = $db->prepare("INSERT INTO course_modules (
                            course_id, title, order_index, created_at, updated_at
                        ) VALUES (?, ?, ?, NOW(), NOW())");
                        $moduleStmt->execute([$courseId, $moduleTitle, $moduleIndex + 1]);
                        $moduleId = $db->lastInsertId();
                        
                        if (!empty($_POST['lesson_titles'][$moduleIndex])) {
                            foreach ($_POST['lesson_titles'][$moduleIndex] as $lessonIndex => $lessonTitle) {
                                $lessonTitle = trim(filter_var($lessonTitle, FILTER_SANITIZE_STRING));
                                $lessonUrl = !empty($_POST['lesson_urls'][$moduleIndex][$lessonIndex]) ? 
                                    filter_var($_POST['lesson_urls'][$moduleIndex][$lessonIndex], FILTER_VALIDATE_URL) : null;
                                
                                if (empty($lessonTitle) || empty($lessonUrl)) continue;
                                
                                $lessonDescription = !empty($_POST['lesson_descriptions'][$moduleIndex][$lessonIndex]) ? 
                                    trim(filter_var($_POST['lesson_descriptions'][$moduleIndex][$lessonIndex], FILTER_SANITIZE_STRING)) : 
                                    "Pelajaran untuk modul " . $moduleTitle;
                                
                                $lessonStmt = $db->prepare("INSERT INTO course_lessons (
                                    module_id, title, type, video_url, order_index, is_free, 
                                    is_downloadable, description, created_at, updated_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                                
                                $lessonStmt->execute([
                                    $moduleId, $lessonTitle, 'video', $lessonUrl,
                                    $lessonIndex + 1, 1, 0, $lessonDescription
                                ]);
                                
                                $totalLessons++;
                            }
                        }
                    }
                    
                    if ($totalLessons > 0) {
                        $updateStmt = $db->prepare("UPDATE courses SET total_lessons = ? WHERE id = ?");
                        $updateStmt->execute([$totalLessons, $courseId]);
                    }
                }
                
                if (!empty($_POST['tags'])) {
                    $tagsInput = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
                    $tags = array_unique(array_filter(array_map('trim', explode(',', $tagsInput))));
                    
                    $tagStmt = $db->prepare("INSERT INTO course_tags (course_id, tag_name, created_at) 
                        VALUES (?, ?, NOW())");
                    
                    foreach ($tags as $tag) {
                        if (!empty($tag) && strlen($tag) <= 50) {
                            $tagStmt->execute([$courseId, $tag]);
                        }
                    }
                }
                
                $db->commit();
                header("Location: kursus-saya.php?success=1");
                exit();
            } catch (PDOException $e) {
                $db->rollBack();
                $error = "Gagal membuat kursus: " . $e->getMessage();
                
                if (!empty($coverImage) && file_exists($_SERVER['DOCUMENT_ROOT'] . $coverImage)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $coverImage);
                }
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
    <title>Buat Kursus Baru - Mentor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor-buat-kursus.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tagify@3.22.1/dist/tagify.min.css">
    <style>
        .module-container {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .module-header h4 {
            margin: 0;
            color: #1e40af;
        }
        .remove-module {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .lessons-container {
            margin-top: 1rem;
        }
        .lesson {
            background: #fff;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
        }
        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .lesson-header h5 {
            margin: 0;
            color: #1e40af;
            font-size: 1rem;
        }
        .remove-lesson {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .add-module, .add-lesson {
            background: #e0f2fe;
            color: #0369a1;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .youtube-preview {
            margin-top: 15px;
            display: none;
        }
        .youtube-preview iframe {
            width: 100%;
            height: 315px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .youtube-help {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }
        .form-section {
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .form-section h3 {
            margin-bottom: 1rem;
            color: #1a365d;
            font-size: 1.1rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2d3748;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="url"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
            transition: border-color 0.2s;
        }
        .form-group textarea {
            min-height: 120px;
        }
        .form-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }
        .required {
            color: #e53e3e;
        }
        .tags-input {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }
        .tagify {
            --tag-bg: #4299e1;
            --tag-hover: #3182ce;
            --tag-text-color: white;
            --tags-border-color: #e2e8f0;
            --tag-remove-btn-color: white;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-primary {
            background-color: #4299e1;
            color: white;
        }
        .btn-primary:hover {
            background-color: #3182ce;
        }
        .btn-secondary {
            background-color: #e2e8f0;
            color: #4a5568;
        }
        .btn-secondary:hover {
            background-color: #cbd5e0;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }
        .alert-danger {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
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
                <a href="/MindCraft-Project/views/mentor/dashboard.php" >
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
                <a href="/MindCraft-Project/views/mentor/buat-kursus-baru.php" class="active" >
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
                <h1>Buat Kursus Baru</h1>
                <p>Isi detail kursus Anda di bawah ini.</p>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
            </div>
            <div class="content-body">
                <form class="course-form" method="POST" enctype="multipart/form-data" id="createCourseForm">
                    <div class="form-section">
                        <h3>Informasi Dasar Kursus</h3>
                        <div class="form-group">
                            <label for="title">Judul Kursus <span class="required">*</span></label>
                            <input type="text" id="title" name="title" placeholder="Contoh: Belajar PHP Dasar untuk Pemula" 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Deskripsi Kursus <span class="required">*</span></label>
                            <textarea id="description" name="description" rows="6" 
                                      placeholder="Jelaskan secara singkat tentang kursus ini..." required><?php 
                                      echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Detail Kursus</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="category">Kategori <span class="required">*</span></label>
                                <select id="category" name="category" required>
                                    <option value="" disabled selected>Pilih Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                            <?php echo ($_POST['category'] ?? '') === $cat['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="difficulty">Tingkat Kesulitan</label>
                                <select id="difficulty" name="difficulty">
                                    <option value="Pemula" <?php echo ($_POST['difficulty'] ?? 'Pemula') === 'Pemula' ? 'selected' : ''; ?>>Pemula</option>
                                    <option value="Menengah" <?php echo ($_POST['difficulty'] ?? '') === 'Menengah' ? 'selected' : ''; ?>>Menengah</option>
                                    <option value="Mahir" <?php echo ($_POST['difficulty'] ?? '') === 'Mahir' ? 'selected' : ''; ?>>Mahir</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price">Harga (Rp)</label>
                                <input type="number" id="price" name="price" placeholder="Contoh: 150000" min="0" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="duration_hours">Durasi Kursus (Jam)</label>
                                <input type="number" id="duration_hours" name="duration_hours" min="0" 
                                       value="<?php echo htmlspecialchars($_POST['duration_hours'] ?? '0'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="language">Bahasa Pengantar</label>
                                <select id="language" name="language">
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo htmlspecialchars($lang); ?>" 
                                            <?php echo ($_POST['language'] ?? 'Bahasa Indonesia') === $lang ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lang); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Konten Kursus</h3>
                        <div class="form-group">
                            <label for="cover_image">Gambar Sampul (Cover) <span class="required">*</span></label>
                            <input type="file" id="cover_image" name="cover_image" accept="image/png, image/jpeg, image/webp" required>
                            <small>Rekomendasi ukuran: 1280x720px. Format: JPG, PNG, WEBP.</small>
                        </div>
                        
                        <div id="modulesContainer">
                            <!-- Modules will be added here -->
                        </div>
                        
                        <button type="button" class="add-module" id="addModuleBtn">
                            <i class="fas fa-plus"></i> Tambah Modul
                        </button>
                    </div>

                    <div class="form-section">
                        <h3>Hasil Pembelajaran</h3>
                        <div class="form-group">
                            <label for="what_you_learn">Apa yang akan dipelajari siswa</label>
                            <textarea id="what_you_learn" name="what_you_learn" rows="4" 
                                      placeholder="Masukkan poin-poin pembelajaran, pisahkan dengan baris baru"><?php 
                                      echo htmlspecialchars($_POST['what_you_learn'] ?? ''); ?></textarea>
                            <small>Gunakan baris baru untuk setiap poin pembelajaran</small>
                        </div>
                        <div class="form-group">
                            <label for="requirements">Persyaratan</label>
                            <textarea id="requirements" name="requirements" rows="4" 
                                      placeholder="Apa saja yang perlu dipersiapkan siswa sebelum mengikuti kursus ini?"><?php 
                                      echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="target_audience">Target Audiens</label>
                            <textarea id="target_audience" name="target_audience" rows="4" 
                                      placeholder="Untuk siapa kursus ini ditujukan?"><?php 
                                      echo htmlspecialchars($_POST['target_audience'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="draft" class="btn btn-secondary">Simpan sebagai Draft</button>
                        <button type="submit" name="publish" class="btn btn-primary">Publikasikan</button>
                    </div>
                </form>
            </div> 
        </main> 
    </div> 

    <script src="https://cdn.jsdelivr.net/npm/tagify@3.22.1/dist/tagify.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modulesContainer = document.getElementById('modulesContainer');
            const addModuleBtn = document.getElementById('addModuleBtn');
            let moduleCounter = 0;
            
            // Add initial module
            addModule();
            
            addModuleBtn.addEventListener('click', addModule);
            
            function addModule() {
                moduleCounter++;
                const moduleId = `module_${moduleCounter}`;
                
                const moduleElement = document.createElement('div');
                moduleElement.className = 'module-container';
                moduleElement.dataset.moduleId = moduleId;
                
                moduleElement.innerHTML = `
                    <div class="module-header">
                        <h4>Modul ${moduleCounter}</h4>
                        <button type="button" class="remove-module" onclick="removeModule('${moduleId}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label for="${moduleId}_title">Judul Modul</label>
                        <input type="text" id="${moduleId}_title" name="module_titles[]" required>
                    </div>
                    <div class="lessons-container" id="${moduleId}_lessons">
                        <!-- Lessons will be added here -->
                    </div>
                    <button type="button" class="add-lesson" onclick="addLesson('${moduleId}')">
                        <i class="fas fa-plus"></i> Tambah Pelajaran
                    </button>
                `;
                
                modulesContainer.appendChild(moduleElement);
                addLesson(moduleId);
            }
            
            // Initialize Tagify
            const tagsInput = document.getElementById('tags');
            if (tagsInput) {
                new Tagify(tagsInput, {
                    duplicates: false,
                    trim: true,
                    pattern: /^[a-zA-Z0-9\s,]+$/,
                    dropdown: { enabled: 0 }
                });
            }
        });

        function addLesson(moduleId) {
            const lessonsContainer = document.getElementById(`${moduleId}_lessons`);
            const lessonCount = lessonsContainer.querySelectorAll('.lesson').length + 1;
            const moduleIndex = parseInt(moduleId.split('_')[1]) - 1;
            
            const lessonElement = document.createElement('div');
            lessonElement.className = 'lesson';
            
            lessonElement.innerHTML = `
                <div class="lesson-header">
                    <h5>Pelajaran ${lessonCount}</h5>
                    <button type="button" class="remove-lesson" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-group">
                    <label>Judul Pelajaran</label>
                    <input type="text" name="lesson_titles[${moduleIndex}][]" required>
                </div>
                <div class="form-group">
                    <label>URL Video YouTube</label>
                    <input type="url" name="lesson_urls[${moduleIndex}][]" pattern="^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/.+$">
                    <div class="youtube-preview" id="${moduleId}_lesson_${lessonCount}_preview"></div>
                </div>
                <div class="form-group">
                    <label>Deskripsi Pelajaran</label>
                    <textarea name="lesson_descriptions[${moduleIndex}][]" rows="3"></textarea>
                </div>
            `;
            
            lessonsContainer.appendChild(lessonElement);
            
            // Add YouTube preview functionality
            const youtubeInput = lessonElement.querySelector('input[type="url"]');
            const youtubePreview = lessonElement.querySelector('.youtube-preview');
            
            youtubeInput.addEventListener('input', function() {
                updateYouTubePreview(this, youtubePreview);
            });
        }

        function removeModule(moduleId) {
            const moduleElement = document.querySelector(`[data-module-id="${moduleId}"]`);
            if (moduleElement) {
                moduleElement.remove();
                
                // Renumber remaining modules
                const modules = document.querySelectorAll('.module-container');
                modules.forEach((module, index) => {
                    const moduleHeader = module.querySelector('.module-header h4');
                    if (moduleHeader) {
                        moduleHeader.textContent = `Modul ${index + 1}`;
                    }
                });
            }
        }

        function updateYouTubePreview(inputElement, previewElement) {
            const url = inputElement.value.trim();
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            
            if (url && match && match[2].length === 11) {
                previewElement.style.display = 'block';
                previewElement.innerHTML = `
                    <iframe src="https://www.youtube.com/embed/${match[2]}" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                    <p class="youtube-help">Pratinjau video YouTube</p>
                `;
            } else {
                previewElement.style.display = 'none';
            }
        }
    </script>
</body>
</html>
