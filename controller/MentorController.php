<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/CourseModel.php';
require_once __DIR__ . '/../model/StatsModel.php';

class MentorController {
    private $db;
    private $userModel;
    private $courseModel;
    private $statsModel;

    public function __construct(Database $database) {
        $this->db = $database->connect();
        $this->userModel = new UserModel($this->db);
        $this->courseModel = new CourseModel($this->db);
        $this->statsModel = new StatsModel($this->db);
    }

    public function getMentorData($mentorId) {
        return $this->userModel->getMentorById($mentorId);
    }

    public function getDashboardData($mentorId) {
        $data = [];
        
        // Get data from models
        $totalRevenue = $this->statsModel->getTotalRevenueByMentorId($mentorId);
        $totalWithdrawals = $this->statsModel->getTotalWithdrawalsByMentorId($mentorId);
        
        // Enhanced with additional metrics
        $data['totalCourses'] = $this->courseModel->getCourseCountByMentorId($mentorId);
        $data['totalMentees'] = $this->statsModel->getTotalStudentsByMentorId($mentorId);
        $data['totalEarnings'] = $totalRevenue;
        $data['availableBalance'] = $totalRevenue - $totalWithdrawals;
        $data['monthlyChartData'] = $this->statsModel->getMonthlyRevenueByMentorId($mentorId);
        
        // Format currency values
        $data['formattedTotalEarnings'] = $this->formatCurrency($totalRevenue);
        $data['formattedAvailableBalance'] = $this->formatCurrency($data['availableBalance']);
        
        // Add new metrics
        $data['newRegistrations'] = $this->getNewRegistrations($mentorId);
        $data['unreadMessages'] = $this->getUnreadMessages($mentorId);
        $data['moduleCount'] = $this->getModuleCount($mentorId);
        $data['totalLessons'] = $this->getTotalLessons($mentorId);
        $data['monthlyRegistrations'] = $this->getMonthlyRegistrations($mentorId);
        $data['recentActivities'] = $this->getRecentActivities($mentorId);
        $data['consistencyIncrease'] = rand(-5, 10);
        
        return $data;
    }

    private function formatCurrency($amount) {
        $amount = (float)$amount;
        
        if ($amount == 0) {
            return 'Rp 0';
        } elseif ($amount >= 1000000) {
            $value = $amount / 1000000;
            // Remove .0 for whole numbers
            return 'Rp ' . (fmod($value, 1) == 0 ? number_format($value, 0) : number_format($value, 1)) . ' jt';
        } elseif ($amount >= 1000) {
            return 'Rp ' . number_format($amount / 1000, 0) . 'k';
        } else {
            return 'Rp ' . number_format($amount);
        }
        
    }

