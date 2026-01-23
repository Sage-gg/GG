<?php
// Manual autoloader (Composer replacement)

spl_autoload_register(function ($class) {

    // PHPMailer
    if (str_starts_with($class, 'PHPMailer\\PHPMailer\\')) {
        $path = __DIR__ . '/phpmailer/phpmailer/src/' .
            str_replace('PHPMailer\\PHPMailer\\', '', $class) . '.php';
        if (file_exists($path)) require $path;
        return;
    }

    // TCPDF (global class)
    if ($class === 'TCPDF') {
        require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
        return;
    }
});
