<?php
/*
 * deck_delete_DCREATE.php
 * to delete a deck after the user presses the button FROM DECK CREATE
 * by emerson
 */


 if (!isset($_SESSION["user_id"])) {
     die("Not logged in.");
 }

 $user_id = $_SESSION["user_id"];
 $deck_id = intval($_POST["deck_id"]);

 // 1. Verify ownership BEFORE deleting
 $check_stmt = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
 $check_stmt->bind_param("i", $deck_id);
 $check_stmt->execute();
 $result = $check_stmt->get_result()->fetch_assoc();

 if (!$result || $result["user_id"] != $user_id) {
     // User is not the owner, reject the request
     $_SESSION["deck_create_error"] = "You do not have permission to delete this deck, matey!";
     header("Location: deck_create.php");
     exit;
 }

 // 2. Proceed with deletion safely
 $delete_stmt = $conn->prepare("DELETE FROM decks WHERE id = ? AND user_id = ?");
 $delete_stmt->bind_param("ii", $deck_id, $user_id);
 $delete_stmt->execute();

 // redirect back with success message
    header("Location: deck_create.php");
    exit();
}

?>
