<?php
// composer_check.php
echo "Checking Composer and PHPMailer installation<br><br>";

// Include use statements at the top level
use PHPMailer\PHPMailer\PHPMailer;

// Check if composer.json exists and what's in it
if (file_exists('composer.json')) {
    echo "composer.json: FOUND<br>";
    $composer_content = file_get_contents('composer.json');
    echo "Contents:<br><pre>" . htmlspecialchars($composer_content) . "</pre><br>";
} else {
    echo "composer.json: NOT FOUND<br><br>";
}

// Check vendor directory
if (is_dir('vendor')) {
    echo "vendor directory contents:<br>";
    $vendor_contents = scandir('vendor');
    foreach ($vendor_contents as $item) {
        if ($item !== '.' && $item !== '..') {
            echo "- $item " . (is_dir("vendor/$item") ? "(folder)" : "(file)") . "<br>";
        }
    }
    echo "<br>";
}

// Try to include autoload and test PHPMailer
if (file_exists('vendor/autoload.php')) {
    echo "Loading vendor/autoload.php...<br>";
    require_once 'vendor/autoload.php';
    echo "Autoloader loaded successfully<br>";
    
    // Check if PHPMailer classes are available
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<strong style='color: green;'>PHPMailer class is available!</strong><br>";
        
        // Load email config
        if (file_exists('email_config.php')) {
            require_once 'email_config.php';
            echo "Email config loaded<br>";
            
            // Test PHPMailer instantiation
            try {
                $mail = new PHPMailer(true);
                echo "<strong style='color: green;'>PHPMailer can be instantiated successfully!</strong><br>";
                echo "Your email verification should work now.<br>";
            } catch (Exception $e) {
                echo "<strong style='color: red;'>Error creating PHPMailer instance: " . $e->getMessage() . "</strong><br>";
            }
        }
    } else {
        echo "<strong style='color: red;'>PHPMailer class not found</strong><br>";
        echo "You need to install PHPMailer via Composer:<br>";
        echo "<code>composer require phpmailer/phpmailer</code><br>";
    }
} else {
    echo "vendor/autoload.php not found<br>";
}

// Show current working directory for Composer commands
echo "<br>To install PHPMailer, run this command in: <strong>" . getcwd() . "</strong><br>";
echo "<code>composer require phpmailer/phpmailer</code><br>";
?>
