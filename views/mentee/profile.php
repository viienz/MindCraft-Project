<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../landingpage/landingpage.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$user_type = $_SESSION['user_type'];

// Get user stats
function getUserStats($pdo, $user_id) {
    $stats = ['courses_taken' => 0, 'courses_completed' => 0, 'certificates' => 0];
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
        $stmt->execute([$user_id]);
        $stats['courses_taken'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND progress_percentage = 100");
        $stmt->execute([$user_id]);
        $stats['courses_completed'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND certificate_issued = 1");
        $stmt->execute([$user_id]);
        $stats['certificates'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    return $stats;
}

$userStats = getUserStats($pdo, $user_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Profil Pengguna</title>
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/header.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentee_profile.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentee-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="profile-container">
        <h1 class="profile-title">Profil Saya</h1>
        
        <div class="profile-card">
            <div class="profile-sidebar">
                <div class="avatar-large">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <h2 class="profile-name"><?= htmlspecialchars($username) ?></h2>
                <span class="user-badge"><?= htmlspecialchars($user_type) ?></span>
                
                <div class="profile-contact">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($email) ?></span>
                </div>
                
                <div class="profile-actions">
                    <button onclick="openEditModal()" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Edit Profil
                    </button>
                    <a href="settings.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Pengaturan
                    </a>
                </div>
            </div>
            
            <div class="profile-content">
                <h3 class="section-title">Statistik Pembelajaran</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Kursus Diikuti</div>
                        <div class="stat-value"><?= $userStats['courses_taken'] ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Kursus Selesai</div>
                        <div class="stat-value"><?= $userStats['courses_completed'] ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Sertifikat</div>
                        <div class="stat-value"><?= $userStats['certificates'] ?></div>
                    </div>
                </div>
                
                <h3 class="section-title">Aktivitas Terakhir</h3>
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p>Tidak ada aktivitas terakhir</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profil</h3>
                <button onclick="closeModal()" class="close-btn">&times;</button>
            </div>
            <form method="POST" action="profile.php" class="modal-body">
                <div class="form-group">
                    <label for="username">Nama Lengkap</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($email) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Tipe Akun</label>
                    <input type="text" value="<?= htmlspecialchars($user_type) ?>" disabled>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
  // Profile Page JavaScript
function openEditModal() {
    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
    </script>
</body>
</html>