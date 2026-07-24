<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id'])) {
    // Add debugging information
    error_log("Courses.php access denied. Session data: " . print_r($_SESSION, true));
    header("Location: ../landingpage/landingpage.php");
    exit();
}

require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
$db = $database->connect();

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'] ?? 'Mentee';

// Initialize filter variables from GET parameters
$categoryFilter = $_GET['category'] ?? 'all';
$levelFilter = $_GET['level'] ?? 'all';
$priceFilter = $_GET['price'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build query for published courses with filters
$query = "SELECT c.*, u.username as instructor_name FROM courses c 
          JOIN users u ON c.mentor_id = u.id 
          WHERE c.status = 'Published'";
$params = [];

// Apply filters
if ($categoryFilter !== 'all') {
    $query .= " AND c.category = ?";
    $params[] = $categoryFilter;
}

if ($levelFilter !== 'all') {
    $query .= " AND c.difficulty = ?";
    $params[] = ucfirst($levelFilter);
}

if ($priceFilter !== 'all') {
    $query .= " AND c.is_premium = ?";
    $params[] = ($priceFilter === 'premium') ? 1 : 0;
}

if (!empty($searchTerm)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.category LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY c.created_at DESC";

// Get filtered courses
$courses = [];
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal mengambil data kursus: " . $e->getMessage();
}

// Get enrolled courses for the user
$enrolledCourses = [];
if ($user_type === 'Mentee') {
    try {
        $stmt = $db->prepare("SELECT c.*, e.progress_percentage, e.last_accessed 
                             FROM enrollments e 
                             JOIN courses c ON e.course_id = c.id 
                             WHERE e.student_id = ? AND e.status = 'active'");
        $stmt->execute([$user_id]);
        $enrolledCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Gagal mengambil kursus yang diikuti: " . $e->getMessage();
    }
}

// Get stats data
try {
    // Get total published courses
    $stmt = $db->query("SELECT COUNT(*) as total_courses FROM courses WHERE status = 'Published'");
    $totalCourses = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'] ?? 0;
    
    // Get total active students (users with type Mentee)
    $stmt = $db->query("SELECT COUNT(*) as total_students FROM users WHERE user_type = 'Mentee'");
    $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'] ?? 0;
    
    // Get total active mentors (users with type Mentor)
    $stmt = $db->query("SELECT COUNT(*) as total_mentors FROM users WHERE user_type = 'Mentor'");
    $totalMentors = $stmt->fetch(PDO::FETCH_ASSOC)['total_mentors'] ?? 0;
    
    // Get unique categories for filter dropdown
    $stmt = $db->query("SELECT DISTINCT category FROM courses WHERE status = 'Published'");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Get popular categories with course counts
    $stmt = $db->query("SELECT * FROM course_categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $popularCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($popularCategories as &$category) {
        $stmt = $db->prepare("SELECT COUNT(*) as course_count FROM courses WHERE category = ? AND status = 'Published'");
        $stmt->execute([$category['name']]);
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $category['course_count'] = $countResult['course_count'] ?? 0;
    }
    unset($category);
} catch (PDOException $e) {
    // Fallback values if there's an error
    $totalCourses = 0;
    $totalStudents = 0;
    $totalMentors = 0;
    $categories = [];
    $popularCategories = [];
    error_log("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kursus - MindCraft Platform E-Lifestyle</title>
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentee-dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-50: #e6f7ff;
            --primary-100: #bae7ff;
            --primary-200: #91d5ff;
            --primary-300: #69c0ff;
            --primary-400: #40a9ff;
            --primary-500: #1890ff;
            --primary-600: #096dd9;
            --primary-700: #0050b3;
            --primary-800: #003a8c;
            --primary-900: #002766;
            
            --secondary-50: #f6ffed;
            --secondary-100: #d9f7be;
            --secondary-200: #b7eb8f;
            --secondary-300: #95de64;
            --secondary-400: #73d13d;
            --secondary-500: #52c41a;
            --secondary-600: #389e0d;
            --secondary-700: #237804;
            --secondary-800: #135200;
            --secondary-900: #092b00;
            
            --accent-500: #19A7CE;
            --accent-600: #0d8eb8;
            --accent-700: #0077a3;
            
            --success-500: #52c41a;
            --warning-500: #faad14;
            --error-500: #f5222d;
            
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --gray-500: #9e9e9e;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
            
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --shadow-xl: 0 12px 32px rgba(0, 0, 0, 0.15);
            
            --transition-fast: 0.15s;
            --transition-normal: 0.3s;
            --transition-slow: 0.5s;
        }

        /* Header Styles */
        .header {
            background-color: white;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 12px 0;
            transition: all var(--transition-normal);
        }

        .header.scrolled {
            box-shadow: var(--shadow-md);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: var(--container-max-width);
            margin: 0 auto;
            padding: 0 var(--container-padding);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo img {
            height: 42px;
            width: auto;
            transition: transform var(--transition-fast);
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            background: linear-gradient(135deg, var(--accent-500) 0%, var(--primary-600) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Navigation */
        .main-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 16px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: var(--rounded-lg);
            transition: all var(--transition-fast);
            position: relative;
        }

        .nav-link:hover {
            color: var(--accent-600);
            background-color: rgba(25, 167, 206, 0.1);
        }

        .nav-link.active {
            color: var(--accent-600);
            background-color: rgba(25, 167, 206, 0.1);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: var(--accent-500);
        }

        .nav-link i {
            font-size: 0.9rem;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-500) 0%, var(--primary-600) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            transition: transform var(--transition-fast);
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .username {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-800);
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: capitalize;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background-color: white;
            border-radius: var(--rounded-lg);
            box-shadow: var(--shadow-md);
            width: 220px;
            padding: 8px 0;
            display: none;
            z-index: 100;
            opacity: 0;
            transform: translateY(-10px);
            transition: all var(--transition-fast);
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: var(--gray-700);
            text-decoration: none;
            transition: all var(--transition-fast);
            font-size: 0.9rem;
        }

        .dropdown-item:hover {
            background-color: var(--gray-50);
            color: var(--accent-600);
            padding-left: 20px;
        }

        .dropdown-item i {
            width: 18px;
            text-align: center;
            color: var(--gray-500);
        }

        .dropdown-item:hover i {
            color: var(--accent-600);
        }

        .dropdown-divider {
            height: 1px;
            background-color: var(--gray-200);
            margin: 8px 0;
        }

        /* Menu Toggle Button (for mobile) */
        .btn-menu {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--gray-600);
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all var(--transition-fast);
        }

        .btn-menu:hover {
            background-color: rgba(25, 167, 206, 0.1);
            color: var(--accent-600);
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--accent-500) 0%, var(--primary-600) 100%);
            color: white;
            padding: 16px 0;
            text-align: center;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(25, 167, 206, 0.2);
        }

        .welcome-banner p {
            margin: 0;
        }

        .welcome-banner strong {
            font-weight: 600;
        }

        /* Page Hero Styles */
        .page-hero {
            background: linear-gradient(135deg, #19A7CE 0%, #146C94 100%);
            padding: var(--space-16) 0 var(--space-12);
            position: relative;
            overflow: hidden;
            color: white;
            text-align: center;
        }

        .page-hero + .course-filters {
    margin-top: 0;
}


        .page-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.05)" d="M0,0 L100,0 L100,100 L0,100 Z" /></svg>');
            background-size: cover;
            z-index: 1;
        }

        .page-hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 var(--space-4);
        }

        .page-hero h1 {
            font-size: 2.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: var(--space-4);
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-hero p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: var(--space-8);
            line-height: 1.6;
        }

        .stats-overview {
            display: flex;
            justify-content: center;
            gap: var(--space-8);
            margin-top: var(--space-8);
        }

        .stat-item {
            background-color: rgba(255,255,255,0.15);
            backdrop-filter: blur(5px);
            padding: var(--space-6) var(--space-4);
            border-radius: var(--rounded-xl);
            min-width: 160px;
            transition: all var(--transition-fast);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .stat-item:hover {
            transform: translateY(-4px);
            background-color: rgba(255,255,255,0.25);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: var(--space-2);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
        }

        /* Filter Section */
.course-filters {
    background-color: white;
    padding: var(--space-6) 0;
    position: sticky;
    top: 72px; /* Adjust this value based on your header height */
    z-index: 900;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all var(--transition-normal);
}

.course-filters.sticky {
    box-shadow: var(--shadow-md);
    padding: var(--space-4) 0;
}

        .filter-bar {
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
        }

        .search-wrapper {
            display: flex;
            gap: var(--space-3);
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 16px;
            color: var(--gray-400);
        }

        #course-search {
            width: 100%;
            padding: var(--space-3) var(--space-3) var(--space-3) 42px;
            border: 1px solid var(--gray-300);
            border-radius: var(--rounded-lg);
            font-size: 0.95rem;
            transition: all var(--transition-fast);
        }

        #course-search:focus {
            border-color: var(--accent-500);
            box-shadow: 0 0 0 3px rgba(25, 167, 206, 0.2);
            outline: none;
        }

        .search-btn {
            padding: var(--space-3) var(--space-6);
            border-radius: var(--rounded-lg);
            font-weight: 500;
        }

        .filter-options {
            display: flex;
            gap: var(--space-4);
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-1);
        }

        .filter-group label {
            font-size: 0.8rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .filter-group select {
            padding: var(--space-2) var(--space-3);
            border: 1px solid var(--gray-300);
            border-radius: var(--rounded-lg);
            background-color: white;
            min-width: 140px;
            color: var(--gray-700);
            font-size: 0.9rem;
            transition: all var(--transition-fast);
        }

        .filter-group select:focus {
            border-color: var(--accent-500);
            box-shadow: 0 0 0 3px rgba(25, 167, 206, 0.2);
            outline: none;
        }

        .reset-btn {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2) var(--space-4);
            margin-top: 18px;
        }

        /* Course Categories */
        .course-categories {
            padding: var(--space-8) 0;
            padding-top: var(--space-8);
            background-color: var(--gray-50);
        }

        .course-categories h2 {
            text-align: center;
            margin-bottom: var(--space-6);
            font-size: 1.75rem;
            color: var(--gray-900);
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: var(--space-4);
        }

        .category-card {
            background-color: white;
            border-radius: var(--rounded-xl);
            padding: var(--space-6);
            text-align: center;
            transition: all var(--transition-fast);
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-500);
        }

        .category-icon {
            font-size: 2rem;
            margin-bottom: var(--space-3);
            transition: transform var(--transition-fast);
        }

        .category-card:hover .category-icon {
            transform: scale(1.1);
        }

        .category-card h3 {
            font-size: 1.1rem;
            margin-bottom: var(--space-2);
            color: var(--gray-800);
        }

        .category-card p {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: var(--space-3);
            line-height: 1.5;
        }

        .course-count {
            font-size: 0.75rem;
            color: var(--accent-600);
            font-weight: 500;
            background-color: rgba(25, 167, 206, 0.1);
            padding: var(--space-1) var(--space-2);
            border-radius: 20px;
            display: inline-block;
        }

        /* Tabs */
        .tabs {
            background-color: white;
            border-bottom: 1px solid var(--gray-200);
        }

        .tab-links {
            display: flex;
            border-bottom: none;
        }

        .tab-link {
            padding: var(--space-3) var(--space-4);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            color: var(--gray-600);
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }

        .tab-link:hover {
            color: var(--accent-600);
        }

        .tab-link.active {
            color: var(--accent-600);
            border-bottom: 2px solid var(--accent-600);
        }

        .tab-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--accent-600);
        }

        /* Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--space-6);
            margin-top: var(--space-4);
        }

        .course-card {
            background-color: white;
            border-radius: var(--rounded-xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            border: 1px solid var(--gray-200);
            cursor: pointer;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-500);
        }

        .course-image {
            position: relative;
            height: 160px;
            overflow: hidden;
        }

        .course-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-slow);
        }

        .course-card:hover .course-image img {
            transform: scale(1.05);
        }

        .course-badges {
            position: absolute;
            top: var(--space-3);
            left: var(--space-3);
            display: flex;
            gap: var(--space-2);
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-level {
            background-color: rgba(140, 82, 255, 0.1);
            color: #8c52ff;
        }

        .badge-price.free {
            background-color: rgba(82, 196, 26, 0.1);
            color: var(--secondary-600);
        }

        .badge-price.premium {
            background-color: rgba(24, 144, 255, 0.1);
            color: var(--primary-600);
        }

        .course-content {
            padding: var(--space-4);
        }

        .course-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-2);
            line-height: 1.3;
        }

        .course-description {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: var(--space-3);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-meta {
            display: flex;
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-1);
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .meta-item i {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .course-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: var(--space-3);
            border-top: 1px solid var(--gray-200);
        }

        .instructor {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .instructor-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        .instructor-name {
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .course-price {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .course-price.free {
            color: var(--secondary-600);
        }

        .course-price.premium {
            color: var(--primary-600);
        }

        /* Enrolled Courses */
        .enrolled-courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-6);
            margin-top: var(--space-4);
        }

        .enrolled-course-card {
            background-color: white;
            border-radius: var(--rounded-xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            border: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }

        .enrolled-course-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-500);
        }

        .enrolled-course-card .course-image {
            height: 140px;
        }

        .course-info {
            padding: var(--space-4);
            flex: 1;
        }

        .course-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-3);
        }

        .progress-container {
            height: 6px;
            background-color: var(--gray-200);
            border-radius: 3px;
            margin-bottom: var(--space-2);
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-500), var(--primary-500));
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        .progress-text {
            font-size: 0.75rem;
            color: var(--gray-600);
            display: block;
            margin-bottom: var(--space-3);
        }

        .course-meta {
            display: flex;
            gap: var(--space-4);
            margin-top: var(--space-3);
        }

        .course-meta span {
            display: flex;
            align-items: center;
            gap: var(--space-1);
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .course-meta i {
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .course-actions {
            padding: 0 var(--space-4) var(--space-4);
        }

        .continue-btn {
            width: 100%;
            padding: var(--space-2);
            border-radius: var(--rounded-lg);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Empty States */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: var(--space-12) 0;
            text-align: center;
            grid-column: 1 / -1;
        }

        .empty-state img {
            max-width: 240px;
            margin-bottom: var(--space-6);
            opacity: 0.8;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--gray-800);
            margin-bottom: var(--space-2);
            font-weight: 600;
        }

        .empty-state p {
            color: var(--gray-600);
            margin-bottom: var(--space-4);
            max-width: 400px;
            line-height: 1.6;
        }

        /* ==================== */
        /* === FOOTER STYLES === */
        /* ==================== */
        .footer {
            background-color: var(--gray-900);
            color: var(--gray-400);
            padding: var(--space-6) 0;
            font-size: 0.85rem;
            border-top: 1px solid var(--gray-800);
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: var(--space-3);
        }

        .footer-logo img {
            height: 40px;
            margin-bottom: var(--space-2);
            opacity: 0.8;
            transition: opacity var(--transition-fast);
        }

        .footer-logo img:hover {
            opacity: 1;
        }

        .footer-logo p {
            color: var(--gray-500);
            margin-bottom: var(--space-2);
        }

        .footer-copyright p {
            color: var(--gray-600);
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .category-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .courses-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }

            .btn-menu {
                display: none;
                
            }

            
        }

        @media (max-width: 768px) {
            .header {
                padding: 8px 0;
            }
            
            
            
            .main-nav {
                display: none;
            }
            
            .btn-menu {
                display: flex;
                
            }
            
            .user-info {
                display: none;
            }
            
            .page-hero h1 {
                font-size: 2rem;
            }
            
            .stats-overview {
                flex-direction: column;
                align-items: center;
                gap: var(--space-4);
            }
            
            .stat-item {
                width: 100%;
                max-width: 200px;
            }
            
            .filter-options {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group select {
                width: 100%;
            }
            
            .reset-btn {
                margin-top: 0;
                align-self: flex-end;
            }
            
            .footer {
                padding: var(--space-4) 0;
            }
            
            .footer-logo img {
                height: 24px;
            }
            
            .footer-logo p,
            .footer-copyright p {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .notification-bell {
                display: none;
            }
            
            .page-hero h1 {
                font-size: 1.75rem;
            }
            
            .page-hero p {
                font-size: 1rem;
            }
            
            .search-wrapper {
                flex-direction: column;
            }
            
            .search-btn {
                width: 100%;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-500);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-600);
        }
    </style>
</head>

<body>
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

    <!-- Welcome message for logged in user -->
    <div class="welcome-banner">
        <div class="container">
            <p>Selamat datang, <strong><?php echo htmlspecialchars($username); ?></strong>! Temukan kursus yang tepat untuk Anda 🎓</p>
        </div>
    </div>

    <!-- Page Hero Section untuk Kursus -->
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-content">
                <h1>Jelajahi Kursus Digital</h1>
                <p>Temukan kursus berkualitas tinggi yang dirancang untuk mengembangkan keterampilan digital Anda. Dari pemula hingga mahir, kami menyediakan pembelajaran yang komprehensif.</p>
                <div class="stats-overview">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $totalCourses; ?>+</div>
                        <div class="stat-label">Kursus Tersedia</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $totalStudents; ?>+</div>
                        <div class="stat-label">Siswa Aktif</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $totalMentors; ?>+</div>
                        <div class="stat-label">Mentor Berpengalaman</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search and Filter Section -->
    <section class="course-filters">
        <div class="container">
            <form method="GET" action="kursus.php" class="filter-bar">
                <div class="search-wrapper">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="course-search" name="search" placeholder="Cari kursus yang Anda inginkan..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary search-btn" id="search-btn">Cari</button>
                </div>

                <div class="filter-options">
                    <div class="filter-group">
                        <label for="category-filter">Kategori:</label>
                        <select id="category-filter" name="category">
                            <option value="all" <?php echo ($categoryFilter === 'all') ? 'selected' : ''; ?>>Semua Kategori</option>
                            <?php
                            foreach ($categories as $category) {
                                $selected = ($categoryFilter === $category) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($category) . '" ' . $selected . '>' . htmlspecialchars($category) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="level-filter">Level:</label>
                        <select id="level-filter" name="level">
                            <option value="all" <?php echo ($levelFilter === 'all') ? 'selected' : ''; ?>>Semua Level</option>
                            <option value="pemula" <?php echo ($levelFilter === 'pemula') ? 'selected' : ''; ?>>Pemula</option>
                            <option value="menengah" <?php echo ($levelFilter === 'menengah') ? 'selected' : ''; ?>>Menengah</option>
                            <option value="mahir" <?php echo ($levelFilter === 'mahir') ? 'selected' : ''; ?>>Mahir</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="price-filter">Harga:</label>
                        <select id="price-filter" name="price">
                            <option value="all" <?php echo ($priceFilter === 'all') ? 'selected' : ''; ?>>Semua Harga</option>
                            <option value="free" <?php echo ($priceFilter === 'free') ? 'selected' : ''; ?>>Gratis</option>
                            <option value="premium" <?php echo ($priceFilter === 'premium') ? 'selected' : ''; ?>>Premium</option>
                        </select>
                    </div>

                    <a href="kursus.php" class="btn btn-outline reset-btn">
                        <i class="fas fa-undo"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </section>

        <section class="tabs">
        <div class="container">
            <div class="tab-links">
                <button class="tab-link active" data-tab="all-courses">Semua Kursus</button>
                <button class="tab-link" data-tab="my-courses">Kursus Saya</button>
            </div>
        </div>
    </section>

    <main>
        <div class="container">
            <section id="all-courses" class="tab-content active">
                <div class="section-header">
                    <h2>Jelajahi Kursus</h2>
                    <?php if ($categoryFilter !== 'all' || $levelFilter !== 'all' || $priceFilter !== 'all' || !empty($searchTerm)): ?>
                        <p>Menampilkan hasil filter: 
                            <?php 
                            $filtersApplied = [];
                            if ($categoryFilter !== 'all') $filtersApplied[] = "Kategori: " . htmlspecialchars($categoryFilter);
                            if ($levelFilter !== 'all') $filtersApplied[] = "Level: " . htmlspecialchars(ucfirst($levelFilter));
                            if ($priceFilter !== 'all') $filtersApplied[] = "Harga: " . htmlspecialchars(ucfirst($priceFilter));
                            if (!empty($searchTerm)) $filtersApplied[] = "Pencarian: " . htmlspecialchars($searchTerm);
                            echo implode(", ", $filtersApplied);
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="courses-grid" id="courses-container">
                    <?php if (empty($courses)): ?>
                        <div class="empty-state">
                            <img src="/MindCraft-Project/assets/img/empty-search.svg" alt="Tidak ada kursus">
                            <h3>Tidak ada kursus yang ditemukan</h3>
                            <p>Silakan coba dengan filter yang berbeda atau jelajahi kategori lain</p>
                            <a href="kursus.php" class="btn btn-primary">Reset Filter</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card" data-course-id="<?php echo $course['id']; ?>" data-category="<?php echo htmlspecialchars(strtolower($course['category'])); ?>">
                                <div class="course-image">
                                    <img src="<?php echo htmlspecialchars($course['cover_image'] ?? '/MindCraft-Project/assets/img/default-course.jpg'); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <div class="course-badges">
                                        <span class="badge badge-level"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                                        <span class="badge badge-price <?php echo ($course['is_premium']) ? 'premium' : 'free'; ?>">
                                            <?php echo ($course['is_premium']) ? 'Premium' : 'Gratis'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="course-content">
                                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                    <div class="course-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $course['duration_hours'] ?? 0; ?> Jam</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-film"></i>
                                            <span><?php echo $course['total_lessons'] ?? 0; ?> Pelajaran</span>
                                        </div>
                                    </div>
                                    <div class="course-footer">
                                        <div class="instructor">
                                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Instruktur" class="instructor-avatar">
                                            <span class="instructor-name"><?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                        </div>
                                        <div class="course-price <?php echo ($course['is_premium']) ? 'premium' : 'free'; ?>">
                                            <?php if ($course['is_premium']): ?>
                                                Rp <?php echo number_format($course['price'], 0, ',', '.'); ?>
                                            <?php else: ?>
                                                Gratis
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section id="my-courses" class="tab-content">
                <div class="section-header">
                    <h2>Kursus Saya</h2>
                </div>
                <div class="enrolled-courses" id="enrolled-courses-container">
                    <?php if (empty($enrolledCourses)): ?>
                        <div class="empty-state">
                            <img src="/MindCraft-Project/assets/img/empty-courses.svg" alt="Belum ada kursus">
                            <h3>Anda belum mengikuti kursus apapun</h3>
                            <p>Jelajahi katalog kursus kami dan mulai perjalanan belajar Anda</p>
                            <button class="btn btn-primary" onclick="document.querySelector('.tab-link[data-tab=\"all-courses\"]').click()">Jelajahi Kursus</button>
                        </div>
                    <?php else: ?>
                        <div class="enrolled-courses-grid">
                            <?php foreach ($enrolledCourses as $course): ?>
                                <div class="enrolled-course-card">
                                    <div class="course-image">
                                        <img src="<?php echo htmlspecialchars($course['cover_image'] ?? '/MindCraft-Project/assets/img/default-course.jpg'); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    </div>
                                    <div class="course-info">
                                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <div class="progress-container">
                                            <div class="progress-bar" style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $course['progress_percentage'] ?? 0; ?>% selesai</span>
                                        <div class="course-meta">
                                            <span><i class="fas fa-star"></i> <?php echo number_format($course['avg_rating'] ?? 0, 1); ?></span>
                                            <span><i class="fas fa-clock"></i> <?php echo $course['duration_hours'] ?? 0; ?> Jam</span>
                                        </div>
                                    </div>
                                    <div class="course-actions">
                                        <button class="btn btn-primary continue-btn" data-course-id="<?php echo $course['id']; ?>">Lanjutkan</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Course Categories Quick Access -->
    <section class="course-categories">
        <div class="container">
            <h2>Kategori Populer</h2>
            <div class="category-grid">
                <?php
                // Map icon names to Font Awesome icons
                $iconMap = [
                    '📚' => 'fas fa-book',
                    '🎨' => 'fas fa-paint-brush',
                    '💻' => 'fas fa-laptop-code',
                    '📈' => 'fas fa-chart-line',
                    '🎭' => 'fas fa-theater-masks',
                    '💪' => 'fas fa-dumbbell',
                    '🎵' => 'fas fa-music',
                    '📸' => 'fas fa-camera',
                    '🗣️' => 'fas fa-language',
                    '🌟' => 'fas fa-star'
                ];
                
                foreach ($popularCategories as $category) {
                    $icon = $iconMap[$category['icon']] ?? 'fas fa-star';
                    
                    echo '<a href="kursus.php?category=' . htmlspecialchars($category['name']) . '" class="category-card">
                        <div class="category-icon" style="color: ' . htmlspecialchars($category['color']) . '">
                            <i class="' . $icon . '"></i>
                        </div>
                        <h3>' . htmlspecialchars($category['name']) . '</h3>
                        <p>' . htmlspecialchars($category['description'] ?? 'Kursus tentang ' . $category['name']) . '</p>
                        <span class="course-count">' . $category['course_count'] . ' Kursus</span>
                    </a>';
                }
                ?>
            </div>
        </div>
    </section>



    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="../../assets/img/20250502_083014.png" alt="MindCraft Logo" width="40">
                    <p>Platform Belajar Digital Modern</p>
                </div>
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> MindCraft. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });

            // User menu toggle
            const userAvatar = document.getElementById('user-avatar');
            const dropdownMenu = document.getElementById('dropdown-menu');

            if (userAvatar) {
                userAvatar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (dropdownMenu) {
                    dropdownMenu.classList.remove('show');
                }
            });

            // Mobile menu toggle
            const menuToggle = document.getElementById('menu-toggle');
            const mainNav = document.querySelector('.main-nav');

            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', function() {
                    mainNav.classList.toggle('active');
                });
            }

            // Continue button functionality for enrolled courses
            document.querySelectorAll('.continue-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const courseId = this.getAttribute('data-course-id');
                    // Redirect to course player page
                    window.location.href = `course-player.php?id=${courseId}`;
                });
            });

            window.addEventListener('scroll', function() {
    const filters = document.querySelector('.course-filters');
    const hero = document.querySelector('.page-hero');
    const heroBottom = hero.offsetTop + hero.offsetHeight;
    
    if (window.pageYOffset > heroBottom) {
        filters.classList.add('sticky');
    } else {
        filters.classList.remove('sticky');
    }
});

            // Course card click handler
            document.querySelectorAll('.course-card').forEach(card => {
                card.addEventListener('click', function() {
                    const courseId = this.getAttribute('data-course-id');
                    window.location.href = `course-detail.php?id=${courseId}`;
                });
            });

            // Header scroll effect
            window.addEventListener('scroll', function() {
                const header = document.getElementById('main-header');
                if (window.scrollY > 10) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
        });
    </script>
</body>
</html>