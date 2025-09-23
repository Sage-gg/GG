<?php
/**
 * Simple autoloader for sage-gg/financial-system project
 * This loads classes from the src/ directory based on PSR-4 standard
 */

spl_autoload_register(function ($className) {
    // Define the base directory for your namespace
    $baseDir = __DIR__ . '/src/';
    
    // Convert namespace separators to directory separators
    $file = $baseDir . str_replace('\\', '/', $className) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Optional: Load additional libraries manually if needed
// This is where you could add other manual includes if necessary
