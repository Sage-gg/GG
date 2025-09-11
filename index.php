<?php
require_once 'db.php';

// Require login to access dashboard
requireLogin();

// Function to get collections summary data
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

// Get the summaries
$collectionsSummary = getCollectionsSummary();
$budgetSummary = getBudgetSummary();

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
    }
    .summary-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
    }
    .text-collected { color: #198754; }
    .text-pending { color: #fd7e14; }
    .text-overdue { color: #dc3545; }
    .text-budget { color: #0d6efd; }
    .text-spent { color: #dc3545; }
    .text-remaining { color: #198754; }
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
          <div class="summary-card">
            <i class="bi bi-cash-coin summary-icon text-collected"></i>
            <div class="summary-label">Total Collected</div>
            <div class="summary-value text-collected"><?php echo formatCurrency($collectionsSummary['total_collected']); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card">
            <i class="bi bi-clock-history summary-icon text-pending"></i>
            <div class="summary-label">Pending Collections</div>
            <div class="summary-value text-pending"><?php echo formatCurrency($collectionsSummary['total_pending']); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card">
            <i class="bi bi-exclamation-triangle summary-icon text-overdue"></i>
            <div class="summary-label">Overdue Collections</div>
            <div class="summary-value text-overdue"><?php echo formatCurrency($collectionsSummary['total_overdue']); ?></div>
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
          <div class="summary-card">
            <i class="bi bi-pie-chart summary-icon text-budget"></i>
            <div class="summary-label">Total Budget</div>
            <div class="summary-value text-budget"><?php echo formatCurrency($budgetSummary['total_budget']); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card">
            <i class="bi bi-cash-stack summary-icon text-spent"></i>
            <div class="summary-label">Total Spent</div>
            <div class="summary-value text-spent"><?php echo formatCurrency($budgetSummary['total_used']); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="summary-card">
            <i class="bi bi-wallet2 summary-icon text-remaining"></i>
            <div class="summary-label">Remaining Budget</div>
            <div class="summary-value text-remaining"><?php echo formatCurrency($budgetSummary['total_remaining']); ?></div>
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
      
      // Show warning 5 minutes before logout
      warningTimeout = setTimeout(function() {
        if (confirm('Your session will expire in 5 minutes due to inactivity. Click OK to continue your session.')) {
          // User clicked OK, send activity ping
          fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin'
          });
          resetSessionTimer();
        }
      }, <?php echo (SESSION_TIMEOUT - 300) * 1000; ?>); // 5 minutes before 10-minute timeout
      
      // Auto logout after full timeout
      sessionTimeout = setTimeout(function() {
        alert('Your session has expired due to inactivity. You will be redirected to the login page.');
        window.location.href = 'login.php?timeout=1';
      }, <?php echo SESSION_TIMEOUT * 1000; ?>); // 10 minutes
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

</body>
</html>