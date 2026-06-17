<?php
/** @var mysqli $conn */
$conn = include "connect.php";
require_once "classes/DeckManager.php";

$user_id = $_SESSION["user_id"];
$deckManager = new DeckManager($conn);

$limit = $deckManager->getUserDailyLimit($user_id);
$deck_states = $deckManager->getDeckStates($user_id);

$query = "
SELECT d.id AS deck_id, d.title, u.username
FROM decks d
LEFT JOIN users u ON d.user_id = u.id
WHERE d.user_id = ? OR d.id IN (SELECT deck_id FROM deck_contributors WHERE user_id = ? AND status = 'accepted')
ORDER BY d.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

include "header.php";
?>
<div class="container mt-5 col-8">
    <h3>Study Decks</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>New</th>
                <th>In progress</th>
                <th>Review</th>
                <th>Owner</th>
                <th>Free Mode</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()):

                $stats = $deck_states[$row["deck_id"]] ?? [
                    "raw_new_count" => 0,
                    "in_progress" => 0,
                    "review" => 0,
                    "seen_today" => 0,
                ];

                // BUG 10 FIX: Subtract seen_today from the global daily limit
                $remainingNewLimit = max(0, $limit - $stats["seen_today"]);
                $new_count = min($remainingNewLimit, $stats["raw_new_count"]);
                ?>
                <tr>
                    <td class="align-middle">
                        <a href="study_deck.php?id=<?= $row[
                            "deck_id"
                        ] ?>" class="deck-link text-decoration-none fw-bold">
                            <?= mb_substr(
                                htmlspecialchars($row["title"]),
                                0,
                                30,
                            ) . (mb_strlen($row["title"]) > 30 ? "..." : "") ?>
                    </td>
                    <td style="color: Tomato;" class="align-middle"><?= $new_count ?></td>
                    <td style="color: Orange;" class="align-middle"><?= $stats[
                        "in_progress"
                    ] ?></td>
                    <td style="color: MediumSeaGreen;" class="align-middle"><?= $stats[
                        "review"
                    ] ?></td>
                    <td class="align-middle"><?= htmlspecialchars(
                        $row["username"],
                    ) ?>
                    </td>
                    <td class="align-middle"><a href="study_free_deck.php?id=<?= $row[
                        "deck_id"
                    ] ?>" class="btn btn-sm btn-outline-secondary px-2 py-0">Free Mode</a></td>
                </tr>
            <?php
            endwhile; ?>
        </tbody>
    </table>
</div>
<?php include "footer.php"; ?>
