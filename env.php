<?php
// env.php - load environment variables
function loadEnv($path = 'e_cred.env') {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // skip comments
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // remove quotes if present
        if (preg_match('/^(["\'])(.*)\1$/m', $value, $matches)) {
            $value = $matches[2];
        }
        
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
    return true;
}
?>