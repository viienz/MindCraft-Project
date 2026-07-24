<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Mentor') {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/MentorController.php';
$database = new Database();
$db = $database->connect();

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

// Get mentor ID
$mentorId = $_SESSION['user_id'];

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Determine which form was submitted
        if (isset($_POST['update_profile'])) {
            // Update profile information
            $fullName = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
            $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $website = filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL);
            $linkedin = filter_input(INPUT_POST, 'linkedin', FILTER_SANITIZE_URL);
            $instagram = filter_input(INPUT_POST, 'instagram', FILTER_SANITIZE_URL);
            $youtube = filter_input(INPUT_POST, 'youtube', FILTER_SANITIZE_URL);
            $specialization = filter_input(INPUT_POST, 'specialization', FILTER_SANITIZE_STRING);
            $experienceYears = filter_input(INPUT_POST, 'experience_years', FILTER_SANITIZE_NUMBER_INT);
            $education = filter_input(INPUT_POST, 'education', FILTER_SANITIZE_STRING);
            $certifications = filter_input(INPUT_POST, 'certifications', FILTER_SANITIZE_STRING);

            // Handle profile picture upload
            $profilePicture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/uploads/profile_pictures/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExt = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $fileName = 'mentor_' . $mentorId . '_' . time() . '.' . $fileExt;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                    $profilePicture = '/MindCraft-Project/assets/uploads/profile_pictures/' . $fileName;
                }
            }

            // Check if profile exists
            $query = "SELECT id FROM mentor_profiles WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $mentorId);
            $stmt->execute();
            $profileExists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($profileExists) {
                // Update existing profile
                $query = "UPDATE mentor_profiles SET 
                          full_name = :full_name,
                          bio = :bio,
                          phone = :phone,
                          website = :website,
                          linkedin = :linkedin,
                          instagram = :instagram,
                          youtube = :youtube,
                          specialization = :specialization,
                          experience_years = :experience_years,
                          education = :education,
                          certifications = :certifications" .
                         ($profilePicture ? ", profile_picture = :profile_picture" : "") . "
                          WHERE user_id = :user_id";
            } else {
                // Insert new profile
                $query = "INSERT INTO mentor_profiles 
                          (user_id, full_name, bio, phone, website, linkedin, instagram, youtube, 
                          specialization, experience_years, education, certifications" .
                          ($profilePicture ? ", profile_picture" : "") . ")
                          VALUES 
                          (:user_id, :full_name, :bio, :phone, :website, :linkedin, :instagram, :youtube,
                          :specialization, :experience_years, :education, :certifications" .
                          ($profilePicture ? ", :profile_picture" : "") . ")";
            }

            $stmt = $db->prepare($query);
            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':website', $website);
            $stmt->bindParam(':linkedin', $linkedin);
            $stmt->bindParam(':instagram', $instagram);
            $stmt->bindParam(':youtube', $youtube);
            $stmt->bindParam(':specialization', $specialization);
            $stmt->bindParam(':experience_years', $experienceYears);
            $stmt->bindParam(':education', $education);
            $stmt->bindParam(':certifications', $certifications);
            if ($profilePicture) {
                $stmt->bindParam(':profile_picture', $profilePicture);
            }
            $stmt->bindParam(':user_id', $mentorId);
            $stmt->execute();

            $successMessage = 'Profil berhasil diperbarui!';

        } elseif (isset($_POST['update_settings'])) {
            // Update mentor settings
            $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
            $pushNotifications = isset($_POST['push_notifications']) ? 1 : 0;
            $courseNotifications = isset($_POST['course_notifications']) ? 1 : 0;
            $reviewNotifications = isset($_POST['review_notifications']) ? 1 : 0;
            $paymentNotifications = isset($_POST['payment_notifications']) ? 1 : 0;
            $marketingEmails = isset($_POST['marketing_emails']) ? 1 : 0;
            $profileVisibility = filter_input(INPUT_POST, 'profile_visibility', FILTER_SANITIZE_STRING);
            $autoAcceptStudents = isset($_POST['auto_accept_students']) ? 1 : 0;
            $courseApprovalRequired = isset($_POST['course_approval_required']) ? 1 : 0;
            $languagePreference = filter_input(INPUT_POST, 'language_preference', FILTER_SANITIZE_STRING);
            $timezone = filter_input(INPUT_POST, 'timezone', FILTER_SANITIZE_STRING);
            $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);

            // Check if settings exist
            $query = "SELECT id FROM mentor_settings WHERE mentor_id = :mentor_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->execute();
            $settingsExist = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($settingsExist) {
                $query = "UPDATE mentor_settings SET 
                          email_notifications = :email_notifications,
                          push_notifications = :push_notifications,
                          course_notifications = :course_notifications,
                          review_notifications = :review_notifications,
                          payment_notifications = :payment_notifications,
                          marketing_emails = :marketing_emails,
                          profile_visibility = :profile_visibility,
                          auto_accept_students = :auto_accept_students,
                          course_approval_required = :course_approval_required,
                          language_preference = :language_preference,
                          timezone = :timezone,
                          currency = :currency
                          WHERE mentor_id = :mentor_id";
            } else {
                $query = "INSERT INTO mentor_settings 
                          (mentor_id, email_notifications, push_notifications, course_notifications,
                          review_notifications, payment_notifications, marketing_emails, profile_visibility,
                          auto_accept_students, course_approval_required, language_preference, timezone, currency)
                          VALUES 
                          (:mentor_id, :email_notifications, :push_notifications, :course_notifications,
                          :review_notifications, :payment_notifications, :marketing_emails, :profile_visibility,
                          :auto_accept_students, :course_approval_required, :language_preference, :timezone, :currency)";
            }

            $stmt = $db->prepare($query);
            $stmt->bindParam(':email_notifications', $emailNotifications);
            $stmt->bindParam(':push_notifications', $pushNotifications);
            $stmt->bindParam(':course_notifications', $courseNotifications);
            $stmt->bindParam(':review_notifications', $reviewNotifications);
            $stmt->bindParam(':payment_notifications', $paymentNotifications);
            $stmt->bindParam(':marketing_emails', $marketingEmails);
            $stmt->bindParam(':profile_visibility', $profileVisibility);
            $stmt->bindParam(':auto_accept_students', $autoAcceptStudents);
            $stmt->bindParam(':course_approval_required', $courseApprovalRequired);
            $stmt->bindParam(':language_preference', $languagePreference);
            $stmt->bindParam(':timezone', $timezone);
            $stmt->bindParam(':currency', $currency);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->execute();

            $successMessage = 'Pengaturan berhasil diperbarui!';

        } elseif (isset($_POST['update_payout'])) {
            // Update payout settings
            $payoutMethod = filter_input(INPUT_POST, 'payout_method', FILTER_SANITIZE_STRING);
            $payoutSchedule = filter_input(INPUT_POST, 'payout_schedule', FILTER_SANITIZE_STRING);
            $minimumPayout = filter_input(INPUT_POST, 'minimum_payout', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $taxInformation = filter_input(INPUT_POST, 'tax_information', FILTER_SANITIZE_STRING);

            // Update mentor_settings
            $query = "UPDATE mentor_settings SET 
                      payout_method = :payout_method,
                      payout_schedule = :payout_schedule,
                      minimum_payout = :minimum_payout,
                      tax_information = :tax_information
                      WHERE mentor_id = :mentor_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':payout_method', $payoutMethod);
            $stmt->bindParam(':payout_schedule', $payoutSchedule);
            $stmt->bindParam(':minimum_payout', $minimumPayout);
            $stmt->bindParam(':tax_information', $taxInformation);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->execute();

            // Handle bank account information
            if ($payoutMethod === 'bank_transfer') {
                $bankName = filter_input(INPUT_POST, 'bank_name', FILTER_SANITIZE_STRING);
                $accountNumber = filter_input(INPUT_POST, 'account_number', FILTER_SANITIZE_STRING);
                $accountName = filter_input(INPUT_POST, 'account_name', FILTER_SANITIZE_STRING);

                // Check if bank account already exists
                $query = "SELECT id FROM mentor_bank_accounts WHERE mentor_id = :mentor_id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->execute();
                $bankAccountExists = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($bankAccountExists) {
                    // Update existing bank account
                    $query = "UPDATE mentor_bank_accounts SET 
                              bank_name = :bank_name,
                              account_number = :account_number,
                              account_name = :account_name,
                              is_verified = 1
                              WHERE mentor_id = :mentor_id";
                } else {
                    // Insert new bank account
                    $query = "INSERT INTO mentor_bank_accounts 
                              (mentor_id, bank_name, account_number, account_name, is_verified)
                              VALUES 
                              (:mentor_id, :bank_name, :account_number, :account_name, 1)";
                }

                $stmt = $db->prepare($query);
                $stmt->bindParam(':bank_name', $bankName);
                $stmt->bindParam(':account_number', $accountNumber);
                $stmt->bindParam(':account_name', $accountName);
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->execute();
            } else {
                // Handle e-wallet information
                $ewalletType = filter_input(INPUT_POST, 'ewallet_type', FILTER_SANITIZE_STRING);
                $ewalletName = filter_input(INPUT_POST, 'ewallet_name', FILTER_SANITIZE_STRING);
                $ewalletPhone = filter_input(INPUT_POST, 'ewallet_phone', FILTER_SANITIZE_STRING);

                // Check if e-wallet already exists
                $query = "SELECT id FROM mentor_ewallets WHERE mentor_id = :mentor_id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->execute();
                $ewalletExists = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ewalletExists) {
                    // Update existing e-wallet
                    $query = "UPDATE mentor_ewallets SET 
                              type = :type,
                              name = :name,
                              phone_number = :phone_number,
                              is_verified = 1
                              WHERE mentor_id = :mentor_id";
                } else {
                    // Insert new e-wallet
                    $query = "INSERT INTO mentor_ewallets 
                              (mentor_id, type, name, phone_number, is_verified)
                              VALUES 
                              (:mentor_id, :type, :name, :phone_number, 0)";
                }

                $stmt = $db->prepare($query);
                $stmt->bindParam(':type', $ewalletType);
                $stmt->bindParam(':name', $ewalletName);
                $stmt->bindParam(':phone_number', $ewalletPhone);
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->execute();
            }

            $successMessage = 'Pengaturan pembayaran berhasil diperbarui!';
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        $errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Get mentor profile data
$query = "SELECT * FROM mentor_profiles WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $mentorId);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get mentor settings
$query = "SELECT * FROM mentor_settings WHERE mentor_id = :mentor_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If settings don't exist, create default settings
if (!$settings) {
    $query = "INSERT INTO mentor_settings (mentor_id) VALUES (:mentor_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    
    // Get the newly created settings
    $query = "SELECT * FROM mentor_settings WHERE mentor_id = :mentor_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get bank account info
$query = "SELECT * FROM mentor_bank_accounts WHERE mentor_id = :mentor_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$bankAccount = $stmt->fetch(PDO::FETCH_ASSOC);

// Get e-wallet info
$query = "SELECT * FROM mentor_ewallets WHERE mentor_id = :mentor_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$ewallet = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user data
$query = "SELECT username, email FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $mentorId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Mentor - MindCraft</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_pengaturan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header class="top-header">
        <div class="logo">MindCraft</div>
        <div class="header-right">
            <div class="header-profile">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($mentorName, 0, 1)); ?>
                </div>
                <span class="profile-name"><?php echo htmlspecialchars($user['username'] ?? 'Mentor'); ?></span>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">   
        <ul class="sidebar-menu">
            <li>
                <a href="/MindCraft-Project/views/mentor/dashboard.php">
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
                <a href="/MindCraft-Project/views/mentor/pengaturan.php" class="active">
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
            <h1>Pengaturan Mentor</h1>
        </div>

        <div class="content-body">
            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($successMessage); ?></span>
                    <button class="btn-close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($errorMessage); ?></span>
                    <button class="btn-close">&times;</button>
                </div>
            <?php endif; ?>

            <div class="settings-tabs">
                <button class="tab-btn active" data-tab="profile">Profil Saya</button>
                <button class="tab-btn" data-tab="settings">Pengaturan Akun</button>
                <button class="tab-btn" data-tab="payout">Pembayaran & Pencairan</button>
            </div>

            <div class="tab-content active" id="profile-tab">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3>Informasi Pribadi</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Nama Lengkap</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="3"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                            <small>Deskripsi singkat tentang diri Anda</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Informasi Kontak</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Nomor Telepon</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="linkedin">LinkedIn</label>
                                <input type="url" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($profile['linkedin'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="instagram">Instagram</label>
                                <input type="url" id="instagram" name="instagram" value="<?php echo htmlspecialchars($profile['instagram'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="youtube">YouTube</label>
                            <input type="url" id="youtube" name="youtube" value="<?php echo htmlspecialchars($profile['youtube'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Informasi Profesional</h3>
                        <div class="form-group">
                            <label for="specialization">Spesialisasi</label>
                            <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($profile['specialization'] ?? ''); ?>">
                            <small>Contoh: Web Development, UI/UX Design, Data Science</small>
                        </div>
                        <div class="form-group">
                            <label for="experience_years">Pengalaman (tahun)</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" max="50" value="<?php echo htmlspecialchars($profile['experience_years'] ?? '0'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="education">Pendidikan</label>
                            <textarea id="education" name="education" rows="2"><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                            <small>Contoh: S1 Teknik Informatika - Universitas Indonesia</small>
                        </div>
                        <div class="form-group">
                            <label for="certifications">Sertifikasi</label>
                            <textarea id="certifications" name="certifications" rows="2"><?php echo htmlspecialchars($profile['certifications'] ?? ''); ?></textarea>
                            <small>Pisahkan dengan koma, contoh: Google Certified, AWS Certified</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            <div class="tab-content" id="settings-tab">
                <form method="POST">
                    <div class="form-section">
                        <h3>Preferensi Notifikasi</h3>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="email_notifications" <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Email Notifikasi</span>
                            </label>
                            <label>
                                <input type="checkbox" name="push_notifications" <?php echo ($settings['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Push Notifikasi</span>
                            </label>
                            <label>
                                <input type="checkbox" name="course_notifications" <?php echo ($settings['course_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Notifikasi Kursus</span>
                            </label>
                            <label>
                                <input type="checkbox" name="review_notifications" <?php echo ($settings['review_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Notifikasi Ulasan</span>
                            </label>
                            <label>
                                <input type="checkbox" name="payment_notifications" <?php echo ($settings['payment_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Notifikasi Pembayaran</span>
                            </label>
                            <label>
                                <input type="checkbox" name="marketing_emails" <?php echo ($settings['marketing_emails'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Email Marketing</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Pengaturan Privasi</h3>
                        <div class="form-group">
                            <label for="profile_visibility">Visibilitas Profil</label>
                            <select id="profile_visibility" name="profile_visibility">
                                <option value="public" <?php echo ($settings['profile_visibility'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Publik (Semua orang bisa melihat)</option>
                                <option value="limited" <?php echo ($settings['profile_visibility'] ?? 'public') === 'limited' ? 'selected' : ''; ?>>Terbatas (Hanya siswa yang terdaftar)</option>
                                <option value="private" <?php echo ($settings['profile_visibility'] ?? 'public') === 'private' ? 'selected' : ''; ?>>Privat (Hanya saya yang bisa melihat)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Pengaturan Kursus</h3>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="auto_accept_students" <?php echo ($settings['auto_accept_students'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Terima siswa secara otomatis</span>
                            </label>
                            <label>
                                <input type="checkbox" name="course_approval_required" <?php echo ($settings['course_approval_required'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Butuh persetujuan admin untuk kursus baru</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Preferensi Lainnya</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="language_preference">Bahasa</label>
                                <select id="language_preference" name="language_preference">
                                    <option value="id" <?php echo ($settings['language_preference'] ?? 'id') === 'id' ? 'selected' : ''; ?>>Bahasa Indonesia</option>
                                    <option value="en" <?php echo ($settings['language_preference'] ?? 'id') === 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="timezone">Zona Waktu</label>
                                <select id="timezone" name="timezone">
                                    <option value="Asia/Jakarta" <?php echo ($settings['timezone'] ?? 'Asia/Jakarta') === 'Asia/Jakarta' ? 'selected' : ''; ?>>WIB (Jakarta)</option>
                                    <option value="Asia/Makassar" <?php echo ($settings['timezone'] ?? 'Asia/Jakarta') === 'Asia/Makassar' ? 'selected' : ''; ?>>WITA (Makassar)</option>
                                    <option value="Asia/Jayapura" <?php echo ($settings['timezone'] ?? 'Asia/Jakarta') === 'Asia/Jayapura' ? 'selected' : ''; ?>>WIT (Jayapura)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="currency">Mata Uang</label>
                                <select id="currency" name="currency">
                                    <option value="IDR" <?php echo ($settings['currency'] ?? 'IDR') === 'IDR' ? 'selected' : ''; ?>>IDR (Rupiah)</option>
                                    <option value="USD" <?php echo ($settings['currency'] ?? 'IDR') === 'USD' ? 'selected' : ''; ?>>USD (Dollar)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_settings" class="btn-save">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            <div class="tab-content" id="payout-tab">
                <form method="POST">
                    <div class="form-section">
                        <h3>Metode Pembayaran</h3>
                        <div class="form-group">
                            <label for="payout_method">Metode Pencairan</label>
                            <select id="payout_method" name="payout_method">
                                <option value="bank_transfer" <?php echo ($settings['payout_method'] ?? 'bank_transfer') === 'bank_transfer' ? 'selected' : ''; ?>>Transfer Bank</option>
                                <option value="gopay" <?php echo ($settings['payout_method'] ?? 'bank_transfer') === 'gopay' ? 'selected' : ''; ?>>GoPay</option>
                                <option value="dana" <?php echo ($settings['payout_method'] ?? 'bank_transfer') === 'dana' ? 'selected' : ''; ?>>DANA</option>
                                <option value="ovo" <?php echo ($settings['payout_method'] ?? 'bank_transfer') === 'ovo' ? 'selected' : ''; ?>>OVO</option>
                            </select>
                        </div>

                        <div id="bank-account-fields" class="<?php echo ($settings['payout_method'] ?? 'bank_transfer') !== 'bank_transfer' ? 'hidden' : ''; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bank_name">Nama Bank</label>
                                    <select id="bank_name" name="bank_name">
                                        <option value="">Pilih Bank</option>
                                        <option value="BCA" <?php echo ($bankAccount['bank_name'] ?? '') === 'BCA' ? 'selected' : ''; ?>>BCA</option>
                                        <option value="Mandiri" <?php echo ($bankAccount['bank_name'] ?? '') === 'Mandiri' ? 'selected' : ''; ?>>Mandiri</option>
                                        <option value="BNI" <?php echo ($bankAccount['bank_name'] ?? '') === 'BNI' ? 'selected' : ''; ?>>BNI</option>
                                        <option value="BRI" <?php echo ($bankAccount['bank_name'] ?? '') === 'BRI' ? 'selected' : ''; ?>>BRI</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="account_number">Nomor Rekening</label>
                                    <input type="text" id="account_number" name="account_number" value="<?php echo htmlspecialchars($bankAccount['account_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="account_name">Nama Pemilik Rekening</label>
                                <input type="text" id="account_name" name="account_name" value="<?php echo htmlspecialchars($bankAccount['account_name'] ?? ''); ?>">
                            </div>
                            <?php if (!empty($bankAccount['is_verified'])): ?>
                                <div class="verification-status verified">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Akun bank telah diverifikasi</span>
                                </div>
                            <?php elseif (!empty($bankAccount['bank_name'])): ?>
                                <div class="verification-status pending">
                                    <i class="fas fa-clock"></i>
                                    <span>Menunggu verifikasi admin</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="ewallet-fields" class="<?php echo ($settings['payout_method'] ?? 'bank_transfer') === 'bank_transfer' ? 'hidden' : ''; ?>">
                            <div class="form-group">
                                <label for="ewallet_type">Jenis E-Wallet</label>
                                <select id="ewallet_type" name="ewallet_type">
                                    <option value="gopay" <?php echo ($ewallet['type'] ?? '') === 'gopay' ? 'selected' : ''; ?>>GoPay</option>
                                    <option value="dana" <?php echo ($ewallet['type'] ?? '') === 'dana' ? 'selected' : ''; ?>>DANA</option>
                                    <option value="ovo" <?php echo ($ewallet['type'] ?? '') === 'ovo' ? 'selected' : ''; ?>>OVO</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ewallet_name">Nama di E-Wallet</label>
                                <input type="text" id="ewallet_name" name="ewallet_name" value="<?php echo htmlspecialchars($ewallet['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ewallet_phone">Nomor Telepon E-Wallet</label>
                                <input type="text" id="ewallet_phone" name="ewallet_phone" value="<?php echo htmlspecialchars($ewallet['phone_number'] ?? ''); ?>">
                            </div>
                            <?php if (!empty($ewallet['is_verified'])): ?>
                                <div class="verification-status verified">
                                    <i class="fas fa-check-circle"></i>
                                    <span>E-wallet telah diverifikasi</span>
                                </div>
                            <?php elseif (!empty($ewallet['type'])): ?>
                                <div class="verification-status pending">
                                    <i class="fas fa-clock"></i>
                                    <span>Menunggu verifikasi admin</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Pengaturan Pencairan</h3>
                        <div class="form-group">
                            <label for="payout_schedule">Jadwal Pencairan</label>
                            <select id="payout_schedule" name="payout_schedule">
                                <option value="weekly" <?php echo ($settings['payout_schedule'] ?? 'monthly') === 'weekly' ? 'selected' : ''; ?>>Mingguan (Setiap Senin)</option>
                                <option value="biweekly" <?php echo ($settings['payout_schedule'] ?? 'monthly') === 'biweekly' ? 'selected' : ''; ?>>Dua Minggu Sekali (Senin kedua)</option>
                                <option value="monthly" <?php echo ($settings['payout_schedule'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Bulanan (Tanggal 5 setiap bulan)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="minimum_payout">Minimum Pencairan</label>
                            <input type="number" id="minimum_payout" name="minimum_payout" min="10000" step="10000" value="<?php echo htmlspecialchars($settings['minimum_payout'] ?? '100000'); ?>">
                            <small>Minimum saldo yang bisa ditarik (dalam IDR)</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Informasi Pajak</h3>
                        <div class="form-group">
                            <label for="tax_information">NPWP (Opsional)</label>
                            <input type="text" id="tax_information" name="tax_information" value="<?php echo htmlspecialchars($settings['tax_information'] ?? ''); ?>">
                            <small>Jika Anda memiliki NPWP, mohon isi untuk keperluan pelaporan pajak</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_payout" class="btn-save">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="/MindCraft-Project/assets/js/mentor_dashboard.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tab switching
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });

            // Toggle between bank and e-wallet fields based on payout method
            const payoutMethod = document.getElementById('payout_method');
            const bankFields = document.getElementById('bank-account-fields');
            const ewalletFields = document.getElementById('ewallet-fields');

            if (payoutMethod && bankFields && ewalletFields) {
                payoutMethod.addEventListener('change', function() {
                    if (this.value === 'bank_transfer') {
                        bankFields.classList.remove('hidden');
                        ewalletFields.classList.add('hidden');
                    } else {
                        bankFields.classList.add('hidden');
                        ewalletFields.classList.remove('hidden');
                    }
                });
            }

            // Preview profile picture before upload
            const profilePictureInput = document.getElementById('profile_picture');
            const profilePicturePreview = document.querySelector('.profile-picture-preview img');
            const profilePicturePlaceholder = document.querySelector('.profile-picture-placeholder');

            if (profilePictureInput && (profilePicturePreview || profilePicturePlaceholder)) {
                profilePictureInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            if (profilePicturePreview) {
                                profilePicturePreview.src = event.target.result;
                            } else if (profilePicturePlaceholder) {
                                profilePicturePlaceholder.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Close alert buttons
            document.querySelectorAll('.btn-close').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.alert').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html>