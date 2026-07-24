<?php
header('Content-Type: application/json');
require_once '/../../config/Database.php';;

session_start();
$response = ['success' => false, 'message' => ''];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Anda harus login terlebih dahulu');
    }

    $userId = $_SESSION['user_id'];
    
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user's enrolled courses
    $stmt = $pdo->prepare("
        SELECT e.*, 
               c.title, 
               c.cover_image, 
               c.duration_hours,
               c.total_lessons,
               c.avg_rating
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.student_id = ? AND e.status = 'active'
    ");
    $stmt->execute([$userId]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['enrollments'] = $enrollments;
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>