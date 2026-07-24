<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Mentor') {
    header("Location: /MindCraft-Project/views/landingpage/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../../config/Database.php';
$database = new Database();
$db = $database->connect();

// Get mentor ID
$mentorId = $_SESSION['user_id'];

// Query for total earnings (all completed transactions)
$query = "SELECT SUM(net_amount) as total_earnings 
          FROM earnings 
          WHERE mentor_id = :mentor_id 
          AND status = 'completed'";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$totalEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['total_earnings'] ?? 0;

// Query for total withdrawals (all completed withdrawals)
$query = "SELECT SUM(amount) as total_withdrawals 
          FROM withdrawals 
          WHERE mentor_id = :mentor_id 
          AND status = 'completed'";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$totalWithdrawals = $stmt->fetch(PDO::FETCH_ASSOC)['total_withdrawals'] ?? 0;

// Calculate available balance
$availableBalance = $totalEarnings - $totalWithdrawals;

// Query for mentor's payout settings
$query = "SELECT minimum_payout, payout_method, 
                 payout_schedule, tax_information
          FROM mentor_settings
          WHERE mentor_id = :mentor_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$payoutSettings = $stmt->fetch(PDO::FETCH_ASSOC);

// Query for verified bank accounts
$query = "SELECT id, bank_name, account_number, account_name 
          FROM mentor_bank_accounts 
          WHERE mentor_id = :mentor_id AND is_verified = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query for verified e-wallets
$query = "SELECT id, type, name, phone_number 
          FROM mentor_ewallets 
          WHERE mentor_id = :mentor_id AND is_verified = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':mentor_id', $mentorId);
$stmt->execute();
$ewallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$minimumWithdrawal = $payoutSettings['minimum_payout'] ?? 100000.00;
$payoutMethod = $payoutSettings['payout_method'] ?? 'bank_transfer';
$selectedBankAccount = null;
$selectedEwallet = null;

// Handle withdrawal request
$withdrawalSuccess = false;
$withdrawalError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $selectedMethod = filter_input(INPUT_POST, 'method', FILTER_SANITIZE_STRING);
    $accountId = filter_input(INPUT_POST, 'account_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate amount
    if ($amount <= 0) {
        $withdrawalError = 'Jumlah penarikan harus lebih dari 0';
    } elseif ($amount > $availableBalance) {
        $withdrawalError = 'Jumlah penarikan melebihi saldo tersedia';
    } elseif ($amount < $minimumWithdrawal) {
        $withdrawalError = 'Minimum penarikan adalah ' . number_format($minimumWithdrawal, 0, ',', '.');
    } else {
        // Validate payment method and account
        if ($selectedMethod === 'bank_transfer') {
            if (empty($bankAccounts)) {
                $withdrawalError = 'Anda belum mengatur akun bank untuk penarikan';
            } else {
                foreach ($bankAccounts as $account) {
                    if ($account['id'] == $accountId) {
                        $selectedBankAccount = $account;
                        break;
                    }
                }
                
                if (!$selectedBankAccount) {
                    $withdrawalError = 'Akun bank yang dipilih tidak valid';
                }
            }
        } else {
            if (empty($ewallets)) {
                $withdrawalError = 'Anda belum mengatur e-wallet untuk penarikan';
            } else {
                foreach ($ewallets as $ewallet) {
                    if ($ewallet['id'] == $accountId) {
                        $selectedEwallet = $ewallet;
                        break;
                    }
                }
                
                if (!$selectedEwallet) {
                    $withdrawalError = 'E-wallet yang dipilih tidak valid';
                }
            }
        }
        
        if (empty($withdrawalError)) {
            try {
                $db->beginTransaction();
                
                // Create withdrawal record in withdrawals table
                $query = "INSERT INTO withdrawals 
                          (mentor_id, amount, method, account_details, status, reference_id, created_at, updated_at, processed_at, completed_at)
                          VALUES 
                          (:mentor_id, :amount, :method, :account_details, 'completed', :reference_id, NOW(), NOW(), NOW(), NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':method', $selectedMethod);
                
                $accountDetails = json_encode($selectedMethod === 'bank_transfer' ? $selectedBankAccount : $selectedEwallet);
                $stmt->bindParam(':account_details', $accountDetails);
                
                $referenceId = 'WD-' . date('YmdHis') . '-' . mt_rand(100, 999);
                $stmt->bindParam(':reference_id', $referenceId);
                $stmt->execute();
                
                // Create negative transaction in earnings table
                $query = "INSERT INTO earnings 
                          (mentor_id, transaction_type, amount, commission_rate, platform_fee, net_amount, status, payout_status, withdrawal_method, withdrawal_account, description, created_at, updated_at, payout_date)
                          VALUES 
                          (:mentor_id, 'withdrawal', :amount, 0, 0, :net_amount, 'completed', 'paid', :withdrawal_method, :withdrawal_account, 'Penarikan saldo mentor', NOW(), NOW(), NOW())";
                $stmt = $db->prepare($query);
                $netAmount = -$amount;
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->bindParam(':amount', $amount);
                $stmt->bindParam(':net_amount', $netAmount);
                $stmt->bindParam(':withdrawal_method', $selectedMethod);
                
                $withdrawalAccount = $selectedMethod === 'bank_transfer' 
                    ? $selectedBankAccount['bank_name'] . ' - ' . $selectedBankAccount['account_number']
                    : $selectedEwallet['type'] . ' - ' . $selectedEwallet['phone_number'];
                $stmt->bindParam(':withdrawal_account', $withdrawalAccount);
                $stmt->execute();
                
                $db->commit();
                $withdrawalSuccess = true;
                
                // Update available balance after successful withdrawal
                $availableBalance -= $amount;
                
            } catch (Exception $e) {
                $db->rollBack();
                $withdrawalError = 'Terjadi kesalahan saat memproses penarikan: ' . $e->getMessage();
            }
        }
    }
}

