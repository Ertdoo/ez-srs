<?php

class DeckManager
{
    /** @var mysqli */
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    public function getUserDailyLimit($userId)
    {
        $stmt = $this->conn->prepare(
            "SELECT new_cards_per_day FROM users WHERE id = ?",
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return isset($result["new_cards_per_day"])
            ? (int) $result["new_cards_per_day"]
            : 15;
    }

    public function getDeckStates($userId)
    {
        // Filter learning bounds securely and add seen_today check
        $query = "
            SELECT
                c.deck_id,
                COUNT(CASE WHEN s.card_id IS NULL THEN 1 END) as raw_new_count,
                COUNT(CASE WHEN s.learning_due_at IS NOT NULL AND YEAR(s.learning_due_at) > 0 AND s.learning_due_at <= NOW() + INTERVAL 20 MINUTE THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN s.card_id IS NOT NULL AND s.learning_due_at IS NULL AND s.next_due_date IS NOT NULL AND YEAR(s.next_due_date) > 0 AND s.next_due_date <= CURDATE() THEN 1 END) as review_count,
                COUNT(CASE WHEN s.card_id IS NOT NULL AND DATE(s.first_seen_at) = CURDATE() THEN 1 END) as seen_today
            FROM cards c
            LEFT JOIN (
                SELECT card_id, next_due_date, learning_due_at, first_seen_at FROM study_sessions WHERE user_id = ?
            ) s ON c.id = s.card_id
            GROUP BY c.deck_id
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $states = [];
        while ($row = $result->fetch_assoc()) {
            $states[$row["deck_id"]] = [
                "raw_new_count" => (int) $row["raw_new_count"],
                "in_progress" => (int) $row["in_progress_count"],
                "review" => (int) $row["review_count"],
                "seen_today" => (int) $row["seen_today"],
            ];
        }
        return $states;
    }

    public function getStudyQueues($userId, $deckId, $newLimit)
    {
        // Daily review limit to prevent endless 20-card refills
        $reviewLimit = 200;
        $revStmt = $this->conn->prepare("
            SELECT COUNT(*) AS cnt FROM study_sessions ss JOIN cards c ON c.id = ss.card_id
            WHERE ss.user_id = ? AND c.deck_id = ? AND DATE(ss.reviewed_at) = CURDATE() AND ss.learning_due_at IS NULL
        ");
        $revStmt->bind_param("ii", $userId, $deckId);
        $revStmt->execute();
        $revRow = $revStmt->get_result()->fetch_assoc();
        $reviewsToday = isset($revRow["cnt"]) ? (int) $revRow["cnt"] : 0;

        $dueFetchLimit = max(0, $reviewLimit - $reviewsToday));

        $due = [];
        if ($dueFetchLimit > 0) {
            $dueStmt = $this->conn->prepare("
                SELECT c.id, c.question, c.answer, c.difficulty, ss.ease_factor, ss.interval_days, ss.next_due_date, ss.learning_due_at, 'due' as queue
                FROM cards c JOIN study_sessions ss ON ss.card_id = c.id AND ss.user_id = ?
                WHERE c.deck_id = ? AND ss.learning_due_at IS NULL AND ss.next_due_date IS NOT NULL AND YEAR(ss.next_due_date) > 0 AND ss.next_due_date <= CURDATE()
                ORDER BY ss.next_due_date ASC LIMIT ?
            ");
            $dueStmt->bind_param("iii", $userId, $deckId, $dueFetchLimit);
            $dueStmt->execute();
            $due = $dueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // CRITICAL SYNC FIX: Modified interval to check up to NOW() + 20 MINUTES to perfectly align with getDeckStates()
        $learnStmt = $this->conn->prepare("
            SELECT c.id, c.question, c.answer, c.difficulty, ss.ease_factor, ss.interval_days, ss.next_due_date, ss.learning_due_at, 'learning' as queue
            FROM cards c JOIN study_sessions ss ON ss.card_id = c.id AND ss.user_id = ?
            WHERE c.deck_id = ? AND ss.learning_due_at IS NOT NULL AND YEAR(ss.learning_due_at) > 0 AND ss.learning_due_at <= NOW() + INTERVAL 20 MINUTE
            ORDER BY ss.learning_due_at ASC
        ");
        $learnStmt->bind_param("ii", $userId, $deckId);
        $learnStmt->execute();
        $learn = $learnStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // New Cards
        $seenStmt = $this->conn->prepare("
            SELECT COUNT(*) AS cnt FROM study_sessions ss JOIN cards c ON c.id = ss.card_id
            WHERE ss.user_id = ? AND c.deck_id = ? AND DATE(ss.first_seen_at) = CURDATE()
        ");
        $seenStmt->bind_param("ii", $userId, $deckId);
        $seenStmt->execute();
        $seenRow = $seenStmt->get_result()->fetch_assoc();
        $seenToday = isset($seenRow["cnt"]) ? (int) $seenRow["cnt"] : 0;

        $remainingNew = max(0, $newLimit - $seenToday);

        $new = [];
        if ($remainingNew > 0) {
            $newStmt = $this->conn->prepare("
                SELECT c.id, c.question, c.answer, c.difficulty, 2.50 as ease_factor, 0 as interval_days, NULL as next_due_date, NULL as learning_due_at, 'new' as queue
                FROM cards c WHERE c.deck_id = ? AND NOT EXISTS (SELECT 1 FROM study_sessions ss WHERE ss.card_id = c.id AND ss.user_id = ?)
                ORDER BY c.id ASC LIMIT ?
            ");
            $newStmt->bind_param("iii", $deckId, $userId, $remainingNew);
            $newStmt->execute();
            $new = $newStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return ["due" => $due, "learn" => $learn, "new" => $new];
    }
}
