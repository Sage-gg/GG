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

  /* Optional: quick CSV export (hook up the button if you want)
  } elseif ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=budgets_export_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Period','Department','Cost Center','Allocated','Used','Difference','Approved By','Approval Status','Description','Created At']);
    $res = $conn->query("SELECT * FROM budgets ORDER BY created_at DESC");
    while($r = $res->fetch_assoc()){
      $diff = (float)$r['amount_allocated'] - (float)$r['amount_used'];
      fputcsv($out, [
        $r['id'],$r['period'],$r['department'],$r['cost_center'],
        $r['amount_allocated'],$r['amount_used'],$diff,$r['approved_by'],$r['approval_status'],$r['description'],$r['created_at']
      ]);
    }
    fclose($out);
    exit;
  */
  } else {
    back('warning','Unknown action.');
  }
} catch (Throwable $e) {
  back('danger','Error: '.$e->getMessage());
}
