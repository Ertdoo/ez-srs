<?php include ("connect.php"); ?>
<?php include ("header.php");
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
} else {
    $message = "";
}?>

<head>
    <title>Login - EzSrs</title>
</head>

<body class="d-flex justify-content-center align-items-center vh-100 container marketing flex-column-reverse">

        <form action="land_login_action.php" method="POST" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Login</h2>

            <?php if ($message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
            <div class="text-center mt-3">
                <a href="land_register.php" class="text-decoration-none">Or register yer account, scallywag</a>
            </div>
        </form>

</body>
<?php include ("footer.php"); ?>
