// session_check.js - Universal session management script
// Include this file on all protected pages

(function() {
    'use strict';
    
    // Configuration - these values are set by PHP
    const SESSION_TIMEOUT = window.SESSION_TIMEOUT || 120000; // 2 minutes in milliseconds
    const WARNING_TIME = SESSION_TIMEOUT - 60000; // 1 minute before expiry
    
    let sessionTimeout;
    let warningTimeout;
    let lastActivity = Date.now();
    let warningShown = false;
    
    // Session management functions
    function resetSessionTimer() {
        clearTimeout(sessionTimeout);
        clearTimeout(warningTimeout);
        lastActivity = Date.now();
        warningShown = false;
        
        // Show warning 5 minutes before logout
        warningTimeout = setTimeout(function() {
            if (!warningShown) {
                warningShown = true;
                if (confirm('Your session will expire in 1 minute due to inactivity. Click OK to continue your session.')) {
                    // User clicked OK, send activity ping
                    fetch(window.location.href, {
                        method: 'HEAD',
                        credentials: 'same-origin'
                    }).then(() => {
                        resetSessionTimer();
                    }).catch(() => {
                        // If fetch fails, still reset timer
                        resetSessionTimer();
                    });
                } else {
                    // User clicked Cancel, logout immediately
                    window.location.href = 'logout.php';
                }
            }
        }, WARNING_TIME);
        
        // Auto logout after full timeout
        sessionTimeout = setTimeout(function() {
            alert('Your session has expired due to inactivity. You will be redirected to the login page.');
            window.location.href = 'login.php?timeout=1';
        }, SESSION_TIMEOUT);
    }
    
    // Track user activity
    function trackActivity() {
        const currentTime = Date.now();
        if (currentTime - lastActivity > 60000) { // Only reset if more than 1 minute passed
            resetSessionTimer();
        }
    }
    
    // Activity event listeners
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click', 'keydown'];
    
    activityEvents.forEach(function(event) {
        document.addEventListener(event, trackActivity, { 
            capture: true, 
            passive: true 
        });
    });
    
    // Initialize session timer when page loads
    document.addEventListener('DOMContentLoaded', function() {
        resetSessionTimer();
    });
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible, check if we need to refresh session
            trackActivity();
        }
    });
    
    // Handle window focus
    window.addEventListener('focus', function() {
        trackActivity();
    });
    
    // Expose functions globally if needed
    window.SessionManager = {
        reset: resetSessionTimer,
        trackActivity: trackActivity
    };
    
})();
