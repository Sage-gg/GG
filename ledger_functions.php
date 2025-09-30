<?php
require_once 'db.php';

// ===================
// JOURNAL ENTRIES FUNCTIONS
// ===================

// Get all journal entries with optional filters
function getJournalEntries($fromDate = null, $toDate = null, $accountCode = null, $limit = 50, $offset = 0) {
    global $conn;
    
    $sql = "SELECT je.*, coa.account_name 
            FROM journal_entries je 
            JOIN chart_of_accounts coa ON je.account_code = coa.account_code 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($fromDate) {
        $sql .= " AND je.date >= ?";
        $params[] = $fromDate;
        $types .= "s";
    }
    
    if ($toDate) {
        $sql .= " AND je.date <= ?";
        $params[] = $toDate;
        $types .= "s";
    }
    
    if ($accountCode) {
        $sql .= " AND je.account_code = ?";
        $params[] = $accountCode;
        $types .= "s";
    }
    
    $sql .= " ORDER BY je.date DESC, je.id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Add new journal entry
function addJournalEntry($date, $reference, $accountCode, $amount, $type, $description) {
    global $conn;
    
    // Generate entry ID if not provided
    if (empty($reference)) {
        $reference = generateEntryId();
    }
    
    $debit = ($type === 'debit') ? $amount : 0.00;
    $credit = ($type === 'credit') ? $amount : 0.00;
    
    $sql = "INSERT INTO journal_entries (entry_id, date, account_code, debit, credit, description, approved_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Admin', 'Posted')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdds", $reference, $date, $accountCode, $debit, $credit, $description);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'id' => $conn->insert_id,
            'entry_id' => $reference,
            'message' => 'Journal entry added successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Database error: ' . $stmt->error
        ];
    }
}

// Get single journal entry
function getJournalEntry($id) {
    global $conn;
    
    $sql = "SELECT je.*, coa.account_name 
            FROM journal_entries je 
            JOIN chart_of_accounts coa ON je.account_code = coa.account_code 
            WHERE je.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Update journal entry
function updateJournalEntry($id, $date, $reference, $accountCode, $amount, $type, $description) {
    global $conn;
    
    $debit = ($type === 'debit') ? $amount : 0.00;
    $credit = ($type === 'credit') ? $amount : 0.00;
    
    $sql = "UPDATE journal_entries 
            SET date = ?, entry_id = ?, account_code = ?, debit = ?, credit = ?, description = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddsi", $date, $reference, $accountCode, $debit, $credit, $description, $id);
    
    return $stmt->execute();
}

// Delete journal entry
function deleteJournalEntry($id) {
    global $conn;
    
    $sql = "DELETE FROM journal_entries WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// Generate unique entry ID
function generateEntryId() {
    global $conn;
    
    $sql = "SELECT MAX(CAST(SUBSTRING(entry_id, 4) AS UNSIGNED)) as max_num FROM journal_entries WHERE entry_id LIKE 'GL-%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $nextNum = ($row['max_num'] ?? 1000) + 1;
    return "GL-" . $nextNum;
}

// ===================
// CHART OF ACCOUNTS FUNCTIONS - FIXED
// ===================

// Get all accounts - FIXED to show only active accounts
function getChartOfAccounts() {
    global $conn;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE status = 'Active' ORDER BY account_code";
    $result = $conn->query($sql);
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get single account
function getAccount($accountCode) {
    global $conn;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE account_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $accountCode);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Add new account
function addAccount($accountCode, $accountName, $accountType, $description) {
    global $conn;
    
    // Check if account code already exists
    $checkSql = "SELECT account_code FROM chart_of_accounts WHERE account_code = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $accountCode);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        return false; // Account code already exists
    }
    
    $sql = "INSERT INTO chart_of_accounts (account_code, account_name, account_type, description, status) 
            VALUES (?, ?, ?, ?, 'Active')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $accountCode, $accountName, $accountType, $description);
    
    return $stmt->execute();
}

// Update account
function updateAccount($originalCode, $newCode, $accountName, $accountType, $description) {
    global $conn;
    
    $sql = "UPDATE chart_of_accounts 
            SET account_code = ?, account_name = ?, account_type = ?, description = ? 
            WHERE account_code = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $newCode, $accountName, $accountType, $description, $originalCode);
    
    return $stmt->execute();
}

