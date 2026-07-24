<?php
/**
 * Withdrawal related functions for mentor dashboard
 * File: withdrawal_functions.php
 * Location: includes/mentor/
 */

/**
 * Format currency to Indonesian Rupiah
 */
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Get payment method icon
 */
function getMethodIcon($method) {
    $icons = [
        'bank_transfer' => '🏦',
        'gopay' => '💚',
        'ovo' => '💜',
        'dana' => '💙',
        'shopeepay' => '🧡'
    ];
    return $icons[$method] ?? '🏦';
}

/**
 * Get processing time for each method
 */
function getProcessingTime($method) {
    $times = [
        'bank_transfer' => '1-2 hari kerja',
        'gopay' => 'Instan',
        'ovo' => 'Instan',
        'dana' => 'Instan',
        'shopeepay' => 'Instan'
    ];
    return $times[$method] ?? '1-2 hari kerja';
}

/**
 * Get Available Balance
 * Returns: ['available' => float, 'total_paid' => float, 'pending' => float]
 */
function getAvailableBalance($db, $mentorId) {
    try {
        $stmt = $db->prepare("
            SELECT 
                SUM(CASE WHEN status = 'completed' AND payout_status = 'pending' THEN net_amount ELSE 0 END) as available_balance,
                SUM(CASE WHEN status = 'completed' AND payout_status = 'paid' THEN net_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN transaction_type = 'withdrawal' AND status = 'pending' THEN ABS(net_amount) ELSE 0 END) as pending_withdrawals
            FROM earnings 
            WHERE mentor_id = ?
        ");
        $stmt->execute([$mentorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'available' => (float)($result['available_balance'] ?? 0),
            'total_paid' => (float)($result['total_paid'] ?? 0),
            'pending' => (float)($result['pending_withdrawals'] ?? 0)
        ];
    } catch (Exception $e) {
        error_log("Error getting available balance: " . $e->getMessage());
        return [
            'available' => 0,
            'total_paid' => 0,
            'pending' => 0
        ];
    }
}

/**
 * Get Withdrawal Settings
 * Returns: ['minimum_payout' => float, 'preferred_method' => string, 'schedule' => string]
 */
function getWithdrawalSettings($db, $mentorId) {
    try {
        $stmt = $db->prepare("
            SELECT 
                minimum_payout,
                payout_method,
                payout_schedule
            FROM mentor_settings 
            WHERE mentor_id = ?
        ");
        $stmt->execute([$mentorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'minimum_payout' => (float)($result['minimum_payout'] ?? 100000),
                'preferred_method' => $result['payout_method'] ?? 'bank_transfer',
                'schedule' => $result['payout_schedule'] ?? 'monthly'
            ];
        }
        
        // Default values if no settings found
        return [
            'minimum_payout' => 100000,
            'preferred_method' => 'bank_transfer',
            'schedule' => 'monthly'
        ];
    } catch (Exception $e) {
        error_log("Error getting withdrawal settings: " . $e->getMessage());
        return [
            'minimum_payout' => 100000,
            'preferred_method' => 'bank_transfer',
            'schedule' => 'monthly'
        ];
    }
}

/**
 * Get Saved Payment Methods
 * Returns: ['bank_accounts' => array, 'ewallets' => array]
 */
function getSavedPaymentMethods($db, $mentorId) {
    try {
        // Get mentor profile
        $stmt = $db->prepare("
            SELECT full_name, phone
            FROM mentor_profiles
            WHERE user_id = ?
        ");
        $stmt->execute([$mentorId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get bank accounts
        $bankAccounts = [];
        $stmt = $db->prepare("
            SELECT id, bank_name, account_number, account_name, is_verified
            FROM mentor_bank_accounts
            WHERE mentor_id = ?
        ");
        $stmt->execute([$mentorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bankAccounts[] = [
                'id' => $row['id'],
                'bank_name' => $row['bank_name'],
                'account_number' => $row['account_number'],
                'account_name' => $row['account_name'] ?? ($profile['full_name'] ?? ''),
                'is_verified' => (bool)$row['is_verified']
            ];
        }
        
        // Get e-wallets
        $ewallets = [];
        $stmt = $db->prepare("
            SELECT id, type, name, phone_number, is_verified
            FROM mentor_ewallets
            WHERE mentor_id = ?
        ");
        $stmt->execute([$mentorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ewallets[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'name' => $row['name'],
                'phone' => $row['phone_number'] ?? ($profile['phone'] ?? ''),
                'is_verified' => (bool)$row['is_verified']
            ];
        }
        
        return [
            'bank_accounts' => $bankAccounts,
            'ewallets' => $ewallets
        ];
        
    } catch (Exception $e) {
        error_log("Error getting saved methods: " . $e->getMessage());
        return [
            'bank_accounts' => [],
            'ewallets' => []
        ];
    }
}

/**
 * Process Withdrawal Request
 * Returns: ['success' => bool, 'errors' => array, 'reference_id' => string (if success)]
 */
function processWithdrawalRequest($data, $db, $mentorId) {
    $errors = [];
    
    // Validation
    $amount = floatval($data['amount'] ?? 0);
    $method = $data['withdrawal_method'] ?? '';
    $accountInfo = $data['account_info'] ?? '';
    $description = trim($data['description'] ?? '');
    
    // Check available balance
    $balance = getAvailableBalance($db, $mentorId);
    if ($amount <= 0) {
        $errors[] = 'Jumlah penarikan harus lebih dari 0';
    } elseif ($amount > $balance['available']) {
        $errors[] = 'Jumlah melebihi saldo tersedia';
    }
    
    // Validate method
    if (empty($method)) {
        $errors[] = 'Pilih metode penarikan';
    }
    
    if (empty($accountInfo)) {
        $errors[] = 'Pilih atau masukkan informasi akun tujuan';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Process withdrawal
    try {
        // Generate reference ID
        $referenceId = 'WD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert withdrawal record
        $stmt = $db->prepare("
            INSERT INTO earnings 
            (mentor_id, transaction_type, amount, net_amount, status, payout_status, 
             withdrawal_method, withdrawal_account, description, reference_id) 
            VALUES (?, 'withdrawal', ?, ?, 'pending', 'pending', ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $mentorId,
            $amount,
            -$amount, // Negative amount for withdrawal
            $method,
            $accountInfo,
            $description,
            $referenceId
        ]);
        
        if ($success) {
            return [
                'success' => true,
                'reference_id' => $referenceId,
                'amount' => $amount,
                'method' => $method,
                'account' => $accountInfo
            ];
        }
        
        return ['success' => false, 'errors' => ['Gagal memproses penarikan. Silakan coba lagi.']];
        
    } catch (Exception $e) {
        error_log("Withdrawal processing error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Terjadi kesalahan sistem. Silakan coba lagi.']];
    }
}

/**
 * Get Withdrawal History
 */
function getWithdrawalHistory($db, $mentorId, $limit = 10) {
    try {
        $stmt = $db->prepare("
            SELECT 
                e.id,
                e.reference_id,
                e.amount,
                e.net_amount,
                e.withdrawal_method,
                e.withdrawal_account,
                e.description,
                e.status,
                e.payout_status,
                e.created_at,
                e.processed_at
            FROM earnings e
            WHERE e.mentor_id = ?
            AND e.transaction_type = 'withdrawal'
            ORDER BY e.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$mentorId, $limit]);
        
        $withdrawals = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $withdrawals[] = [
                'id' => $row['id'],
                'reference_id' => $row['reference_id'],
                'amount' => abs($row['amount']),
                'net_amount' => abs($row['net_amount']),
                'method' => $row['withdrawal_method'],
                'account' => $row['withdrawal_account'],
                'description' => $row['description'],
                'status' => $row['status'],
                'payout_status' => $row['payout_status'],
                'created_at' => $row['created_at'],
                'processed_at' => $row['processed_at']
            ];
        }
        
        return $withdrawals;
        
    } catch (Exception $e) {
        error_log("Error getting withdrawal history: " . $e->getMessage());
        return [];
    }
}