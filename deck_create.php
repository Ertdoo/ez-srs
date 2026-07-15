<?php $conn = include "connect.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
    $user_id = $_SESSION["user_id"];
} else {
    die(
        '<br><h1>Yer not logged in matey!</h1><br><a href="land_login.php">Go to login</a>'
    );
}

$message = "";
$message_success = "";
if (!empty($_SESSION["deck_create_error"])) {
    $message = $_SESSION["deck_create_error"];
    unset($_SESSION["deck_create_error"]);
} elseif (!empty($_SESSION["deck_create_success"])) {
    $message_success = $_SESSION["deck_create_success"];
    unset($_SESSION["deck_create_success"]);
} else {
    $message = "";
    $message_success = "";
    if (isset($_SESSION["deck_create_error"])) {
        unset($_SESSION["deck_create_error"]);
    }
    if (isset($_SESSION["deck_create_success"])) {
        unset($_SESSION["deck_create_success"]);
    }
}

// Define how many decks you want per page
$decks_per_page = 10;

// Get current page number from URL, default to 1
$page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
$offset = ($page - 1) * $decks_per_page;

$count_query = "
SELECT COUNT(DISTINCT decks.id) as total
FROM decks
WHERE decks.user_id = ?
   OR decks.id IN (
       SELECT deck_contributors.deck_id
       FROM deck_contributors
       WHERE deck_contributors.user_id = ?
         AND deck_contributors.status = 'accepted'
   )
";

$stmt_count = $conn->prepare($count_query);
$stmt_count->bind_param("ii", $user_id, $user_id);
$stmt_count->execute();
$count_result = $stmt_count->get_result()->fetch_assoc();

$total_decks = $count_result["total"] ?? 0;
$total_pages = ceil($total_decks / $decks_per_page);

// find cards that match
$card_query = "
SELECT
    decks.id AS deck_id,
    decks.title,
    decks.description,
    decks.is_public,
    decks.created_at,
    decks.user_id AS deck_user_id,
    COUNT(cards.id) AS total_cards
FROM decks
LEFT JOIN cards ON decks.id = cards.deck_id
WHERE decks.user_id = ?
    OR decks.id IN (
        SELECT deck_contributors.deck_id
        FROM deck_contributors
        WHERE deck_contributors.user_id = ?
          AND deck_contributors.status = 'accepted'
    )
GROUP BY decks.id, decks.title, decks.description, decks.is_public, decks.created_at, decks.user_id
ORDER BY decks.created_at DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($card_query);
$stmt->bind_param("iiii", $user_id, $user_id, $decks_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include "header.php"; ?>
<head>
    <title>Create deck</title>
</head>

<style>
    /* Forces consistent single-line height across all text cells */
    #create_deck_list td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
        vertical-align: middle;
        height: 45px;
    }
</style>

<div class="container mt-5">
    <div class="row">
        <div class="col-sm-5">
            <h3>Create new deck</h3>
            <p>* Required</p>

            <form action="deck_create_action.php" method="POST" class="w-100" style="max-width: 400px;margin-bottom: 20px;">

                <?php if ($message): ?>
                <br>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <?php if ($message_success): ?>
                <br>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message_success) ?>
                </div>
                <?php endif; ?>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label for="deck_title" class="form-label">Deck title: *</label>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" id="deck_title" name="deck_title" class="form-control" style="width: 300px;" maxlength="30" placeholder="Max 50 characters matey!" required>
                    </div>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label for="deck_desc" class="form-label">Description:</label>
                    </div>
                    <div class="col-sm-8">
                        <textarea id="deck_desc" name="deck_desc" class="form-control" style="height: 100px; width: 300px;" maxlength="1000" placeholder="Max 1000 characters matey!"></textarea>
                    </div>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label for="deck_collab" class="form-label">Collaborators:</label>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" id="deck_collab" name="deck_collab" class="form-control" style="width: 300px;" maxlength="100" placeholder="Usernames seperated by commas">
                    </div>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label class="form-label" for="make_public">Make deck public? *</label>
                    </div>
                    <div class="col-sm-8">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="make_public" name="make_public">
                        </div>
                    </div>
                </div>
                <button type="submit" name="deck_create_btn" class="btn btn-outline-primary">Create deck</button>
            </form>
        </div>

        <div class="col-sm-7">
            <h3>Your decks</h3>
            <table class="table table-striped" id="create_deck_list">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Public</th>
                        <th>Cards</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $is_owner = ($row["deck_user_id"] == $user_id);

                            echo "<tr>";
                            echo "<td>" .
                                mb_substr(
                                    htmlspecialchars($row["title"]),
                                    0,
                                    30,
                                ) .
                                (mb_strlen($row["title"]) > 30 ? "..." : "") .
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
                            echo "<td>";

                            if ($is_owner) {
                                echo '<form action="deck_delete_DCREATE.php" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure matey?\');">
                                    <input type="hidden" name="deck_id" value="' .
                                    intval($row["deck_id"]) .
                                    '">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>';
                            } else {
                                echo '<span class="badge bg-secondary">Editor</span>';
                            }

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted'>No decks found yet, matey!</td></tr>";
                    } ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4" style="margin:0 auto">
                    <ul class="pagination pagination-sm" style="gap:4px">

                        <li class="page-item <?= $page <= 1
                            ? "disabled"
                            : "" ?>">
                            <a class="page-link rounded" href="?page=<?= $page -
                                1 ?>"
                              style="background:#161929;border-color:#2a2f4a;color:#a0a8c8">
                                &#8592;
                            </a>
                        </li>

                        <?php
                        $range = 1;
                        $display = [];
                        if ($total_pages <= 5 + $range * 2) {
                            $display = range(1, $total_pages);
                        } else {
                            $display[] = 1;
                            if ($page - $range > 2) {
                                $display[] = null;
                            }
                            $start = max(2, $page - $range);
                            $end = min($total_pages - 1, $page + $range);
                            if ($page <= $range + 2) {
                                $end = 2 + $range * 2;
                            } elseif ($page >= $total_pages - $range - 1) {
                                $start = $total_pages - 1 - $range * 2;
                            }
                            for ($i = $start; $i <= $end; $i++) {
                                $display[] = $i;
                            }
                            if ($page + $range < $total_pages - 1) {
                                $display[] = null;
                            }
                            $display[] = $total_pages;
                        }
                        ?>

                        <?php foreach ($display as $p): ?>
                            <?php if ($p === null): ?>
                                <li class="page-item disabled">
                                    <span class="page-link rounded"
                                          style="background:#161929;border-color:#2a2f4a;color:#4a4f6a">
                                        &hellip;
                                    </span>
                                </li>
                            <?php else: ?>
                                <li class="page-item <?= $page == $p
                                    ? "active"
                                    : "" ?>">
                                    <a class="page-link rounded" href="?page=<?= $p ?>"
                                      style="<?= $page == $p
                                          ? "background:#1a3a1a;border-color:#7ec87e;color:#7ec87e;font-weight:600"
                                          : "background:#161929;border-color:#2a2f4a;color:#a0a8c8" ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <li class="page-item <?= $page >= $total_pages
                            ? "disabled"
                            : "" ?>">
                            <a class="page-link rounded" href="?page=<?= $page +
                                1 ?>"
                              style="background:#161929;border-color:#2a2f4a;color:#a0a8c8">
                                &#8594;
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
