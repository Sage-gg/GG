<?php
require_once 'db.php';
// index.php
// Require login to access dashboard
requireModuleAccess('dashboard');  // ← ADD THIS LINE (replaces requireLogin)


// ORIGINAL FUNCTIONS - Keep for backward compatibility
function getCollectionsSummary() {
  global $conn;
  
  $total_collected = 0;
  $total_pending = 0;
  $total_overdue = 0;
  
  $today = date('Y-m-d');
  
  try {
    $sql = "SELECT * FROM collections";
    $res = $conn->query($sql);
    
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $total_collected += (float)$r['amount_paid'];
        
        // Calculate pending amount (amount due - amount paid, not below zero)
        $pending = max(0, (float)$r['amount_due'] - (float)$r['amount_paid']);
        $total_pending += $pending;

        // Calculate overdue (pending amounts past due date for unpaid/partial status)
        if ($r['payment_status'] !== 'Paid' && $r['due_date'] < $today && $pending > 0) {
          $total_overdue += $pending;
        }
      }
    }
  } catch (Exception $e) {
    // Handle database errors gracefully
    error_log("Dashboard collections summary error: " . $e->getMessage());
  }
  
  return [
    'total_collected' => round($total_collected, 2),
    'total_pending' => round($total_pending, 2),
    'total_overdue' => round($total_overdue, 2)
  ];
}

// Function to get budget summary data
function getBudgetSummary() {
  global $conn;
  
  $total_budget = 0;
  $total_used = 0;
  $total_remaining = 0;
  
  try {
    $sql = "SELECT 
              COALESCE(SUM(amount_allocated),0) AS total_budget,
              COALESCE(SUM(amount_used),0) AS total_used,
              COALESCE(SUM(amount_allocated - amount_used),0) AS total_remaining
            FROM budgets";
    
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
      $total_budget = (float)$row['total_budget'];
      $total_used = (float)$row['total_used'];
      $total_remaining = (float)$row['total_remaining'];
    }
  } catch (Exception $e) {
    // Handle database errors gracefully
    error_log("Dashboard budget summary error: " . $e->getMessage());
  }
  
  return [
    'total_budget' => round($total_budget, 2),
    'total_used' => round($total_used, 2),
    'total_remaining' => round($total_remaining, 2)
  ];
}

// NEW DETAILED BREAKDOWN FUNCTIONS
function getCollectionsBreakdown() {
  global $conn;
  
  $breakdown = [
    'total_collected' => ['amount' => 0, 'records' => []],
    'total_pending' => ['amount' => 0, 'records' => []],
    'total_overdue' => ['amount' => 0, 'records' => []],
    'summary_stats' => []
  ];
  
  $today = date('Y-m-d');
  
  try {
    $sql = "SELECT * FROM collections ORDER BY due_date ASC";
    $res = $conn->query($sql);
    
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        // COLLECTED: All payments made
        $amountPaid = (float)$r['amount_paid'];
        if ($amountPaid > 0) {
          $breakdown['total_collected']['amount'] += $amountPaid;
          $breakdown['total_collected']['records'][] = [
            'client' => $r['client_name'],
            'invoice' => $r['invoice_no'],
            'amount_due' => $r['amount_due'],
            'amount_paid' => $amountPaid,
            'payment_status' => $r['payment_status'],
            'billing_date' => $r['billing_date'],
            'due_date' => $r['due_date']
          ];
        }
        
        // PENDING: Amount due minus amount paid (not yet collected)
        $pending = max(0, (float)$r['amount_due'] - (float)$r['amount_paid']);
        if ($pending > 0) {
          $breakdown['total_pending']['amount'] += $pending;
          $breakdown['total_pending']['records'][] = [
            'client' => $r['client_name'],
            'invoice' => $r['invoice_no'],
            'amount_due' => $r['amount_due'],
            'amount_paid' => $r['amount_paid'],
            'pending_amount' => $pending,
            'payment_status' => $r['payment_status'],
            'due_date' => $r['due_date'],
            'days_until_due' => (strtotime($r['due_date']) - strtotime($today)) / 86400
          ];
        }
        
        // OVERDUE: Pending amounts past due date
        if ($r['payment_status'] !== 'Paid' && $r['due_date'] < $today && $pending > 0) {
          $breakdown['total_overdue']['amount'] += $pending;
          $breakdown['total_overdue']['records'][] = [
            'client' => $r['client_name'],
            'invoice' => $r['invoice_no'],
            'amount_due' => $r['amount_due'],
            'amount_paid' => $r['amount_paid'],
            'overdue_amount' => $pending,
            'payment_status' => $r['payment_status'],
            'due_date' => $r['due_date'],
            'days_overdue' => floor((strtotime($today) - strtotime($r['due_date'])) / 86400),
            'penalty' => $r['penalty']
          ];
        }
      }
      
      // Calculate summary statistics
      $breakdown['summary_stats'] = [
        'total_invoices' => $res->num_rows,
        'paid_count' => count(array_filter($breakdown['total_collected']['records'], function($r) {
          return $r['payment_status'] === 'Paid';
        })),
        'partial_count' => count(array_filter($breakdown['total_pending']['records'], function($r) {
          return $r['payment_status'] === 'Partial';
        })),
        'unpaid_count' => count(array_filter($breakdown['total_pending']['records'], function($r) {
          return $r['payment_status'] === 'Unpaid';
        })),
        'overdue_count' => count($breakdown['total_overdue']['records'])
      ];
    }
  } catch (Exception $e) {
    error_log("Collections breakdown error: " . $e->getMessage());
  }
  
  return $breakdown;
}

