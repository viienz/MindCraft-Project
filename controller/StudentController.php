<?php
// Pastikan file model disertakan
require_once __DIR__ . '/../model/CourseModel.php';
require_once __DIR__ . '/../model/UserModel.php'; // Mungkin diperlukan nanti

class StudentController {
    private $courseModel;
    private $userModel;

    public function __construct($db) {
        // Menggunakan standar PDO yang sama
        $this->courseModel = new CourseModel($db);
        $this->userModel = new UserModel($db);
    }

    public function dashboard() {
        // Memastikan sesi sudah berjalan dan user_id ada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: /MindCraft-Project/views/landingpage/login.php');
            exit();
        }

        $userId = $_SESSION['user_id'];
        
        // Memanggil method model yang sudah berbasis PDO
        $data['myCourses'] = $this->courseModel->getCoursesByMentee($userId);
        $data['recommendations'] = $this->courseModel->getRandomCourses(5); // Mengambil 5 kursus acak

        // Mengirim data ke view
        $this->loadView('mentee/dashboard', $data);
    }

    public function katalog() {
        $data['courses'] = $this->courseModel->getPublishedCourses(100); // Ambil semua kursus
        $this->loadView('mentee/katalog', $data);
    }

    public function kursusSaya() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: /MindCraft-Project/views/landingpage/login.php');
            exit();
        }
        $userId = $_SESSION['user_id'];
        
        $data['myCourses'] = $this->courseModel->getCoursesByMentee($userId);
        $this->loadView('mentee/kursus_saya', $data);
    }

    public function detailKursus($id) {
        $courseId = filter_var($id, FILTER_VALIDATE_INT);
        if (!$courseId) {
            // Tampilkan halaman tidak ditemukan jika ID tidak valid
            http_response_code(404);
            echo "Kursus tidak ditemukan.";
            exit();
        }
        
        $data['course'] = $this->courseModel->getCourseById($courseId);
        $data['modules'] = $this->courseModel->getModulesByCourseId($courseId);
        
        // Cek apakah kursus ditemukan
        if (!$data['course']) {
            http_response_code(404);
            echo "Kursus tidak ditemukan.";
            exit();
        }

        $this->loadView('mentee/detail-kursus', $data);
    }
    
    public function enroll($courseId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: /MindCraft-Project/views/landingpage/login.php');
            exit();
        }
        $studentId = $_SESSION['user_id'];

        // Cek apakah sudah terdaftar
        if (!$this->courseModel->isAlreadyEnrolled($studentId, $courseId)) {
            $this->courseModel->enrollStudent($studentId, $courseId);
        }

        // Redirect kembali ke halaman detail kursus
        header("Location: /MindCraft-Project/index.php?page=detailKursus&id=" . $courseId);
        exit();
    }

    /**
     * Helper function untuk memuat view dan mengirimkan data
     */
    private function loadView($view, $data = []) {
        // Mengekstrak array $data menjadi variabel individual
        extract($data);
        
        // Memuat file view
        // Path disesuaikan agar bekerja dengan struktur direktori
        require_once __DIR__ . "/../views/$view.php";
    }
}