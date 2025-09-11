<?php
// collections_action.php
include 'db.php';

// Basic sanitizer
function n($v){ return is_numeric($v) ? $v : 0; }
function s($v){ return trim($v ?? ''); }

// File upload handler
function handleFileUpload($fileInputName, $oldFileName = null) {
    $uploadDir = 'invoice_uploads/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFileName; // Keep existing file if no new file uploaded
    }
    
    $file = $_FILES[$fileInputName];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only images (JPEG, PNG, GIF, WebP) and PDF files are allowed.');
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum size is 5MB.');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file.');
    }
    
    // Delete old file if exists and is different
    if ($oldFileName && $oldFileName !== $fileName && file_exists($uploadDir . $oldFileName)) {
        unlink($uploadDir . $oldFileName);
    }
    
    return $fileName;
}

// Delete file helper
function deleteFile($fileName) {
    if ($fileName && file_exists('invoice_uploads/' . $fileName)) {
        unlink('invoice_uploads/' . $fileName);
    }
}

// Penalty calculation function
function calculatePenalty($dueDate, $paymentStatus, $amountDue, $amountPaid) {
    // Don't apply penalty if already paid
    if ($paymentStatus === 'Paid') {
        return 0.00;
    }
    
    $today = new DateTime();
    $due = new DateTime($dueDate);
    
    // Don't apply penalty if not past due date
    if ($due >= $today) {
        return 0.00;
    }
    
    // Calculate days past due
    $interval = $today->diff($due);
    $daysPastDue = $interval->days;
    
    // Calculate remaining unpaid amount
    $remainingUnpaid = max(0, $amountDue - $amountPaid);
    
    // If nothing is unpaid, no penalty
    if ($remainingUnpaid <= 0) {
        return 0.00;
    }
    
    // Apply penalty: Fixed 850 + (remaining unpaid × 0.10% × days past due)
    $basePenalty = 850;
    $dailyPenaltyRate = 0.001; // 0.10%
    $dailyPenalty = $remainingUnpaid * $dailyPenaltyRate * $daysPastDue;
    
    $totalPenalty = $basePenalty + $dailyPenalty;
    
    return round($totalPenalty, 2); // Round to 2 decimal places
}