// Function to format currency
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarik Dana - Mentor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_dashboard.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/mentor_tarik-dana.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header class="top-header">
        <div class="logo">MindCraft</div>
        <div class="header-right">
            <div class="header-profile">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'M', 0, 1)); ?>
                </div>
                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Mentor'); ?></span>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">   
        <ul class="sidebar-menu">
            <li>
                <a href="/MindCraft-Project/views/mentor/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/kursus-saya.php">
                    <i class="fas fa-book"></i>
                    <span>Kursus Saya</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/buat-kursus-baru.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Buat Kursus Baru</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/pendapatan.php" class="active">
                    <i class="fas fa-wallet"></i>
                    <span>Pendapatan</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/analitik.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Analitik</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/pengaturan.php">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            <li>
                <a href="/MindCraft-Project/views/mentor/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="sidebar-version">v1.0.0</div>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header">
            <div class="header-title-wrapper">
                <h1>Tarik Dana</h1>
                <a href="/MindCraft-Project/views/mentor/pendapatan.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="content-body">
            <?php if ($withdrawalSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Penarikan berhasil diproses!</h3>
                        <p>Dana telah berhasil dikirim ke akun Anda. Penarikan ini otomatis terverifikasi dan diproses.</p>
                    </div>
                    <a href="/MindCraft-Project/views/mentor/pendapatan.php" class="btn-close">&times;</a>
                </div>
            <?php elseif ($withdrawalError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>Gagal mengajukan penarikan</h3>
                        <p><?php echo htmlspecialchars($withdrawalError); ?></p>
                    </div>
                    <a href="#" class="btn-close">&times;</a>
                </div>
            <?php endif; ?>

            <div class="withdrawal-container">
                <div class="withdrawal-card">
                    <div class="balance-summary">
                        <div class="balance-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="balance-details">
                            <div class="balance-title">Saldo Tersedia</div>
                            <div class="balance-amount"><?php echo formatRupiah($availableBalance); ?></div>
                            <div class="balance-info">
                                Minimum penarikan: <?php echo formatRupiah($minimumWithdrawal); ?>
                                <?php if ($availableBalance < $minimumWithdrawal): ?>
                                    <span class="warning-text">(Saldo belum mencukupi untuk penarikan)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="withdrawal-form">
                        <div class="form-group">
                            <label for="amount">Jumlah Penarikan</label>
                            <div class="input-with-suffix">
                                <input type="number" id="amount" name="amount" 
                                       min="<?php echo $minimumWithdrawal; ?>" 
                                       max="<?php echo $availableBalance; ?>" 
                                       step="1000" 
                                       value="<?php echo min($availableBalance, max($minimumWithdrawal, $availableBalance / 2)); ?>"
                                       required>
                                <span class="input-suffix">IDR</span>
                            </div>
                            <div class="amount-slider">
                                <input type="range" id="amountRange" min="<?php echo $minimumWithdrawal; ?>" 
                                       max="<?php echo $availableBalance; ?>" 
                                       step="1000" 
                                       value="<?php echo min($availableBalance, max($minimumWithdrawal, $availableBalance / 2)); ?>">
                                <div class="slider-labels">
                                    <span><?php echo formatRupiah($minimumWithdrawal); ?></span>
                                    <span><?php echo formatRupiah($availableBalance); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="method">Metode Penarikan</label>
                            <div class="method-selector">
                                <div class="method-option">
                                    <input type="radio" id="bankMethod" name="method" value="bank_transfer" 
                                           <?php echo $payoutMethod === 'bank_transfer' ? 'checked' : ''; ?>>
                                    <label for="bankMethod">
                                        <i class="fas fa-university"></i>
                                        <span>Transfer Bank</span>
                                        <div class="method-detail">
                                            <?php if (!empty($bankAccounts)): ?>
                                                <select name="account_id" class="account-select" required>
                                                    <?php foreach ($bankAccounts as $account): ?>
                                                        <option value="<?php echo $account['id']; ?>">
                                                            <?php echo htmlspecialchars($account['bank_name'] . ' - ' . $account['account_name'] . ' (' . $account['account_number'] . ')'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="warning-text">Belum ada akun bank yang terdaftar</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                                <div class="method-option">
                                    <input type="radio" id="ewalletMethod" name="method" value="gopay" 
                                           <?php echo $payoutMethod !== 'bank_transfer' ? 'checked' : ''; ?>>
                                    <label for="ewalletMethod">
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>E-Wallet</span>
                                        <div class="method-detail">
                                            <?php if (!empty($ewallets)): ?>
                                                <select name="account_id" class="account-select" required>
                                                    <?php foreach ($ewallets as $ewallet): ?>
                                                        <option value="<?php echo $ewallet['id']; ?>">
                                                            <?php echo htmlspecialchars(ucfirst($ewallet['type'])) . ' - ' . $ewallet['name'] . ' (' . $ewallet['phone_number'] . ')'; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="warning-text">Belum ada e-wallet yang terdaftar</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <a href="/MindCraft-Project/views/mentor/pengaturan.php" class="link-change-method">
                                <i class="fas fa-cog"></i> Ubah metode pembayaran di Pengaturan
                            </a>
                        </div>

                        <div class="form-group">
                            <label>Jadwal Pembayaran</label>
                            <div class="schedule-info">
                                <i class="fas fa-calendar-alt"></i>
                                <span>
                                    <?php 
                                    $schedule = $payoutSettings['payout_schedule'] ?? 'monthly';
                                    echo ucfirst($schedule === 'weekly' ? 'Mingguan' : 
                                          ($schedule === 'biweekly' ? 'Dua Minggu Sekali' : 'Bulanan')); 
                                    ?>
                                    (Setiap <?php echo $schedule === 'weekly' ? 'Senin' : 
                                              ($schedule === 'biweekly' ? 'Senin kedua' : 'Tanggal 5'); ?>)
                                </span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit" 
                                <?php echo ($availableBalance < $minimumWithdrawal || (empty($bankAccounts) && empty($ewallets))) ? 'disabled' : ''; ?>>
                                <i class="fas fa-paper-plane"></i> Ajukan Penarikan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Informasi Penarikan</h3>
                    <ul class="info-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Penarikan akan diproses otomatis setelah diverifikasi</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Biaya penarikan ditanggung oleh sistem</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Pastikan informasi pembayaran Anda sudah benar</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Dana akan masuk dalam 1-3 jam kerja setelah penarikan</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="/MindCraft-Project/assets/js/mentor_dashboard.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Sync amount input and range slider
            const amountInput = document.getElementById('amount');
            const amountRange = document.getElementById('amountRange');
            
            if (amountInput && amountRange) {
                amountInput.addEventListener('input', function() {
                    amountRange.value = this.value;
                });
                
                amountRange.addEventListener('input', function() {
                    amountInput.value = this.value;
                });
            }
            
            // Format amount input on blur
            if (amountInput) {
                amountInput.addEventListener('blur', function() {
                    const min = parseFloat(this.min);
                    const max = parseFloat(this.max);
                    let value = parseFloat(this.value) || 0;
                    
                    // Ensure value is within bounds
                    value = Math.max(min, Math.min(max, value));
                    
                    // Round to nearest 1000
                    value = Math.round(value / 1000) * 1000;
                    
                    this.value = value;
                });
            }
            
            // Close alert buttons
            document.querySelectorAll('.btn-close').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.closest('.alert').style.display = 'none';
                });
            });
        });
    </script>
</body>
</html>