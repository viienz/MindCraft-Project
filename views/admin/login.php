<?php
session_start();

// Simple authentication logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Replace with your actual admin credentials or database check
    $admin_email = 'admin@mindcraft.com';
    $admin_password = 'admin123'; // In production, use hashed passwords
    
    if ($email === $admin_email && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = "Invalid email or password";
    }
}

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-light: #7c3aed;
            --primary-dark: #5b21b6;
            --accent: #06b6d4;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-600: #4b5563;
            --gray-900: #111827;
            --success: #10b981;
            --error: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }
        
        .login-container {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 440px;
            padding: 48px 40px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        }
        
        .brand-logo .logo-icon {
            width: 56px;
            height: 56px;
            background: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            box-shadow: var(--shadow-md);
        }
        
        .brand-logo .logo-icon i {
            font-size: 28px;
            color: white;
        }
        
        .brand-logo .brand-text h1 {
            font-size: 24px;
            font-weight: 700;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 4px;
        }
        
        .brand-logo .brand-text p {
            font-size: 12px;
            color: var(--gray-400);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }
        
        .login-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--gray-600);
            font-size: 15px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 16px 16px 52px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--gray-50);
            color: var(--gray-900);
        }
        
        .form-input::placeholder {
            color: var(--gray-400);
            font-weight: 400;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-light);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input:focus + i {
            color: var(--primary-light);
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary-light);
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 32px 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider span {
            padding: 0 16px;
            color: var(--gray-400);
            font-size: 14px;
            font-weight: 500;
        }
        
        .error-alert {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #fca5a5;
            color: #dc2626;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .error-alert i {
            font-size: 18px;
            margin-right: 12px;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 24px;
        }
        
        .forgot-password a {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .forgot-password a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-light);
            transition: width 0.3s ease;
        }
        
        .forgot-password a:hover::after {
            width: 100%;
        }
        
        .security-info {
            text-align: center;
            margin-top: 32px;
            padding: 16px;
            background: rgba(6, 182, 212, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(6, 182, 212, 0.2);
        }
        
        .security-info i {
            color: var(--accent);
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .security-info p {
            color: var(--gray-600);
            font-size: 13px;
            line-height: 1.4;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
                margin: 10px;
            }
            
            .brand-logo .logo-icon {
                width: 48px;
                height: 48px;
            }
            
            .brand-logo .logo-icon i {
                font-size: 24px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-logo">
            <div class="logo-icon">
                <i class="fas fa-shield"></i>
            </div>
            <div class="brand-text">
                <h1>MindCraft</h1>
                <p>Admin Portal</p>
            </div>
        </div>
        
        <div class="login-header">
            <h2>Selamat Datang</h2>
            <p>Masukkan kredensial Anda untuk mengakses dashboard admin</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Masukkan email" required>
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Masukkan kata sandi" required>
                    <i class="fas fa-lock"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggle-icon"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                Masuk ke Dashboard
            </button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        function showForgotPassword() {
            alert('Fitur reset password akan segera tersedia. Silakan hubungi administrator sistem.');
        }
        
        // Add smooth focus transitions
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = '#7c3aed';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('i').style.color = '#9ca3af';
                }
            });
        });
        
        // Add loading state to login button
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.querySelector('.login-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Memverifikasi...';
            btn.disabled = true;
            
            // Re-enable button after 3 seconds (in case of error)
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        });
    </script>
</body>
</html>