function getBudgetBreakdown() {
  global $conn;
  
  $breakdown = [
    'total_budget' => ['amount' => 0, 'records' => []],
    'total_used' => ['amount' => 0, 'records' => []],
    'total_remaining' => ['amount' => 0, 'records' => []],
    'by_department' => [],
    'by_period' => [],
    'summary_stats' => []
  ];
  
  try {
    $sql = "SELECT * FROM budgets ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $allocated = (float)$row['amount_allocated'];
        $used = (float)$row['amount_used'];
        $remaining = $allocated - $used;
        $utilizationPct = $allocated > 0 ? ($used / $allocated) * 100 : 0;
        
        // TOTAL BUDGET: Sum of all allocated amounts
        $breakdown['total_budget']['amount'] += $allocated;
        $breakdown['total_budget']['records'][] = [
          'department' => $row['department'],
          'cost_center' => $row['cost_center'],
          'period' => $row['period'],
          'allocated' => $allocated,
          'approval_status' => $row['approval_status'],
          'approved_by' => $row['approved_by']
        ];
        
        // TOTAL USED: Sum of all used amounts
        $breakdown['total_used']['amount'] += $used;
        $breakdown['total_used']['records'][] = [
          'department' => $row['department'],
          'cost_center' => $row['cost_center'],
          'period' => $row['period'],
          'used' => $used,
          'allocated' => $allocated,
          'utilization_pct' => $utilizationPct
        ];
        
        // TOTAL REMAINING: Sum of all remaining amounts
        $breakdown['total_remaining']['amount'] += $remaining;
        $breakdown['total_remaining']['records'][] = [
          'department' => $row['department'],
          'cost_center' => $row['cost_center'],
          'period' => $row['period'],
          'remaining' => $remaining,
          'allocated' => $allocated,
          'remaining_pct' => $allocated > 0 ? ($remaining / $allocated) * 100 : 0
        ];
        
        // Group by department
        if (!isset($breakdown['by_department'][$row['department']])) {
          $breakdown['by_department'][$row['department']] = [
            'allocated' => 0,
            'used' => 0,
            'remaining' => 0,
            'count' => 0
          ];
        }
        $breakdown['by_department'][$row['department']]['allocated'] += $allocated;
        $breakdown['by_department'][$row['department']]['used'] += $used;
        $breakdown['by_department'][$row['department']]['remaining'] += $remaining;
        $breakdown['by_department'][$row['department']]['count']++;
        
        // Group by period
        if (!isset($breakdown['by_period'][$row['period']])) {
          $breakdown['by_period'][$row['period']] = [
            'allocated' => 0,
            'used' => 0,
            'remaining' => 0,
            'count' => 0
          ];
        }
        $breakdown['by_period'][$row['period']]['allocated'] += $allocated;
        $breakdown['by_period'][$row['period']]['used'] += $used;
        $breakdown['by_period'][$row['period']]['remaining'] += $remaining;
        $breakdown['by_period'][$row['period']]['count']++;
      }
      
      // Calculate summary statistics
      $breakdown['summary_stats'] = [
        'total_budgets' => $result->num_rows,
        'approved_count' => 0,
        'pending_count' => 0,
        'rejected_count' => 0,
        'overall_utilization' => $breakdown['total_budget']['amount'] > 0 ? 
          ($breakdown['total_used']['amount'] / $breakdown['total_budget']['amount']) * 100 : 0
      ];
      
      // Count approval statuses
      foreach ($breakdown['total_budget']['records'] as $rec) {
        if ($rec['approval_status'] === 'Approved') $breakdown['summary_stats']['approved_count']++;
        elseif ($rec['approval_status'] === 'Pending') $breakdown['summary_stats']['pending_count']++;
        elseif ($rec['approval_status'] === 'Rejected') $breakdown['summary_stats']['rejected_count']++;
      }
    }
  } catch (Exception $e) {
    error_log("Budget breakdown error: " . $e->getMessage());
  }
  
  return $breakdown;
}

