// FIXED: Sidebar toggle functionality - starts closed by default
let sidebarOpen = false;

document.getElementById('toggleSidebar').addEventListener('click', function(e) {
  e.preventDefault();
  
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.querySelector('.main-content');
  
  sidebarOpen = !sidebarOpen;
  
  if (sidebarOpen) {
    sidebar.classList.add('show');
    if (window.innerWidth > 768) {
      mainContent.classList.add('sidebar-open');
    }
  } else {
    sidebar.classList.remove('show');
    mainContent.classList.remove('sidebar-open');
  }
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
  if (window.innerWidth <= 768 && sidebarOpen) {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    
    if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
      sidebar.classList.remove('show');
      document.querySelector('.main-content').classList.remove('sidebar-open');
      sidebarOpen = false;
    }
  }
});

// Handle window resize
window.addEventListener('resize', function() {
  const mainContent = document.querySelector('.main-content');
  
  if (window.innerWidth <= 768) {
    mainContent.classList.remove('sidebar-open');
  } else if (sidebarOpen) {
    mainContent.classList.add('sidebar-open');
  }
});

// Financial System button click handler
document.getElementById('financialBtn').addEventListener('click', function() {
  alert('You clicked FINANCIAL SYSTEM! Customize this action.');
});

// Popup toggle functionality
const bellIcon = document.getElementById('bellIcon');
const profileIcon = document.getElementById('profileIcon');
const bellPopup = document.getElementById('bellPopup');
const profilePopup = document.getElementById('profilePopup');

// Toggle popup visibility function
function togglePopup(popup) {
  if (popup.classList.contains('d-none')) {
    // Hide both first
    bellPopup.classList.add('d-none');
    profilePopup.classList.add('d-none');
    // Show the requested one
    popup.classList.remove('d-none');
  } else {
    popup.classList.add('d-none');
  }
}

bellIcon.addEventListener('click', function(e) {
  e.preventDefault();
  togglePopup(bellPopup);
});

profileIcon.addEventListener('click', function(e) {
  e.preventDefault();
  togglePopup(profilePopup);
});

// Close popups when clicking outside
document.addEventListener('click', function(event) {
  if (!bellIcon.contains(event.target) && !bellPopup.contains(event.target)) {
    bellPopup.classList.add('d-none');
  }
  if (!profileIcon.contains(event.target) && !profilePopup.contains(event.target)) {
    profilePopup.classList.add('d-none');
  }
});

// Set active navigation link based on current page
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