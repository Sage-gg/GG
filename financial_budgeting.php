<?php
// financial_budgeting.php
session_start();

// Include database connection with error handling
require_once 'db.php';

// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check your database configuration in db.php");
}

// --- Search handling ---
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build query
$sql = "SELECT id, period, department, cost_center, amount_allocated, amount_used, approved_by, approval_status, description, created_at
        FROM budgets";
$params = [];
$types  = '';

if ($q !== '') {
  $sql .= " WHERE period LIKE CONCAT('%', ?, '%')
            OR department LIKE CONCAT('%', ?, '%')
            OR cost_center LIKE CONCAT('%', ?, '%')
            OR approved_by LIKE CONCAT('%', ?, '%')
            OR approval_status LIKE CONCAT('%', ?, '%')
            OR description LIKE CONCAT('%', ?, '%')";
  $params = [$q,$q,$q,$q,$q,$q];
  $types  = 'ssssss';
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    die("Database query failed: " . $e->getMessage());
}

// --- Summary cards ---
try {
    $sumSql = "SELECT 
                COALESCE(SUM(amount_allocated),0) AS total_budget,
                COALESCE(SUM(amount_used),0) AS total_used,
                COALESCE(SUM(amount_allocated - amount_used),0) AS total_remaining
               FROM budgets";
    $summaryResult = $conn->query($sumSql);
    if (!$summaryResult) {
        throw new Exception("Summary query failed: " . $conn->error);
    }
    $summary = $summaryResult->fetch_assoc();
} catch (Exception $e) {
    // Set default values if query fails
    $summary = [
        'total_budget' => 0,
        'total_used' => 0,
        'total_remaining' => 0
    ];
    error_log("Summary query error: " . $e->getMessage());
}

function peso($n) {
  return '₱' . number_format((float)$n, 2);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Budgeting & Cost Allocation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .table thead th { white-space: nowrap; }
    .summary-card {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
      text-align: center;
      min-width: 100px;
      max-width: 120px;
    }
    .summary-label {
      font-size: 0.75rem;
      color: #6c757d;
      font-weight: 500;
      margin-bottom: 0.25rem;
      line-height: 1;
    }
    .summary-value {
      font-size: 0.875rem;
      font-weight: 600;
      line-height: 1;
    }
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-3">Budgeting & Cost Allocation</h2>

    <!-- Flash messages -->
    <?php if(isset($_SESSION['flash'])): ?>
      <div class="alert alert-<?=$_SESSION['flash']['type']?> alert-dismissible fade show" role="alert">
        <?=$_SESSION['flash']['msg']?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Filters, Actions, and Summary Cards in one row -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
      <!-- Left side: Search functionality -->
      <form class="d-flex align-items-center" method="get">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search by department, cost center, purpose..." value="<?=htmlspecialchars($q)?>" />
          <button class="btn btn-outline-secondary" type="submit">Search</button>
          <?php if($q!==''): ?>
            <a class="btn btn-outline-danger" href="financial_budgeting.php">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <!-- Center: Summary Cards -->
      <div class="d-flex gap-2">
        <div class="summary-card">
          <div class="summary-label">Total Budget</div>
          <div class="summary-value text-primary"><?= peso($summary['total_budget']) ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Spent</div>
          <div class="summary-value text-danger"><?= peso($summary['total_used']) ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Remaining</div>
          <div class="summary-value text-success"><?= peso($summary['total_remaining']) ?></div>
        </div>
      </div>

      <!-- Right side: Add Budget button -->
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addBudgetModal">+ Add Budget</button>
        <!-- Optional export placeholder
        <a class="btn btn-outline-primary" href="budgets_actions.php?action=export">Export CSV</a>
        -->
      </div>
    </div>

    <!-- Budget Table -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Period</th>
            <th>Department</th>
            <th>Cost Center</th>
            <th>Allocated Amount</th>
            <th>Used</th>
            <th>Difference</th>
            <th>Status</th>
            <th>Approved By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($rows)): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">No budgets found. Add one to get started.</td></tr>
        <?php else: ?>
          <?php foreach($rows as $i => $r):
              $diff = (float)$r['amount_allocated'] - (float)$r['amount_used'];
              // Simple status: overspent/ on track
              if ($diff < 0) {
                $statusBadge = '<span class="badge bg-danger">Overspent</span>';
              } elseif ($diff < 0.05 * (float)$r['amount_allocated']) {
                $statusBadge = '<span class="badge bg-warning text-dark">Tight</span>';
              } else {
                $statusBadge = '<span class="badge bg-success">On Track</span>';
              }
              // pack data attributes for JS
              $dataAttrs = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
          ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r['period']) ?></td>
              <td><?= htmlspecialchars($r['department']) ?></td>
              <td><?= htmlspecialchars($r['cost_center']) ?></td>
              <td><?= peso($r['amount_allocated']) ?></td>
              <td><?= peso($r['amount_used']) ?></td>
              <td><?= ($diff<0?'-':'') . peso(abs($diff)) ?></td>
              <td><?= $statusBadge ?></td>
              <td><?= htmlspecialchars($r['approved_by']) ?></td>
              <td>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-primary btn-view" 
                          data-record="<?=$dataAttrs?>" data-bs-toggle="modal" data-bs-target="#viewBudgetModal">View</button>
                  <button type="button" class="btn btn-sm btn-warning btn-edit" 
                          data-record="<?=$dataAttrs?>" data-bs-toggle="modal" data-bs-target="#editBudgetModal">Edit</button>
                  <button type="button" class="btn btn-sm btn-danger btn-delete" 
                          data-id="<?=$r['id']?>" data-name="<?=htmlspecialchars($r['department'].' - '.$r['cost_center'])?>" 
                          data-bs-toggle="modal" data-bs-target="#deleteBudgetModal">Delete</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- (Optional) Pagination UI kept as static placeholder -->
    <nav aria-label="Budget Pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item disabled"><a class="page-link">Previous</a></li>
        <li class="page-item active"><a class="page-link">1</a></li>
        <li class="page-item"><a class="page-link">2</a></li>
        <li class="page-item"><a class="page-link">3</a></li>
        <li class="page-item"><a class="page-link">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

