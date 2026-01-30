<?php
// collections_action.php with Fiscal Year Support
include 'db.php';

function n($v){ return is_numeric($v) ? $v : 0; }
function s($v){ return trim($v ?? ''); }

// Get fiscal year settings
function getFiscalYearSettings($conn) {
    $res = $conn->query("SELECT * FROM fiscal_year_settings WHERE is_active = 1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return ['start_month' => 1, 'start_day' => 1];
}

// Calculate fiscal year based on date and settings
function calculateFiscalYear($date, $settings) {
    $d = new DateTime($date);
    $year = (int)$d->format('Y');
    $month = (int)$d->format('m');
    $day = (int)$d->format('d');
    
    $startMonth = (int)$settings['start_month'];
    $startDay = (int)$settings['start_day'];
    
    // If date is before fiscal year start, it belongs to previous fiscal year
    if ($month < $startMonth || ($month == $startMonth && $day < $startDay)) {
        return 'FY' . ($year - 1);
    }
    
    return 'FY' . $year;
}

function handleFileUpload($fileInputName, $oldFileName = null) {
    $uploadDir = 'invoice_uploads/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFileName;
    }
    
    $file = $_FILES[$fileInputName];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only images (JPEG, PNG, GIF, WebP) and PDF files are allowed.');
    }
    
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file.');
    }
    
    if ($oldFileName && $oldFileName !== $fileName && file_exists($uploadDir . $oldFileName)) {
        unlink($uploadDir . $oldFileName);
    }
    
    return $fileName;
}

function deleteFile($fileName) {
    if ($fileName && file_exists('invoice_uploads/' . $fileName)) {
        unlink('invoice_uploads/' . $fileName);
    }
}

function calculatePenalty($dueDate, $paymentStatus, $amountDue, $amountPaid) {
    if ($paymentStatus === 'Paid') {
        return 0.00;
    }
    
    $today = new DateTime();
    $due = new DateTime($dueDate);
    
    if ($due >= $today) {
        return 0.00;
    }
    
    $interval = $today->diff($due);
    $daysPastDue = $interval->days;
    
    $remainingUnpaid = max(0, $amountDue - $amountPaid);
    
    if ($remainingUnpaid <= 0) {
        return 0.00;
    }
    
    $basePenalty = 850;
    $dailyPenaltyRate = 0.001;
    $dailyPenalty = $remainingUnpaid * $dailyPenaltyRate * $daysPastDue;
    
    $totalPenalty = $basePenalty + $dailyPenalty;
    
    return round($totalPenalty, 2);
}

$VAT_RATE = 12.0;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get fiscal years list
if ($action === 'get_fiscal_years') {
    $sql = "SELECT DISTINCT fiscal_year FROM collections WHERE fiscal_year IS NOT NULL ORDER BY fiscal_year DESC";
    $res = $conn->query($sql);
    $years = [];
    while ($row = $res->fetch_assoc()) {
        $years[] = $row['fiscal_year'];
    }
    header('Content-Type: application/json');
    echo json_encode($years);
    exit;
}

// Get fiscal year settings
if ($action === 'get_fy_settings') {
    $settings = getFiscalYearSettings($conn);
    header('Content-Type: application/json');
    echo json_encode($settings);
    exit;
}

