<?php
class StatsModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Menghitung total pendapatan kotor dari tabel 'earnings'.
     */
    public function getTotalRevenueByMentorId($mentor_id) {
        // Mengambil jumlah dari semua transaksi tipe 'course_sale'
        $stmt = $this->db->prepare(
            "SELECT SUM(net_amount) as total_revenue FROM earnings WHERE mentor_id = :mentor_id AND transaction_type = 'course_sale'"
        );
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_revenue'] ?? 0;
    }

    /**
     * Menghitung jumlah total siswa yang unik dari tabel 'enrollments'.
     */
    public function getTotalStudentsByMentorId($mentor_id) {
        // Query ini sudah kita perbaiki sebelumnya dan seharusnya sudah benar
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT e.student_id) as total_students
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE c.mentor_id = :mentor_id
        ");
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_students'] ?? 0;
    }

    /**
     * Menghitung total dana yang sudah ditarik dari tabel 'earnings'.
     */
    public function getTotalWithdrawalsByMentorId($mentor_id) {
        // Mengambil jumlah dari semua transaksi tipe 'withdrawal'
        $stmt = $this->db->prepare(
            "SELECT SUM(amount) as total_withdrawals FROM earnings WHERE mentor_id = :mentor_id AND transaction_type = 'withdrawal'"
        );
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return abs($result['total_withdrawals'] ?? 0); // Diambil nilai absolutnya
    }

    /**
     * Mengambil riwayat penarikan dana (payout) dari tabel 'earnings'.
     */
    public function getPayoutHistoryByMentorId($mentor_id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM earnings WHERE mentor_id = :mentor_id AND transaction_type = 'withdrawal' ORDER BY created_at DESC"
        );
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil data transaksi bulanan untuk grafik dari tabel 'earnings'.
     */
    public function getMonthlyRevenueByMentorId($mentor_id) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month, 
                SUM(net_amount) as monthly_revenue
            FROM earnings
            WHERE mentor_id = :mentor_id AND transaction_type = 'course_sale' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cari fungsi getRecentEarningsByMentorId di dalam StatsModel dan modifikasi:

    public function getRecentEarningsByMentorId($mentor_id, $limit = 5) {
        $stmt = $this->db->prepare(
            // TAMBAHKAN e.id as transaction_id DI SINI
            "SELECT e.id as transaction_id, e.net_amount, e.created_at, c.title as course_title 
            FROM earnings e
            LEFT JOIN courses c ON e.course_id = c.id
            WHERE e.mentor_id = :mentor_id AND e.transaction_type = 'course_sale' 
            ORDER BY e.created_at DESC
            LIMIT :limit"
        );
        $stmt->bindValue(':mentor_id', $mentor_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>