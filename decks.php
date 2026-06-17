<?php $conn = include "connect.php";
//user id
$user_id = $_SESSION["user_id"];

// fetch all decks from db
$getdeckscreate = $conn->prepare("SELECT * FROM decks WHERE user_id = ?");
$getdeckscreate->bind_param("s", $user_id);
$getdeckscreate->execute();
$result = $getdeckscreate->get_result();

// find cards that match
$card_query = "
SELECT
    decks.id AS deck_id,
    decks.user_id,
    decks.title,
    users.username,
    decks.description,
    decks.is_public,
    decks.created_at,
    COUNT(cards.id) AS total_cards
FROM decks
LEFT JOIN cards ON decks.id = cards.deck_id
LEFT JOIN users ON decks.user_id = users.id
WHERE decks.user_id = ?
    OR decks.id IN (
        SELECT deck_contributors.deck_id
        FROM deck_contributors
        WHERE deck_contributors.user_id = ?
          AND deck_contributors.status = 'accepted'
    )
GROUP BY decks.id, decks.title, decks.description, decks.is_public, decks.created_at
ORDER BY decks.created_at DESC
";

$stmt = $conn->prepare($card_query);
$stmt->bind_param("ii", $user_id, $user_id); // both placeholders are user_id
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include "header.php"; ?>
<head>
    <title>Manage Decks</title>
</head>
<div class="container mt-5">
    <div class="row">
        <div>
            <h3>Manage Decks</h3>
            <table class="table table-striped" id="create_deck_list">
                <thead>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Public</th>
                    <th>Total Cards</th>
                    <th>Owner</th>
                    <th></th>

                </thead>
                <tbody>
                    <tr>
                        <?php if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" .
                                    mb_substr(
                                        htmlspecialchars($row["title"]),
                                        0,
                                        30,
                                    ) .
                                    (mb_strlen($row["title"]) > 30
                                        ? "..."
                                        : "") .
                                    "</td>";
                                echo "<td>" .
                                    mb_substr(
                                        htmlspecialchars($row["description"]),
                                        0,
                                        30,
                                    ) .
                                    (mb_strlen($row["description"]) > 30
                                        ? "..."
                                        : "") .
                                    "</td>";
                                echo "<td>" .
                                    ($row["is_public"] ? "✓" : "—") .
                                    "</td>";
                                echo "<td>" . $row["total_cards"] . "</td>";
                                echo "<td>" .
                                    htmlspecialchars($row["username"]) .
                                    "</td>";
                                echo "<td><a href='deck_details.php?id=" .
                                    $row["deck_id"] .
                                    "'>Manage deck</a></td>";
                                echo "</tr>";
                            }
                        } ?>
                    </tr>
                </tbody>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
