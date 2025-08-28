<?php
require_once 'db.php';

// Add new expense
function addExpense($data, $receiptFile = null) {
    $conn = getDBConnection();
    
    // Calculate tax amount
    $taxAmount = calculateTaxAmount($data['tax_type'], $data['amount']);
    
    // Handle file upload
    $receiptFileName = null;
    if ($receiptFile && $receiptFile['error'] == 0) {
        $uploadDir = 'uploads/receipts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($receiptFile['name'], PATHINFO_EXTENSION);
        $receiptFileName = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $uploadPath = $uploadDir . $receiptFileName;
        
        if (!move_uploaded_file($receiptFile['tmp_name'], $uploadPath)) {
            $receiptFileName = null;
        }
    }
    
    $sql = "INSERT INTO expenses (expense_date, category, vendor, amount, remarks, tax_type, tax_amount, receipt_file, payment_method, vehicle, job_linked, approved_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdssdssssss", 
        $data['expense_date'],
        $data['category'],
        $data['vendor'],
        $data['amount'],
        $data['remarks'],
        $data['tax_type'],
        $taxAmount,
        $receiptFileName,
        $data['payment_method'],
        $data['vehicle'],
        $data['job_linked'],
        $data['approved_by'],
        $data['status']
    );
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Update expense
function updateExpense($id, $data, $receiptFile = null) {
    $conn = getDBConnection();
    
    // Calculate tax amount
    $taxAmount = calculateTaxAmount($data['tax_type'], $data['amount']);
    
    // Handle file upload
    $receiptFileName = null;
    if ($receiptFile && $receiptFile['error'] == 0) {
        // Get current receipt file to delete if exists
        $currentFile = getCurrentReceiptFile($id);
        if ($currentFile && file_exists('uploads/receipts/' . $currentFile)) {
            unlink('uploads/receipts/' . $currentFile);
        }
        
        $uploadDir = 'uploads/receipts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($receiptFile['name'], PATHINFO_EXTENSION);
        $receiptFileName = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $uploadPath = $uploadDir . $receiptFileName;
        
        if (move_uploaded_file($receiptFile['tmp_name'], $uploadPath)) {
            $sql = "UPDATE expenses SET expense_date=?, category=?, vendor=?, amount=?, remarks=?, tax_type=?, tax_amount=?, receipt_file=?, payment_method=?, vehicle=?, job_linked=?, approved_by=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdssdssssssi", 
                $data['expense_date'],
                $data['category'],
                $data['vendor'],
                $data['amount'],
                $data['remarks'],
                $data['tax_type'],
                $taxAmount,
                $receiptFileName,
                $data['payment_method'],
                $data['vehicle'],
                $data['job_linked'],
                $data['approved_by'],
                $data['status'],
                $id
            );
        }
    } else {
        $sql = "UPDATE expenses SET expense_date=?, category=?, vendor=?, amount=?, remarks=?, tax_type=?, tax_amount=?, payment_method=?, vehicle=?, job_linked=?, approved_by=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdssdsssssi", 
            $data['expense_date'],
            $data['category'],
            $data['vendor'],
            $data['amount'],
            $data['remarks'],
            $data['tax_type'],
            $taxAmount,
            $data['payment_method'],
            $data['vehicle'],
            $data['job_linked'],
            $data['approved_by'],
            $data['status'],
            $id
        );
    }
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Get current receipt file name
function getCurrentReceiptFile($id) {
    $conn = getDBConnection();
    $sql = "SELECT receipt_file FROM expenses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $row ? $row['receipt_file'] : null;
}

// Delete expense
function deleteExpense($id) {
    $conn = getDBConnection();
    
    // Get receipt file to delete
    $receiptFile = getCurrentReceiptFile($id);
    if ($receiptFile && file_exists('uploads/receipts/' . $receiptFile)) {
        unlink('uploads/receipts/' . $receiptFile);
    }
    
    $sql = "DELETE FROM expenses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Get all expenses with pagination and search
function getExpenses($page = 1, $limit = 10, $search = '') {
    $conn = getDBConnection();
    $offset = ($page - 1) * $limit;
    
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE category LIKE ? OR vendor LIKE ? OR remarks LIKE ? OR vehicle LIKE ? OR job_linked LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = 'sssss';
    }
    
    $sql = "SELECT * FROM expenses $whereClause ORDER BY expense_date DESC, created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $expenses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    
    return $expenses;
}

// Get single expense by ID
function getExpenseById($id) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM expenses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $expense = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $expense;
}

// Get total count for pagination
function getTotalExpenses($search = '') {
    $conn = getDBConnection();
    
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE category LIKE ? OR vendor LIKE ? OR remarks LIKE ? OR vehicle LIKE ? OR job_linked LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = 'sssss';
    }
    
    $sql = "SELECT COUNT(*) as total FROM expenses $whereClause";
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $row['total'];
}

// Get expense summary
function getExpenseSummary($search = '') {
    $conn = getDBConnection();
    
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE category LIKE ? OR vendor LIKE ? OR remarks LIKE ? OR vehicle LIKE ? OR job_linked LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = 'sssss';
    }
    
    $sql = "SELECT 
                SUM(amount) as total_expenses, 
                SUM(tax_amount) as total_tax,
                SUM(amount - tax_amount) as net_after_tax
            FROM expenses $whereClause";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return [
        'total_expenses' => $summary['total_expenses'] ?? 0,
        'total_tax' => $summary['total_tax'] ?? 0,
        'net_after_tax' => $summary['net_after_tax'] ?? 0
    ];
}
?>