<?php
require_once 'db.php';

// Require login to access dashboard
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Financial System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      <div class="row g-4">

        <div class="col-md-4">
          <a href="index.php" class="card-link">
            <div class="card p-4">
              <h5>Dashboard</h5>
              <p>Get a quick overview of key financial metrics for crane and trucking operations.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_collections.php" class="card-link">
            <div class="card p-4">
              <h5>Collections Management</h5>
              <p>Track and manage income from services, rentals, deliveries, and other receivables.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_budgeting.php" class="card-link">
            <div class="card p-4">
              <h5>Budgeting & Cost Allocation</h5>
              <p>Plan operational budgets and allocate costs across vehicle fleets and project sites.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_expense.php" class="card-link">
            <div class="card p-4">
              <h5>Expense Tracking & Tax Management</h5>
              <p>Monitor fuel, maintenance, salaries, and tax-related expenses with accurate logs.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_ledger.php" class="card-link">
            <div class="card p-4">
              <h5>General Ledger Module</h5>
              <p>Maintain a comprehensive log of all company transactions for internal control.</p>
            </div>
          </a>
        </div>

        <div class="col-md-4">
          <a href="financial_reporting.php" class="card-link">
            <div class="card p-4">
              <h5>Financial Reporting Module</h5>
              <p>Generate reports for income, expenditures, and fleet profitability for stakeholders.</p>
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