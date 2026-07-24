<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Optional: Add a logout message to display on login page
session_start();
$_SESSION['logout_message'] = 'Anda berhasil logout';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Logout</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-light: #7c3aed;
            --success: #10b981;
            --white: #ffffff;
            --gray-100: #f3f4f6;
            --gray-600: #4b5563;
            --gray-900: #111827;
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
        
        .logout-container {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 420px;
            padding: 48px 40px;
            text-align: center;
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
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: checkmark 0.8s ease-out 0.3s both;
        }
        
        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(45deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.1) rotate(45deg);
                opacity: 1;
            }
            100% {
                transform: scale(1) rotate(45deg);
                opacity: 1;
            }
        }
        
        .logout-icon i {
            font-size: 36px;
            color: white;
        }
        
        .logout-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
        }
        
        .logout-header p {
            color: var(--gray-600);
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 32px;
        }
        
        .logout-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .btn {
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }
        
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid rgba(75, 85, 99, 0.2);
        }
        
        .btn-secondary:hover {
            background: var(--white);
            color: var(--gray-900);
            transform: translateY(-1px);
        }
        
        .countdown {
            margin-top: 24px;
            padding: 16px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .countdown p {
            color: var(--gray-600);
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .countdown-timer {
            font-size: 20px;
            font-weight: 700;
            color: var(--success);
        }
        
        @media (max-width: 480px) {
            .logout-container {
                padding: 32px 24px;
                margin: 10px;
            }
            
            .logout-header h1 {
                font-size: 24px;
            }
            
            .logout-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <div class="logout-header">
            <h1>Logout Berhasil!</h1>
            <p>Anda telah berhasil keluar dari panel admin MindCraft. Semua sesi telah dihapus dengan aman.</p>
        </div>
        
        <div class="logout-actions">
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                Login Kembali
            </a>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Kembali ke Beranda
            </a>
        </div>
        
        <div class="countdown">
            <p>Anda akan diarahkan ke halaman login dalam:</p>
            <div class="countdown-timer" id="countdown">5</div>
        </div>
    </div>

    <script>
        // Countdown timer
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);
        
        // Add click handler for manual redirect
        document.addEventListener('click', () => {
            clearInterval(timer);
        });
        
        // Clear any remaining session storage
        if (typeof(Storage) !== "undefined") {
            localStorage.clear();
            sessionStorage.clear();
        }
        
        // Prevent back button to access admin pages
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>