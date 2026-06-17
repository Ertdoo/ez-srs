<?php include ("connect.php"); ?>
<?php 
// action template
if (isset($_SESSION['username'])) {
    $username = ($_SESSION['username']);
} else {
    header('Location: login_invalid.php');
    exit;
}
// $form_email = trim($_POST["email"] ?? '');
?>