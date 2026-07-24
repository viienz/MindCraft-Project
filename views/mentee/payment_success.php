<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../landingpage/landingpage.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'mindcraft';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Check if transaction ID exists
if (!isset($_GET['transaction_id']) || !isset($_GET['course_id'])) {
    header("Location: payment.php");
    exit();
}

$transactionId = $_GET['transaction_id'];
$courseId = $_GET['course_id'];

// Get transaction details
try {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE transaction_id = :transaction_id");
    $stmt->execute([':transaction_id' => $transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception("Transaksi tidak ditemukan");
    }

    // Verify enrollment
    $enrollStmt = $conn->prepare("SELECT * FROM enrollments 
                                WHERE student_id = :student_id 
                                AND course_id = :course_id 
                                AND status = 'active'");
    $enrollStmt->execute([
        ':student_id' => $_SESSION['user_id'],
        ':course_id' => $courseId
    ]);
    $enrollment = $enrollStmt->fetch();

    if (!$enrollment) {
        throw new Exception("Pendaftaran kursus tidak ditemukan. Silakan hubungi dukungan.");
    }

    // Get course details
    $courseStmt = $conn->prepare("SELECT id, title FROM courses WHERE id = :course_id");
    $courseStmt->execute([':course_id' => $courseId]);
    $course = $courseStmt->fetch();

    if (!$course) {
        throw new Exception("Detail kursus tidak ditemukan");
    }

} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle receipt download
if (isset($_POST['download_struk'])) {
    require_once(__DIR__ . '/../../tcpdf/tcpdf.php');
    
    try {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('MindCraft');
        $pdf->SetAuthor('MindCraft');
        $pdf->SetTitle('Struk Pembayaran - ' . $transactionId);
        $pdf->SetSubject('Struk Pembayaran');
        $pdf->SetKeywords('Struk, Pembayaran, MindCraft');
        $pdf->AddPage();

        $html = '
        <style>
            h1 { color: #3A59D1; text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 8px; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; width: 40%; }
        </style>
        <h1>Struk Pembayaran MindCraft</h1>
        <table>
            <tr><td class="label">ID Transaksi</td><td>' . $transactionId . '</td></tr>
            <tr><td class="label">Tanggal</td><td>' . date('d/m/Y H:i', strtotime($transaction['transaction_date'])) . '</td></tr>
            <tr><td class="label">Nama Pelanggan</td><td>' . htmlspecialchars($transaction['customer_name']) . '</td></tr>
            <tr><td class="label">Nomor Telepon</td><td>' . htmlspecialchars($transaction['customer_phone']) . '</td></tr>
            <tr><td class="label">Kursus</td><td>' . htmlspecialchars($course['title']) . '</td></tr>
            <tr><td class="label">Metode Pembayaran</td><td>' . strtoupper($transaction['payment_method']) . '</td></tr>
            <tr><td class="label">Total Pembayaran</td><td>Rp ' . number_format($transaction['total_amount'], 0, ',', '.') . '</td></tr>
            <tr><td class="label">Status</td><td style="color:green;font-weight:bold;">' . strtoupper($transaction['status']) . '</td></tr>
        </table>
        <p style="text-align:center;margin-top:30px;font-style:italic;">Terima kasih telah mempercayai MindCraft</p>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('struk_pembayaran_' . $transactionId . '.pdf', 'D');
        exit();
    } catch(Exception $e) {
        die("Gagal membuat struk: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - MindCraft</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s forwards 0.3s;
        }
        
        .success-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
            margin-bottom: 30px;
            transform: scale(0.95);
            animation: cardScaleIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards 0.5s;
        }
        
        .success-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .success-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #4cc9f0 0%, #4895ef 50%, #4361ee 100%);
            animation: rainbow 8s linear infinite;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: scale(0);
            animation: iconPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards 0.8s;
        }
        
        .success-icon i {
            font-size: 40px;
            color: #4cc9f0;
            opacity: 0;
            transform: rotate(-45deg);
            animation: iconCheck 0.5s ease forwards 1.2s;
        }
        
        .success-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s ease forwards 1s;
        }
        
        .success-subtitle {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s ease forwards 1.1s;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .subscription-card {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6f0ff 100%);
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid rgba(67, 97, 238, 0.2);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards 1.3s;
        }
        
        .subscription-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }
        
        .subscription-name {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .subscription-price {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .transaction-details {
            background-color: var(--light);
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 25px 0;
            border: 1px solid var(--light-gray);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards 1.4s;
        }
        
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
            opacity: 0;
            transform: translateX(-10px);
        }
        
        .detail-row:nth-child(1) { animation: fadeInLeft 0.4s ease forwards 1.5s; }
        .detail-row:nth-child(2) { animation: fadeInLeft 0.4s ease forwards 1.6s; }
        .detail-row:nth-child(3) { animation: fadeInLeft 0.4s ease forwards 1.7s; }
        .detail-row:nth-child(4) { animation: fadeInLeft 0.4s ease forwards 1.8s; }
        .detail-row:nth-child(5) { animation: fadeInLeft 0.4s ease forwards 1.9s; }
        .detail-row:nth-child(6) { animation: fadeInLeft 0.4s ease forwards 2.0s; }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            width: 200px;
            color: var(--gray);
            font-size: 15px;
        }
        
        .detail-value {
            flex: 1;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-completed {
            background-color: #e6fcf5;
            color: #0ca678;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards 2.1s;
        }
        
        .btn {
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        .btn-primary::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
            transition: var(--transition);
        }
        
        .btn-primary:hover::after {
            left: 100%;
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background-color: #f0f4ff;
            transform: translateY(-3px);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes cardScaleIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes iconPop {
            0% {
                transform: scale(0);
            }
            70% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes iconCheck {
            0% {
                opacity: 0;
                transform: rotate(-45deg) scale(0.5);
            }
            100% {
                opacity: 1;
                transform: rotate(0) scale(1);
            }
        }
        
        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        /* Confetti styles */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            opacity: 0;
            z-index: 999;
            animation: confettiFall linear forwards;
        }
        
        @keyframes confettiFall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 1;
            }
        }
        
        /* Floating animation for price */
        .subscription-price {
            animation: float 3s ease-in-out infinite 2s;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-label {
                width: 100%;
            }
            
            .button-group {
                flex-direction: column;
                gap: 12px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="success-title">Pembayaran Berhasil!</h1>
                <p class="success-subtitle">Anda sekarang terdaftar di kursus: <?php echo htmlspecialchars($course['title']); ?></p>
            </div>
            
            <div class="card-body">
                <div class="subscription-card">
                    <div class="subscription-name"><?php echo htmlspecialchars($transaction['subscription_type']); ?></div>
                    <div class="subscription-price">Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></div>
                </div>
                
                <div class="transaction-details">
                    <div class="detail-row">
                        <div class="detail-label">ID Transaksi</div>
                        <div class="detail-value"><?php echo htmlspecialchars($transactionId); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal Transaksi</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Nama Pelanggan</div>
                        <div class="detail-value"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Nomor Telepon</div>
                        <div class="detail-value"><?php echo htmlspecialchars($transaction['customer_phone']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Kursus</div>
                        <div class="detail-value"><?php echo htmlspecialchars($course['title']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Metode Pembayaran</div>
                        <div class="detail-value"><?php echo strtoupper($transaction['payment_method']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status Pembayaran</div>
                        <div class="detail-value">
                            <span class="status-badge status-completed"><?php echo strtoupper($transaction['status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <form method="post" style="display:inline;">
                        <button type="submit" name="download_struk" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Download Struk Pembayaran
                        </button>
                    </form>
                    <a href="course-player.php?id=<?php echo $courseId; ?>" class="btn btn-primary">
                        <i class="fas fa-book-open"></i> Mulai Belajar
                    </a>
                    <a href="kursus.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced confetti effect
        document.addEventListener('DOMContentLoaded', function() {
            // Create confetti
            const colors = ['#4361ee', '#3f37c9', '#4895ef', '#4cc9f0', '#3a0ca3', '#7209b7', '#f72585'];
            const shapes = ['circle', 'rect', 'triangle'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                
                // Random properties
                const size = Math.random() * 10 + 5;
                const color = colors[Math.floor(Math.random() * colors.length)];
                const shape = shapes[Math.floor(Math.random() * shapes.length)];
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 3 + 2;
                const delay = Math.random() * 2;
                
                // Apply properties
                confetti.style.left = left + 'vw';
                confetti.style.top = -20 + 'px';
                confetti.style.backgroundColor = color;
                confetti.style.width = size + 'px';
                confetti.style.height = size + 'px';
                confetti.style.animationDuration = animationDuration + 's';
                confetti.style.animationDelay = delay + 's';
                
                // Shape variations
                if (shape === 'circle') {
                    confetti.style.borderRadius = '50%';
                } else if (shape === 'triangle') {
                    confetti.style.width = '0';
                    confetti.style.height = '0';
                    confetti.style.backgroundColor = 'transparent';
                    confetti.style.borderLeft = size/2 + 'px solid transparent';
                    confetti.style.borderRight = size/2 + 'px solid transparent';
                    confetti.style.borderBottom = size + 'px solid ' + color;
                }
                
                document.body.appendChild(confetti);
            }
            
            // Add ripple effect to buttons on click
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const x = e.clientX - e.target.getBoundingClientRect().left;
                    const y = e.clientY - e.target.getBoundingClientRect().top;
                    
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>