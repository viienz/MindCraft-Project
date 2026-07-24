-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2025 at 08:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mindcraft`
--

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `category` enum('Pendidikan','UI/UX','Programming','Bisnis') NOT NULL,
  `status` enum('Published','Draft','Archived') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `difficulty` enum('Pemula','Menengah','Mahir') NOT NULL DEFAULT 'Pemula',
  `description` text NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `allow_reviews` tinyint(1) NOT NULL DEFAULT 1,
  `send_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `auto_certificate` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `duration_hours` int(11) DEFAULT 0,
  `total_lessons` int(11) DEFAULT 0,
  `language` varchar(50) DEFAULT 'Bahasa Indonesia',
  `requirements` text DEFAULT NULL,
  `what_you_learn` text DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `total_enrollments` int(11) DEFAULT 0,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `last_updated` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_analytics`
--

CREATE TABLE `course_analytics` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `views` int(11) DEFAULT 0,
  `enrollments` int(11) DEFAULT 0,
  `completions` int(11) DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `avg_watch_time` int(11) DEFAULT 0,
  `student_engagement` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3A59D1',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_categories`
--

INSERT INTO `course_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Pendidikan', 'pendidikan', 'Kursus untuk meningkatkan pengetahuan akademik', '📚', '#3A59D1', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(2, 'UI/UX Design', 'ui-ux', 'Kursus desain antarmuka dan pengalaman pengguna', '🎨', '#9333EA', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(3, 'Programming', 'programming', 'Kursus pemrograman dan pengembangan software', '💻', '#059669', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(4, 'Bisnis & Marketing', 'bisnis', 'Kursus untuk mengembangkan kemampuan bisnis', '📈', '#DC2626', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(5, 'Kerajinan & Seni', 'kerajinan', 'Kursus seni dan kerajinan tangan', '🎭', '#EA580C', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(6, 'Kesehatan & Kebugaran', 'kesehatan', 'Kursus untuk menjaga kesehatan tubuh', '💪', '#16A34A', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(7, 'Musik & Audio', 'musik', 'Kursus musik dan produksi audio', '🎵', '#7C3AED', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(8, 'Fotografi & Video', 'fotografi', 'Kursus fotografi dan videografi', '📸', '#0284C7', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(9, 'Bahasa', 'bahasa', 'Kursus pembelajaran bahasa asing', '🗣️', '#DB2777', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41'),
(10, 'Hobi & Lifestyle', 'hobi', 'Kursus untuk mengembangkan hobi', '🌟', '#65A30D', 1, 0, '2025-06-24 07:51:41', '2025-06-24 07:51:41');

-- --------------------------------------------------------

--
-- Table structure for table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('video','text','quiz','assignment','download') NOT NULL DEFAULT 'video',
  `content` longtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_duration` int(11) DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `is_downloadable` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_modules`
--

CREATE TABLE `course_modules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `duration_minutes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_progress`
--

CREATE TABLE `course_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `completed` tinyint(1) DEFAULT 0,
  `watch_time` int(11) DEFAULT 0,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_tags`
--

CREATE TABLE `course_tags` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earnings`
--

CREATE TABLE `earnings` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `transaction_type` enum('course_sale','tip','bonus','refund','withdrawal') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT 70.00,
  `platform_fee` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `payout_status` enum('pending','paid','hold') DEFAULT 'pending',
  `payout_date` timestamp NULL DEFAULT NULL,
  `withdrawal_method` varchar(50) DEFAULT NULL,
  `withdrawal_account` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_date` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_path` varchar(500) DEFAULT NULL,
  `payment_status` enum('free','paid','pending','refunded') DEFAULT 'free',
  `payment_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','completed','dropped','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_bank_accounts`
--

CREATE TABLE `mentor_bank_accounts` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `bank_name` varchar(50) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentor_bank_accounts`
--

