<?php
// merge_proposal.php
$conn = include "connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: land_login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$proposal_id = (int) ($_POST["proposal_id"] ?? 0);
$deck_id = (int) ($_POST["deck_id"] ?? 0);
$action = $_POST["action"] ?? ''; // 'merge' or 'reject'

if ($proposal_id <= 0 || $deck_id <= 0 || !in_array($action, ['merge', 'reject'])) {
    $_SESSION["deck_review_error"] = "Invalid request, matey!";
    header("Location: deck_review_proposals.php?id=" . $deck_id);
    exit();
}

// 1. Verify Ownership
$deck_check = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$deck_check->bind_param("i", $deck_id);
$deck_check->execute();
$deck = $deck_check->get_result()->fetch_assoc();

if (!$deck || $deck["user_id"] != $user_id) {
    $_SESSION["deck_review_error"] = "Avast! Ye don't own this deck.";
    header("Location: decks.php");
    exit();
}

// 2. Fetch Proposal
$prop_check = $conn->prepare("SELECT * FROM card_proposals WHERE id = ? AND deck_id = ? AND status = 'pending'");
$prop_check->bind_param("ii", $proposal_id, $deck_id);
$prop_check->execute();
$proposal = $prop_check->get_result()->fetch_assoc();

if (!$proposal) {
    $_SESSION["deck_review_error"] = "Proposal not found or already processed.";
    header("Location: deck_review_proposals.php?id=" . $deck_id);
    exit();
}

// 3. Execute Action in Transaction
$conn->begin_transaction();
try {
    if ($action === 'merge') {
        if ($proposal['action_type'] === 'add') {
            $sql = "INSERT INTO cards (deck_id, question, answer, card_type, difficulty) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $deck_id, $proposal['proposed_question'], $proposal['proposed_answer'], $proposal['proposed_card_type'], $proposal['proposed_difficulty']);
            $stmt->execute();
        }
        elseif ($proposal['action_type'] === 'edit') {
            $sql = "UPDATE cards SET question = ?, answer = ?, card_type = ?, difficulty = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $proposal['proposed_question'], $proposal['proposed_answer'], $proposal['proposed_card_type'], $proposal['proposed_difficulty'], $proposal['card_id']);
            $stmt->execute();
        }
        elseif ($proposal['action_type'] === 'delete') {
            $sql = "DELETE FROM cards WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $proposal['card_id']);
            $stmt->execute();
        }

        // Mark proposal as merged
        $update_prop = $conn->prepare("UPDATE card_proposals SET status = 'merged' WHERE id = ?");
        $update_prop->bind_param("i", $proposal_id);
        $update_prop->execute();

        // Log Activity
        $desc = "Merged a '{$proposal['action_type']}' proposal";
        $log = $conn->prepare("INSERT INTO activities (user_id, activity_type, target_id, description) VALUES (?, 'merged_proposal', ?, ?)");
        $log->bind_param("iis", $user_id, $deck_id, $desc);
        $log->execute();

        $_SESSION["deck_review_success"] = "Proposal merged successfully!";
    }
    else { // Reject
        $update_prop = $conn->prepare("UPDATE card_proposals SET status = 'rejected' WHERE id = ?");
        $update_prop->bind_param("i", $proposal_id);
        $update_prop->execute();

        // Log Activity
        $desc = "Rejected a '{$proposal['action_type']}' proposal";
        $log = $conn->prepare("INSERT INTO activities (user_id, activity_type, target_id, description) VALUES (?, 'rejected_proposal', ?, ?)");
        $log->bind_param("iis", $user_id, $deck_id, $desc);
        $log->execute();

        $_SESSION["deck_review_success"] = "Proposal rejected.";
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["deck_review_error"] = "Database error while processing proposal. Try again.";
    error_log("Merge error: " . $e->getMessage());
}

header("Location: deck_review_proposals.php?id=" . $deck_id);
exit();
?>
