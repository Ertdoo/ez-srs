<?php
/*
 * deck_delete_DDETAILS.php
 * to delete a deck after the user presses the button FROM DECK DETAILS
 * by emerson
 */

$conn = include "connect.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
} else {
    header("Location: login.php");
    exit();
}

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

if (isset($_POST["deck_id"])) {
    $deck_id = intval($_POST["deck_id"]); // force integer type to prevent text injection

    $stmt = $conn->prepare("DELETE FROM decks WHERE id = ?");
    $stmt->bind_param("i", $deck_id);
    $stmt->execute();

    header("Location: decks.php");
    exit();
}

?>
