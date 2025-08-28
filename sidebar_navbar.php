<?php
require_once 'db.php';

// Make sure user is logged in to access this navbar
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user information
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get first letter of username for avatar
$profile_initial = strtoupper(substr($username, 0, 1));

// Determine role display
$role_display = $role === 'admin' ? 'Administrator' : 'User';
?>

<!-- Top Navbar -->
<nav class="navbar navbar-dark bg-dark shadow-sm px-4 py-3 position-relative">
  <div class="d-flex align-items-center">
    <button class="btn btn-outline-light sidebar-toggle" id="toggleSidebar">
      <span class="navbar-toggler-icon">☰</span>
    </button>
    
    <!-- Financial System Text next to menu button -->
    <button class="financial-btn text-light fw-normal ms-3" id="financialBtn" aria-label="Financial System Home">
      FINANCIAL SYSTEM
    </button>
  </div>

  <div class="ms-auto d-flex align-items-center gap-3 position-relative">
    <!-- Session Timer Display -->
    <div class="text-light small me-3" id="sessionTimer" style="font-size: 0.85rem;">
      Session: <span id="timeRemaining">10:00</span>
    </div>
    
    <!-- Notifications -->
    <div class="nav-item-wrapper position-relative">
      <a href="#" class="nav-link text-light d-flex align-items-center" id="bellIcon" aria-label="Notifications">
        <span class="notification-icon">🔔</span>
        <span class="notification-badge">3</span>
      </a>
      
      <!-- Bell Popup -->
      <div id="bellPopup" class="popup d-none shadow-lg">
        <div class="popup-header">
          <span class="popup-title">Notifications</span>
          <span class="notification-count">3</span>
        </div>
        <div class="popup-body">
          <div class="notification-item">
            <span class="notification-dot"></span>
            <span>New transaction recorded</span>
          </div>
          <div class="notification-item">
            <span class="notification-dot"></span>
            <span>Budget alert: 80% used</span>
          </div>
          <div class="notification-item">
            <span class="notification-dot"></span>
            <span>Monthly report ready</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <!-- System Brand Header -->
  <div class="sidebar-brand">
    <div class="brand-section d-flex align-items-center justify-content-center">
      <div class="brand-text">CraneSYSTEM</div>
    </div>
  </div>

  <!-- Profile Header -->
  <div class="sidebar-header px-4 py-3">
    <div class="profile-section d-flex align-items-center">
      <div class="profile-avatar">
        <span class="profile-initial"><?php echo $profile_initial; ?></span>
      </div>
      <div class="profile-info ms-3">
        <div class="profile-name text-light fw-bold"><?php echo htmlspecialchars($username); ?></div>
        <div class="profile-role text-muted"><?php echo $role_display; ?></div>
      </div>
    </div>
  </div>
  
  <!-- Main Navigation Menu -->
  <div class="sidebar-menu">
    <ul class="nav flex-column px-3 py-2">
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link active" href="index.php">
          <span class="nav-icon">📊</span>
          <span class="nav-text">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="financial_collections.php">
          <span class="nav-icon">💰</span>
          <span class="nav-text">Collections Management</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="financial_budgeting.php">
          <span class="nav-icon">📋</span>
          <span class="nav-text">Budgeting and Cost Allocation Management</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="financial_expense.php">
          <span class="nav-icon">💳</span>
          <span class="nav-text">Expense Tracking and Tax Management</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="financial_ledger.php">
          <span class="nav-icon">📖</span>
          <span class="nav-text">General Ledger Module</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="financial_reporting.php">
          <span class="nav-icon">📈</span>
          <span class="nav-text">Financial Reporting Module</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Profile Menu Section -->
  <div class="sidebar-profile-menu">
    <div class="profile-divider"></div>
    <ul class="nav flex-column px-3 py-2">
      <?php if (isAdmin()): ?>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="user_management.php">
          <span class="nav-icon">👥</span>
          <span class="nav-text">User Management</span>
        </a>
      </li>
      <?php endif; ?>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="profile.php">
          <span class="nav-icon">👤</span>
          <span class="nav-text">Profile</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link" href="settings.php">
          <span class="nav-icon">⚙️</span>
          <span class="nav-text">Settings</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Logout Section (Bottom) -->
  <div class="sidebar-logout">
    <div class="logout-divider"></div>
    <ul class="nav flex-column px-3 py-2">
      <li class="nav-item">
        <a class="nav-link sidebar-nav-link logout-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
          <span class="nav-icon">🚪</span>
          <span class="nav-text">Logout</span>
        </a>
      </li>
    </ul>
  </div>
</div>

