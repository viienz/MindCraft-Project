<?php
header('Content-Type: application/json');
require_once '/../../config/Database.php';

$response = ['success' => false, 'message' => ''];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get course ID if specified
    $courseId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    // Get filter parameters
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    $price = isset($_GET['price']) ? $_GET['price'] : null;

    if ($courseId) {
        // Get single course details
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) AS avg_rating,
                   (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) AS total_enrollments
            FROM courses c
            WHERE c.id = ? AND c.status = 'Published'
        ");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($course) {
            $response['success'] = true;
            $response['course'] = $course;
        } else {
            $response['message'] = 'Kursus tidak ditemukan';
        }
    } else {
        // Get all courses with filters
        $sql = "
            SELECT c.*, 
                   (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) AS avg_rating,
                   (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) AS total_enrollments
            FROM courses c
            WHERE c.status = 'Published'
        ";
        
        $params = [];
        
        // Apply filters
        if ($category && $category !== 'all') {
            $sql .= " AND LOWER(c.category) = ?";
            $params[] = strtolower($category);
        }
        
        if ($level && $level !== 'all') {
            $levelMap = [
                'beginner' => 'Pemula',
                'intermediate' => 'Menengah',
                'advanced' => 'Mahir'
            ];
            $sql .= " AND c.difficulty = ?";
            $params[] = $levelMap[$level] ?? 'Pemula';
        }
        
        if ($price && $price !== 'all') {
            if ($price === 'free') {
                $sql .= " AND c.is_premium = 0";
            } elseif ($price === 'premium') {
                $sql .= " AND c.is_premium = 1";
            }
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['courses'] = $courses;
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>