// FIXED Delete account function - Always delete permanently
function deleteAccount($accountCode) {
    global $conn;
    
    try {
        $conn->autocommit(false); // Start transaction
        
        // First, delete all associated journal entries
        $deleteJournalSql = "DELETE FROM journal_entries WHERE account_code = ?";
        $deleteJournalStmt = $conn->prepare($deleteJournalSql);
        
        if (!$deleteJournalStmt) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Database prepare error: ' . $conn->error
            ];
        }
        
        $deleteJournalStmt->bind_param("s", $accountCode);
        $deleteJournalStmt->execute();
        
        // Then delete the account itself
        $deleteAccountSql = "DELETE FROM chart_of_accounts WHERE account_code = ?";
        $deleteAccountStmt = $conn->prepare($deleteAccountSql);
        
        if (!$deleteAccountStmt) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Database prepare error: ' . $conn->error
            ];
        }
        
        $deleteAccountStmt->bind_param("s", $accountCode);
        
        if ($deleteAccountStmt->execute()) {
            if ($deleteAccountStmt->affected_rows > 0) {
                $conn->commit(); // Commit transaction
                $conn->autocommit(true);
                return [
                    'success' => true,
                    'message' => 'Account and all associated transactions deleted successfully'
                ];
            } else {
                $conn->rollback();
                $conn->autocommit(true);
                return [
                    'success' => false,
                    'message' => 'Account not found'
                ];
            }
        } else {
            $conn->rollback();
            $conn->autocommit(true);
            return [
                'success' => false,
                'message' => 'Failed to delete account: ' . $deleteAccountStmt->error
            ];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        return [
            'success' => false,
            'message' => 'Database error occurred: ' . $e->getMessage()
        ];
    }
}

// ===================
// LIQUIDATION RECORDS FUNCTIONS
// ===================

// Get all liquidation records
function getLiquidationRecords($limit = 50, $offset = 0) {
    global $conn;
    
    $sql = "SELECT * FROM liquidation_records ORDER BY date DESC, id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Add liquidation record
function addLiquidationRecord($date, $liquidationId, $employee, $purpose, $totalAmount, $status = 'Pending') {
    global $conn;
    
    // Generate liquidation ID if not provided
    if (empty($liquidationId)) {
        $liquidationId = generateLiquidationId();
    }
    
    $sql = "INSERT INTO liquidation_records (liquidation_id, date, employee, purpose, total_amount, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssds", $liquidationId, $date, $employee, $purpose, $totalAmount, $status);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'id' => $conn->insert_id,
            'liquidation_id' => $liquidationId,
            'message' => 'Liquidation record added successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Database error: ' . $stmt->error
        ];
    }
}

