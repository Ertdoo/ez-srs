<?php $conn = include "connect.php"; ?>
<?php // deck_create_action.php (lets go 19:10 10/12/2025)
// get the user


if (isset($_SESSION["user_id"])) {
    $user_id = (int) $_SESSION["user_id"];
} else {
    //echo $_SESSION['user_id'];
    header("Location: login_invalid.php");
    exit();
}
//ini vars
$_SESSION["deck_create_error"] = "";
$_SESSION["deck_create_success"] = "";
$form_deck_title = "";
$form_deck_description = "";
$form_public = 0; // get values from form
$form_deck_title = trim($_POST["deck_title"] ?? "");
$form_deck_description = trim($_POST["deck_desc"] ?? "");
if (isset($_POST["make_public"])) {
    $form_public = 1;
}
if (empty($form_deck_title)) {
    $_SESSION["deck_create_error"] =
        "Nothin' can be found in the title laddie!";
    header("Location: deck_create.php");
    exit();
}
$stmt_deck_create = $conn->prepare(
    "INSERT INTO decks (user_id,title,description,is_public) VALUES(?,?,?,?)",
);
$stmt_deck_create->bind_param(
    "issi",
    $user_id,
    $form_deck_title,
    $form_deck_description,
    $form_public,
);
if (!$stmt_deck_create->execute()) {
    $_SESSION["deck_create_error"] =
        "Deck could not be installed to the ship...";
    error_log("db exe fail: " . $stmt_deck_create->error);
    $stmt_deck_create->close();
    header("Location: deck_create.php");
    exit();
}
$stmt_deck_create->close();
$conn->close();
$_SESSION["deck_create_success"] =
    "The deed is done! That deck be built, true and proper!";
header("Location: deck_create.php");
exit();
 ?>
