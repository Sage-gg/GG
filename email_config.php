<?php
// =============================================================================
// email_config.php
// Email Configuration for 2FA System
// =============================================================================

// SMTP Configuration - UPDATE THESE WITH YOUR EMAIL SETTINGS
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_PORT', 587); // Common ports: 587 (TLS), 465 (SSL), 25 (non-encrypted)
define('SMTP_USERNAME', 'financials.cali@gmail.com'); // Your email address
define('SMTP_PASSWORD', 'jtkp pgvt ihdb viie'); // Your email password or app password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('FROM_EMAIL', 'financials.cali@gmail.com'); // Sender email
define('FROM_NAME', 'Financials - Crane & Trucking Management System'); // Sender name

// Verification settings
define('VERIFICATION_CODE_EXPIRY', 10 * 60); // 10 minutes in seconds
define('MAX_VERIFICATION_ATTEMPTS', 3); // Maximum attempts per code
define('ACCESS_TOKEN_VALIDITY', 10 * 24 * 60 * 60); // 10 days in seconds

/* 
IMPORTANT SETUP NOTES:

For Gmail:
1. Enable 2-factor authentication on your Google account
2. Go to Google Account Settings > Security > App passwords
3. Generate a new app password for "Mail"
4. Use this app password instead of your regular password

For other email providers:
- Yahoo: Use app passwords (similar to Gmail)
- Outlook/Hotmail: Use regular password or app password
- Custom SMTP: Check with your hosting provider for settings

Common SMTP Settings:
- Gmail: smtp.gmail.com:587 (TLS) or smtp.gmail.com:465 (SSL)
- Yahoo: smtp.mail.yahoo.com:587 (TLS) or smtp.mail.yahoo.com:465 (SSL)
- Outlook: smtp-mail.outlook.com:587 (TLS)

Make sure to update the email credentials above before using!
*/

?>