INSERT INTO `mentor_bank_accounts` (`id`, `mentor_id`, `bank_name`, `account_number`, `account_name`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 20, 'Mandiri', '11111111', 'Qnzl', 1, '2025-07-12 10:14:27', '2025-07-12 16:01:27'),
(2, 24, 'BRI', '3726262728', 'cbdhddhdh', 1, '2025-07-12 13:50:11', '2025-07-12 13:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_ewallets`
--

CREATE TABLE `mentor_ewallets` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `type` enum('gopay','dana','ovo','shopeepay') NOT NULL,
  `name` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentor_ewallets`
--

INSERT INTO `mentor_ewallets` (`id`, `mentor_id`, `type`, `name`, `phone_number`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 20, 'ovo', 'cobaewallet', '938373737737', 1, '2025-07-12 09:58:39', '2025-07-12 12:57:07'),
(2, 24, 'ovo', 'coba1', '0851222444', 1, '2025-07-12 13:03:21', '2025-07-12 13:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_profiles`
--

CREATE TABLE `mentor_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `education` text DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `teaching_language` varchar(100) DEFAULT 'Bahasa Indonesia',
  `timezone` varchar(50) DEFAULT 'Asia/Jakarta',
  `availability_status` enum('Available','Busy','Away') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_settings`
--

CREATE TABLE `mentor_settings` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 1,
  `course_notifications` tinyint(1) DEFAULT 1,
  `review_notifications` tinyint(1) DEFAULT 1,
  `payment_notifications` tinyint(1) DEFAULT 1,
  `marketing_emails` tinyint(1) DEFAULT 0,
  `profile_visibility` enum('public','private','limited') DEFAULT 'public',
  `auto_accept_students` tinyint(1) DEFAULT 1,
  `course_approval_required` tinyint(1) DEFAULT 0,
  `language_preference` varchar(50) DEFAULT 'id',
  `timezone` varchar(50) DEFAULT 'Asia/Jakarta',
  `currency` varchar(10) DEFAULT 'IDR',
  `payout_method` varchar(50) DEFAULT 'bank_transfer',
  `payout_schedule` enum('weekly','biweekly','monthly') DEFAULT 'monthly',
  `minimum_payout` decimal(10,2) DEFAULT 100000.00,
  `tax_information` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('general','course_related','support','feedback') DEFAULT 'general',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `status` enum('sent','delivered','read','archived') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('course_enrollment','new_review','payment_received','course_completed','system_update','achievement') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('bank','ewallet') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `code`, `name`, `type`, `is_active`, `created_at`) VALUES