// CONSTANT VAT RATE (server-side guard)
$VAT_RATE = 12.0;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
  // Get filter parameters instead of search query
  $dateFrom = s($_GET['date_from'] ?? '');
  $dateTo = s($_GET['date_to'] ?? '');
  $paymentStatus = s($_GET['payment_status'] ?? '');
  
  // Get pagination parameters
  $limit = intval($_GET['limit'] ?? 5);
  $offset = intval($_GET['offset'] ?? 0);

  $where = "";
  $conditions = [];
  
  // Date range filter
  if ($dateFrom !== '') {
    $conditions[] = "billing_date >= '" . $conn->real_escape_string($dateFrom) . "'";
  }
  if ($dateTo !== '') {
    $conditions[] = "billing_date <= '" . $conn->real_escape_string($dateTo) . "'";
  }
  
  // Payment status filter
  if ($paymentStatus !== '' && $paymentStatus !== 'All') {
    $conditions[] = "payment_status = '" . $conn->real_escape_string($paymentStatus) . "'";
  }
  
  if (!empty($conditions)) {
    $where = "WHERE " . implode(" AND ", $conditions);
  }

  // Count total records for pagination
  $countSql = "SELECT COUNT(*) as total FROM collections $where";
  $countRes = $conn->query($countSql);
  $totalRecords = $countRes->fetch_assoc()['total'];

  // Get paginated rows
  $rows = [];
  $sql = "SELECT * FROM collections $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
  $res = $conn->query($sql);
  while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
  }

  // Calculate totals for summary cards (from ALL records, not just current page)
  $total_collected = 0;
  $total_pending = 0;
  $total_overdue = 0;

  $today = date('Y-m-d');
  
  // Get all records for summary calculation
  $allSql = "SELECT * FROM collections $where";
  $allRes = $conn->query($allSql);
  
  while ($r = $allRes->fetch_assoc()) {
    $total_collected += (float)$r['amount_paid'];
    // pending = total due - paid (not below zero)
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

    // Handle file upload
    $receipt_attachment = null;
    try {
      $receipt_attachment = handleFileUpload('receipt_attachment');
    } catch (Exception $e) {
      http_response_code(400);
      echo "File upload error: " . $e->getMessage();
      exit;
    }

    // Server-side VAT computation (authoritative)
    $vat_rate   = $VAT_RATE;
    $vat_amount = ($vat_applied === 'Yes') ? round($amount_base * ($vat_rate/100), 2) : 0.00;
    $amount_due = round($amount_base + $vat_amount, 2);

    // Server-side penalty calculation (authoritative)
    $penalty = calculatePenalty($due_date, $payment_status, $amount_due, $amount_paid);

    $stmt = $conn->prepare("INSERT INTO collections
      (client_name, invoice_no, billing_date, due_date, amount_base, vat_applied, vat_rate, vat_amount, amount_due, amount_paid, penalty, mode_of_payment, payment_status, receipt_type, collector_name, receipt_attachment)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
      "ssssdsdddddsssss",
      $client_name, $invoice_no, $billing_date, $due_date,
      $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
      $amount_paid, $penalty, $mode_of_payment, $payment_status, $receipt_type, $collector_name, $receipt_attachment
    );

    if ($stmt->execute()) {
      echo "ok";
    } else {
      // If database insert fails, clean up uploaded file
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

    // Get current record to check for existing attachment
    $currentRes = $conn->query("SELECT receipt_attachment FROM collections WHERE id=$id");
    $currentRecord = $currentRes->fetch_assoc();
    $currentAttachment = $currentRecord['receipt_attachment'] ?? null;

    // Handle file upload
    $receipt_attachment = null;
    try {
      $receipt_attachment = handleFileUpload('receipt_attachment', $currentAttachment);
    } catch (Exception $e) {
      http_response_code(400);
      echo "File upload error: " . $e->getMessage();
      exit;
    }

    // Server-side VAT recompute
    $vat_rate   = $VAT_RATE;
    $vat_amount = ($vat_applied === 'Yes') ? round($amount_base * ($vat_rate/100), 2) : 0.00;
    $amount_due = round($amount_base + $vat_amount, 2);

    // Server-side penalty calculation (authoritative)
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
        receipt_attachment=?
      WHERE id=?");
    $stmt->bind_param(
      "ssssdsdddddsssssi",
      $client_name, $invoice_no, $billing_date, $due_date,
      $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
      $amount_paid, $penalty, $mode_of_payment, $payment_status, $receipt_type, $collector_name, $receipt_attachment,
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
  
  // Get the attachment filename before deleting the record
  $res = $conn->query("SELECT receipt_attachment FROM collections WHERE id=$id");
  $record = $res->fetch_assoc();
  $attachmentFile = $record['receipt_attachment'] ?? null;
  
  if ($conn->query("DELETE FROM collections WHERE id=$id")) {
    // Delete the associated file if it exists
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

// Optional: Bulk penalty recalculation for existing records
if ($action === 'recalculate_penalties') {
  $result = $conn->query("SELECT id, due_date, payment_status FROM collections");
  $updated = 0;
  
  while ($row = $result->fetch_assoc()) {
    // Get current amount_due and amount_paid for penalty calculation
    $detailResult = $conn->query("SELECT amount_due, amount_paid FROM collections WHERE id = " . $row['id']);
    $detail = $detailResult->fetch_assoc();
    
    $penalty = calculatePenalty($row['due_date'], $row['payment_status'], $detail['amount_due'], $detail['amount_paid']);
    $stmt = $conn->prepare("UPDATE collections SET penalty = ? WHERE id = ?");
    $stmt->bind_param("di", $penalty, $row['id']);
    if ($stmt->execute()) {
      $updated++;
    }
  }
  
  echo "Penalties recalculated for $updated records";
  exit;
}

// Fallback
http_response_code(400);
echo "Invalid action";
?>