<?php
$conn = include "connect.php";
require_once "classes/AnkiScheduler.php";
require_once "classes/DeckManager.php"; // Needed for count refreshing

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$card_id = isset($_POST["card_id"]) ? intval($_POST["card_id"]) : 0;
$deck_id = isset($_POST["deck_id"]) ? intval($_POST["deck_id"]) : 0;
$outcome = isset($_POST["outcome"]) ? trim($_POST["outcome"]) : "";

$valid_outcomes = ["again", "hard", "good", "easy"];
if ($card_id === 0 || $deck_id === 0 || !in_array($outcome, $valid_outcomes)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit();
}

// BUG 5 FIX: Verify card ownership / deck access
$auth = $conn->prepare("
    SELECT 1 FROM decks d
    JOIN cards c ON c.deck_id = d.id
    LEFT JOIN deck_contributors dc ON d.id = dc.deck_id
    WHERE c.id = ? AND d.id = ? AND (d.user_id = ? OR (dc.user_id = ? AND dc.status = 'accepted'))
");
$auth->bind_param("iiii", $card_id, $deck_id, $user_id, $user_id);
$auth->execute();
if ($auth->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "forbidden"]);
    exit();
}

$fetch = $conn->prepare(
    "SELECT ease_factor, interval_days, learning_due_at, next_due_date FROM study_sessions WHERE user_id = ? AND card_id = ? LIMIT 1",
);
$fetch->bind_param("ii", $user_id, $card_id);
$fetch->execute();
$row = $fetch->get_result()->fetch_assoc();

$is_new = $row === null;
$ease = $is_new ? 2.5 : floatval($row["ease_factor"]);
$interval = $is_new ? 0 : intval($row["interval_days"]);

// BUG 1 FIX: Pass learning_due_at
$newState = AnkiScheduler::processAnswer(
    $is_new,
    $ease,
    $interval,
    $row["next_due_date"] ?? null,
    $row["learning_due_at"] ?? null,
    $outcome,
);

if ($newState["learning_due_at"] === null) {
    $save = $conn->prepare("
        INSERT INTO study_sessions (user_id, card_id, outcome, ease_factor, interval_days, next_due_date, learning_due_at, reviewed_at, first_seen_at)
        VALUES (?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())
        ON DUPLICATE KEY UPDATE outcome = VALUES(outcome), ease_factor = VALUES(ease_factor), interval_days = VALUES(interval_days), next_due_date = VALUES(next_due_date), learning_due_at = NULL, reviewed_at = NOW()
    ");
    $save->bind_param(
        "iisdis",
        $user_id,
        $card_id,
        $outcome,
        $newState["ease_factor"],
        $newState["interval_days"],
        $newState["next_due_date"],
    );
} else {
    $save = $conn->prepare("
        INSERT INTO study_sessions (user_id, card_id, outcome, ease_factor, interval_days, next_due_date, learning_due_at, reviewed_at, first_seen_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE outcome = VALUES(outcome), ease_factor = VALUES(ease_factor), interval_days = VALUES(interval_days), next_due_date = VALUES(next_due_date), learning_due_at = VALUES(learning_due_at), reviewed_at = NOW()
    ");
    $save->bind_param(
        "iisdiss",
        $user_id,
        $card_id,
        $outcome,
        $newState["ease_factor"],
        $newState["interval_days"],
        $newState["next_due_date"],
        $newState["learning_due_at"],
    );
}
$save->execute();

$log = $conn->prepare(
    "INSERT INTO activities (user_id, activity_type, target_id, description, created_at) VALUES (?, 'studied', ?, ?, NOW())",
);
$desc = "rated card {$card_id} as {$outcome}";
$log->bind_param("iis", $user_id, $deck_id, $desc);
$log->execute();

// BUG 4 FIX: Retrieve absolute backend state to pass back to the client
$deckManager = new DeckManager($conn);
$limit = $deckManager->getUserDailyLimit($user_id);
$states = $deckManager->getDeckStates($user_id);
$stats = $states[$deck_id] ?? [
    "raw_new_count" => 0,
    "in_progress" => 0,
    "review" => 0,
    "seen_today" => 0,
];

$remainingNew = max(0, $limit - $stats["seen_today"]);
$displayNew = min($stats["raw_new_count"], $remainingNew);

$counts = [
    "new" => $displayNew,
    "in_progress" => $stats["in_progress"],
    "review" => $stats["review"],
];

header("Content-Type: application/json");
echo json_encode(["status" => "ok", "counts" => $counts]);
exit();
