<?php
require_once __DIR__ . '/../config/Database.php';

class UserModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Mengambil data mentor berdasarkan ID dari tabel 'users'
    public function getMentorById($id) {
        // PERBAIKAN: Menggunakan tabel 'users' dan memfilter berdasarkan 'user_type'
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id AND user_type = 'Mentor'");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Dalam file UserModel.php
public function getUserById($userId) {
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    
    // Fungsi untuk login (disesuaikan untuk tabel 'users')
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tambahkan fungsi-fungsi ini di dalam class UserModel

    /**
     * Mengambil profil detail mentor dari tabel mentor_profiles.
     */
    public function getMentorProfile($user_id) {
        // Inisialisasi profil kosong jika tidak ditemukan
        $profile = [
            'full_name' => '', 'bio' => '', 'phone' => '', 
            'website' => '', 'linkedin' => '', 'instagram' => '', 'youtube' => '',
            'specialization' => '', 'experience_years' => 0
        ];

        $stmt = $this->db->prepare("SELECT * FROM mentor_profiles WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika ada profil di database, gabungkan dengan data default
        return $result ? array_merge($profile, $result) : $profile;
    }

    // Ganti fungsi updateMentorProfile di dalam file model/UserModel.php

    /**
     * Membuat atau memperbarui profil mentor.
     */
    // Di dalam class UserModel, ganti fungsi updateMentorProfile dengan ini:

    public function updateMentorProfile($data) {
        // Cek dulu apakah profil sudah ada
        $stmt = $this->db->prepare("SELECT id FROM mentor_profiles WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $exists = $stmt->fetch();

        if ($exists) {
            // Jika sudah ada, UPDATE
            $sql = "UPDATE mentor_profiles SET 
                        full_name = :full_name, bio = :bio, phone = :phone, 
                        website = :website, linkedin = :linkedin, instagram = :instagram, youtube = :youtube,
                        specialization = :specialization, experience_years = :experience_years
                    WHERE user_id = :user_id";
        } else {
            // Jika belum ada, INSERT
            $sql = "INSERT INTO mentor_profiles (user_id, full_name, bio, phone, website, linkedin, instagram, youtube, specialization, experience_years) 
                    VALUES (:user_id, :full_name, :bio, :phone, :website, :linkedin, :instagram, :youtube, :specialization, :experience_years)";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
            $stmt->bindValue(':bio', $data['bio'], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindValue(':website', $data['website'], PDO::PARAM_STR);
            $stmt->bindValue(':linkedin', $data['linkedin'], PDO::PARAM_STR);
            $stmt->bindValue(':instagram', $data['instagram'], PDO::PARAM_STR);
            $stmt->bindValue(':youtube', $data['youtube'], PDO::PARAM_STR);
            // Bind value untuk kolom baru
            $stmt->bindValue(':specialization', $data['specialization'], PDO::PARAM_STR);
            $stmt->bindValue(':experience_years', $data['experience_years'], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }



    /**
     * Mengupdate password user di tabel 'users'.
     */
    public function updatePassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Fungsi lainnya bisa ditambahkan di sini...
}
?>