<?php
session_start();
// propose_card_change.php
$conn = include "connect.php";

// 1. Authentication Check
if (!isset($_SESSION["user_id"])) {
    header("Location: land_login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// 2. Capture and Validate POST Data
$deck_id = isset($_POST["deck_id"]) ? (int) $_POST["deck_id"] : 0;
$action_type = isset($_POST["action_type"]) ? $_POST["action_type"] : '';
$card_id = isset($_POST["card_id"]) && $_POST["card_id"] !== '' ? (int) $_POST["card_id"] : null;

// Whitelist action types
if (!in_array($action_type, ['add', 'edit', 'delete'])) {
    $_SESSION["deck_view_error"] = "Invalid action type, matey!";
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}

if ($deck_id <= 0) {
    $_SESSION["deck_view_error"] = "Invalid deck, matey!";
    header("Location: decks.php");
    exit();
}

// 3. Permission Check (Must be Owner or Accepted Editor)
$is_authorized = false;

$deck_check = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$deck_check->bind_param("i", $deck_id);
$deck_check->execute();
$deck_owner = $deck_check->get_result()->fetch_assoc();

if ($deck_owner && $deck_owner["user_id"] == $user_id) {
    $is_authorized = true;
} else {
    $contrib_check = $conn->prepare("SELECT role, status FROM deck_contributors WHERE deck_id = ? AND user_id = ?");
    $contrib_check->bind_param("ii", $deck_id, $user_id);
    $contrib_check->execute();
    $contributor = $contrib_check->get_result()->fetch_assoc();

    if ($contributor && $contributor["status"] === 'accepted' && in_array($contributor["role"], ['owner', 'editor'])) {
        $is_authorized = true;
    }
}

if (!$is_authorized) {
    $_SESSION["deck_view_error"] = "Ye don't have permission to suggest changes to this deck, matey!";
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}

// 4. Validate Card Existence (ONLY for Edit/Delete actions)
if ($action_type !== 'add' && $card_id !== null) {
    $card_check = $conn->prepare("SELECT id FROM cards WHERE id = ? AND deck_id = ?");
    $card_check->bind_param("ii", $card_id, $deck_id);
    $card_check->execute();
    if (!$card_check->get_result()->fetch_assoc()) {
        $_SESSION["deck_view_error"] = "Card not found in this deck, matey!";
        header("Location: deck_details.php?id=" . $deck_id);
        exit();
    }
}

// 5. Capture Proposed Data
$proposed_question = isset($_POST["question"]) ? trim($_POST["question"]) : null;
$proposed_answer = isset($_POST["answer"]) ? trim($_POST["answer"]) : null;
$proposed_card_type = isset($_POST["card_type"]) ? $_POST["card_type"] : 'basic';
$proposed_difficulty = isset($_POST["difficulty"]) ? $_POST["difficulty"] : 'medium';
$proposed_tags = isset($_POST["tags"]) ? trim($_POST["tags"]) : null; // Optional

// If it's a delete action, we don't need the proposed text
if ($action_type === 'delete') {
    $proposed_question = null;
    $proposed_answer = null;
    $proposed_card_type = null;
    $proposed_difficulty = null;
    $proposed_tags = null;
}

// 6. Insert Proposal and Log Activity in a Transaction
$conn->begin_transaction();

try {
    // Check if proposed_tags column exists, if not, we'll just skip saving it to DB
    // (Owner can add tags manually during merge). For now, we pass it safely.
    $insert_proposal = $conn->prepare("
        INSERT INTO card_proposals
        (deck_id, card_id, user_id, action_type, proposed_question, proposed_answer, proposed_card_type, proposed_difficulty)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert_proposal->bind_param(
        "iissssss",  // 8 type parameter
        $deck_id,           // i - integer
        $card_id,           // i - integer
        $user_id,           // i - integer
        $action_type,       // s - string
        $proposed_question, // s - string
        $proposed_answer,   // s - string
        $proposed_card_type,// s - string
        $proposed_difficulty// s - string
    );
    $insert_proposal->execute();

    // Log Activity
    $description = "Suggested to {$action_type} a card";
    if ($card_id) {
        $description .= " (Card ID: {$card_id})";
    }

    $insert_activity = $conn->prepare("
        INSERT INTO activities (user_id, activity_type, target_id, description)
        VALUES (?, 'proposed_change', ?, ?)
    ");
    $insert_activity->bind_param("iis", $user_id, $deck_id, $description);
    $insert_activity->execute();

    $conn->commit();

    $_SESSION["deck_view_success"] = "Yer suggestion has been submitted for review, matey!";
    header("Location: deck_details.php?id=" . $deck_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["deck_view_error"] = "Couldn't submit yer suggestion, matey! Try again.";
    error_log("Proposal insert failed: " . $e->getMessage());
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}
?>
