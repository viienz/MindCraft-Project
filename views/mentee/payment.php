<?php
session_start();

// Koneksi ke database
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

// Ambil metode pembayaran dari database
$paymentMethods = $conn->query("SELECT * FROM payment_methods WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

// Pisahkan metode pembayaran menjadi Bank dan E-Wallet
$bankMethods = array_filter($paymentMethods, function($method) {
    return in_array($method['code'], ['bri', 'bca', 'mandiri', 'bni']);
});
$ewalletMethods = array_filter($paymentMethods, function($method) {
    return in_array($method['code'], ['gopay', 'dana', 'ovo', 'linkaja']);
});

// Get course details from POST
$courseId = $_POST['course_id'] ?? null;
$courseTitle = $_POST['course_title'] ?? 'Kursus Premium';
$coursePrice = $_POST['course_price'] ?? 0;

// Daftar paket subscription
$subscriptionPlans = [
    [
        'id' => 'course_' . $courseId,
        'name' => $courseTitle,
        'price' => (int)$coursePrice,
        'features' => [
            'Akses penuh ke kursus',
            'Materi lengkap',
            'Sertifikat penyelesaian',
            'Dukungan mentor'
        ]
    ]
];

// Proses form pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'])) {
    // Validasi input
    $errors = [];
    $requiredFields = ['nama', 'telepon', 'subscription_plan', 'metode_pembayaran', 'alamat'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Field " . ucfirst(str_replace('_', ' ', $field)) . " harus diisi";
        }
    }
    
    // Validasi paket subscription
    $selectedPlan = null;
    foreach ($subscriptionPlans as $plan) {
        if ($plan['id'] === $_POST['subscription_plan']) {
            $selectedPlan = $plan;
            break;
        }
    }
    
    if (!$selectedPlan) {
        $errors[] = "Paket subscription tidak valid";
    }
    
    if (empty($errors)) {
        // Generate transaction ID
        $transactionId = 'TRX-' . time() . '-' . rand(1000, 9999);
        
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // 1. Simpan transaksi ke tabel transactions
            $stmt = $conn->prepare("INSERT INTO transactions (
                user_id, transaction_id, customer_name, customer_phone, 
                subscription_type, price, total_amount, payment_method, 
                payment_type, bank_account_name, bank_account_number, 
                ewallet_phone, customer_address, transaction_date, status
            ) VALUES (
                :user_id, :transaction_id, :customer_name, :customer_phone, 
                :subscription_type, :price, :total_amount, :payment_method, 
                :payment_type, :bank_account_name, :bank_account_number, 
                :ewallet_phone, :customer_address, NOW(), 'completed'
            )");
            
            $stmt->execute([
                ':user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
                ':transaction_id' => $transactionId,
                ':customer_name' => $_POST['nama'],
                ':customer_phone' => $_POST['telepon'],
                ':subscription_type' => $selectedPlan['name'],
                ':price' => $selectedPlan['price'],
                ':total_amount' => $selectedPlan['price'],
                ':payment_method' => $_POST['metode_pembayaran'],
                ':payment_type' => in_array($_POST['metode_pembayaran'], ['bri', 'bca', 'mandiri', 'bni']) ? 'bank' : 'ewallet',
                ':bank_account_name' => $_POST['nama_rekening'] ?? null,
                ':bank_account_number' => $_POST['nomor_rekening'] ?? null,
                ':ewallet_phone' => $_POST['nomor_ewallet'] ?? null,
                ':customer_address' => $_POST['alamat']
            ]);
            
            // 2. Enroll student in the course
            $enrollStmt = $conn->prepare("INSERT INTO enrollments 
                (student_id, course_id, enrollment_date, payment_status, payment_amount, status) 
                VALUES 
                (:student_id, :course_id, NOW(), 'paid', :amount, 'active')");
            $enrollStmt->execute([
                ':student_id' => $_SESSION['user_id'],
                ':course_id' => $courseId,
                ':amount' => $selectedPlan['price']
            ]);
            
            // 3. Catat pendapatan untuk mentor
            // Pertama, cari mentor_id dari course yang dibeli
            $mentorStmt = $conn->prepare("SELECT mentor_id FROM courses WHERE id = :course_id");
            $mentorStmt->execute([':course_id' => $courseId]);
            $mentorId = $mentorStmt->fetchColumn();
            
            if ($mentorId) {
                $platformFee = $selectedPlan['price'] * 0.3; // 30% platform fee
                $netAmount = $selectedPlan['price'] - $platformFee;
                
                $earningStmt = $conn->prepare("INSERT INTO earnings (
                    mentor_id, course_id, student_id, transaction_type, 
                    amount, commission_rate, platform_fee, net_amount, 
                    status, payout_status, reference_id, created_at
                ) VALUES (
                    :mentor_id, :course_id, :student_id, 'course_sale',
                    :amount, 70.00, :platform_fee, :net_amount,
                    'completed', 'pending', :reference_id, NOW()
                )");
                
                $earningStmt->execute([
                    ':mentor_id' => $mentorId,
                    ':course_id' => $courseId,
                    ':student_id' => $_SESSION['user_id'],
                    ':amount' => $selectedPlan['price'],
                    ':platform_fee' => $platformFee,
                    ':net_amount' => $netAmount,
                    ':reference_id' => $transactionId
                ]);
            }
            
            // Commit transaction
            $conn->commit();
            
            // Redirect ke halaman sukses dengan parameter transaksi
            header("Location: payment_success.php?transaction_id=" . $transactionId . "&course_id=" . $courseId);
            exit();
        } catch(PDOException $e) {
            $conn->rollBack();
            $errors[] = "Terjadi kesalahan saat menyimpan transaksi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - MindCraft</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --primary-dark: #3a0ca3;
            --secondary: #7209b7;
            --success: #4ade80;
            --danger: #f87171;
            --warning: #fbbf24;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #f1f5f9;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #007bff, #e0e0e0);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .form-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 16px;
            position: relative;
        }

        .form-body {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 20px;
            padding-bottom: 0;
        }

        .form-section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-section-title i {
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }

        .required::after {
            content: " *";
            color: var(--danger);
        }

        input[type="text"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            transition: var(--transition);
            background-color: var(--light);
        }

        input[type="text"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
            background-color: var(--white);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Plan Options */
        .plan-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }

        .plan-option {
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 25px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .plan-option:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .plan-option.selected {
            border: 2px solid var(--primary);
            background-color: var(--primary-light);
            box-shadow: var(--shadow-md);
        }

        .plan-option.selected::after {
            content: "✓";
            position: absolute;
            top: 15px;
            right: 15px;
            width: 28px;
            height: 28px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .plan-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .plan-name {
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 12px;
            color: var(--primary);
        }

        .plan-price {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .plan-price span {
            font-size: 16px;
            font-weight: 500;
            color: var(--gray);
        }

        .plan-features {
            list-style-type: none;
        }

        .plan-features li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 28px;
            font-size: 15px;
        }

        .plan-features li::before {
            content: "✓";
            color: var(--success);
            position: absolute;
            left: 8px;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Payment Methods Tabs */
        .payment-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .payment-tab {
            padding: 12px 20px;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray);
            border-bottom: 3px solid transparent;
            transition: var(--transition);
        }

        .payment-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .payment-tab:hover:not(.active) {
            color: var(--dark);
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-method {
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            padding: 18px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            background: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .payment-method:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
            border-color: var(--primary);
        }

        .payment-method.selected {
            border: 2px solid var(--primary);
            background-color: var(--primary-light);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .payment-method input[type="radio"] {
            display: none;
        }

        .payment-icon {
            width: 80px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 12px;
            filter: grayscale(100%);
            opacity: 0.8;
            transition: var(--transition);
        }

        .payment-method.selected .payment-icon {
            filter: grayscale(0%);
            opacity: 1;
        }

        .payment-name {
            font-weight: 500;
            font-size: 14px;
        }

        /* Payment Details */
        .payment-details {
            background: var(--light);
            border-radius: var(--radius-sm);
            padding: 25px;
            margin-top: 25px;
            display: none;
            animation: fadeIn 0.3s ease-out;
            border: 1px solid #e2e8f0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            border: none;
            padding: 16px;
            font-size: 17px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            margin-top: 20px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        /* Error/Success Messages */
        .alert {
            padding: 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 30px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background-color: #dcfce7;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert i {
            font-size: 20px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                margin: 15px auto;
            }
            
            .form-body {
                padding: 25px;
            }
            
            .plan-options {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }

        /* Floating Labels Effect */
        .float-label {
            position: relative;
        }

        .float-label label {
            position: absolute;
            left: 18px;
            top: 14px;
            color: var(--gray);
            font-size: 15px;
            transition: var(--transition);
            pointer-events: none;
            background: var(--white);
            padding: 0 6px;
        }

        .float-label input:focus + label,
        .float-label input:not(:placeholder-shown) + label {
            top: -10px;
            font-size: 13px;
            color: var(--primary);
        }

        /* Price Highlight */
        .price-highlight {
            position: relative;
            display: inline-block;
        }

        .price-highlight::after {
            content: "";
            position: absolute;
            bottom: 6px;
            left: 0;
            width: 100%;
            height: 10px;
            background: rgba(67, 97, 238, 0.15);
            z-index: -1;
            border-radius: 4px;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge-primary {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Lengkapi Pembayaran</h1>
            <p>Pilih paket subscription dan metode pembayaran yang sesuai</p>
        </div>
        
        <div class="form-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="payment.php" method="post">
                <!-- Hidden fields for course data -->
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($courseId ?? ''); ?>">
                <input type="hidden" name="course_title" value="<?php echo htmlspecialchars($courseTitle); ?>">
                <input type="hidden" name="course_price" value="<?php echo htmlspecialchars($coursePrice); ?>">
                
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user-circle"></i>
                        <span>Informasi Pribadi</span>
                    </div>
                    
                    <div class="form-group float-label">
                        <input type="text" id="nama" name="nama" placeholder=" " required>
                        <label for="nama" class="required">Nama Lengkap</label>
                    </div>
                    
                    <div class="form-group float-label">
                        <input type="tel" id="telepon" name="telepon" placeholder=" " required>
                        <label for="telepon" class="required">Nomor Telepon</label>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat" class="required">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-box-open"></i>
                        <span>Paket Subscription Dipilih</span>
                    </div>
                    
                    <div class="plan-options">
                        <?php foreach ($subscriptionPlans as $plan): ?>
                            <label class="plan-option">
                                <input type="radio" name="subscription_plan" value="<?php echo $plan['id']; ?>" required checked>
                                <div class="plan-name"><?php echo $plan['name']; ?></div>
                                <div class="plan-price"><span class="price-highlight">Rp <?php echo number_format($plan['price'], 0, ',', '.'); ?></span></div>
                                <ul class="plan-features">
                                    <?php foreach ($plan['features'] as $feature): ?>
                                        <li><?php echo $feature; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-credit-card"></i>
                        <span>Metode Pembayaran</span>
                    </div>
                    
                    <div class="payment-tabs">
                        <div class="payment-tab active" data-tab="bank">Transfer Bank</div>
                        <div class="payment-tab" data-tab="ewallet">E-Wallet</div>
                    </div>
                    
                    <div id="bank-tab" class="tab-content active">
                        <div class="payment-methods">
                            <?php foreach ($bankMethods as $method): ?>
                                <label class="payment-method">
                                    <input type="radio" name="metode_pembayaran" value="<?php echo $method['code']; ?>" required>
                                    <img src="/MindCraft-Project/assets/img/<?php echo $method['code']; ?>.png" alt="<?php echo $method['name']; ?>" class="payment-icon">
                                    <div class="payment-name"><?php echo $method['name']; ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div id="bank-details" class="payment-details">
                            <div class="form-group float-label">
                                <input type="text" id="nama_rekening" name="nama_rekening" placeholder=" ">
                                <label for="nama_rekening">Nama Pemilik Rekening</label>
                            </div>
                            
                            <div class="form-group float-label">
                                <input type="text" id="nomor_rekening" name="nomor_rekening" placeholder=" ">
                                <label for="nomor_rekening">Nomor Rekening</label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="ewallet-tab" class="tab-content">
                        <div class="payment-methods">
                            <?php foreach ($ewalletMethods as $method): ?>
                                <label class="payment-method">
                                    <input type="radio" name="metode_pembayaran" value="<?php echo $method['code']; ?>" required>
                                    <img src="/MindCraft-Project/assets/img/<?php echo $method['code']; ?>.png" alt="<?php echo $method['name']; ?>" class="payment-icon">
                                    <div class="payment-name"><?php echo $method['name']; ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div id="ewallet-details" class="payment-details">
                            <div class="form-group float-label">
                                <input type="text" id="nomor_ewallet" name="nomor_ewallet" placeholder=" ">
                                <label for="nomor_ewallet">Nomor E-Wallet</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <span>Bayar Sekarang</span>
                    <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Plan selection
            const planOptions = document.querySelectorAll('.plan-option');
            planOptions.forEach(option => {
                option.addEventListener('click', function() {
                    planOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
            
            // Select the first plan by default
            if (planOptions.length > 0) {
                planOptions[0].click();
            }
            
            // Payment method selection
            const paymentMethods = document.querySelectorAll('.payment-method');
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    // Remove selected class from all methods in the same tab
                    const tabContent = this.closest('.tab-content');
                    tabContent.querySelectorAll('.payment-method').forEach(m => {
                        m.classList.remove('selected');
                    });
                    
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                    
                    // Show appropriate details
                    const selectedMethod = this.querySelector('input').value;
                    const bankDetails = document.getElementById('bank-details');
                    const ewalletDetails = document.getElementById('ewallet-details');
                    
                    bankDetails.style.display = 'none';
                    ewalletDetails.style.display = 'none';
                    
                    if (['bri', 'bca', 'mandiri', 'bni'].includes(selectedMethod)) {
                        bankDetails.style.display = 'block';
                    } else if (['gopay', 'dana', 'ovo', 'linkaja'].includes(selectedMethod)) {
                        ewalletDetails.style.display = 'block';
                    }
                });
            });
            
            // Select first payment method by default
            if (paymentMethods.length > 0) {
                paymentMethods[0].click();
            }
            
            // Tab switching
            const tabs = document.querySelectorAll('.payment-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab') + '-tab';
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    document.getElementById(tabId).classList.add('active');
                    
                    // Reset selected payment method when switching tabs
                    document.querySelectorAll('.payment-method').forEach(method => {
                        method.classList.remove('selected');
                    });
                    document.getElementById('bank-details').style.display = 'none';
                    document.getElementById('ewallet-details').style.display = 'none';
                    
                    // Select first payment method in the new tab
                    const firstMethod = document.querySelector(`#${tabId} .payment-method`);
                    if (firstMethod) {
                        firstMethod.click();
                    }
                });
            });
            
            // Initialize floating labels
            const floatInputs = document.querySelectorAll('.float-label input');
            floatInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentNode.querySelector('label').classList.add('active');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentNode.querySelector('label').classList.remove('active');
                    }
                });
                
                // Check on page load if there's a value
                if (input.value) {
                    input.parentNode.querySelector('label').classList.add('active');
                }
            });
        });
    </script>
</body>
</html>