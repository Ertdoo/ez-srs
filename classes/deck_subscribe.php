<?php
session_start();
// deck_subscribe.php - Subscribe to a public deck
$conn = include __DIR__ . "/../connect.php";

if (!isset($_SESSION["user_id"])) {
    die(
        '<br><h1>Yer not logged in matey!</h1><br><a href="../land_login.php">Go to login</a>'
    );
}

$user_id = $_SESSION["user_id"];
$deck_id = isset($_POST["deck_id"]) ? (int) $_POST["deck_id"] : 0;

if ($deck_id <= 0) {
    $_SESSION["deck_view_error"] = "Invalid deck, matey!";
    header("Location: ../deck_view.php");
    exit();
}

// Make sure the deck exists and is public
$check_query = "SELECT id, user_id, is_public FROM decks WHERE id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();

if (!$deck) {
    $_SESSION["deck_view_error"] = "Deck not found, matey!";
    header("Location: ../deck_browse.php");
    exit();
}

// Check if deck is public
if (!$deck["is_public"]) {
    $_SESSION["deck_view_error"] = "This deck is private, matey!";
    header("Location: ../deck_browse.php");
    exit();
}

if ($deck["user_id"] == $user_id) {
    $_SESSION["deck_view_error"] = "Ye already own this deck, matey!";
    header("Location: ../deck_view.php?id=" . $deck_id);
    exit();
}

// Check if a relationship already exists
$existing_query =
    "SELECT id, role, status FROM deck_contributors WHERE deck_id = ? AND user_id = ?";
$stmt2 = $conn->prepare($existing_query);
$stmt2->bind_param("ii", $deck_id, $user_id);
$stmt2->execute();
$existing = $stmt2->get_result()->fetch_assoc();

if ($existing) {
    $_SESSION["deck_view_error"] =
        "Ye already have a relationship with this deck, matey!";
    header("Location: ../deck_view.php?id=" . $deck_id);
    exit();
}

// Insert subscription: role = viewer, status = accepted, invited_by = self (no formal invite)
$insert_query = "
    INSERT INTO deck_contributors (deck_id, user_id, role, invited_by, status)
    VALUES (?, ?, 'viewer', ?, 'accepted')
";
$stmt3 = $conn->prepare($insert_query);
$stmt3->bind_param("iii", $deck_id, $user_id, $user_id);

if ($stmt3->execute()) {
    $_SESSION["deck_view_success"] =
        "Ye be subscribed to this deck now, matey!";
} else {
    $_SESSION["deck_view_error"] = "Couldn't subscribe ye, matey! Try again.";
}

header("Location: ../user_dashboard.php");
exit();
