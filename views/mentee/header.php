<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header" id="main-header">
        <div class="header-content">
            <!-- Logo Section -->
            <a href="kursus.php" class="logo">
                <img src="../../assets/img/20250502_083014.png" alt="MindCraft Logo" id="logo-img">
                <h1>MindCraft</h1>
            </a>
            
            <!-- Main Navigation -->
            <nav class="main-nav">
                <ul>
                    <li><a href="kursus.php" class="nav-link active"><i class="fas fa-book-open"></i> Kursus</a></li>
                    <li><a href="ai_assistant.php" class="nav-link"><i class="fas fa-robot"></i> MindBot</a></li>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a></li>
                </ul>
            </nav>
            
            <!-- User Actions -->
            <div class="header-actions">  
                
                <!-- User menu for logged in users -->
                <div class="user-menu">
                    <div class="user-avatar" id="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($username); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_type); ?></span>
                    </div>
                    <div class="dropdown-menu" id="dropdown-menu">
                        <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profil</a>
                        <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Pengaturan</a>
                        <div class="dropdown-divider"></div>
                        <a href="../landingpage/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
                
                <button class="btn btn-menu" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
