<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'MindCraft - Mentor Dashboard'); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="/MindCraft-Project/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/main.css">
    <?php if (!empty($page_css)): ?>
        <link rel="stylesheet" href="/MindCraft-Project/assets/css/<?php echo htmlspecialchars($page_css); ?>">
    <?php endif; ?>
    
    <!-- JS -->
    <script src="/MindCraft-Project/assets/js/main.js" defer></script>
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --info: #3b82f6;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --rounded-sm: 0.25rem;
            --rounded: 0.5rem;
            --rounded-md: 0.75rem;
            --rounded-lg: 1rem;
            --rounded-full: 9999px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
            line-height: 1.5;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        a {
            text-decoration: none;
            color: var(--primary);
            transition: all 0.2s ease;
        }
        
        a:hover {
            color: var(--primary-dark);
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header Styles */
        .top-header {
            display: none;
            background-color: white;
            padding: 1rem;
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            z-index: 90;
            height: 70px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }
        
        .logo {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo-icon {
            color: var(--primary);
            font-size: 1.75rem;
        }
        
        .mobile-menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--rounded-full);
            transition: all 0.2s ease;
        }
        
        .mobile-menu-toggle:hover {
            background-color: var(--gray-100);
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background-color: white;
            box-shadow: var(--shadow);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 100;
            padding: 1.5rem 0;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 1rem;
        }
        
        .sidebar-logo {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--rounded-full);
            object-fit: cover;
            background-color: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-700);
            font-weight: 600;
        }
        
        .sidebar-user-info h4 {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-user-info p {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--gray-600);
            text-decoration: none;
            transition: all 0.2s ease;
            gap: 0.75rem;
        }
        
        .sidebar-menu li a:hover {
            background-color: var(--gray-50);
            color: var(--primary);
        }
        
        .sidebar-menu li a.active {
            background-color: var(--primary-light);
            color: white;
            font-weight: 500;
        }
        
        .sidebar-menu li a i {
            width: 24px;
            text-align: center;
        }
        
        .sidebar-menu .menu-divider {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
            margin-top: 1rem;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        /* Responsive Styles */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 6rem;
            }
            
            .top-header {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem;
                padding-top: 6rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <header class="top-header">
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-brain logo-icon"></i>
                <span>MindCraft</span>
            </div>
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-brain"></i>
                    <span>MindCraft</span>
                </div>
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        <?php 
                            $initial = isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'M';
                            echo $initial;
                        ?>
                    </div>
                    <div class="sidebar-user-info">
                        <h4><?php echo htmlspecialchars($_SESSION['username'] ?? 'Mentor'); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Mentor'); ?></p>
                    </div>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="/MindCraft-Project/views/mentor/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a></li>
                
                <li><a href="/MindCraft-Project/views/mentor/kursus-saya.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'kursus-saya.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Kursus Saya</span>
                </a></li>
                
                <li><a href="/MindCraft-Project/views/mentor/buat-kursus-baru.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'buat-kursus-baru.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Buat Kursus Baru</span>
                </a></li>
                
                <li><a href="/MindCraft-Project/views/mentor/pendapatan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'pendapatan.php' || 
                                                                                      basename($_SERVER['PHP_SELF']) === 'tarik-dana.php' || 
                                                                                      basename($_SERVER['PHP_SELF']) === 'riwayat-penarikan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Pendapatan</span>
                </a></li>
                
                <li><a href="/MindCraft-Project/views/mentor/analitik.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'analitik.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analitik</span>
                </a></li>
                
                <li><a href="/MindCraft-Project/views/mentor/pengaturan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'pengaturan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a></li>
                
                <li class="menu-divider">Akun</li>
                
                <li><a href="/MindCraft-Project/views/landingpage/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">