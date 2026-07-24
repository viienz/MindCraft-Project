<?php
class CourseModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ==================================================================
    // FUNGSI UNTUK MENTOR
    // ==================================================================

    /**
     * [FIXED] Menghitung jumlah total kursus milik seorang mentor.
     * Fungsi ini yang hilang sebelumnya.
     */
    public function getCourseCountByMentorId($mentor_id) {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM courses WHERE mentor_id = :mentor_id");
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getCoursesByMentorId($mentor_id) {
        $stmt = $this->db->prepare("
            SELECT 
                c.id as course_id, 
                c.title as course_name, 
                c.category, 
                c.status,
                c.difficulty,
                c.price,
                c.cover_image as thumbnail, 
                c.total_lessons,
                c.avg_rating,
                COUNT(e.student_id) as student_count
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.mentor_id = :mentor_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCourse($data) {
        $sql = "INSERT INTO courses (mentor_id, title, slug, category, difficulty, description, cover_image, price, status, created_at, updated_at) 
                VALUES (:mentor_id, :title, :slug, :category, :difficulty, :description, :cover_image, :price, :status, NOW(), NOW())";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':mentor_id', $data['mentor_id'], PDO::PARAM_INT);
            $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindValue(':slug', $data['slug'], PDO::PARAM_STR);
            $stmt->bindValue(':category', $data['category'], PDO::PARAM_STR);
            $stmt->bindValue(':difficulty', $data['difficulty'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindValue(':cover_image', $data['cover_image'], PDO::PARAM_STR);
            $stmt->bindValue(':price', $data['price']);
            $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateCourse($data) {
        $sql_base = "UPDATE courses SET title = :title, slug = :slug, category = :category, difficulty = :difficulty, description = :description, price = :price, status = :status";
        $sql_image = ", cover_image = :cover_image";
        $sql_where = " WHERE id = :id AND mentor_id = :mentor_id";
        
        $sql = $sql_base . (!empty($data['cover_image']) ? $sql_image : '') . $sql_where;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindValue(':mentor_id', $data['mentor_id'], PDO::PARAM_INT);
            $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindValue(':slug', $data['slug'], PDO::PARAM_STR);
            $stmt->bindValue(':category', $data['category'], PDO::PARAM_STR);
            $stmt->bindValue(':difficulty', $data['difficulty'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindValue(':price', $data['price']);
            $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
            if (!empty($data['cover_image'])) {
                $stmt->bindValue(':cover_image', $data['cover_image'], PDO::PARAM_STR);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // ==================================================================
    // FUNGSI UNTUK DATA UMUM / MENTEE (DENGAN SINTAKS PDO)
    // ==================================================================

    public function getCourseById($id) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getPublishedCourses($limit = 100) {
        $sql = "SELECT c.*, u.username as mentor_username FROM courses c JOIN users u ON c.mentor_id = u.id WHERE c.status = 'Published' ORDER BY c.created_at DESC";
        if (is_int($limit) && $limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getModulesByCourseId($course_id) {
        $stmt = $this->db->prepare("SELECT * FROM course_modules WHERE course_id = :course_id ORDER BY order_index ASC");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCoursesByMentee($student_id) {
        $stmt = $this->db->prepare("
            SELECT c.*, e.enrollment_date FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.student_id = :student_id AND c.status = 'Published'
        ");
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRandomCourses($limit = 5) {
        $stmt = $this->db->prepare("SELECT id, title, category, cover_image FROM courses WHERE status = 'Published' ORDER BY RAND() LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isAlreadyEnrolled($student_id, $course_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = :student_id AND course_id = :course_id");
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    public function enrollStudent($student_id, $course_id) {
        $stmt = $this->db->prepare("INSERT INTO enrollments (student_id, course_id, enrollment_date) VALUES (:student_id, :course_id, NOW())");
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>