// Save fiscal year settings
if ($action === 'save_fy_settings') {
    $startMonth = (int)($_POST['start_month'] ?? 1);
    $startDay = (int)($_POST['start_day'] ?? 1);
    
    // Deactivate all existing settings
    $conn->query("UPDATE fiscal_year_settings SET is_active = 0");
    
    // Insert new setting
    $stmt = $conn->prepare("INSERT INTO fiscal_year_settings (start_month, start_day, is_active) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $startMonth, $startDay);
    $stmt->execute();
    
    // Recalculate fiscal year for all records
    $settings = ['start_month' => $startMonth, 'start_day' => $startDay];
    $result = $conn->query("SELECT id, billing_date FROM collections");
    
    while ($row = $result->fetch_assoc()) {
        $fiscalYear = calculateFiscalYear($row['billing_date'], $settings);
        $updateStmt = $conn->prepare("UPDATE collections SET fiscal_year = ? WHERE id = ?");
        $updateStmt->bind_param("si", $fiscalYear, $row['id']);
        $updateStmt->execute();
    }
    
    echo "ok";
    exit;
}

if ($action === 'list') {
    $dateFrom = s($_GET['date_from'] ?? '');
    $dateTo = s($_GET['date_to'] ?? '');
    $paymentStatus = s($_GET['payment_status'] ?? '');
    $fiscalYear = s($_GET['fiscal_year'] ?? '');
    
    $limit = intval($_GET['limit'] ?? 5);
    $offset = intval($_GET['offset'] ?? 0);

    $where = "";
    $conditions = [];
    
    if ($fiscalYear !== '') {
        $conditions[] = "fiscal_year = '" . $conn->real_escape_string($fiscalYear) . "'";
    }
    
    if ($dateFrom !== '') {
        $conditions[] = "billing_date >= '" . $conn->real_escape_string($dateFrom) . "'";
    }
    if ($dateTo !== '') {
        $conditions[] = "billing_date <= '" . $conn->real_escape_string($dateTo) . "'";
    }
    
    if ($paymentStatus !== '' && $paymentStatus !== 'All') {
        $conditions[] = "payment_status = '" . $conn->real_escape_string($paymentStatus) . "'";
    }
    
    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }

    $countSql = "SELECT COUNT(*) as total FROM collections $where";
    $countRes = $conn->query($countSql);
    $totalRecords = $countRes->fetch_assoc()['total'];

    $rows = [];
    $sql = "SELECT * FROM collections $where ORDER BY billing_date DESC, id DESC LIMIT $limit OFFSET $offset";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    $total_collected = 0;
    $total_pending = 0;
    $total_overdue = 0;
    $today = date('Y-m-d');
    
    $allSql = "SELECT * FROM collections $where";
    $allRes = $conn->query($allSql);
    
    while ($r = $allRes->fetch_assoc()) {
        $total_collected += (float)$r['amount_paid'];
        $pending = max(0, (float)$r['amount_due'] - (float)$r['amount_paid']);
        $total_pending += $pending;

        if ($r['payment_status'] !== 'Paid' && $r['due_date'] < $today) {
            $total_overdue += $pending;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows,
        'total_records' => intval($totalRecords),
        'totals' => [
            'total_collected' => round($total_collected, 2),
            'total_pending'   => round($total_pending, 2),
            'total_overdue'   => round($total_overdue, 2),
        ]
    ]);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT * FROM collections WHERE id=$id");
    if ($res && $res->num_rows) {
        header('Content-Type: application/json');
        echo json_encode($res->fetch_assoc());
    } else {
        http_response_code(404);
        echo "Not found";
    }
    exit;
}

