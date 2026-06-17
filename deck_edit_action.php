<?php include ("connect.php"); ?>
<?php 
// action template
if (isset($_SESSION['username'])) {
    $username = ($_SESSION['username']);
} else {
    header('Location: login_invalid.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$deck_id = isset($_POST['deck_id']) ? intval($_POST['deck_id']) : 0;

//ini vars
$_SESSION['deck_create_error'] = '';
$_SESSION['deck_create_success'] = '';
$form_deck_title = '';
$form_deck_description = '';
$form_public = 0;

$check_owner = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$check_owner->bind_param("i", $deck_id);
$check_owner->execute();
$result = $check_owner->get_result();
$deck = $result->fetch_assoc();

if (!$deck || $deck['user_id'] != $user_id) {
    $_SESSION['deck_create_error'] = "Ye ain't the cap'n of this deck!";
    header('Location: deck_details.php?id='.$deck_id);
}

// get values from form
$form_deck_title = trim($_POST["deck_title"] ?? '');
$form_deck_description = trim($_POST['deck_desc'] ?? '');
if (isset($_POST['make_public'])) {
    $form_public = 1;
}

// Prepare the update query
$stmt_deck_update = $conn->prepare("
    UPDATE decks 
    SET title = ?, 
        description = ?, 
        is_public = ? 
    WHERE id = ? AND user_id = ?
");

// Bind the parameters
// "ssiii" means: string, string, integer, integer, integer
$stmt_deck_update->bind_param(
    "ssiii", 
    $form_deck_title, 
    $form_deck_description, 
    $form_public, 
    $deck_id, 
    $user_id
);

// Execute the update
if ($stmt_deck_update->execute()) {
    $_SESSION['deck_create_success'] = "Deck updated successfully!";
    header('Location: deck_details.php?id='.$deck_id);
} else {
    $_SESSION['deck_create_error'] = "Error updating deck: " . $conn->error;
    header('Location: deck_details.php?id='.$deck_id);
}
?>