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

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $courseId = isset($input['course_id']) ? intval($input['course_id']) : null;
    
    if (!$courseId) {
        throw new Exception('ID kursus tidak valid');
    }

    $userId = $_SESSION['user_id'];
    
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if course exists and is published
    $stmt = $pdo->prepare("SELECT id, is_premium, price FROM courses WHERE id = ? AND status = 'Published'");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        throw new Exception('Kursus tidak ditemukan atau tidak tersedia');
    }

    // Check if user is already enrolled
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    
    if ($stmt->fetch()) {
        throw new Exception('Anda sudah terdaftar dalam kursus ini');
    }

    // Enroll the user
    $stmt = $pdo->prepare("
        INSERT INTO enrollments 
        (student_id, course_id, enrollment_date, progress_percentage, status, payment_status, payment_amount)
        VALUES (?, ?, NOW(), 0, 'active', ?, ?)
    ");
    
    $paymentStatus = $course['is_premium'] ? 'pending' : 'free';
    $paymentAmount = $course['is_premium'] ? $course['price'] : 0;
    
    $stmt->execute([$userId, $courseId, $paymentStatus, $paymentAmount]);
    
    $response['success'] = true;
    $response['message'] = 'Berhasil mendaftar kursus';
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>