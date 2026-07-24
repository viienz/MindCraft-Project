<?php
require 'config.php';

if (ob_get_length()) ob_clean();

header('Content-Type: application/json');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
   
    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }
    
    try {
     
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Email not registered']);
            exit;
        }
        
        if (password_verify($data['password'], $user['password'])) {
        
            session_regenerate_id(true);
    
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['gender'] = $user['gender'];
            
            if ($user['user_type'] === 'Mentor') {
                $_SESSION['mentor_id'] = $user['id'];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'user_type' => $user['user_type'],
                    'gender' => $user['gender']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method. Only POST requests are accepted.',
        'received_method' => $_SERVER['REQUEST_METHOD']
    ]);
}
?>