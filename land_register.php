<?php 
include ("connect.php");

$form_error = '';
if (isset($_SESSION['form_error'])) {
    $form_error = $_SESSION['form_error'];
    unset($_SESSION['form_error']);
}
$saved_username = $_SESSION['saved_username'] ?? '';
$saved_email = $_SESSION['saved_email'] ?? '';
unset($_SESSION['saved_username'], $_SESSION['saved_email']); 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include ("header.php"); 
?>
<head>
    <title>Register - EzSrs</title>
</head>
<body class="d-flex justify-content-center align-items-center vh-100 container marketing flex-column-reverse">
    <form action="land_register_action.php" method="POST" style="width: 100%; max-width: 400px;">
        <h2 class="text-center mb-4accelerator">Register</h2>

        <?php if ($form_error): ?>
            <br>
            <?php 
            $alert_class = 'alert-danger';
            
            // Fix Deprecated warning: ensure output is always a non-null string before htmlspecialchars
            $error_message = is_array($form_error) ? implode(', ', $form_error) : (string)$form_error;
            ?>
            <div class="alert <?php echo $alert_class; ?>">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="username" class="form-label">Username:</label>
            <input type="text" id="username" name="username" class="form-control mb-3" required 
                   value="<?php echo htmlspecialchars($saved_username); ?>" 
                   maxlength="50">

            <label for="email" class="form-label">Email:</label>
            <input type="email" id="email" name="email" class="form-control mb-3" required
                   value="<?php echo htmlspecialchars($saved_email); ?>"
                   maxlength="100">

            <label for="password" class="form-label">Password:</label>
            <input type="password" id="password" name="password" class="form-control mb-1" required minlength="8">
            <div class="form-text mb-3">Must be at least 8 characters.</div>

            <label for="confirmpassword" class="form-label">Confirm password:</label>
            <input type="password" id="confirmpassword" name="confirmpassword" class="form-control mb-3" required minlength="8">
            
            <button type="submit" class="btn btn-primary w-100">Register</button>
            <br><br>
            <a href="land_login.php" class="button-class">Or login to existing yer account, fellow buccaneer</a>
    </form>
</body>

<?php include ("footer.php"); ?>