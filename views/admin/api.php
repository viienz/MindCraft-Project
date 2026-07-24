<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_connect.php';

$db = new Database();
$conn = $db->connect();

$requestMethod = $_SERVER["REQUEST_METHOD"];
$entity = isset($_GET['entity']) ? $_GET['entity'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : null;
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

switch ($entity) {
    case 'users':
        handleUsers($conn, $requestMethod, $id, $input);
        break;
        
    case 'courses':
        handleCourses($conn, $requestMethod, $id, $input);
        break;
        
    case 'categories':
        handleCategories($conn, $requestMethod, $id, $input);
        break;
        
    case 'enrollments':
        handleEnrollments($conn, $requestMethod, $id, $input);
        break;
        
    case 'stats':
        handleStats($conn);
        break;
        
    case 'chart-data':
        handleChartData($conn, $period);
        break;

    case 'earnings':
        handleEarnings($conn, $requestMethod, $id, $input);
        break;
    
    case 'mentor-settings':
        handleMentorSettings($conn, $requestMethod, $id, $input);
        break;
    
    case 'course-progress':
        handleCourseProgress($conn, $requestMethod, $id, $input);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        break;
}

function handleEarnings($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT e.*, u.username as mentor_name, c.title as course_title, s.username as student_name 
                    FROM earnings e 
                    LEFT JOIN users u ON e.mentor_id = u.id 
                    LEFT JOIN courses c ON e.course_id = c.id 
                    LEFT JOIN users s ON e.student_id = s.id 
                    WHERE e.id = ?");
                $stmt->execute([$id]);
                $earning = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($earning) {
                    echo json_encode(['success' => true, 'data' => $earning]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Earning not found']);
                }
            } else {
                $stmt = $conn->query("SELECT e.*, u.username as mentor_name, c.title as course_title, s.username as student_name 
                    FROM earnings e 
                    LEFT JOIN users u ON e.mentor_id = u.id 
                    LEFT JOIN courses c ON e.course_id = c.id 
                    LEFT JOIN users s ON e.student_id = s.id 
                    ORDER BY e.created_at DESC");
                $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $earnings]);
            }
            break;
            
        case 'PUT':
            if (!$id || !isset($input['status']) || !isset($input['payout_status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Status and payout_status are required']);
                return;
            }
            
            try {
                $stmt = $conn->prepare("UPDATE earnings SET status = ?, payout_status = ? WHERE id = ?");
                $stmt->execute([$input['status'], $input['payout_status'], $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Earning updated successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Earning not found or no changes made']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleMentorSettings($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT ms.*, u.username as mentor_name 
                    FROM mentor_settings ms 
                    JOIN users u ON ms.mentor_id = u.id 
                    WHERE ms.id = ?");
                $stmt->execute([$id]);
                $setting = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($setting) {
                    echo json_encode(['success' => true, 'data' => $setting]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Mentor setting not found']);
                }
            } else {
                $stmt = $conn->query("SELECT ms.*, u.username as mentor_name 
                    FROM mentor_settings ms 
                    JOIN users u ON ms.mentor_id = u.id 
                    ORDER BY ms.mentor_id");
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $settings]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleCourseProgress($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT cp.*, u.username as student_name, c.title as course_title, l.title as lesson_title 
                    FROM course_progress cp 
                    JOIN users u ON cp.student_id = u.id 
                    JOIN courses c ON cp.course_id = c.id 
                    LEFT JOIN course_lessons l ON cp.lesson_id = l.id 
                    WHERE cp.id = ?");
                $stmt->execute([$id]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($progress) {
                    echo json_encode(['success' => true, 'data' => $progress]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Course progress not found']);
                }
            } else {
                $stmt = $conn->query("SELECT cp.*, u.username as student_name, c.title as course_title, l.title as lesson_title 
                    FROM course_progress cp 
                    JOIN users u ON cp.student_id = u.id 
                    JOIN courses c ON cp.course_id = c.id 
                    LEFT JOIN course_lessons l ON cp.lesson_id = l.id 
                    ORDER BY cp.last_accessed DESC");
                $progressItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $progressItems]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleUsers($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT id, username, email, user_type, gender, created_at FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } else {
                $stmt = $conn->query("SELECT id, username, email, user_type, gender, created_at FROM users ORDER BY created_at DESC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $users]);
            }
            break;
            
        case 'POST':
            if (!validateUserData($input, true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                return;
            }
            
            try {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $checkStmt->execute([$input['username'], $input['email']]);
                if ($checkStmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                    return;
                }
                
                $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type, gender) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['username'],
                    $input['email'],
                    $hashedPassword,
                    $input['user_type'],
                    $input['gender']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'User created successfully',
                    'id' => $conn->lastInsertId()
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'PUT':
            if (!$id || !validateUserData($input, false)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                return;
            }
            
            try {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
                $checkStmt->execute([$id]);
                if (!$checkStmt->fetch()) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    return;
                }
                
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $checkStmt->execute([$input['username'], $input['email'], $id]);
                if ($checkStmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                    return;
                }
                
                if (!empty($input['password'])) {
                    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, user_type = ?, gender = ? WHERE id = ?");
                    $stmt->execute([
                        $input['username'],
                        $input['email'],
                        $hashedPassword,
                        $input['user_type'],
                        $input['gender'],
                        $id
                    ]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ?, gender = ? WHERE id = ?");
                    $stmt->execute([
                        $input['username'],
                        $input['email'],
                        $input['user_type'],
                        $input['gender'],
                        $id
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                return;
            }
            
            try {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleCourses($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT c.*, u.username as mentor_name FROM courses c JOIN users u ON c.mentor_id = u.id WHERE c.id = ?");
                $stmt->execute([$id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($course) {
                    echo json_encode(['success' => true, 'data' => $course]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Course not found']);
                }
            } else {
                $stmt = $conn->query("SELECT c.*, u.username as mentor_name FROM courses c JOIN users u ON c.mentor_id = u.id ORDER BY c.created_at DESC");
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $courses]);
            }
            break;
            
        case 'POST':
            if (!validateCourseData($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }
            
            try {
                $slug = createSlug($input['title']);
                $stmt = $conn->prepare("INSERT INTO courses (
                    mentor_id, title, slug, category, difficulty, description, cover_image, 
                    price, is_premium, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $input['mentor_id'],
                    $input['title'],
                    $slug,
                    $input['category'],
                    $input['difficulty'],
                    $input['description'],
                    $input['cover_image'],
                    $input['price'],
                    $input['is_premium'],
                    $input['status']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Course created successfully',
                    'id' => $conn->lastInsertId()
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'PUT':
            if (!$id || !validateCourseData($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }
            
            try {
                $slug = createSlug($input['title']);
                $stmt = $conn->prepare("UPDATE courses SET 
                    mentor_id = ?, title = ?, slug = ?, category = ?, difficulty = ?, 
                    description = ?, cover_image = ?, price = ?, is_premium = ?, status = ?
                    WHERE id = ?");
                    
                $stmt->execute([
                    $input['mentor_id'],
                    $input['title'],
                    $slug,
                    $input['category'],
                    $input['difficulty'],
                    $input['description'],
                    $input['cover_image'],
                    $input['price'],
                    $input['is_premium'],
                    $input['status'],
                    $id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Course updated successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Course not found or no changes made']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            try {
                $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Course not found']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleCategories($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT * FROM course_categories WHERE id = ?");
                $stmt->execute([$id]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category) {
                    echo json_encode(['success' => true, 'data' => $category]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Category not found']);
                }
            } else {
                $stmt = $conn->query("SELECT * FROM course_categories ORDER BY sort_order ASC");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;
            
        case 'POST':
            if (!validateCategoryData($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }
            
            try {
                $slug = createSlug($input['name']);
                $stmt = $conn->prepare("INSERT INTO course_categories (
                    name, slug, description, icon, color, is_active, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $input['name'],
                    $slug,
                    $input['description'],
                    $input['icon'],
                    $input['color'],
                    $input['is_active'],
                    $input['sort_order'] ?? 0
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Category created successfully',
                    'id' => $conn->lastInsertId()
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'PUT':
            if (!$id || !validateCategoryData($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }
            
            try {
                $slug = createSlug($input['name']);
                $stmt = $conn->prepare("UPDATE course_categories SET 
                    name = ?, slug = ?, description = ?, icon = ?, color = ?, 
                    is_active = ?, sort_order = ?
                    WHERE id = ?");
                    
                $stmt->execute([
                    $input['name'],
                    $slug,
                    $input['description'],
                    $input['icon'],
                    $input['color'],
                    $input['is_active'],
                    $input['sort_order'] ?? 0,
                    $id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Category not found or no changes made']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            try {
                // Check if category is used in any courses
                $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE category = (SELECT name FROM course_categories WHERE id = ?)");
                $checkStmt->execute([$id]);
                $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Cannot delete category that is in use by courses']);
                    return;
                }
                
                $stmt = $conn->prepare("DELETE FROM course_categories WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Category not found']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleEnrollments($conn, $method, $id, $input) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $conn->prepare("SELECT e.*, u.username as student_name, c.title as course_title 
                    FROM enrollments e 
                    JOIN users u ON e.student_id = u.id 
                    JOIN courses c ON e.course_id = c.id 
                    WHERE e.id = ?");
                $stmt->execute([$id]);
                $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($enrollment) {
                    echo json_encode(['success' => true, 'data' => $enrollment]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
                }
            } else {
                $stmt = $conn->query("SELECT e.*, u.username as student_name, c.title as course_title 
                    FROM enrollments e 
                    JOIN users u ON e.student_id = u.id 
                    JOIN courses c ON e.course_id = c.id 
                    ORDER BY e.enrollment_date DESC");
                $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $enrollments]);
            }
            break;
            
        case 'PUT':
            if (!$id || !isset($input['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }
            
            try {
                $stmt = $conn->prepare("UPDATE enrollments SET status = ? WHERE id = ?");
                $stmt->execute([$input['status'], $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Enrollment updated successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Enrollment not found or no changes made']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID is required']);
                break;
            }
            
            try {
                $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Enrollment deleted successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function validateUserData($data, $requirePassword = true) {
    $valid = isset($data['username']) && strlen($data['username']) >= 3 && 
             isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL) && 
             isset($data['user_type']) && in_array($data['user_type'], ['Mentee', 'Mentor']) && 
             isset($data['gender']) && in_array($data['gender'], ['Laki-laki', 'Perempuan']);
             
    if ($requirePassword) {
        $valid = $valid && isset($data['password']) && strlen($data['password']) >= 6;
    }
    
    return $valid;
}

function validateCourseData($data) {
    $validStatuses = ['Draft', 'Published', 'Archived'];
    $validDifficulties = ['Pemula', 'Menengah', 'Mahir'];
    
    return isset($data['mentor_id']) && is_numeric($data['mentor_id']) &&
           isset($data['title']) && strlen($data['title']) >= 5 && 
           isset($data['category']) && 
           isset($data['difficulty']) && in_array($data['difficulty'], $validDifficulties) && 
           isset($data['description']) && 
           isset($data['status']) && in_array($data['status'], $validStatuses) &&
           isset($data['price']) && is_numeric($data['price']) &&
           isset($data['is_premium']) && ($data['is_premium'] == 0 || $data['is_premium'] == 1);
}

function validateCategoryData($data) {
    return isset($data['name']) && strlen($data['name']) >= 3 && 
           isset($data['color']) && preg_match('/^#[a-f0-9]{6}$/i', $data['color']) &&
           isset($data['is_active']) && ($data['is_active'] == 0 || $data['is_active'] == 1);
}

function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

function handleStats($conn) {
    try {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM users WHERE user_type = 'Mentee') as total_mentees,
                    (SELECT COUNT(*) FROM users WHERE user_type = 'Mentor') as total_mentors,
                    (SELECT COUNT(*) FROM courses) as total_courses,
                    (SELECT COUNT(*) FROM enrollments) as total_enrollments,
                    (SELECT COUNT(*) FROM course_categories) as total_categories";
        
        $stmt = $conn->query($query);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $stats,
            'timestamp' => time()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function handleChartData($conn, $period = 'daily') {
    try {
        $data = [];
        
        // User distribution
        $stmt = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
        $data['user_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // User growth based on period
        switch ($period) {
            case 'daily':
                // Last 30 days
                $stmt = $conn->query("
                    SELECT 
                        DATE_FORMAT(created_at, '%e %b') as date,
                        SUM(CASE WHEN user_type = 'Mentee' THEN 1 ELSE 0 END) as mentees,
                        SUM(CASE WHEN user_type = 'Mentor' THEN 1 ELSE 0 END) as mentors
                    FROM users
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at), DATE_FORMAT(created_at, '%e %b')
                    ORDER BY DATE(created_at)
                ");
                break;
                
            case 'monthly':
                // Last 12 months
                $stmt = $conn->query("
                    SELECT 
                        DATE_FORMAT(created_at, '%b %Y') as date,
                        SUM(CASE WHEN user_type = 'Mentee' THEN 1 ELSE 0 END) as mentees,
                        SUM(CASE WHEN user_type = 'Mentor' THEN 1 ELSE 0 END) as mentors
                    FROM users
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY MONTH(created_at), YEAR(created_at), DATE_FORMAT(created_at, '%b %Y')
                    ORDER BY YEAR(created_at), MONTH(created_at)
                ");
                break;
                
            case 'yearly':
                // Last 5 years
                $stmt = $conn->query("
                    SELECT 
                        DATE_FORMAT(created_at, '%Y') as date,
                        SUM(CASE WHEN user_type = 'Mentee' THEN 1 ELSE 0 END) as mentees,
                        SUM(CASE WHEN user_type = 'Mentor' THEN 1 ELSE 0 END) as mentors
                    FROM users
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
                    GROUP BY YEAR(created_at), DATE_FORMAT(created_at, '%Y')
                    ORDER BY YEAR(created_at)
                ");
                break;
        }
        
        $data['user_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data['growth_period'] = $period;
        
        // Course status
        $stmt = $conn->query("SELECT status, COUNT(*) as count FROM courses GROUP BY status");
        $data['course_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Course category
        $stmt = $conn->query("SELECT category, COUNT(*) as count FROM courses GROUP BY category");
        $data['course_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}