<?php
// budgets_actions.php - UPDATED WITH AUTO-SYNC TO CORE 2
session_start();
include 'db.php';
require_once 'core2_api_config.php';
require_once 'core2_api_sender.php';

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
    $new_budget_id = $conn->insert_id;
    $stmt->close();

    if ($ok) {
      // Auto-sync to Core 2 if enabled
      $sync_message = '';
      if (AUTO_SYNC_ENABLED) {
        $sync_result = syncBudgetToCore2($new_budget_id);
        if ($sync_result['success']) {
          $sync_message = ' and synced to Core 2 (Asset ID: ' . ($sync_result['core2_asset_id'] ?? 'N/A') . ')';
        } else {
          $sync_message = ' (Core 2 sync failed: ' . $sync_result['message'] . ')';
        }
      }
      
      back('success','Budget allocation added successfully' . $sync_message);
    }
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
    $old_approval_status = $_POST['old_approval_status'] ?? '';

    if ($id <= 0) back('danger','Invalid record.');

    $sql = "UPDATE budgets
            SET period=?, department=?, cost_center=?, amount_allocated=?, amount_used=?, approved_by=?, approval_status=?, description=?
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssddsssi', $period, $department, $cost_center, $amount_allocated, $amount_used, $approved_by, $approval_status, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      // Auto-sync to Core 2 if enabled and status changed to Approved
      $sync_message = '';
      $should_sync = AUTO_SYNC_ENABLED;
      
      if (SYNC_ON_APPROVAL && $old_approval_status !== 'Approved' && $approval_status === 'Approved') {
        $should_sync = true;
      }
      
      if ($should_sync) {
        $sync_result = syncBudgetToCore2($id);
        if ($sync_result['success']) {
          $sync_message = ' and synced to Core 2';
        } else {
          $sync_message = ' (Core 2 sync failed: ' . $sync_result['message'] . ')';
        }
      }
      
      back('success','Budget allocation updated successfully' . $sync_message);
    }
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

  } elseif ($action === 'manual_sync') {
    // Manual sync action - sync specific budget to Core 2
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) back('danger','Invalid budget ID for sync.');
    
    $sync_result = syncBudgetToCore2($id);
    
    if ($sync_result['success']) {
      back('success', 'Budget successfully synced to Core 2. Asset ID: ' . ($sync_result['core2_asset_id'] ?? 'N/A'));
    } else {
      back('danger', 'Failed to sync to Core 2: ' . $sync_result['message']);
    }
    
  } elseif ($action === 'bulk_sync') {
    // Bulk sync action - sync all approved budgets
    $synced = 0;
    $failed = 0;
    
    $stmt = $conn->prepare("SELECT id FROM budgets WHERE approval_status = 'Approved'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
      $sync_result = syncBudgetToCore2($row['id']);
      if ($sync_result['success']) {
        $synced++;
      } else {
        $failed++;
      }
    }
    
    $stmt->close();
    
    back('success', "Bulk sync completed. Synced: {$synced}, Failed: {$failed}");
    
  } else {
    back('warning','Unknown action.');
  }
} catch (Throwable $e) {
  back('danger','Error: '.$e->getMessage());
}

/**
 * Sync budget to Core 2 with retry logic
 * 
 * @param int $budget_id Budget ID to sync
 * @return array Sync result
 */
function syncBudgetToCore2($budget_id) {
  $attempts = 0;
  $max_attempts = SYNC_RETRY_ATTEMPTS;
  
  while ($attempts < $max_attempts) {
    $attempts++;
    
    // Prepare request
    $ch = curl_init();
    $api_url = dirname($_SERVER['PHP_SELF']) . '/core2_api_sender.php';
    $full_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $api_url;
    
    curl_setopt_array($ch, [
      CURLOPT_URL => $full_url . '?budget_id=' . $budget_id,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => API_TIMEOUT
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response !== false) {
      $result = json_decode($response, true);
      
      if ($result && $result['success']) {
        return [
          'success' => true,
          'message' => $result['message'] ?? 'Synced successfully',
          'core2_asset_id' => $result['data']['core2_asset_id'] ?? null
        ];
      }
    }
    
    // If not last attempt, wait before retry
    if ($attempts < $max_attempts) {
      sleep(SYNC_RETRY_DELAY);
    }
  }
  
  // All attempts failed
  return [
    'success' => false,
    'message' => 'Failed after ' . $max_attempts . ' attempts'
  ];
}


