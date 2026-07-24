<?php
session_start();
require_once __DIR__ . '/../../config/Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized");
}

// Validate input
if (!isset($_POST['student_id'], $_POST['course_id'], $_POST['lesson_id'], $_POST['progress'])) {
    http_response_code(400);
    die("Bad Request");
}

$student_id = intval($_POST['student_id']);
$course_id = intval($_POST['course_id']);
$lesson_id = intval($_POST['lesson_id']);
$progress = min(100, max(0, intval($_POST['progress'])));

// Verify the student is enrolled
$database = new Database();
$db = $database->connect();

$stmt = $db->prepare("SELECT * FROM enrollments 
                     WHERE student_id = ? AND course_id = ? 
                     AND status = 'active'");
$stmt->execute([$student_id, $course_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    http_response_code(403);
    die("Forbidden");
}

// Save progress
$stmt = $db->prepare("INSERT INTO course_progress 
                    (student_id, course_id, lesson_id, progress, last_accessed) 
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    progress = GREATEST(progress, ?), 
                    last_accessed = NOW()");
$stmt->execute([$student_id, $course_id, $lesson_id, $progress, $progress]);

http_response_code(200);
echo "Progress saved";
?>