// Get the summaries (original functions)
$collectionsSummary = getCollectionsSummary();
$budgetSummary = getBudgetSummary();

// Get the detailed breakdowns (new functions)
$collectionsBreakdown = getCollectionsBreakdown();
$budgetBreakdown = getBudgetBreakdown();

// Format currency function
function formatCurrency($amount) {
  return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Financial System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .card-link {
      text-decoration: none;
      color: inherit;
    }
    .card-link .card {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      cursor: pointer;
    }
    .card-link .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    .summary-card {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border: 1px solid #dee2e6;
      border-radius: 0.5rem;
      padding: 1.5rem;
      text-align: center;
      height: 100%;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      position: relative;
    }
    .summary-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .summary-card::after {
      content: 'Click for details';
      position: absolute;
      bottom: 0.5rem;
      right: 0.75rem;
      font-size: 0.7rem;
      color: #6c757d;
      opacity: 0;
      transition: opacity 0.2s;
    }
    .summary-card:hover::after {
      opacity: 1;
    }
    .summary-icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }
    .summary-label {
      font-size: 0.9rem;
      color: #6c757d;
      font-weight: 500;
      margin-bottom: 0.5rem;
    }
    .summary-value {
      font-size: 1.5rem;
      font-weight: 700;
      line-height: 1.2;
      min-height: 2rem;
    }
    .text-collected { color: #198754; }
    .text-pending { color: #fd7e14; }
    .text-overdue { color: #dc3545; }
    .text-budget { color: #0d6efd; }
    .text-spent { color: #dc3545; }
    .text-remaining { color: #198754; }
    
    /* Privacy Toggle Button */
    .privacy-toggle {
      position: absolute;
      top: 0.75rem;
      right: 0.75rem;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid #dee2e6;
      border-radius: 0.25rem;
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
      cursor: pointer;
      transition: all 0.2s;
      z-index: 10;
    }
    .privacy-toggle:hover {
      background: white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .privacy-toggle i {
      font-size: 0.875rem;
    }
    
    /* Hidden value style - keep same size as visible */
    .summary-value.value-hidden {
      letter-spacing: 0.1rem;
      /* Don't change font-size to keep uniform appearance */
    }
    
    /* Breakdown Modal Styles */
    .breakdown-section {
      background: #f8f9fa;
      border-radius: 0.375rem;
      padding: 1rem;
      margin-bottom: 1rem;
    }
    .breakdown-header {
      font-weight: 600;
      color: #495057;
      margin-bottom: 0.75rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #dee2e6;
    }
    .breakdown-item {
      padding: 0.5rem;
      margin-bottom: 0.5rem;
      background: white;
      border-radius: 0.25rem;
      border-left: 3px solid #0d6efd;
    }
    .breakdown-item:hover {
      background: #f1f3f5;
    }
    .stat-badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.875rem;
      font-weight: 500;
    }
    .formula-box {
      background: #fff3cd;
      border: 1px solid #ffc107;
      border-radius: 0.25rem;
      padding: 0.75rem;
      margin: 0.5rem 0;
      font-family: 'Courier New', monospace;
      font-size: 0.875rem;
    }
    .detail-table {
      font-size: 0.875rem;
    }
    .detail-table th {
      background: #e9ecef;
      font-weight: 600;
      padding: 0.5rem;
    }
    .detail-table td {
      padding: 0.5rem;
    }
  </style>
</head>
<body>

  <?php include 'sidebar_navbar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid mt-4 px-4">
      
      <?php if (isset($_GET['access_denied'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <strong>Access Denied!</strong> You don't have permission to access that resource.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <!-- Page Content -->
      <h2 class="fw-bold mb-4">Dashboard</h2>
      
      <!-- Collections Summary -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Collections Summary</h4>
        <a href="financial_collections.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-right"></i> View Details
        </a>
      </div>
      
      <div class="row g-4 mb-5">
        <div class="col-md-4">
          <div class="summary-card" onclick="showBreakdown('collected', event)">
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'collected')" title="Show/Hide Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
            <i class="bi bi-cash-coin summary-icon text-collected"></i>
            <div class="summary-label">Total Collected</div>
            <div class="summary-value text-collected" id="value-collected" data-value="<?php echo formatCurrency($collectionsSummary['total_collected']); ?>">
              ₱ ••••••••
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card" onclick="showBreakdown('pending', event)">
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'pending')" title="Show/Hide Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
            <i class="bi bi-clock-history summary-icon text-pending"></i>
            <div class="summary-label">Pending Collections</div>
            <div class="summary-value text-pending" id="value-pending" data-value="<?php echo formatCurrency($collectionsSummary['total_pending']); ?>">
              ₱ ••••••••
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card" onclick="showBreakdown('overdue', event)">
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'overdue')" title="Show/Hide Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
            <i class="bi bi-exclamation-triangle summary-icon text-overdue"></i>
            <div class="summary-label">Overdue Collections</div>
            <div class="summary-value text-overdue" id="value-overdue" data-value="<?php echo formatCurrency($collectionsSummary['total_overdue']); ?>">
              ₱ ••••••••
            </div>
          </div>
        </div>
      </div>

      <!-- Budget Summary -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Budget Summary</h4>
        <a href="financial_budgeting.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-right"></i> View Details
        </a>
      </div>
      
      <div class="row g-4 mb-5">
        <div class="col-md-4">
          <div class="summary-card" onclick="showBreakdown('budget', event)">
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'budget')" title="Show/Hide Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
            <i class="bi bi-pie-chart summary-icon text-budget"></i>
            <div class="summary-label">Total Budget</div>
            <div class="summary-value text-budget" id="value-budget" data-value="<?php echo formatCurrency($budgetSummary['total_budget']); ?>">
              ₱ ••••••••
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card" onclick="showBreakdown('spent', event)">
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'spent')" title="Show/Hide Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
            <i class="bi bi-cash-stack summary-icon text-spent"></i>
            <div class="summary-label">Total Spent</div>
            <div class="summary-value text-spent" id="value-spent" data-value="<?php echo formatCurrency($budgetSummary['total_used']); ?>">
              ₱ ••••••••
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card" onclick="showBreakdown('remaining', event)">
            <button class="privacy-toggle" onclick="togglePrivacy(event, 'remaining')" title="Show/Hide Amount">
              <i class="bi bi-eye-slash"></i>
            </button>
            <i class="bi bi-wallet2 summary-icon text-remaining"></i>
            <div class="summary-label">Remaining Budget</div>
            <div class="summary-value text-remaining" id="value-remaining" data-value="<?php echo formatCurrency($budgetSummary['total_remaining']); ?>">
              ₱ ••••••••
            </div>
          </div>
        </div>
      </div>

      <!-- Module Navigation Cards -->
      <h4 class="fw-bold mb-3">Financial Modules</h4>
      <div class="row g-4">

        <div class="col-md-4">
          <a href="index.php" class="card-link">
            <div class="card p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-speedometer2 me-3 text-primary" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Dashboard</h5>
              </div>
              <p class="text-muted mb-0">Get a quick overview of key financial metrics for crane and trucking operations.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_collections.php" class="card-link">
            <div class="card p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-collection me-3 text-success" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Collections Management</h5>
              </div>
              <p class="text-muted mb-0">Track and manage income from services, rentals, deliveries, and other receivables.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_budgeting.php" class="card-link">
            <div class="card p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-pie-chart me-3 text-info" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Budgeting & Cost Allocation</h5>
              </div>
              <p class="text-muted mb-0">Plan operational budgets and allocate costs across vehicle fleets and project sites.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_expense.php" class="card-link">
            <div class="card p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-receipt me-3 text-warning" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Expense Tracking & Tax Management</h5>
              </div>
              <p class="text-muted mb-0">Monitor fuel, maintenance, salaries, and tax-related expenses with accurate logs.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_ledger.php" class="card-link">
            <div class="card p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-journal-text me-3 text-secondary" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">General Ledger Module</h5>
              </div>
              <p class="text-muted mb-0">Maintain a comprehensive log of all company transactions for internal control.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_reporting.php" class="card-link">
            <div class="card p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-graph-up me-3 text-danger" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Financial Reporting Module</h5>
              </div>
              <p class="text-muted mb-0">Generate reports for income, expenditures, and fleet profitability for stakeholders.</p>
            </div>
          </a>
        </div>

      </div>
    </div>
  </div>

  <!-- Breakdown Modals -->
  <?php include 'dashboard_breakdown_modals.php'; ?>

  <!-- Session timeout handling -->
  <script>
    // Auto-logout functionality
    let sessionTimeout;
    let warningTimeout;
    let lastActivity = Date.now();

    function resetSessionTimer() {
      clearTimeout(sessionTimeout);
      clearTimeout(warningTimeout);
      lastActivity = Date.now();
      
      // Show warning 1 minute before logout
      warningTimeout = setTimeout(function() {
        if (confirm('Your session will expire in 1 minute due to inactivity. Click OK to continue your session.')) {
          // User clicked OK, send activity ping
          fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin'
          });
          resetSessionTimer();
        }
      }, <?php echo (SESSION_TIMEOUT - 60) * 1000; ?>); // 1 minute before 2-minute timeout
      
      // Auto logout after full timeout
      sessionTimeout = setTimeout(function() {
        alert('Your session has expired due to inactivity. You will be redirected to the login page.');
        window.location.href = 'login.php?timeout=1';
      }, <?php echo SESSION_TIMEOUT * 1000; ?>); // 2 minutes
    }

    // Track user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
      document.addEventListener(event, function() {
        if (Date.now() - lastActivity > 60000) { // Only reset if more than 1 minute passed
          resetSessionTimer();
        }
      }, { capture: true, passive: true });
    });

    // Start session timer
    resetSessionTimer();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  // Store breakdown data
  const breakdownData = <?php echo json_encode([
    'collections' => $collectionsBreakdown,
    'budget' => $budgetBreakdown
  ]); ?>;

  // Track visibility state for each card
  const visibilityState = {
    'collected': false,
    'pending': false,
    'overdue': false,
    'budget': false,
    'spent': false,
    'remaining': false
  };

  function togglePrivacy(event, cardId) {
    // Prevent card click event from firing
    event.stopPropagation();
    
    const valueElement = document.getElementById('value-' + cardId);
    const button = event.currentTarget;
    const icon = button.querySelector('i');
    
    // Toggle visibility state
    visibilityState[cardId] = !visibilityState[cardId];
    
    if (visibilityState[cardId]) {
      // Show the actual value
      valueElement.textContent = valueElement.getAttribute('data-value');
      valueElement.classList.remove('value-hidden');
      icon.className = 'bi bi-eye';
      button.title = 'Hide Amount';
    } else {
      // Hide the value with asterisks
      valueElement.textContent = '₱ ••••••••';
      valueElement.classList.add('value-hidden');
      icon.className = 'bi bi-eye-slash';
      button.title = 'Show Amount';
    }
  }

  function showBreakdown(type, event) {
    // Check if the click target is the privacy toggle button or its child
    if (event && (event.target.classList.contains('privacy-toggle') || 
        event.target.closest('.privacy-toggle'))) {
      return; // Don't show breakdown if privacy button was clicked
    }
    
    const modalId = type + 'BreakdownModal';
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
  }

  function peso(n) {
    return '₱' + parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }
  </script>

</body>
</html>
