<?php
// collections_action.php
include 'db.php';

// Basic sanitizer
function n($v){ return is_numeric($v) ? $v : 0; }
function s($v){ return trim($v ?? ''); }

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
  $q = $conn->real_escape_string($_GET['query'] ?? '');

  $where = "";
  if ($q !== '') {
    $like = "'%$q%'";
    $where = "WHERE client_name LIKE $like OR invoice_no LIKE $like OR collector_name LIKE $like";
  }

  $rows = [];
  $sql  = "SELECT * FROM collections $where ORDER BY id DESC";
  $res  = $conn->query($sql);
  while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
  }

  // Summary cards
  $total_collected = 0;
  $total_pending   = 0;
  $total_overdue   = 0;

  $today = date('Y-m-d');
  foreach ($rows as $r) {
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

  // Server-side VAT computation (authoritative)
  $vat_rate   = $VAT_RATE;
  $vat_amount = ($vat_applied === 'Yes') ? round($amount_base * ($vat_rate/100), 2) : 0.00;
  $amount_due = round($amount_base + $vat_amount, 2);

  // Server-side penalty calculation (authoritative)
  $penalty = calculatePenalty($due_date, $payment_status, $amount_due, $amount_paid);

  $stmt = $conn->prepare("INSERT INTO collections
    (client_name, invoice_no, billing_date, due_date, amount_base, vat_applied, vat_rate, vat_amount, amount_due, amount_paid, penalty, mode_of_payment, payment_status, receipt_type, collector_name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param(
    "ssssdsdddddssss",
    $client_name, $invoice_no, $billing_date, $due_date,
    $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
    $amount_paid, $penalty, $mode_of_payment, $payment_status, $receipt_type, $collector_name
  );

  if ($stmt->execute()) {
    echo "ok";
  } else {
    http_response_code(500);
    echo "DB Error: " . $conn->error;
  }
  exit;
}

if ($action === 'update') {
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
      collector_name=?
    WHERE id=?");
  $stmt->bind_param(
    "ssssdsdddddssssi",
    $client_name, $invoice_no, $billing_date, $due_date,
    $amount_base, $vat_applied, $vat_rate, $vat_amount, $amount_due,
    $amount_paid, $penalty, $mode_of_payment, $payment_status, $receipt_type, $collector_name,
    $id
  );

  if ($stmt->execute()) {
    echo "ok";
  } else {
    http_response_code(500);
    echo "DB Error: " . $conn->error;
  }
  exit;
}

if ($action === 'delete') {
  $id = (int) ($_POST['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); echo "Invalid ID"; exit; }
  if ($conn->query("DELETE FROM collections WHERE id=$id")) {
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