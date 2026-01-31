<?php
require_once 'db.php';

// ===================
// JOURNAL ENTRIES FUNCTIONS
// ===================

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

function addJournalEntry($date, $reference, $accountCode, $amount, $type, $description, $sourceModule = 'Manual Entry') {
    global $conn;
    
    if (empty($reference)) {
        $reference = generateEntryId();
    }
    
    $debit = ($type === 'debit') ? $amount : 0.00;
    $credit = ($type === 'credit') ? $amount : 0.00;
    
    $sql = "INSERT INTO journal_entries (entry_id, date, reference, account_code, debit, credit, description, source_module, approved_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Admin', 'Posted')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssddss", $reference, $date, $reference, $accountCode, $debit, $credit, $description, $sourceModule);
    
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

function deleteJournalEntry($id) {
    global $conn;
    
    $sql = "DELETE FROM journal_entries WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

function generateEntryId() {
    global $conn;
    
    $sql = "SELECT MAX(CAST(SUBSTRING(entry_id, 4) AS UNSIGNED)) as max_num FROM journal_entries WHERE entry_id LIKE 'GL-%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $nextNum = ($row['max_num'] ?? 1000) + 1;
    return "GL-" . $nextNum;
}

// ===================
// CHART OF ACCOUNTS FUNCTIONS
// ===================

function getChartOfAccounts() {
    global $conn;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE status = 'Active' ORDER BY account_code";
    $result = $conn->query($sql);
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAccount($accountCode) {
    global $conn;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE account_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $accountCode);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function addAccount($accountCode, $accountName, $accountType, $description) {
    global $conn;
    
    $checkSql = "SELECT account_code FROM chart_of_accounts WHERE account_code = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $accountCode);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        return false;
    }
    
    $sql = "INSERT INTO chart_of_accounts (account_code, account_name, account_type, description, status) 
            VALUES (?, ?, ?, ?, 'Active')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $accountCode, $accountName, $accountType, $description);
    
    return $stmt->execute();
}

function updateAccount($originalCode, $newCode, $accountName, $accountType, $description) {
    global $conn;
    
    $sql = "UPDATE chart_of_accounts 
            SET account_code = ?, account_name = ?, account_type = ?, description = ? 
            WHERE account_code = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $newCode, $accountName, $accountType, $description, $originalCode);
    
    return $stmt->execute();
}

