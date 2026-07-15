<?php
session_start();
// propose_card_batch.php
$conn = include "connect.php";

// 1. Authentication Check
if (!isset($_SESSION["user_id"])) {
    header("Location: land_login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// 2. Capture and Validate Input
$deck_id = isset($_POST["deck_id"]) ? (int) $_POST["deck_id"] : 0;
$batch_cards = isset($_POST["batch_cards"]) ? trim($_POST["batch_cards"]) : "";
$default_card_type = isset($_POST["default_card_type"]) ? $_POST["default_card_type"] : "basic";
$default_difficulty = isset($_POST["default_difficulty"]) ? $_POST["default_difficulty"] : "medium";

if ($deck_id <= 0 || empty($batch_cards)) {
    $_SESSION["deck_view_error"] = "Invalid deck or empty batch, matey!";
    header("Location: deck_details.php?id=" . $deck_id);
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

// 4. Parse Batch Input
$lines = explode("\n", $batch_cards);
$proposals = [];
$invalid_lines = [];

foreach ($lines as $line_num => $line) {
    $line = trim($line);
    if (empty($line)) continue; // Skip empty lines

    // Split by the first '|' character only
    $parts = explode('|', $line, 2);

    if (count($parts) < 2) {
        $invalid_lines[] = "Line " . ($line_num + 1) . ": Missing '|' separator";
        continue;
    }

    $question = trim($parts[0]);
    $answer = trim($parts[1]);

    if (empty($question) || empty($answer)) {
        $invalid_lines[] = "Line " . ($line_num + 1) . ": Question or Answer is empty";
        continue;
    }

    $proposals[] = [
        'question' => $question,
        'answer' => $answer,
        'card_type' => $default_card_type,
        'difficulty' => $default_difficulty
    ];
}

if (empty($proposals)) {
    $_SESSION["deck_view_error"] = "No valid cards found in batch. Check format: Question | Answer";
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}

// 5. Insert Proposals in a Transaction
$conn->begin_transaction();
$success_count = 0;

try {
    $insert_proposal = $conn->prepare("
        INSERT INTO card_proposals
        (deck_id, card_id, user_id, action_type, proposed_question, proposed_answer, proposed_card_type, proposed_difficulty)
        VALUES (?, NULL, ?, 'add', ?, ?, ?, ?)
    ");

    foreach ($proposals as $prop) {
        $insert_proposal->bind_param(
            "isssss",
            $deck_id,
            $user_id,
            $prop['question'],
            $prop['answer'],
            $prop['card_type'],
            $prop['difficulty']
        );
        $insert_proposal->execute();
        $success_count++;
    }

    // Log single activity for the whole batch
    $description = "Suggested a batch of {$success_count} new cards";
    $insert_activity = $conn->prepare("
        INSERT INTO activities (user_id, activity_type, target_id, description)
        VALUES (?, 'proposed_change', ?, ?)
    ");
    $insert_activity->bind_param("iis", $user_id, $deck_id, $description);
    $insert_activity->execute();

    $conn->commit();

    $success_msg = "Successfully submitted {$success_count} card proposals!";
    if (!empty($invalid_lines)) {
        $success_msg .= " (Note: " . count($invalid_lines) . " lines were skipped due to formatting errors.)";
    }

    $_SESSION["deck_view_success"] = $success_msg;
    header("Location: deck_details.php?id=" . $deck_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["deck_view_error"] = "Couldn't submit yer batch proposal, matey! Try again.";
    error_log("Batch proposal insert failed: " . $e->getMessage());
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}
?>
