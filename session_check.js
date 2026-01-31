// session_check.js - Universal session management script
// Include this file on all protected pages

(function() {
    'use strict';
    
    // Configuration - these values are set by PHP
    const SESSION_TIMEOUT = window.SESSION_TIMEOUT || 120000; // 2 minutes in milliseconds
    const WARNING_TIME = SESSION_TIMEOUT - 60000; // 1 minute before expiry
    const PING_INTERVAL = 30000; // Ping server every 30 seconds when active
    
    let sessionTimeout;
    let warningTimeout;
    let pingInterval;
    let lastActivity = Date.now();
    let lastPing = Date.now();
    let warningShown = false;
    let userActive = false;
    
    // Ping the server to keep session alive
    function pingServer() {
        fetch('session_ping.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => {
            if (response.status === 401) {
                // Session expired on server
                window.location.href = 'login.php?timeout=1';
            }
            return response.json();
        }).then(data => {
            if (data.status === 'logged_out') {
                window.location.href = 'login.php?timeout=1';
            }
            lastPing = Date.now();
        }).catch(error => {
            console.error('Session ping failed:', error);
        });
    }
    
    // Session management functions
    function resetSessionTimer() {
        clearTimeout(sessionTimeout);
        clearTimeout(warningTimeout);
        lastActivity = Date.now();
        warningShown = false;
        
        // Show warning before logout
        warningTimeout = setTimeout(function() {
            if (!warningShown) {
                warningShown = true;
                if (confirm('Your session will expire in 1 minute due to inactivity. Click OK to continue your session.')) {
                    // User clicked OK, send activity ping
                    pingServer();
                    resetSessionTimer();
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
        userActive = true;
        
        // Reset timer if more than 1 minute passed since last activity
        if (currentTime - lastActivity > 60000) {
            resetSessionTimer();
        }
        
        lastActivity = currentTime;
        
        // Ping server if it's been more than PING_INTERVAL since last ping
        if (currentTime - lastPing > PING_INTERVAL) {
            pingServer();
        }
    }
    
    // Activity event listeners - capture interactions with modals too
    const activityEvents = [
        'mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 
        'click', 'keydown', 'input', 'change', 'focus', 'blur'
    ];
    
    activityEvents.forEach(function(event) {
        document.addEventListener(event, trackActivity, { 
            capture: true,  // Capture phase to catch events in modals
            passive: true 
        });
    });
    
    // Regular ping interval when user is active
    pingInterval = setInterval(function() {
        const timeSinceActivity = Date.now() - lastActivity;
        
        // Only ping if user was active in the last minute
        if (timeSinceActivity < 60000) {
            pingServer();
        }
    }, PING_INTERVAL);
    
    // Initialize session timer when page loads
    document.addEventListener('DOMContentLoaded', function() {
        resetSessionTimer();
        pingServer(); // Initial ping
    });
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible, ping server immediately
            pingServer();
            trackActivity();
        }
    });
    
    // Handle window focus
    window.addEventListener('focus', function() {
        pingServer();
        trackActivity();
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        clearInterval(pingInterval);
        clearTimeout(sessionTimeout);
        clearTimeout(warningTimeout);
    });
    
    // Expose functions globally if needed
    window.SessionManager = {
        reset: resetSessionTimer,
        trackActivity: trackActivity,
        ping: pingServer
    };
    
})();
