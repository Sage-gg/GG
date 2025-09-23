<?php
// simple_test.php - Basic diagnostic script
echo "PHP is working<br>";
echo "Current directory: " . getcwd() . "<br>";

// Check if email_config.php exists
if (file_exists('email_config.php')) {
    echo "email_config.php: FOUND<br>";
    require_once 'email_config.php';
    echo "email_config.php: LOADED<br>";
} else {
    echo "email_config.php: NOT FOUND<br>";
}

// Check PHPMailer paths
$paths = [
    'vendor/autoload.php',
    'PHPMailer/src/PHPMailer.php',
    'lib/phpmailer/src/PHPMailer.php'
];

echo "<br>PHPMailer Check:<br>";
foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "$path: FOUND<br>";
    } else {
        echo "$path: NOT FOUND<br>";
    }
}

// List directory contents
echo "<br>Directory contents:<br>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo $file . (is_dir($file) ? ' (folder)' : ' (file)') . "<br>";
    }
}
?>