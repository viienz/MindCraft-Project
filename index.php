<?php
// /index.php (Versi Perbaikan)

// Error reporting untuk development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mulai sesi dengan aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies utama
require_once __DIR__ . '/config/Database.php';

// --- ROUTER SEDERHANA ---
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/MindCraft-Project/';

// Bersihkan path dari base path dan query string
$path = str_replace($base_path, '', $request_uri);
$path = strtok($path, '?');
$path = trim($path, '/');

// Arahkan ke halaman landing jika path kosong atau halaman utama
if ($path === '' || $path === 'index.php') {
    // Jika sudah login, arahkan ke dashboard masing-masing
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_type'] === 'Mentor') {
            header('Location: ' . $base_path . 'views/mentor/dashboard.php');
            exit();
        } elseif ($_SESSION['user_type'] === 'Mentee') {
            header('Location: ' . $base_path . 'views/mentee/kursus.php');
            exit();
        }
    }
    // Jika belum login, tampilkan landing page
    include __DIR__ . '/views/landingpage/landingpage.php';
    exit();
}

// Menangani rute berdasarkan bagian pertama dari path
$parts = explode('/', $path);
$main_route = $parts[0] ?? '';

switch ($main_route) {
    // Rute untuk Form Actions (Create/Update)
    case 'controller':
        $controller_name = $parts[1] ?? '';
        $controller_path = __DIR__ . '/controller/' . $controller_name . '.php';
        if (file_exists($controller_path)) {
            require_once $controller_path;
        } else {
            http_response_code(404);
            echo "Controller not found.";
        }
        break;

    // Rute untuk Halaman Mentor
    case 'views':
        if (isset($parts[1]) && $parts[1] === 'mentor') {
            $page = $parts[2] ?? 'dashboard.php';
            $page_path = __DIR__ . '/views/mentor/' . $page;

            if (file_exists($page_path)) {
                include $page_path;
            } else {
                http_response_code(404);
                include __DIR__ . '/views/mentor/404.php'; // Halaman 404 khusus mentor
            }
        }
        // Anda bisa tambahkan logika untuk /views/mentee atau /views/admin di sini
        break;

    // Rute untuk Login/Logout/Register
    case 'auth':
        $auth_page = $parts[1] ?? 'login.php';
        $auth_path = __DIR__ . '/views/landingpage/' . $auth_page;
        if (file_exists($auth_path)) {
            include $auth_path;
        } else {
            http_response_code(404);
            echo "Page not found.";
        }
        break;

    // Jika tidak ada rute yang cocok
    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>Halaman yang Anda cari tidak ditemukan.</p>";
        break;
}