<style>
  /* Dark Theme Variables */
  :root {
    --dark-bg: #1a1a1a;
    --darker-bg: #111111;
    --sidebar-bg: #2d2d2d;
    --sidebar-hover: #3d3d3d;
    --text-light: #ffffff;
    --text-muted: #b0b0b0;
    --accent-color: #007bff;
    --accent-hover: #0056b3;
    --border-color: #404040;
    --navbar-height: 64px;
  }

  /* Reset body background for dark theme */
  body {
    background-color: var(--dark-bg) !important;
  }

  /* Top Navbar Styling - FIXED POSITION */
  nav.navbar {
    background: linear-gradient(135deg, var(--darker-bg) 0%, #2a2a2a 100%) !important;
    border-bottom: 1px solid var(--border-color);
    backdrop-filter: blur(10px);
    position: fixed !important;
    top: 0;
    left: 280px;
    right: 0;
    width: calc(100% - 280px);
    z-index: 999;
    transition: left 0.3s ease, width 0.3s ease;
  }

  .navbar-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1050;
  }

  .financial-btn {
    color: var(--text-light);
    font-size: 1rem;
    letter-spacing: 1px;
    border: none;
    cursor: pointer;
    user-select: none;
    padding: 0;
    background: transparent;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .financial-btn:hover {
    color: #ccc;
    text-decoration: none;
  }

  .financial-btn:active {
    color: var(--text-light);
  }

  /* Session Timer Styling */
  #sessionTimer {
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  /* Sidebar Toggle Button */
  .sidebar-toggle {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--text-light);
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.3s ease;
  }

  .sidebar-toggle:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
  }

  /* Navbar Items */
  .nav-item-wrapper {
    position: relative;
  }

  .navbar .nav-link {
    color: var(--text-light) !important;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 8px 12px;
  }

  .navbar .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
  }

  /* Notification Icon */
  .notification-icon {
    font-size: 20px;
    position: relative;
  }

  .notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
  }

  /* Sidebar Styling */
  .sidebar {
    width: 280px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, var(--sidebar-bg) 0%, #252525 100%);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
    z-index: 1040;
    transition: transform 0.3s ease;
    overflow-y: auto;
    border-right: 1px solid var(--border-color);
    transform: translateX(0);
    display: flex;
    flex-direction: column;
  }

  /* System Brand Header */
  .sidebar-brand {
    height: 80px;
    background: linear-gradient(135deg, var(--darker-bg), #1f1f1f);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 0 1rem;
  }

  .brand-section {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .brand-text {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-light);
    letter-spacing: 2px;
    text-align: center;
    line-height: 1;
  }

  /* Profile Section in Sidebar Header */
  .sidebar-header {
    border-bottom: 1px solid var(--border-color);
    background: rgba(0, 0, 0, 0.2);
    padding: 1rem 1.5rem !important;
    flex-shrink: 0;
    height: 80px;
    display: flex;
    align-items: center;
  }

  .profile-section {
    display: flex;
    align-items: center;
    width: 100%;
  }

  .profile-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
  }

  .profile-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-top: 8px;
  }

  .profile-name {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    line-height: 1.3;
  }

  .profile-role {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.3;
    margin: 0;
  }

  /* Main Sidebar Menu */
  .sidebar-menu {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
  }

  /* Profile Menu Section */
  .sidebar-profile-menu {
    flex-shrink: 0;
    padding: 0;
  }

  .profile-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border-color), transparent);
    margin: 0 1rem;
  }

  /* Logout Section */
  .sidebar-logout {
    flex-shrink: 0;
    margin-top: auto;
    padding: 0 0 1rem 0;
  }

  .logout-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #dc3545, transparent);
    margin: 0 1rem 0.5rem 1rem;
  }

  /* Sidebar Navigation Links */
  .sidebar-nav-link {
    color: var(--text-muted) !important;
    font-weight: 500;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 4px 8px;
    transition: all 0.3s ease;
    background-color: transparent;
    display: flex;
    align-items: center;
    text-decoration: none;
    border-left: 3px solid transparent;
  }

  .sidebar-nav-link:hover {
    background: linear-gradient(135deg, var(--sidebar-hover), #454545);
    color: var(--text-light) !important;
    transform: translateX(4px);
    border-left-color: var(--accent-color);
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
  }

  .sidebar-nav-link.active {
    background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
    color: var(--text-light) !important;
    border-left-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
  }

  /* Special styling for logout link */
  .sidebar-nav-link.logout-link {
    color: #ff9999 !important;
  }

  .sidebar-nav-link.logout-link:hover {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.3));
    color: #ffffff !important;
    border-left-color: #dc3545;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
  }

  .nav-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 16px;
    flex-shrink: 0;
  }

  .nav-text {
    flex: 1;
    font-size: 14px;
    line-height: 1.4;
  }

  /* Popup Styling */
  .popup {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 280px;
    background: var(--sidebar-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    font-size: 14px;
    color: var(--text-light);
    z-index: 1100;
    backdrop-filter: blur(10px);
    overflow: hidden;
  }

  .popup-header {
    background: linear-gradient(135deg, var(--darker-bg), #2a2a2a);
    border-bottom: 1px solid var(--border-color);
    padding: 12px 16px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .popup-title {
    color: var(--text-light);
  }

  .notification-count {
    background: #dc3545;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: bold;
  }

  .popup-body {
    padding: 8px 0;
    max-height: 300px;
    overflow-y: auto;
  }

  /* Notification Items */
  .notification-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: var(--text-muted);
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
  }

  .notification-item:hover {
    background: var(--sidebar-hover);
    color: var(--text-light);
    border-left-color: var(--accent-color);
    transform: translateX(4px);
  }

  .notification-dot {
    width: 8px;
    height: 8px;
    background: var(--accent-color);
    border-radius: 50%;
    margin-right: 12px;
    flex-shrink: 0;
  }

  /* Main Content */
  .main-content {
    margin-left: 280px;
    padding-top: 80px;
    transition: margin-left 0.3s ease;
    background-color: #f8fafc !important;
    min-height: calc(100vh - 64px);
  }

  /* Sidebar states */
  body.sidebar-collapsed .sidebar {
    transform: translateX(-100%);
  }

  body.sidebar-collapsed nav.navbar {
    left: 0;
    width: 100%;
  }

  body.sidebar-collapsed .main-content {
    margin-left: 0;
  }

  /* Mobile responsive */
  @media (max-width: 768px) {
    nav.navbar {
      left: 0 !important;
      width: 100% !important;
    }
    
    .sidebar {
      width: 100%;
      transform: translateX(-100%);
    }

    .sidebar-brand {
      height: 64px;
    }

    .sidebar-header {
      height: 64px;
      padding: 0.75rem 1.25rem !important;
    }

    .brand-text {
      font-size: 16px;
      letter-spacing: 1.5px;
    }
    
    .main-content {
      margin-left: 0 !important;
      padding-top: 80px;
    }
    
    body.sidebar-mobile-open .sidebar {
      transform: translateX(0);
    }

    .financial-btn {
      font-size: 0.9rem;
    }

    #sessionTimer {
      font-size: 0.75rem !important;
    }
  }

  @media (max-width: 576px) {
    .popup {
      width: 260px;
      right: -40px;
    }
    
    .financial-btn {
      font-size: 0.8rem;
    }

    .sidebar {
      width: calc(100% - 20px);
      margin: 0 10px;
      border-radius: 0 0 12px 12px;
    }

    #sessionTimer {
      display: none; /* Hide on very small screens */
    }
  }

  /* Scrollbar Styling */
  .sidebar::-webkit-scrollbar {
    width: 6px;
  }

  .sidebar::-webkit-scrollbar-track {
    background: var(--darker-bg);
  }

  .sidebar::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
  }

  .d-none {
    display: none !important;
  }
