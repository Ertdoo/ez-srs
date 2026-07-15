<?php
// deck_edit_action.php
// Ensure session is started if connect.php doesn't do it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = include "connect.php";

// 1. Authentication Check
if (!isset($_SESSION["user_id"])) {
    header("Location: land_login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// 2. Capture and Validate Input
$deck_id = isset($_POST["deck_id"]) ? (int) $_POST["deck_id"] : 0;
$deck_title = isset($_POST["deck_title"]) ? trim($_POST["deck_title"]) : "";
$deck_desc = isset($_POST["deck_desc"]) ? trim($_POST["deck_desc"]) : "";
$is_public = isset($_POST["make_public"]) ? 1 : 0;
$collab_input = isset($_POST["deck_collab"]) ? trim($_POST["deck_collab"]) : "";

if ($deck_id <= 0 || empty($deck_title)) {
    $_SESSION["deck_edit_error"] = "Deck title cannot be empty, matey!";
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}

// 3. Authorization Check (Must be the owner)
$check_owner = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$check_owner->bind_param("i", $deck_id);
$check_owner->execute();
$owner_result = $check_owner->get_result()->fetch_assoc();

if (!$owner_result || $owner_result["user_id"] != $user_id) {
    $_SESSION["deck_edit_error"] = "Avast! Ye don't have permission to edit this deck.";
    header("Location: user_dashboard.php");
    exit();
}

// 4. Update Deck Details
$update_deck = $conn->prepare("
    UPDATE decks
    SET title = ?, description = ?, is_public = ?
    WHERE id = ? AND user_id = ?
");
$update_deck->bind_param("ssiii", $deck_title, $deck_desc, $is_public, $deck_id, $user_id);

if (!$update_deck->execute()) {
    $_SESSION["deck_edit_error"] = "Failed to update deck details. Try again.";
    error_log("Deck update failed: " . $update_deck->error);
    header("Location: deck_details.php?id=" . $deck_id);
    exit();
}
$update_deck->close();

// 5. Process Collaborators (if any provided)
$success_messages = ["Deck details updated successfully!"];
$warning_messages = [];

if (!empty($collab_input)) {
    // Split by comma and clean up usernames
    $usernames = array_map('trim', explode(',', $collab_input));
    $usernames = array_filter($usernames); // Remove empty entries
    $usernames = array_unique($usernames); // Remove duplicates

    foreach ($usernames as $username) {
        // Skip if it's the owner trying to add themselves (safely check if session username exists)
        if (isset($_SESSION["username"]) && strtolower($username) === strtolower($_SESSION["username"])) {
            continue;
        }

        // Find user ID
        $find_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $find_user->bind_param("s", $username);
        $find_user->execute();
        $target_user = $find_user->get_result()->fetch_assoc();
        $find_user->close();

        if (!$target_user) {
            $warning_messages[] = "User '{$username}' not found.";
            continue;
        }
        //tes

        $target_user_id = (int) $target_user["id"];

        // Check if already a contributor
        $check_contrib = $conn->prepare("SELECT role, status FROM deck_contributors WHERE deck_id = ? AND user_id = ?");
        $check_contrib->bind_param("ii", $deck_id, $target_user_id);
        $check_contrib->execute();
        $existing_contrib = $check_contrib->get_result()->fetch_assoc();
        $check_contrib->close();

        if ($existing_contrib) {
            $warning_messages[] = "'{$username}' is already a {$existing_contrib['role']} on this deck.";
            continue;
        }

        // Insert new collaborator as 'editor' with 'accepted' status
        $insert_contrib = $conn->prepare("
            INSERT INTO deck_contributors (deck_id, user_id, role, invited_by, status)
            VALUES (?, ?, 'editor', ?, 'accepted')
        ");
        $insert_contrib->bind_param("iii", $deck_id, $target_user_id, $user_id);

        if ($insert_contrib->execute()) {
            // Log activity
            $log_activity = $conn->prepare("
                INSERT INTO activities (user_id, activity_type, target_id, description)
                VALUES (?, 'shared', ?, ?)
            ");
            $activity_desc = "Added {$username} as an editor";
            $log_activity->bind_param("iis", $user_id, $deck_id, $activity_desc);

            if (!$log_activity->execute()) {
                // Log activity failure, but don't block the collaborator insert
                error_log("Activity log failed for deck {$deck_id}: " . $log_activity->error);
            }
            $log_activity->close();
        } else {
            // CRITICAL FIX: Log the actual database error so you know WHY it failed
            error_log("Failed to add collaborator '{$username}' to deck {$deck_id}: " . $insert_contrib->error);
            $warning_messages[] = "Failed to add '{$username}'.";
        }
        $insert_contrib->close();
    }
}

// 6. Set Session Messages and Redirect
$_SESSION["deck_edit_success"] = implode(" ", $success_messages);
if (!empty($warning_messages)) {
    $_SESSION["deck_edit_warning"] = implode(" | ", $warning_messages);
}

header("Location: deck_details.php?id=" . $deck_id);
exit();
?>