(1, 'bri', 'BRI', 'bank', 1, '2025-06-24 12:38:31'),
(2, 'bca', 'BCA', 'bank', 1, '2025-06-24 12:38:31'),
(3, 'mandiri', 'Mandiri', 'bank', 1, '2025-06-24 12:38:31'),
(4, 'bni', 'BNI', 'bank', 1, '2025-06-24 12:38:31'),
(5, 'gopay', 'Gopay', 'ewallet', 1, '2025-06-24 12:38:31'),
(6, 'dana', 'DANA', 'ewallet', 1, '2025-06-24 12:38:31'),
(7, 'ovo', 'OVO', 'ewallet', 1, '2025-06-24 12:38:31'),
(8, 'linkaja', 'LinkAja', 'ewallet', 1, '2025-06-24 12:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `mentor_reply` text DEFAULT NULL,
  `mentor_reply_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_title` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `subscription_type` varchar(20) NOT NULL,
  `price` int(11) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `payment_type` enum('bank','ewallet') NOT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ewallet_phone` varchar(20) DEFAULT NULL,
  `customer_address` text NOT NULL,
  `transaction_date` datetime NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `course_id`, `course_title`, `transaction_id`, `customer_name`, `customer_phone`, `subscription_type`, `price`, `total_amount`, `payment_method`, `payment_type`, `bank_account_name`, `bank_account_number`, `ewallet_phone`, `customer_address`, `transaction_date`, `status`, `created_at`) VALUES
(1, NULL, NULL, NULL, 'TRX-1751273271-4273', 'Egyy', '0833333333333333', 'Paket Pro', 299000, 299000, 'bca', 'bank', '22222222', '2222222222', '', '44444444444444444444', '2025-06-30 15:47:51', 'completed', '2025-06-30 08:47:51'),
(2, NULL, 14, '222222222222222', 'TRX-1751372147-1180', 'Koordinator Tugas Akhir', '083333444444', '222222222222222', 200, 200, 'bca', 'bank', '3333333333333', '94844444444', '', 'jjjddddddddddddddddddddddddddddd', '2025-07-01 19:15:47', 'completed', '2025-07-01 12:15:47'),
(3, NULL, 14, '222222222222222', 'TRX-1751383553-7387', 'student', '083333444444', '222222222222222', 200, 200, 'bni', 'bank', 'student', '94844444444', '', 'student home', '2025-07-01 22:25:53', 'completed', '2025-07-01 15:25:53'),
(4, NULL, 18, 'html dasar', 'TRX-1751441321-8634', 'Egyy', '083333444444', 'html dasar', 300, 300, 'gopay', 'ewallet', '', '', '00000000000000000000', '444444444444444444444444', '2025-07-02 14:28:41', 'completed', '2025-07-02 07:28:41'),
(5, NULL, 19, 'membuat chart dengan react', 'TRX-1751734267-1952', 'Teh pucuk', '000000000000', 'membuat chart dengan', 300, 300, 'bni', 'bank', 'egyy', '084378473333', '', '123456', '2025-07-05 23:51:07', 'completed', '2025-07-05 16:51:07'),
(6, NULL, 19, 'membuat chart dengan react', 'TRX-1751737657-6819', 'egyymentee', '0811111111111', 'membuat chart dengan', 300, 300, 'mandiri', 'bank', 'egyy', '08437847444', '', 'concat', '2025-07-06 00:47:37', 'completed', '2025-07-05 17:47:37'),
(7, NULL, 20, 'cara main guitar', 'TRX-1751739498-4099', 'bakso', '0811111111222', 'cara main guitar', 200, 200, 'bca', 'bank', 'egyy', '084378477777', '', 'cccccccccccccccccccccccc', '2025-07-06 01:18:18', 'completed', '2025-07-05 18:18:18'),
(8, NULL, 21, 'kursus belajar guitar', 'TRX-1751742892-7235', 'Sprite', '0811111111111', 'kursus belajar guita', 500, 500, 'dana', 'ewallet', '', '', '09483737373', 'rrirjdkwkwkwwkkwkwkwkwkwwk', '2025-07-06 02:14:52', 'completed', '2025-07-05 19:14:52'),
(9, NULL, 21, 'kursus belajar guitar', 'TRX-1751743440-5680', 'Cimori', '08111112222', 'kursus belajar guita', 500, 500, 'bri', 'bank', 'egyy', '084378477332', '', 'ooooooooooooooooo', '2025-07-06 02:24:00', 'completed', '2025-07-05 19:24:00'),
(10, NULL, 22, 'Free Chess Course From Beginner To Master Level‼️', 'TRX-1751744496-8762', 'coca cola', '66666666666', 'Free Chess Course Fr', 150, 150, 'bca', 'bank', 'yy', '08437847444', '', 'dddddddddddddddddddd', '2025-07-06 02:41:36', 'completed', '2025-07-05 19:41:36'),
(11, NULL, 24, 'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm', 'TRX-1751748090-1214', 'nnnnnnnnnn', '08111112222', 'mmmmmmmmmmmmmmmmmmmm', 900, 900, 'bca', 'bank', 'ppppppppp', '0000000000', '', 'mmmmmmmmm', '2025-07-06 03:41:30', 'completed', '2025-07-05 20:41:30'),
(12, NULL, 28, 'belajar kursus baru', 'TRX-1751753678-9000', 'bbbbbbbbbbb', '66666666666', 'belajar kursus baru', 530000, 530000, 'mandiri', 'bank', '6555555555555', '222222222', '', '8888888888888', '2025-07-06 05:14:38', 'completed', '2025-07-05 22:14:38'),
(13, NULL, 30, 'cobaaaaaaaaaaa kursus part 4', 'TRX-1751774725-6739', 'ptrkrrr', '222222222', 'cobaaaaaaaaaaa kursu', 400000, 400000, 'dana', 'ewallet', '', '', '222222222', 'vvvvvvvvv', '2025-07-06 11:05:25', 'completed', '2025-07-06 04:05:25'),
(14, NULL, 31, 'eeeeeeeeeeeee', 'TRX-1751774812-9848', 'Koordinator Tugas Akhir', '111111111111', 'eeeeeeeeeeeee', 900000, 900000, 'bca', 'bank', '11111111111', '1111111111', '', 'wwwwwww', '2025-07-06 11:06:52', 'completed', '2025-07-06 04:06:52'),
(15, NULL, 32, 'xxxxxxxxxx', 'TRX-1751776202-4287', 'yyyyyy', '888888888', 'xxxxxxxxxx', 500000, 500000, 'bca', 'bank', '3333333', '22222222222', '', 'jjjjjjjjjj', '2025-07-06 11:30:02', 'completed', '2025-07-06 04:30:02'),
(16, NULL, 32, 'xxxxxxxxxx', 'TRX-1751778564-8972', '12234', '083333444444', 'xxxxxxxxxx', 500000, 500000, 'bca', 'bank', '7777777', '7777777777', '', 'hhhhhhhhhhhh', '2025-07-06 12:09:25', 'completed', '2025-07-06 05:09:25'),
(17, NULL, 33, 'contoh kursus', 'TRX-1751865527-8257', '123', '0833334888', 'contoh kursus', 600000, 600000, 'mandiri', 'bank', 'egy', '88888888888', '', 'mmmmmmm', '2025-07-07 12:18:47', 'completed', '2025-07-07 05:18:47'),
(18, NULL, 28, 'belajar kursus baru', 'TRX-1751885019-8576', 'bbbbb', 'bbbbbb', 'belajar kursus baru', 530000, 530000, 'mandiri', 'bank', 'egy', '77777777', '', 'bbbbbbb', '2025-07-07 17:43:39', 'completed', '2025-07-07 10:43:39'),
(19, NULL, 29, 'contoh kursus baru', 'TRX-1751914891-5522', 'ddjdjd', '48383838383', 'contoh kursus baru', 850000, 850000, 'mandiri', 'bank', '222222', '222222', '', '3939393939', '2025-07-08 02:01:31', 'completed', '2025-07-07 19:01:31'),
(20, NULL, 24, 'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm', 'TRX-1751953445-2580', 'xxxxx', '123456', 'mmmmmmmmmmmmmmmmmmmm', 900, 900, 'mandiri', 'bank', 'eeeeeeeeeee', '1111111111', '', 'mfff', '2025-07-08 12:44:05', 'completed', '2025-07-08 05:44:05'),
(21, NULL, 34, 'lllllllllllllll', 'TRX-1752046391-2082', 'ccccccccc', 'cccccccccc', 'lllllllllllllll', 500000, 500000, 'bca', 'bank', 'cccccccccc', 'cccccccccc', '', 'ccccccccc', '2025-07-09 14:33:11', 'completed', '2025-07-09 07:33:11'),
(22, NULL, 34, 'lllllllllllllll', 'TRX-1752047133-7045', 'Egyy', '0833334888', 'lllllllllllllll', 600000, 600000, 'bca', 'bank', 'egy', '1111111111', '', 'xxxx', '2025-07-09 14:45:33', 'completed', '2025-07-09 07:45:33'),
(23, NULL, NULL, NULL, 'TRX-1752048079-1703', 'Egyy', '083333444444', 'contoh kursus', 600000, 600000, 'bca', 'bank', 'tttt', '12345678', '', 'rrrrr', '2025-07-09 15:01:19', 'completed', '2025-07-09 08:01:19'),
(24, NULL, NULL, NULL, 'TRX-1752048274-6359', 'user', '0822233333', 'contoh kursus', 600000, 600000, 'mandiri', 'bank', 'user1', '123456789', '', 'dddddddddd', '2025-07-09 15:04:34', 'completed', '2025-07-09 08:04:34'),
(25, NULL, NULL, NULL, 'TRX-1752052937-7705', 'coba', '474747474', 'hor to play clash ro', 200000, 200000, 'dana', 'ewallet', '', '', '33333333', 'jjjjjjjjjjjjjjjjjjj', '2025-07-09 16:22:17', 'completed', '2025-07-09 09:22:17'),
(26, NULL, NULL, NULL, 'TRX-1752053493-8006', 'vvvvvv', 'vvvvvvvvv', 'vvvvvvvv', 99999999, 99999999, 'bca', 'bank', 'vvvvvv', 'vvvvvvvv', '', 'vvvvvvv', '2025-07-09 16:31:33', 'completed', '2025-07-09 09:31:33'),
(27, NULL, NULL, NULL, 'TRX-1752053938-5466', 'kkkkkkkk', '111111111111', 'ppppppp', 900000, 900000, 'bca', 'bank', 'ppppp', '00000000', '', 'pppppppppp', '2025-07-09 16:38:58', 'completed', '2025-07-09 09:38:58'),
(28, NULL, NULL, NULL, 'TRX-1752066873-1152', 'Egyy', '0833334888', 'coba', 500000, 500000, 'bca', 'bank', 'egy', '1111111111', '', 'mmmmmmmmmmmm', '2025-07-09 20:14:33', 'completed', '2025-07-09 13:14:33'),
(29, NULL, NULL, NULL, 'TRX-1752154349-1746', 'mmmmmmmmmmm', '00000000', 'bbbbbbb', 600000, 600000, 'bca', 'bank', 'egy', '1111111111', '', 'xxxxxxxxx', '2025-07-10 20:32:29', 'completed', '2025-07-10 13:32:29'),
(30, NULL, NULL, NULL, 'TRX-1752161334-8341', 'Egyy', '666666666', 'uuuuu', 700000, 700000, 'dana', 'ewallet', '', '', '44444444444', 'bbbbbbbbbbbb', '2025-07-10 22:28:54', 'completed', '2025-07-10 15:28:54'),
(31, NULL, NULL, NULL, 'TRX-1752165235-1850', 'vvvvvvvvvvv', 'vvvvvvvvvvv', 'zzzzzzz', 400000, 400000, 'bri', 'bank', 'vvvvvvvvv', '2333333333', '', 'vvvvvvvvvv', '2025-07-10 23:33:55', 'completed', '2025-07-10 16:33:55'),
(32, NULL, NULL, NULL, 'TRX-1752215202-8629', 'Egyy', '0833334888', 'coba belajar 1', 300000, 300000, 'bca', 'bank', 'egy', '0000000000', '', 'oooooooooooooo', '2025-07-11 13:26:42', 'completed', '2025-07-11 06:26:42'),
(33, NULL, NULL, NULL, 'TRX-1752216159-4573', 'mmmmmmmm', '0833334888', 'vvvvvvvvvvvv', 100000, 100000, 'bca', 'bank', 'egy', '1111111111', '', 'mmmmmmmmmmm', '2025-07-11 13:42:39', 'completed', '2025-07-11 06:42:39'),
(34, NULL, NULL, NULL, 'TRX-1752220263-2131', 'fvvvvvvvvvvvv', 'vvvvvvvvvvvvvvvvvv', 'mmmmmmmmmmm', 500000, 500000, 'bca', 'bank', 'vvvvvvvvvvv', '1111111111', '', 'vvvvvvvvvvvvvvvvv', '2025-07-11 14:51:03', 'completed', '2025-07-11 07:51:03'),
(35, NULL, NULL, NULL, 'TRX-1752325871-8982', 'mmmmmmmmmmm', '2222222222', 'xxxxxxxxxx', 900000, 900000, 'bca', 'bank', '22222222', '22222222222', '', 'mmmmmmmmmm', '2025-07-12 20:11:11', 'completed', '2025-07-12 13:11:11'),
(36, NULL, NULL, NULL, 'TRX-1752327040-9963', '12', '0833334899', 'xxxxxxxxxx', 900000, 900000, 'mandiri', 'bank', 'eeeeeeeeeee', 'mmmmmmmmm', '', 'mmmmmmmmm', '2025-07-12 20:30:40', 'completed', '2025-07-12 13:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('Mentee','Mentor') NOT NULL,
  `gender` enum('Laki-laki','Perempuan') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('bank_transfer','gopay','dana','ovo','shopeepay') NOT NULL,
  `account_details` text NOT NULL,
  `status` enum('pending','processed','completed','failed') DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_content_user` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `course_analytics`
--
ALTER TABLE `course_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_course_date` (`course_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_module_id` (`module_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_order` (`order_index`);

--
-- Indexes for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_order` (`order_index`);

--
-- Indexes for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`student_id`,`lesson_id`),
  ADD KEY `idx_student_course` (`student_id`,`course_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `fk_progress_course` (`course_id`);

--
-- Indexes for table `course_tags`
--
ALTER TABLE `course_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_tag_name` (`tag_name`);

--
-- Indexes for table `earnings`
--
ALTER TABLE `earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_earnings_student` (`student_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_enrollment_date` (`enrollment_date`);

--
-- Indexes for table `mentor_bank_accounts`
--
ALTER TABLE `mentor_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`);

--
-- Indexes for table `mentor_ewallets`
--
ALTER TABLE `mentor_ewallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`);

--
-- Indexes for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `mentor_settings`
--
ALTER TABLE `mentor_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mentor` (`mentor_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_messages_parent` (`parent_message_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`student_id`,`course_id`),
  ADD KEY `idx_course_id` (`course_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `fk_transaction_user` (`user_id`),
  ADD KEY `fk_transaction_payment_method` (`payment_method`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `course_analytics`
--
ALTER TABLE `course_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `course_modules`
--
ALTER TABLE `course_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `course_progress`
--
ALTER TABLE `course_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT for table `course_tags`
--
ALTER TABLE `course_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `earnings`
--
ALTER TABLE `earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `mentor_bank_accounts`
--
ALTER TABLE `mentor_bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentor_ewallets`
--
ALTER TABLE `mentor_ewallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mentor_settings`
--
ALTER TABLE `mentor_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `fk_content_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_analytics`
--
ALTER TABLE `course_analytics`
  ADD CONSTRAINT `fk_analytics_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD CONSTRAINT `fk_lessons_module` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_modules`
--
ALTER TABLE `course_modules`
  ADD CONSTRAINT `fk_modules_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD CONSTRAINT `fk_progress_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progress_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_tags`
--
ALTER TABLE `course_tags`
  ADD CONSTRAINT `fk_tags_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `earnings`
--
ALTER TABLE `earnings`
  ADD CONSTRAINT `fk_earnings_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_earnings_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_earnings_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mentor_profiles`
--
ALTER TABLE `mentor_profiles`
  ADD CONSTRAINT `fk_mentor_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mentor_settings`
--
ALTER TABLE `mentor_settings`
  ADD CONSTRAINT `fk_mentor_settings_user` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_parent` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transaction_payment_method` FOREIGN KEY (`payment_method`) REFERENCES `payment_methods` (`code`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `fk_withdrawals_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
