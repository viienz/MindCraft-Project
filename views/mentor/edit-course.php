<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Mentor') {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

// Validate course ID from URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: /MindCraft-Project/views/mentor/kursus-saya.php?error=invalid_id");
    exit();
}
$course_id_to_edit = $_GET['id'];

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../model/CourseModel.php';
require_once __DIR__ . '/../../model/UserModel.php';

$database = new Database();
$db = $database->connect();
$courseModel = new CourseModel($db);
$userModel = new UserModel($db);

// Get course data to edit
$course = $courseModel->getCourseById($course_id_to_edit);
$mentor = $userModel->getMentorById($_SESSION['user_id']);

// Security Check: ensure course exists & belongs to logged in mentor
if (!$course || $course['mentor_id'] != $_SESSION['user_id']) {
    header("Location: /MindCraft-Project/views/mentor/kursus-saya.php?error=not_found_or_unauthorized");
    exit();
}

// Get categories for dropdown
$categories = $db->query("SELECT name FROM course_categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kursus - <?php echo htmlspecialchars($course['title']); ?> | MindCraft</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_kursus-saya.css"> -->
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_edit-course.css">
</head>
<body>
    <!-- Top Navigation -->
    <header class="top-header">
        <div class="logo">MindCraft</div>
        <div class="header-right">
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars($mentor['username'] ?? 'Mentor'); ?></span>
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($mentor['username'] ?? 'M', 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
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
                    <span>Keluar</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Edit Kursus</h1>
            <p>Perbarui detail untuk kursus "<?php echo htmlspecialchars($course['title']); ?>"</p>
        </div>

        <form class="course-form" action="/MindCraft-Project/controller/course.php?action=update" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            
            <!-- Course Basic Information -->
            <div class="form-section">
                <h2 class="section-title">Informasi Dasar Kursus</h2>
                
                <div class="form-group">
                    <label for="title" class="form-label">Judul Kursus</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Deskripsi Kursus</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>
            </div>
            
            <!-- Course Details -->
            <div class="form-section">
                <h2 class="section-title">Detail Kursus</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category" class="form-label">Kategori</label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                    <?php echo ($course['category'] == $category) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty" class="form-label">Tingkat Kesulitan</label>
                        <select id="difficulty" name="difficulty" class="form-control">
                            <option value="Pemula" <?php echo ($course['difficulty'] == 'Pemula') ? 'selected' : ''; ?>>Pemula</option>
                            <option value="Menengah" <?php echo ($course['difficulty'] == 'Menengah') ? 'selected' : ''; ?>>Menengah</option>
                            <option value="Mahir" <?php echo ($course['difficulty'] == 'Mahir') ? 'selected' : ''; ?>>Mahir</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price" class="form-label">Harga (Rp)</label>
                        <input type="number" id="price" name="price" class="form-control" 
                               value="<?php echo htmlspecialchars($course['price']); ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration" class="form-label">Durasi (jam)</label>
                        <input type="number" id="duration" name="duration" class="form-control" 
                               value="<?php echo htmlspecialchars($course['duration'] ?? 0); ?>" min="0">
                    </div>
                </div>
            </div>
            
            <!-- Course Cover Image -->
            <div class="form-section">
                <h2 class="section-title">Gambar Sampul</h2>
                
                <div class="form-group">
                    <label for="cover_image" class="form-label">Unggah Gambar Baru</label>
                    <input type="file" id="cover_image" name="cover_image" class="form-control" 
                           accept="image/png, image/jpeg, image/webp">
                    <p class="form-hint">Format: JPG, PNG, atau WEBP. Ukuran maksimal 2MB.</p>
                    
                    <div class="current-image">
                        <p class="form-label">Gambar Saat Ini:</p>
                        <img src="<?php echo htmlspecialchars($course['cover_image']); ?>" 
                             alt="Cover kursus <?php echo htmlspecialchars($course['title']); ?>">
                        <p class="form-hint">Kosongkan jika tidak ingin mengubah gambar.</p>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button type="submit" name="draft" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Simpan Draft
                </button>
                <button type="submit" name="publish" class="btn btn-primary">
                    <i class="fas fa-cloud-upload-alt"></i> Publikasikan
                </button>
            </div>
        </form>
    </main>

    <script src="/MindCraft-Project/assets/js/mentor_dashboard.js"></script>
</body>
</html>