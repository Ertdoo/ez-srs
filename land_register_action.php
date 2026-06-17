<?php
/**
 * land_register_action.php
 * Keep action and frontend separate: action must not output HTML and
 * should always redirect back to the form on error (storing messages in session).
 */
include "connect.php"; // starts session and provides $conn

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: land_register.php');
    exit;
}

// session-based rate limiting
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 1;
    $_SESSION['register_first_attempt'] = time();
} else {
    $_SESSION['register_attempts']++;
    if ($_SESSION['register_attempts'] > 5 && (time() - $_SESSION['register_first_attempt'] < 300)) {
        $_SESSION['form_error'] = 'Yer tried too many times matey';
        header('Location: land_register.php');
        exit;
    }
}

// Pull inputs
$form_username = trim($_POST['username'] ?? '');
$form_email = trim($_POST['email'] ?? '');
$form_password = $_POST['password'] ?? '';
$form_confirmpassword = $_POST['confirmpassword'] ?? '';
$errors = [];

// validate csrf
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['form_error'] = 'Yer security token is expired. Refresh and try again.';
    // preserve inputs
    $_SESSION['saved_username'] = $form_username;
    $_SESSION['saved_email'] = $form_email;
    header('Location: land_register.php');
    exit;
}

// Validation
if (strlen($form_username) < 3 || strlen($form_username) > 50) {
    $errors[] = 'Yer username must be 3 to 50 characters';
}
if (strlen($form_email) > 100) {
    $errors[] = 'Yer email cannot be that long...';
}
if (strlen($form_password) < 8) {
    $errors[] = 'Yer password must be at least 8 characters';
}
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $form_username)) {
    $errors[] = 'Username may only contain letters, numbers, underscores and hyphens';
}
if (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Yer email be unrecognised to me';
}
if ($form_password !== $form_confirmpassword) {
    $errors[] = 'Ye secrets do not match';
}

// If validation failed, store errors and inputs then redirect back
if (!empty($errors)) {
    $_SESSION['form_error'] = $errors;
    $_SESSION['saved_username'] = $form_username;
    $_SESSION['saved_email'] = $form_email;
    header('Location: land_register.php');
    exit;
}

// Check uniqueness
$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    $_SESSION['form_error'] = 'There was an unforseen iceberg...';
    header('Location: land_register.php');
    exit;
}
$stmt->bind_param('ss', $form_username, $form_email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['form_error'] = ['Yer username or email already be claimed...'];
    $_SESSION['saved_username'] = $form_username;
    $_SESSION['saved_email'] = $form_email;
    header('Location: land_register.php');
    exit;
}
$stmt->close();

// Insert user
$pass_hash = password_hash($form_password, PASSWORD_DEFAULT);
$role = 'user';
$user_status = 1;
$stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    $_SESSION['form_error'] = 'There was an unforseen iceberg...';
    header('Location: land_register.php');
    exit;
}
$stmt->bind_param('ssssi', $form_username, $form_email, $pass_hash, $role, $user_status);
if ($stmt->execute()) {
    // Successful registration: log in user and redirect
    session_regenerate_id(true);
    $_SESSION['username'] = $form_username;
    unset($_SESSION['csrf_token']);
    // clear saved input
    unset($_SESSION['saved_username'], $_SESSION['saved_email']);
    $stmt->close();
    $conn->close();
    header('Location: land_login.php');
    exit;
} else {
    error_log('Registration failed for ' . $form_username . ': ' . $stmt->error);
    $_SESSION['form_error'] = 'There was an unforseen iceberg...';
    $_SESSION['saved_username'] = $form_username;
    $_SESSION['saved_email'] = $form_email;
    $stmt->close();
    $conn->close();
    header('Location: land_register.php');
    exit;
}

?>