</style>

<script>
  // Session timer functionality
  let sessionStartTime = <?php echo $_SESSION['last_activity'] * 1000; ?>;
  let sessionTimeout = <?php echo SESSION_TIMEOUT * 1000; ?>;
  let sessionTimerInterval;

  function updateSessionTimer() {
    let currentTime = Date.now();
    let elapsedTime = currentTime - sessionStartTime;
    let remainingTime = sessionTimeout - elapsedTime;
    
    if (remainingTime <= 0) {
      clearInterval(sessionTimerInterval);
      document.getElementById('timeRemaining').textContent = '00:00';
      alert('Your session has expired. You will be redirected to the login page.');
      window.location.href = 'login.php?timeout=1';
      return;
    }
    
    let minutes = Math.floor(remainingTime / 60000);
    let seconds = Math.floor((remainingTime % 60000) / 1000);
    
    document.getElementById('timeRemaining').textContent = 
      String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  }

  // Update session timer every second
  sessionTimerInterval = setInterval(updateSessionTimer, 1000);
  updateSessionTimer(); // Initial call

  // Reset session timer on activity
  ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
    document.addEventListener(event, function() {
      sessionStartTime = Date.now();
    }, { capture: true, passive: true });
  });

  // Financial System button click handler
  document.getElementById('financialBtn').addEventListener('click', function() {
    window.location.href = 'index.php';
  });

  // Sidebar toggle functionality
  document.getElementById('toggleSidebar').addEventListener('click', function(e) {
    e.preventDefault();
    
    if (window.innerWidth <= 768) {
      document.body.classList.toggle('sidebar-mobile-open');
    } else {
      document.body.classList.toggle('sidebar-collapsed');
    }
  });

  // Bell popup functionality
  const bellIcon = document.getElementById('bellIcon');
  const bellPopup = document.getElementById('bellPopup');

  bellIcon.addEventListener('click', function(e) {
    e.preventDefault();
    bellPopup.classList.toggle('d-none');
  });

  // Close popups when clicking outside
  document.addEventListener('click', function(event) {
    if (!bellIcon.contains(event.target) && !bellPopup.contains(event.target)) {
      bellPopup.classList.add('d-none');
    }
  });

  // Set active navigation link
  document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar-nav-link');
    
    navLinks.forEach(link => {
      link.classList.remove('active');
      const href = link.getAttribute('href');
      if (href === currentPage || (currentPage === '' && href === 'index.php')) {
        link.classList.add('active');
      }
    });
  });

  // Close sidebar on mobile when clicking outside
  document.addEventListener('click', function(event) {
    if (window.innerWidth <= 768) {
      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.getElementById('toggleSidebar');
      
      if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
        document.body.classList.remove('sidebar-mobile-open');
      }
    }
  });

  // Handle window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
      document.body.classList.remove('sidebar-mobile-open');
    }
  });
</script>