function deleteAccount($accountCode) {
    global $conn;
    
    try {
        $conn->autocommit(false);
        
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
                $conn->commit();
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
// LIQUIDATION RECORDS FUNCTIONS WITH AUTO JOURNAL ENTRY
// ===================

function getLiquidationRecords($limit = 50, $offset = 0) {
    global $conn;
    
    $sql = "SELECT * FROM liquidation_records ORDER BY date DESC, id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getLiquidationRecord($id) {
    global $conn;
    
    $sql = "SELECT * FROM liquidation_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function addLiquidationRecord($date, $liquidationId, $employee, $purpose, $totalAmount, $status = 'Pending', $expenseAccountCode = null) {
    global $conn;
    
    // Generate liquidation ID if not provided
    if (empty($liquidationId)) {
        $liquidationId = generateLiquidationId();
    }
    
    // Use provided expense account or fallback to default
    if (empty($expenseAccountCode)) {
        // Find default expense account
        $expenseAccountCode = '5300'; // Default expense account code
        
        $accountCheck = getAccount($expenseAccountCode);
        if (!$accountCheck) {
            // If default doesn't exist, find any active expense account
            $accountSql = "SELECT account_code FROM chart_of_accounts WHERE account_type = 'Expense' AND status = 'Active' LIMIT 1";
            $accountResult = $conn->query($accountSql);
            if ($accountResult && $accountResult->num_rows > 0) {
                $expenseAccountCode = $accountResult->fetch_assoc()['account_code'];
            } else {
                return [
                    'success' => false,
                    'message' => 'No expense account found. Please create an expense account first or select one from the dropdown.'
                ];
            }
        }
    } else {
        // Validate provided expense account
        $accountCheck = getAccount($expenseAccountCode);
        if (!$accountCheck) {
            return [
                'success' => false,
                'message' => 'Selected expense account does not exist.'
            ];
        }
        if ($accountCheck['account_type'] !== 'Expense') {
            return [
                'success' => false,
                'message' => 'Selected account must be an Expense type account.'
            ];
        }
    }
    
    // Handle receipt upload
    $receiptFilename = null;
    $receiptPath = null;
    $uploadedAt = null;
    
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleReceiptUpload($_FILES['receipt'], $liquidationId);
        
        if ($uploadResult['success']) {
            $receiptFilename = $uploadResult['filename'];
            $receiptPath = $uploadResult['path'];
            $uploadedAt = date('Y-m-d H:i:s');
        } else {
            return [
                'success' => false,
                'message' => 'File upload failed: ' . $uploadResult['message']
            ];
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert liquidation record WITH expense_account_code
        $sql = "INSERT INTO liquidation_records (liquidation_id, date, employee, purpose, total_amount, status, expense_account_code, receipt_filename, receipt_path, uploaded_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdsssss", $liquidationId, $date, $employee, $purpose, $totalAmount, $status, $expenseAccountCode, $receiptFilename, $receiptPath, $uploadedAt);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert liquidation record: ' . $stmt->error);
        }
        
        $liquidationRecordId = $conn->insert_id;
        
        // Generate unique journal entry reference
        $journalReference = generateEntryId();
        
        // Create journal entry description using only the PURPOSE field
        $journalDescription = $purpose;
        
        // Insert journal entry as DEBIT (expense increases with debit)
        $journalSql = "INSERT INTO journal_entries (entry_id, date, reference, account_code, debit, credit, description, source_module, approved_by, status) 
                       VALUES (?, ?, ?, ?, ?, 0.00, ?, 'Liquidation', 'System', ?)";
        
        $journalStmt = $conn->prepare($journalSql);
        $journalStmt->bind_param("ssssdss", $journalReference, $date, $liquidationId, $expenseAccountCode, $totalAmount, $journalDescription, $status);
        
        if (!$journalStmt->execute()) {
            throw new Exception('Failed to create journal entry: ' . $journalStmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'id' => $liquidationRecordId,
            'liquidation_id' => $liquidationId,
            'journal_entry_id' => $journalReference,
            'expense_account' => $expenseAccountCode,
            'message' => 'Liquidation record and journal entry created successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        // Delete uploaded file if exists
        if ($receiptPath && file_exists($receiptPath)) {
            unlink($receiptPath);
        }
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function updateLiquidationRecord($id, $date, $liquidationId, $employee, $purpose, $totalAmount, $status, $expenseAccountCode = null) {
    global $conn;
    
    $existingRecord = getLiquidationRecord($id);
    
    if (!$existingRecord) {
        return [
            'success' => false,
            'message' => 'Liquidation record not found'
        ];
    }
    
    // Store old liquidation_id to find the journal entry
    $oldLiquidationId = $existingRecord['liquidation_id'];
    
    // Use provided expense account or keep existing one
    if (empty($expenseAccountCode)) {
        $expenseAccountCode = $existingRecord['expense_account_code'] ?? '5300';
    } else {
        // Validate provided expense account
        $accountCheck = getAccount($expenseAccountCode);
        if (!$accountCheck) {
            return [
                'success' => false,
                'message' => 'Selected expense account does not exist.'
            ];
        }
        if ($accountCheck['account_type'] !== 'Expense') {
            return [
                'success' => false,
                'message' => 'Selected account must be an Expense type account.'
            ];
        }
    }
    
    // Handle receipt upload
    $receiptFilename = $existingRecord['receipt_filename'];
    $receiptPath = $existingRecord['receipt_path'];
    $uploadedAt = $existingRecord['uploaded_at'];
    
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        // Delete old receipt if exists
        if ($existingRecord['receipt_path'] && file_exists($existingRecord['receipt_path'])) {
            unlink($existingRecord['receipt_path']);
        }
        
        $uploadResult = handleReceiptUpload($_FILES['receipt'], $liquidationId);
        
        if ($uploadResult['success']) {
            $receiptFilename = $uploadResult['filename'];
            $receiptPath = $uploadResult['path'];
            $uploadedAt = date('Y-m-d H:i:s');
        } else {
            return [
                'success' => false,
                'message' => 'File upload failed: ' . $uploadResult['message']
            ];
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update liquidation record WITH expense_account_code
        $sql = "UPDATE liquidation_records 
                SET date = ?, liquidation_id = ?, employee = ?, purpose = ?, total_amount = ?, status = ?, expense_account_code = ?, 
                    receipt_filename = ?, receipt_path = ?, uploaded_at = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdsssssi", $date, $liquidationId, $employee, $purpose, $totalAmount, $status, $expenseAccountCode, 
                          $receiptFilename, $receiptPath, $uploadedAt, $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update liquidation record: ' . $stmt->error);
        }
        
        // Update corresponding journal entry using only the PURPOSE field
        $journalDescription = $purpose;
        
        $journalSql = "UPDATE journal_entries 
                       SET date = ?, reference = ?, account_code = ?, description = ?, debit = ?, status = ?
                       WHERE reference = ? AND source_module = 'Liquidation'";
        
        $journalStmt = $conn->prepare($journalSql);
        $journalStmt->bind_param("ssssdss", $date, $liquidationId, $expenseAccountCode, $journalDescription, $totalAmount, $status, $oldLiquidationId);
        
        if (!$journalStmt->execute()) {
            throw new Exception('Failed to update journal entry: ' . $journalStmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Liquidation record and journal entry updated successfully'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function deleteLiquidationRecord($id) {
    global $conn;
    
    $record = getLiquidationRecord($id);
    
    if (!$record) {
        return ['success' => false, 'message' => 'Liquidation record not found'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Delete corresponding journal entry
        $deleteJournalSql = "DELETE FROM journal_entries WHERE reference = ? AND source_module = 'Liquidation'";
        $deleteJournalStmt = $conn->prepare($deleteJournalSql);
        $deleteJournalStmt->bind_param("s", $record['liquidation_id']);
        $deleteJournalStmt->execute();
        
        // Delete liquidation record
        $sql = "DELETE FROM liquidation_records WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete liquidation record');
        }
        
        // Delete receipt file if exists
        if (isset($record['receipt_path']) && !empty($record['receipt_path']) && file_exists($record['receipt_path'])) {
            unlink($record['receipt_path']);
        }
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Liquidation record, journal entry, and receipt deleted successfully'];
        
    } catch (Exception $e) {
        $conn->rollback();
        
        return ['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()];
    }
}

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

function handleReceiptUpload($file, $liquidationId) {
    $uploadDir = 'uploads/receipts/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, GIF, and PDF files are allowed.'
        ];
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'message' => 'File size exceeds 5MB limit.'
        ];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $liquidationId . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filepath
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to move uploaded file.'
        ];
    }
}

function deleteReceipt($id) {
    global $conn;
    
    $record = getLiquidationRecord($id);
    
    if ($record && $record['receipt_path']) {
        if (file_exists($record['receipt_path'])) {
            unlink($record['receipt_path']);
        }
        
        $sql = "UPDATE liquidation_records 
                SET receipt_filename = NULL, receipt_path = NULL, uploaded_at = NULL 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Receipt deleted successfully'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Receipt not found or already deleted'
    ];
}

// ===================
// REPORTING FUNCTIONS
// ===================

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
    
    return calculateRunningBalance($entries);
}

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

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2);
    }
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

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

function getAccountsByType($accountType) {
    global $conn;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE account_type = ? AND status = 'Active' ORDER BY account_code";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $accountType);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getDashboardStats() {
    global $conn;
    
    $stats = [];
    
    $sql = "SELECT COUNT(*) as count FROM chart_of_accounts WHERE status = 'Active'";
    $result = $conn->query($sql);
    $stats['total_accounts'] = $result->fetch_assoc()['count'];
    
    $sql = "SELECT COUNT(*) as count FROM journal_entries WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $stats['monthly_transactions'] = $result->fetch_assoc()['count'];
    
    $sql = "SELECT SUM(debit) as total_debit, SUM(credit) as total_credit 
            FROM journal_entries 
            WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $stats['monthly_debits'] = $row['total_debit'] ?? 0;
    $stats['monthly_credits'] = $row['total_credit'] ?? 0;
    
    $sql = "SELECT COUNT(*) as count FROM liquidation_records WHERE status = 'Pending'";
    $result = $conn->query($sql);
    $stats['pending_liquidations'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

?>