if ($action === 'add') {
    try {
        $client_name    = s($_POST['client_name'] ?? '');
        $invoice_no     = s($_POST['invoice_no'] ?? '');
        $billing_date   = s($_POST['billing_date'] ?? '');
        $due_date       = s($_POST['due_date'] ?? '');
        $amount_base    = (float) n($_POST['amount_base'] ?? 0);
        $amount_paid    = (float) n($_POST['amount_paid'] ?? 0);
        $mode_of_payment= s($_POST['mode_of_payment'] ?? '');
        $payment_status = s($_POST['payment_status'] ?? 'Unpaid');
        $vat_applied    = (s($_POST['vat_applied'] ?? 'No') === 'Yes') ? 'Yes' : 'No';
        $receipt_type   = s($_POST['receipt_type'] ?? 'Acknowledgment');
        $collector_name = s($_POST['collector_name'] ?? '');

        // Calculate fiscal year
        $fySettings = getFiscalYearSettings($conn);
        $fiscal_year = calculateFiscalYear($billing_date, $fySettings);

        $receipt_attachment = null;
        try {
            $receipt_attachment = handleFileUpload('receipt_attachment');
        } catch (Exception $e) {
            http_response_code(400);
            echo "File upload error: " . $e->getMessage();
            exit;
        }

        $vat_rate   = $VAT_RATE;
        $vat_amount = ($vat_applied === 'Yes') ? round($amount_base * ($vat_rate/100), 2) : 0.00;
        $amount_due = round($amount_base + $vat_amount, 2);
        $penalty = calculatePenalty($due_date, $payment_status, $amount_due, $amount_paid);

        $stmt = $conn->prepare("INSERT INTO collections
            (client_name, invoice_no, billing_date, due_date, amount_base, vat_applied, vat_rate, vat_amount, amount_due, amount_paid, penalty, mode_of_payment, payment_status, receipt_type, collector_name, receipt_attachment, fiscal_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssdsdddddssssss",
            $client_name, $invoice_no, $billing_date, $due_date,
            $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
            $amount_paid, $penalty, $mode_of_payment, $payment_status, $receipt_type, $collector_name, $receipt_attachment, $fiscal_year
        );

        if ($stmt->execute()) {
            echo "ok";
        } else {
            if ($receipt_attachment) {
                deleteFile($receipt_attachment);
            }
            http_response_code(500);
            echo "DB Error: " . $conn->error;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
    exit;
}

if ($action === 'update') {
    try {
        $id            = (int) ($_POST['id'] ?? 0);
        $client_name    = s($_POST['client_name'] ?? '');
        $invoice_no     = s($_POST['invoice_no'] ?? '');
        $billing_date   = s($_POST['billing_date'] ?? '');
        $due_date       = s($_POST['due_date'] ?? '');
        $amount_base    = (float) n($_POST['amount_base'] ?? 0);
        $amount_paid    = (float) n($_POST['amount_paid'] ?? 0);
        $mode_of_payment= s($_POST['mode_of_payment'] ?? '');
        $payment_status = s($_POST['payment_status'] ?? 'Unpaid');
        $vat_applied    = (s($_POST['vat_applied'] ?? 'No') === 'Yes') ? 'Yes' : 'No';
        $receipt_type   = s($_POST['receipt_type'] ?? 'Acknowledgment');
        $collector_name = s($_POST['collector_name'] ?? '');

        // Recalculate fiscal year
        $fySettings = getFiscalYearSettings($conn);
        $fiscal_year = calculateFiscalYear($billing_date, $fySettings);

        $currentRes = $conn->query("SELECT receipt_attachment FROM collections WHERE id=$id");
        $currentRecord = $currentRes->fetch_assoc();
        $currentAttachment = $currentRecord['receipt_attachment'] ?? null;

        $receipt_attachment = null;
        try {
            $receipt_attachment = handleFileUpload('receipt_attachment', $currentAttachment);
        } catch (Exception $e) {
            http_response_code(400);
            echo "File upload error: " . $e->getMessage();
            exit;
        }

        $vat_rate   = $VAT_RATE;
        $vat_amount = ($vat_applied === 'Yes') ? round($amount_base * ($vat_rate/100), 2) : 0.00;
        $amount_due = round($amount_base + $vat_amount, 2);
        $penalty = calculatePenalty($due_date, $payment_status, $amount_due, $amount_paid);

        $stmt = $conn->prepare("UPDATE collections SET
            client_name=?,
            invoice_no=?,
            billing_date=?,
            due_date=?,
            amount_base=?,
            vat_applied=?,
            vat_rate=?,
            vat_amount=?,
            amount_due=?,
            amount_paid=?,
            penalty=?,
            mode_of_payment=?,
            payment_status=?,
            receipt_type=?,
            collector_name=?,
            receipt_attachment=?,
            fiscal_year=?
          WHERE id=?");
        $stmt->bind_param(
            "ssssdsdddddssssssi",
            $client_name, $invoice_no, $billing_date, $due_date,
            $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
            $amount_paid, $penalty, $mode_of_payment, $payment_status, $receipt_type, $collector_name, $receipt_attachment, $fiscal_year,
            $id
        );

        if ($stmt->execute()) {
            echo "ok";
        } else {
            http_response_code(500);
            echo "DB Error: " . $conn->error;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
    exit;
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) { 
        http_response_code(400); 
        echo "Invalid ID"; 
        exit; 
    }
    
    $res = $conn->query("SELECT receipt_attachment FROM collections WHERE id=$id");
    $record = $res->fetch_assoc();
    $attachmentFile = $record['receipt_attachment'] ?? null;
    
    if ($conn->query("DELETE FROM collections WHERE id=$id")) {
        if ($attachmentFile) {
            deleteFile($attachmentFile);
        }
        echo "ok";
    } else {
        http_response_code(500);
        echo "DB Error: " . $conn->error;
    }
    exit;
}

http_response_code(400);
echo "Invalid action";
?>
