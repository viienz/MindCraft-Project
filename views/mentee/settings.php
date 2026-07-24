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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = "Password saat ini salah";
        } elseif ($new_password !== $confirm_password) {
            $error = "Password baru dan konfirmasi password tidak cocok";
        } elseif (strlen($new_password) < 6) {
            $error = "Password minimal 6 karakter";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $success = "Password berhasil diubah!";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Pengaturan Akun</title>
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/header.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentee_settings.css">
        <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentee-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="settings-container">
        <h1 class="settings-title">Pengaturan Akun</h1>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>
        
        <div class="settings-card">
            <section class="account-section">
                <h2 class="section-title">Informasi Akun</h2>
                
                <div class="account-info">
                    <div class="info-item">
                        <label>Nama Pengguna</label>
                        <div class="info-value"><?= htmlspecialchars($username) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <label>Email</label>
                        <div class="info-value"><?= htmlspecialchars($email) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <label>Tipe Akun</label>
                        <div class="info-value"><?= htmlspecialchars($user_type) ?></div>
                    </div>
                </div>
            </section>
            
            <section class="password-section">
                <h2 class="section-title">Ubah Password</h2>
                
                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password', this)"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <div class="input-group">
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Ubah Password
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    
    <script>
  // Settings Page JavaScript
function togglePassword(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
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

// Password strength validation
document.getElementById('new_password')?.addEventListener('input', function() {
    // You can add password strength validation here
});
    </script>
</body>
</html>