// Get single liquidation record
function getLiquidationRecord($id) {
    global $conn;
    
    $sql = "SELECT * FROM liquidation_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Update liquidation record
function updateLiquidationRecord($id, $date, $liquidationId, $employee, $purpose, $totalAmount, $status) {
    global $conn;
    
    $sql = "UPDATE liquidation_records 
            SET date = ?, liquidation_id = ?, employee = ?, purpose = ?, total_amount = ?, status = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdsi", $date, $liquidationId, $employee, $purpose, $totalAmount, $status, $id);
    
    return $stmt->execute();
}

// Delete liquidation record
function deleteLiquidationRecord($id) {
    global $conn;
    
    $sql = "DELETE FROM liquidation_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// Generate unique liquidation ID
function generateLiquidationId() {
    global $conn;
    
    $year = date('Y');
    $sql = "SELECT MAX(CAST(SUBSTRING(liquidation_id, -3) AS UNSIGNED)) as max_num 
            FROM liquidation_records 
            WHERE liquidation_id LIKE 'LQ-$year-%'";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $nextNum = ($row['max_num'] ?? 0) + 1;
    return "LQ-" . $year . "-" . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

// ===================
// REPORTING FUNCTIONS
// ===================

// Get ledger summary
function getLedgerSummary($fromDate = null, $toDate = null) {
    global $conn;
    
    $sql = "SELECT 
                SUM(debit) as total_debit,
                SUM(credit) as total_credit,
                (SUM(credit) - SUM(debit)) as net_balance
            FROM journal_entries 
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($fromDate) {
        $sql .= " AND date >= ?";
        $params[] = $fromDate;
        $types .= "s";
    }
    
    if ($toDate) {
        $sql .= " AND date <= ?";
        $params[] = $toDate;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get trial balance
function getTrialBalance($asOfDate = null) {
    global $conn;
    
    $sql = "SELECT 
                coa.account_code,
                coa.account_name,
                coa.account_type,
                COALESCE(SUM(je.debit), 0) as total_debit,
                COALESCE(SUM(je.credit), 0) as total_credit,
                (COALESCE(SUM(je.credit), 0) - COALESCE(SUM(je.debit), 0)) as balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_entries je ON coa.account_code = je.account_code";
    
    if ($asOfDate) {
        $sql .= " AND je.date <= '$asOfDate'";
    }
    
    $sql .= " WHERE coa.status = 'Active'
              GROUP BY coa.account_code, coa.account_name, coa.account_type
              ORDER BY coa.account_code";
    
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get account ledger
function getAccountLedger($accountCode, $fromDate = null, $toDate = null) {
    global $conn;
    
    $sql = "SELECT je.*, coa.account_name 
            FROM journal_entries je 
            JOIN chart_of_accounts coa ON je.account_code = coa.account_code 
            WHERE je.account_code = ?";
    
    $params = [$accountCode];
    $types = "s";
    
    if ($fromDate) {
        $sql .= " AND je.date >= ?";
        $params[] = $fromDate;
        $types .= "s";
    }
    
    if ($toDate) {
        $sql .= " AND je.date <= ?";
        $params[] = $toDate;
        $types .= "s";
    }
    
    $sql .= " ORDER BY je.date ASC, je.id ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $entries = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate running balance
    return calculateRunningBalance($entries);
}

// Calculate running balance for ledger entries
function calculateRunningBalance($entries) {
    $runningBalance = 0;
    
    foreach ($entries as &$entry) {
        $runningBalance += ($entry['credit'] - $entry['debit']);
        $entry['running_balance'] = $runningBalance;
    }
    
    return $entries;
}

// ===================
// UTILITY FUNCTIONS
// ===================

// Format currency - Protected against redeclaration
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2);
    }
}

// Validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Get account balance
function getAccountBalance($accountCode, $asOfDate = null) {
    global $conn;
    
    $sql = "SELECT 
                SUM(debit) as total_debit,
                SUM(credit) as total_credit
            FROM journal_entries 
            WHERE account_code = ?";
    
    $params = [$accountCode];
    $types = "s";
    
    if ($asOfDate) {
        $sql .= " AND date <= ?";
        $params[] = $asOfDate;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result()->fetch_assoc();
    
    return [
        'debit' => $result['total_debit'] ?? 0,
        'credit' => $result['total_credit'] ?? 0,
        'balance' => ($result['total_credit'] ?? 0) - ($result['total_debit'] ?? 0)
    ];
}

// Get transaction count
function getTransactionCount($accountCode = null, $fromDate = null, $toDate = null) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM journal_entries WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($accountCode) {
        $sql .= " AND account_code = ?";
        $params[] = $accountCode;
        $types .= "s";
    }
    
    if ($fromDate) {
        $sql .= " AND date >= ?";
        $params[] = $fromDate;
        $types .= "s";
    }
    
    if ($toDate) {
        $sql .= " AND date <= ?";
        $params[] = $toDate;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'];
}

// Validate journal entry data
function validateJournalEntry($date, $accountCode, $amount, $type, $description) {
    $errors = [];
    
    if (!validateDate($date)) {
        $errors[] = "Invalid date format";
    }
    
    if (empty($accountCode)) {
        $errors[] = "Account code is required";
    }
    
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Amount must be a positive number";
    }
    
    if (!in_array($type, ['debit', 'credit'])) {
        $errors[] = "Type must be either 'debit' or 'credit'";
    }
    
    if (empty(trim($description))) {
        $errors[] = "Description is required";
    }
    
    return $errors;
}

// Get accounts by type
function getAccountsByType($accountType) {
    global $conn;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE account_type = ? AND status = 'Active' ORDER BY account_code";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $accountType);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get dashboard statistics
function getDashboardStats() {
    global $conn;
    
    $stats = [];
    
    // Total accounts
    $sql = "SELECT COUNT(*) as count FROM chart_of_accounts WHERE status = 'Active'";
    $result = $conn->query($sql);
    $stats['total_accounts'] = $result->fetch_assoc()['count'];
    
    // Total transactions this month
    $sql = "SELECT COUNT(*) as count FROM journal_entries WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $stats['monthly_transactions'] = $result->fetch_assoc()['count'];
    
    // Total debits and credits this month
    $sql = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
            FROM journal_entries 
            WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $stats['monthly_debits'] = $row['total_debit'] ?? 0;
    $stats['monthly_credits'] = $row['total_credit'] ?? 0;
    
    // Pending liquidations
    $sql = "SELECT COUNT(*) as count FROM liquidation_records WHERE status = 'Pending'";
    $result = $conn->query($sql);
    $stats['pending_liquidations'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

?>
