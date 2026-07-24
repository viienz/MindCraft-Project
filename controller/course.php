<?php
// Menggunakan pengecekan sesi yang aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/CourseModel.php';

class CourseController {
    private $courseModel;

    public function __construct(Database $database) {
        $db = $database->connect();
        $this->courseModel = new CourseModel($db);
    }

    /**
     * Menangani pembuatan kursus baru.
     */
    public function create() {
        // 1. Pastikan mentor sudah login
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Mentor') {
            header("Location: /MindCraft-Project/views/landingpage/login.php?error=access_denied");
            exit();
        }

        // 2. Pastikan request adalah POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            echo "Metode request tidak valid.";
            exit();
        }

        // 3. Ambil data dari form
        $mentor_id = $_SESSION['user_id'];
        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? '';
        $difficulty = $_POST['difficulty'] ?? 'Pemula';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $status = isset($_POST['publish']) ? 'Published' : 'Draft';

        // 4. Validasi data
        if (empty($title) || empty($category) || empty($description)) {
            header("Location: /MindCraft-Project/views/mentor/buat-kursus-baru.php?error=empty_fields");
            exit();
        }
        
        // 5. Proses Upload Gambar
        $cover_image_path = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/course-covers/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = 'cover_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                $cover_image_path = '/MindCraft-Project/uploads/course-covers/' . $unique_filename;
            } else {
                 header("Location: /MindCraft-Project/views/mentor/buat-kursus-baru.php?error=upload_failed");
                 exit();
            }
        } else {
             header("Location: /MindCraft-Project/views/mentor/buat-kursus-baru.php?error=no_image");
             exit();
        }
        
        // 6. Siapkan data untuk disimpan
        $courseData = [
            'mentor_id' => $mentor_id,
            'title' => $title,
            'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))),
            'category' => $category,
            'difficulty' => $difficulty,
            'description' => $description,
            'cover_image' => $cover_image_path,
            'price' => $price,
            'status' => $status
        ];

        // 7. Panggil model untuk menyimpan data
        if ($this->courseModel->createCourse($courseData)) {
            header("Location: /MindCraft-Project/views/mentor/kursus-saya.php?success=course_created");
            exit();
        } else {
            header("Location: /MindCraft-Project/views/mentor/buat-kursus-baru.php?error=creation_failed");
            exit();
        }
    }

    /**
     * Menangani pembaruan (update) kursus yang sudah ada.
     */
    public function update() {
        // 1. Validasi dasar
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            header("Location: /MindCraft-Project/views/landingpage/login.php");
            exit();
        }

        // 2. Ambil data dari form
        $course_id = $_POST['course_id'] ?? 0;
        $mentor_id = $_SESSION['user_id'];
        
        // 3. Security Check: Pastikan mentor adalah pemilik kursus
        $existingCourse = $this->courseModel->getCourseById($course_id);
        if (!$existingCourse || $existingCourse['mentor_id'] != $mentor_id) {
            header("Location: /MindCraft-Project/views/mentor/kursus-saya.php?error=unauthorized");
            exit();
        }

        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? '';
        $difficulty = $_POST['difficulty'] ?? 'Pemula';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $status = isset($_POST['publish']) ? 'Published' : 'Draft';

        // 4. Proses Upload Gambar (hanya jika ada gambar baru)
        $cover_image_path = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/course-covers/';
            // Hapus gambar lama jika ada
            $old_image_path = __DIR__ . '/..' . $existingCourse['cover_image'];
            if ($existingCourse['cover_image'] && file_exists($old_image_path)) {
                @unlink($old_image_path);
            }
            // Upload yang baru
            $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = 'cover_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                $cover_image_path = '/MindCraft-Project/uploads/course-covers/' . $unique_filename;
            }
        }
        
        // 5. Siapkan data untuk di-update
        $courseData = [
            'id' => $course_id,
            'mentor_id' => $mentor_id,
            'title' => $title,
            'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))),
            'category' => $category,
            'difficulty' => $difficulty,
            'description' => $description,
            'price' => $price,
            'status' => $status,
            'cover_image' => $cover_image_path // Akan null jika tidak ada gambar baru, dan tidak akan diupdate oleh model
        ];

        // 6. Panggil model untuk update
        if ($this->courseModel->updateCourse($courseData)) {
            header("Location: /MindCraft-Project/views/mentor/kursus-saya.php?success=course_updated");
        } else {
            header("Location: /MindCraft-Project/views/mentor/edit-course.php?id=$course_id&error=update_failed");
        }
        exit();
    }
}


// --- ROUTING SEDERHANA UNTUK MENANGANI ACTION ---
// Cek apakah ada parameter 'action' di URL
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $database = new Database();
    $controller = new CourseController($database);

    if ($action === 'create') {
        $controller->create();
    } elseif ($action === 'update') {
        $controller->update();
    }
}