<!-- Modals -->
<?php include 'financial_budgeting_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate VIEW & EDIT modals from row data, and DELETE confirmation

function peso(n){
  n = parseFloat(n || 0);
  return '₱' + n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-view');
  if (!btn) return;
  const rec = JSON.parse(btn.dataset.record);
  const diff = (parseFloat(rec.amount_allocated) - parseFloat(rec.amount_used));
  document.getElementById('v_period').textContent = rec.period || '';
  document.getElementById('v_department').textContent = rec.department || '';
  document.getElementById('v_cost_center').textContent = rec.cost_center || '';
  document.getElementById('v_alloc').textContent = peso(rec.amount_allocated);
  document.getElementById('v_used').textContent = peso(rec.amount_used);
  document.getElementById('v_diff').textContent = (diff<0?'-':'') + peso(Math.abs(diff));
  document.getElementById('v_approved_by').textContent = rec.approved_by || '';
  document.getElementById('v_approval_status').textContent = rec.approval_status || '';
  document.getElementById('v_description').textContent = rec.description || '';
}, false);

document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-edit');
  if (!btn) return;
  const rec = JSON.parse(btn.dataset.record);
  // Fill form
  document.getElementById('edit_id').value = rec.id;
  document.getElementById('edit_period').value = rec.period;
  document.getElementById('edit_department').value = rec.department;
  document.getElementById('edit_cost_center').value = rec.cost_center;
  document.getElementById('edit_amount_allocated').value = rec.amount_allocated;
  document.getElementById('edit_amount_used').value = rec.amount_used;
  document.getElementById('edit_approved_by').value = rec.approved_by;
  document.getElementById('edit_approval_status').value = rec.approval_status;
  document.getElementById('edit_description').value = rec.description;
}, false);

document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-delete');
  if (!btn) return;
  document.getElementById('delete_id').value = btn.dataset.id;
  document.getElementById('delete_name').textContent = btn.dataset.name || 'this record';
}, false);
</script>
</body>
</html>