<?php
// budgets_actions.php
session_start();
include 'db.php';

function back($type, $msg){
  $_SESSION['flash'] = ['type'=>$type, 'msg'=>$msg];
  header('Location: financial_budgeting.php');
  exit;
}

$action = $_REQUEST['action'] ?? '';

try {
  if ($action === 'create') {
    $period           = $_POST['period'] ?? '';
    $department       = $_POST['department'] ?? '';
    $cost_center      = $_POST['cost_center'] ?? '';
    $amount_allocated = (float)($_POST['amount_allocated'] ?? 0);
    $amount_used      = (float)($_POST['amount_used'] ?? 0);
    $approved_by      = $_POST['approved_by'] ?? '';
    $approval_status  = $_POST['approval_status'] ?? 'Pending';
    $description      = $_POST['description'] ?? '';

    $sql = "INSERT INTO budgets (period, department, cost_center, amount_allocated, amount_used, approved_by, approval_status, description)
            VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssddsss', $period, $department, $cost_center, $amount_allocated, $amount_used, $approved_by, $approval_status, $description);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) back('success','Budget allocation added successfully.');
    back('danger','Failed to add budget allocation.');

  } elseif ($action === 'update') {
    $id               = (int)($_POST['id'] ?? 0);
    $period           = $_POST['period'] ?? '';
    $department       = $_POST['department'] ?? '';
    $cost_center      = $_POST['cost_center'] ?? '';
    $amount_allocated = (float)($_POST['amount_allocated'] ?? 0);
    $amount_used      = (float)($_POST['amount_used'] ?? 0);
    $approved_by      = $_POST['approved_by'] ?? '';
    $approval_status  = $_POST['approval_status'] ?? 'Pending';
    $description      = $_POST['description'] ?? '';

    if ($id <= 0) back('danger','Invalid record.');

    $sql = "UPDATE budgets
            SET period=?, department=?, cost_center=?, amount_allocated=?, amount_used=?, approved_by=?, approval_status=?, description=?
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssddsssi', $period, $department, $cost_center, $amount_allocated, $amount_used, $approved_by, $approval_status, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) back('success','Budget allocation updated successfully.');
    back('danger','Failed to update budget allocation.');

  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) back('danger','Invalid record.');

    $stmt = $conn->prepare("DELETE FROM budgets WHERE id=?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) back('success','Budget allocation deleted.');
    back('danger','Failed to delete budget allocation.');

  } else {
    back('warning','Unknown action.');
  }
} catch (Throwable $e) {
  back('danger','Error: '.$e->getMessage());
}