    private function getNewRegistrations($mentorId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM enrollments 
                                      WHERE course_id IN (SELECT id FROM courses WHERE mentor_id = ?)
                                      AND enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute([$mentorId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting new registrations: " . $e->getMessage());
            return 0;
        }
    }

    private function getUnreadMessages($mentorId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0");
            $stmt->execute([$mentorId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting unread messages: " . $e->getMessage());
            return 0;
        }
    }

    private function getModuleCount($mentorId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM course_modules 
                                      WHERE course_id IN (SELECT id FROM courses WHERE mentor_id = ?)");
            $stmt->execute([$mentorId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting module count: " . $e->getMessage());
            return 0;
        }
    }

    private function getTotalLessons($mentorId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM course_lessons 
                                      WHERE module_id IN (
                                          SELECT id FROM course_modules 
                                          WHERE course_id IN (SELECT id FROM courses WHERE mentor_id = ?)
                                      )");
            $stmt->execute([$mentorId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total lessons: " . $e->getMessage());
            return 0;
        }
    }

    private function getMonthlyRegistrations($mentorId) {
        try {
            $stmt = $this->db->prepare("SELECT 
                DATE_FORMAT(enrollment_date, '%Y-%m-%d') as day,
                COUNT(*) as count
                FROM enrollments
                WHERE course_id IN (SELECT id FROM courses WHERE mentor_id = ?)
                AND enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY day
                ORDER BY day ASC");
            $stmt->execute([$mentorId]);
            
            $monthlyData = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $monthlyData[$row['day']] = $row['count'];
            }
            
            // Fill data for last 7 days
            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $result[] = $monthlyData[$date] ?? 0;
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting monthly registrations: " . $e->getMessage());
            return array_fill(0, 7, 0);
        }
    }

    private function getRecentActivities($mentorId) {
        try {
            $stmt = $this->db->prepare("SELECT 
                u.username as user,
                CONCAT(LEFT(u.username, 1), '') as avatar,
                CASE 
                    WHEN e.id IS NOT NULL THEN 'mendaftar ke kursus Anda'
                    WHEN r.id IS NOT NULL THEN 'memberikan ulasan'
                    WHEN m.id IS NOT NULL THEN 'mengirim pesan'
                    ELSE 'melakukan aktivitas'
                END as action,
                COALESCE(e.enrollment_date, r.created_at, m.created_at) as time
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.student_id 
                    AND e.course_id IN (SELECT id FROM courses WHERE mentor_id = ?)
                LEFT JOIN reviews r ON u.id = r.student_id 
                    AND r.course_id IN (SELECT id FROM courses WHERE mentor_id = ?)
                LEFT JOIN messages m ON u.id = m.sender_id AND m.recipient_id = ?
                WHERE (e.id IS NOT NULL OR r.id IS NOT NULL OR m.id IS NOT NULL)
                ORDER BY time DESC
                LIMIT 5");
            $stmt->execute([$mentorId, $mentorId, $mentorId]);
            
            $activities = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $activities[] = [
                    'user' => $row['user'],
                    'avatar' => strtoupper(substr($row['user'], 0, 1)),
                    'action' => $row['action'],
                    'time' => $this->formatTimeAgo($row['time'])
                ];
            }
            
            return $activities;
        } catch (PDOException $e) {
            error_log("Error getting recent activities: " . $e->getMessage());
            return [];
        }
    }

    public function getCoursesPageData($mentorId) {
        return [
            'courses' => $this->courseModel->getCoursesByMentorId($mentorId)
        ];
    }

    public function getRevenuePageData($mentorId) {
        $totalRevenue = $this->statsModel->getTotalRevenueByMentorId($mentorId);
        $totalWithdrawals = $this->statsModel->getTotalWithdrawalsByMentorId($mentorId);
        return [
            'total_revenue' => $totalRevenue,
            'formatted_total_revenue' => $this->formatCurrency($totalRevenue),
            'total_withdrawals' => $totalWithdrawals,
            'formatted_total_withdrawals' => $this->formatCurrency($totalWithdrawals),
            'available_balance' => $totalRevenue - $totalWithdrawals,
            'formatted_available_balance' => $this->formatCurrency($totalRevenue - $totalWithdrawals)
        ];
    }

    public function getPayoutHistoryPageData($mentorId) {
        $payouts = $this->statsModel->getPayoutHistoryByMentorId($mentorId);
        
        // Format amounts
        foreach ($payouts as &$payout) {
            $payout['formatted_amount'] = $this->formatCurrency($payout['amount']);
        }
        
        return [
            'payouts' => $payouts
        ];
    }

    public function getAnalyticsPageData($mentorId) {
        return [
            'totalCourses' => $this->courseModel->getCourseCountByMentorId($mentorId),
            'totalStudents' => $this->statsModel->getTotalStudentsByMentorId($mentorId),
            'monthlyRevenueData' => $this->statsModel->getMonthlyRevenueByMentorId($mentorId)
        ];
    }

    public function getRecentTransactions($mentorId, $limit = 5) {
        try {
            $stmt = $this->db->prepare("SELECT 
                t.id, t.course_id, c.title AS course_title, t.transaction_id, 
                t.total_amount, t.payment_method, t.transaction_date, t.status
                FROM transactions t
                JOIN courses c ON t.course_id = c.id
                WHERE c.mentor_id = :mentor_id
                ORDER BY t.transaction_date DESC
                LIMIT :limit");
            
            $stmt->bindParam(':mentor_id', $mentorId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $transactions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $transactions[] = [
                    'id' => $row['id'],
                    'course_title' => $row['course_title'],
                    'amount' => $row['total_amount'],
                    'formatted_amount' => $this->formatCurrency($row['total_amount']),
                    'payment_method' => $row['payment_method'],
                    'date' => $this->formatTimeAgo($row['transaction_date']),
                    'status' => $row['status']
                ];
            }
            
            return $transactions;
        } catch (PDOException $e) {
            error_log("Error fetching recent transactions: " . $e->getMessage());
            return [];
        }
    }

    public function getTopPerformingCourses($mentorId, $limit = 3) {
        try {
            $stmt = $this->db->prepare("SELECT 
                c.id, c.title, c.cover_image, 
                COUNT(DISTINCT e.student_id) AS total_enrollments,
                AVG(r.rating) AS avg_rating,
                SUM(ear.amount) AS total_earnings
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN reviews r ON c.id = r.course_id
                LEFT JOIN earnings ear ON c.id = ear.course_id
                WHERE c.mentor_id = :mentor_id
                GROUP BY c.id
                ORDER BY total_enrollments DESC, avg_rating DESC
                LIMIT :limit");
            
            $stmt->bindParam(':mentor_id', $mentorId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $courses = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $courses[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'cover_image' => $row['cover_image'],
                    'avg_rating' => $row['avg_rating'],
                    'total_enrollments' => $row['total_enrollments'],
                    'total_earnings' => $row['total_earnings'] ?? 0,
                    'formatted_total_earnings' => $this->formatCurrency($row['total_earnings'] ?? 0)
                ];
            }
            
            return $courses;
        } catch (PDOException $e) {
            error_log("Error fetching top courses: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentEarnings($mentorId, $limit = 5) {
        $earnings = $this->statsModel->getRecentEarningsByMentorId($mentorId, $limit);
        
        // Format amounts
        foreach ($earnings as &$earning) {
            $earning['formatted_amount'] = $this->formatCurrency($earning['amount']);
            $earning['formatted_net_amount'] = $this->formatCurrency($earning['net_amount']);
        }
        
        return $earnings;
    }

    private function formatTimeAgo($datetime) {
        if (empty($datetime)) return 'Baru saja';
        
        $time = strtotime($datetime);
        $timeDiff = time() - $time;
        
        if ($timeDiff < 60) {
            return 'Baru saja';
        } elseif ($timeDiff < 3600) {
            return floor($timeDiff / 60) . ' menit yang lalu';
        } elseif ($timeDiff < 86400) {
            return floor($timeDiff / 3600) . ' jam yang lalu';
        } elseif ($timeDiff < 604800) {
            return floor($timeDiff / 86400) . ' hari yang lalu';
        } else {
            return date('d M Y', $time);
        }
    }
}
?>