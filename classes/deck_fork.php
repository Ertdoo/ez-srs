<?php
session_start();
// deck_fork.php - Fork a public deck
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

// Fetch the source deck
$deck_query = "SELECT title, description, is_public FROM decks WHERE id = ?";
$stmt = $conn->prepare($deck_query);
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$source_deck = $stmt->get_result()->fetch_assoc();

if (!$source_deck) {
    $_SESSION["deck_view_error"] = "Deck not found, matey!";
    header("Location: ../deck_browse.php");
    exit();
}

// Check if deck is public
if (!$source_deck["is_public"]) {
    $_SESSION["deck_view_error"] = "This deck is private, matey!";
    header("Location: ../deck_browse.php");
    exit();
}

// Build the forked title (avoid endless "Copy of Copy of...")
$new_title = $source_deck["title"];
if (mb_strlen($new_title) > 24) {
    $new_title = mb_substr($new_title, 0, 24) . "...";
}
$new_title = "Fork of " . $new_title;
if (mb_strlen($new_title) > 100) {
    $new_title = mb_substr($new_title, 0, 100);
}

$conn->begin_transaction();

try {
    // Create the new deck, owned by the current user, private by default
    $insert_deck_query = "
        INSERT INTO decks (user_id, title, description, is_public)
        VALUES (?, ?, ?, 0)
    ";
    $stmt2 = $conn->prepare($insert_deck_query);
    $description = $source_deck["description"] ?? '';
    $stmt2->bind_param(
        "iss",
        $user_id,
        $new_title,
        $description,
    );
    $stmt2->execute();
    $new_deck_id = $conn->insert_id;

    // Copy over the cards
    $cards_query =
        "SELECT question, answer, card_type, media_url, difficulty FROM cards WHERE deck_id = ?";
    $stmt3 = $conn->prepare($cards_query);
    $stmt3->bind_param("i", $deck_id);
    $stmt3->execute();
    $cards_result = $stmt3->get_result();

    $insert_card_query = "
        INSERT INTO cards (deck_id, question, answer, card_type, media_url, difficulty)
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt4 = $conn->prepare($insert_card_query);

    while ($card = $cards_result->fetch_assoc()) {
        $media_url = $card["media_url"] ?? null;
        $difficulty = $card["difficulty"] ?? 'medium';

        $stmt4->bind_param(
            "isssss",
            $new_deck_id,
            $card["question"],
            $card["answer"],
            $card["card_type"],
            $media_url,
            $difficulty,
        );
        $stmt4->execute();
    }

    $conn->commit();

    $_SESSION["deck_create_success"] =
        "Deck forked successfully, matey! It's now in yer own collection.";
    header("Location: ../user_dashboard.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["deck_view_error"] = "Couldn't fork this deck, matey! Try again.";
    header("Location: ../deck_view.php?id=" . $deck_id);
    exit();
}
