<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file is missing!");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        
        // Ignore comments starting with #
        if (strpos(trim($line), '#') === 0) continue;
        
        // Split the line into variable name and value
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Store variables in the server environment
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load the .env file from the project root directory
loadEnv(__DIR__ . '/../.env');
?>