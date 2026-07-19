<?php session_start(); ?>
<?php
date_default_timezone_set('UTC');

$envPath = dirname(__DIR__) . '/ez-srs/e_cred.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
} else {
    die(".env file not found at: $envPath");
}

// git creds
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'ez-srs_db';
$db_user = $_ENV['DB_USER'] ?? 'webuser';
$db_pass = $_ENV['DB_PASS'] ?? ''; // Password from .env

// enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // set charset
    $conn->set_charset('utf8mb4');


    // Optional: Set timezone
    $conn->query("SET time_zone = '+00:00'");

    // Return the connection
    return $conn;

} catch (mysqli_sql_exception $e) {
    // log the error
    error_log("Database Connection Error: " . $e->getMessage());
    // print("Database Connection Error: " . $e->getMessage());
    // show fake generic error
    header('HTTP/1.1 503 Service Unavailable');
    print("<h1>Argh, th' ship is sinkin'!</h1><p>contact ye shipmaster: ezsrs.nixos@gmail.com</p>");
    die("<br><p>Database Connection Error: </p>" . $e->getMessage());
}

?>
