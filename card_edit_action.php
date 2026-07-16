<?php
/*
* card_edit_action.php
* to process the card edit data from modal into the db
* by emerson
*/
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "connect.php";

// 1. Check if user is logged in
if (!isset($_SESSION["username"]) || !isset($_SESSION["user_id"])) {
    die('<br><h1>Yer not logged in matey!</h1><br><a href="land_login.php">Go to login</a>');
}

$user_id = (int) $_SESSION["user_id"];

// 2. Ensure it's a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

// 3. Gather and sanitize input
$deck_id = (int) ($_POST["deck_id"] ?? 0);
$card_id = (int) ($_POST["card_id"] ?? 0);
$question = trim($_POST["question"] ?? "");
$answer = trim($_POST["answer"] ?? "");
$card_type = $_POST["card_type"] ?? "basic";
$difficulty = $_POST["difficulty"] ?? "medium";

// 4. Validate inputs
if (empty($question) || empty($answer)) {
    $_SESSION["deck_edit_warning"] = "Question and Answer cannot be empty, matey!";
    header("Location: deck_details.php?id=" . $deck_id);
    exit;
}

// Enforce enum values from database schema
if (!in_array($card_type, ["basic", "cloze", "image"])) {
    $card_type = "basic";
}
if (!in_array($difficulty, ["easy", "medium", "hard"])) {
    $difficulty = "medium";
}

// 5. Verify deck ownership
$stmt = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();

if (!$deck || $deck["user_id"] != $user_id) {
    die("<h1>Avast!</h1><p>You don't have permission to edit this deck.</p><a href='decks.php'>Return to Dashboard</a>");
}

// 6. Verify the card belongs to this deck
$card_stmt = $conn->prepare("SELECT id FROM cards WHERE id = ? AND deck_id = ?");
$card_stmt->bind_param("ii", $card_id, $deck_id);
$card_stmt->execute();
$card = $card_stmt->get_result()->fetch_assoc();

if (!$card) {
    die("<h1>Card not found!</h1><a href='deck_details.php?id=" . $deck_id . "'>Back to deck</a>");
}

// 7. Update the card
$update_stmt = $conn->prepare("UPDATE cards SET question = ?, answer = ?, card_type = ?, difficulty = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND deck_id = ?");
$update_stmt->bind_param("ssssii", $question, $answer, $card_type, $difficulty, $card_id, $deck_id);

if ($update_stmt->execute()) {
    $_SESSION["deck_create_success"] = "Card updated successfully, matey!";
} else {
    $_SESSION["deck_edit_warning"] = "Failed to update card: " . $conn->error;
}

// 8. Redirect back to the deck details page
header("Location: deck_details.php?id=" . $deck_id);
exit;
?>
?>
