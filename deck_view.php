<?php
// 1. Ensure session is started (if not already handled in connect.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = include "connect.php";

// 2. Check login status without forcing a die()
$is_logged_in = isset($_SESSION["username"]);
$username = $is_logged_in ? $_SESSION["username"] : null;
$user_id = $is_logged_in ? (int) $_SESSION["user_id"] : null;

$deck_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($deck_id <= 0) {
    die('<br><h1>No deck specified, matey!</h1><br><a href="deck_view.php">Go back to browsing</a>');
}



// Flash messages (fork/subscribe actions can set these)
$message = "";
$message_success = "";
if (!empty($_SESSION["deck_view_error"])) {
    $message = $_SESSION["deck_view_error"];
    unset($_SESSION["deck_view_error"]);
} elseif (!empty($_SESSION["deck_view_success"])) {
    $message_success = $_SESSION["deck_view_success"];
    unset($_SESSION["deck_view_success"]);
}

// Fetch the deck + creator info
$deck_query = "
SELECT
    decks.id AS deck_id,
    decks.user_id AS owner_id,
    decks.title,
    decks.description,
    decks.is_public,
    decks.created_at,
    users.username AS creator,
    COUNT(DISTINCT cards.id) AS total_cards
FROM decks
LEFT JOIN users ON decks.user_id = users.id
LEFT JOIN cards ON decks.id = cards.deck_id
WHERE decks.id = ?
GROUP BY decks.id, decks.user_id, decks.title, decks.description, decks.is_public, decks.created_at, users.username
";
$stmt = $conn->prepare($deck_query);
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();

if (!$deck) {
    die('<br><h1>Deck not found, matey!</h1><br><a href="deck_browse.php">Go back to browsing</a>');
}

// Optional: Block guests from viewing private decks
if ($deck["is_public"] == 0) {
    die('<br><h1>This deck is private, matey!</h1><br><a href="land_login.php">Log in to request access</a>');
}

// 3. Only consider them the owner if they are logged in AND their user_id matches
$is_owner = $is_logged_in && ($deck["owner_id"] == $user_id);

// 4. Check deck_contributors ONLY if the user is logged in
$contributor = null;
$is_subscribed = false;
$is_contributor_other_role = false;

if ($is_logged_in) {
    $contrib_query = "
    SELECT role, status
    FROM deck_contributors
    WHERE deck_id = ? AND user_id = ?
    ";
    $stmt2 = $conn->prepare($contrib_query);
    $stmt2->bind_param("ii", $deck_id, $user_id);
    $stmt2->execute();
    $contributor = $stmt2->get_result()->fetch_assoc();

    $is_subscribed =
        $contributor &&
        $contributor["role"] === "viewer" &&
        $contributor["status"] === "accepted";

    $is_contributor_other_role =
        $contributor &&
        $contributor["role"] !== "viewer" &&
        $contributor["status"] === "accepted";
}

// Fetch up to 5 sample cards for the preview
$cards_query = "
SELECT question, answer, card_type, difficulty
FROM cards
WHERE deck_id = ?
LIMIT 5
";
$stmt3 = $conn->prepare($cards_query);
$stmt3->bind_param("i", $deck_id);
$stmt3->execute();
$cards_result = $stmt3->get_result();
?>
<?php include "header.php"; ?>
<head>
    <title><?= htmlspecialchars($deck["title"]) ?></title>
</head>

<style>
    .repo-banner {
        border-bottom: 1px solid #2a2f4a;
        padding-bottom: 16px;
        margin-bottom: 16px;
    }
    .repo-banner-path {
        font-size: 1.6rem;
        font-weight: 600;
    }
    .repo-banner-path .owner {
        color: #a0a8c8;
        font-weight: 400;
    }
    .repo-banner-path .sep {
        color: #4a4f6a;
        font-weight: 400;
        margin: 0 4px;
    }
    .repo-banner-path .title {
        color: #7ec87e;
    }
    .repo-banner-meta {
        color: #6e7390;
        font-size: 0.85rem;
        margin-top: 4px;
    }

    /* --- UPDATED README STYLES --- */
    .readme-box {
        background: transparent;
        border: none;
        padding: 0;
        color: #d2d6ee;
        margin-bottom: 30px;
        line-height: 1.6;
        /* no white-space here */
    }
    .readme-box-header {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #7ec87e;
        margin: 0 0 16px;
        border-bottom: 1px solid #2a2f4a;
        padding-bottom: 8px;
        font-weight: 600;
    }
    .readme-box-content {
        white-space: pre-wrap;
    }
    /* --------------------------- */

    .card-preview-table th {
        color: #6e7390;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #2a2f4a;
    }
    .card-preview-table td {
        vertical-align: top;
        max-width: 260px;
    }
    .badge-difficulty-easy {
        background: #1a3a1a;
        color: #7ec87e;
    }
    .badge-difficulty-medium {
        background: #3a3a1a;
        color: #e0d27e;
    }
    .badge-difficulty-hard {
        background: #3a1a1a;
        color: #e07e7e;
    }
</style>

<div class="container mt-5">

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($message_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message_success) ?></div>
    <?php endif; ?>

    <div class="repo-banner d-flex justify-content-between align-items-start flex-wrap" style="gap:16px">
        <div>
            <div class="repo-banner-path">
                <span class="owner"><?= htmlspecialchars($deck["creator"] ?? "Unknown") ?></span>
                <span class="sep">/</span>
                <span class="title"><?= htmlspecialchars($deck["title"]) ?></span>
            </div>
            <div class="repo-banner-meta">
                Created <?= date("M j, Y", strtotime($deck["created_at"])) ?>
                &middot; <?= intval($deck["total_cards"]) ?> card<?= $deck["total_cards"] == 1 ? "" : "s" ?>
                &middot; <?= $deck["is_public"] ? "Public" : "Private" ?>
            </div>
        </div>

        <!-- 5. Updated Button Logic -->
        <div class="d-flex" style="gap:8px">
            <?php if ($is_owner): ?>
                <a href="deck_details.php?id=<?= $deck_id ?>" class="btn btn-outline-primary">Edit Deck</a>
            <?php else: ?>
                <?php if ($is_logged_in): ?>
                    <!-- Logged-in user actions -->
                    <?php if ($is_subscribed): ?>
                        <button class="btn btn-outline-secondary" disabled>Subscribed</button>
                    <?php elseif ($is_contributor_other_role): ?>
                        <button class="btn btn-outline-secondary" disabled>Collaborator</button>
                    <?php else: ?>
                        <form action="classes/deck_subscribe.php" method="POST" style="display:inline;">
                            <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                            <button type="submit" class="btn btn-outline-primary">Subscribe</button>
                        </form>
                    <?php endif; ?>

                    <form action="classes/deck_fork.php" method="POST" style="display:inline;">
                        <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                        <button type="submit" class="btn btn-outline-success">Fork</button>
                    </form>
                <?php else: ?>
                    <!-- Guest user actions -->
                    <a href="land_login.php" class="btn btn-outline-primary">Log in to Subscribe</a>
                    <a href="land_login.php" class="btn btn-outline-success">Log in to Fork</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="readme-box">
        <div class="readme-box-header">Description</div>
        <div class="readme-box-content"><?php if (!empty($deck["description"])): ?><?= nl2br(htmlspecialchars(trim($deck["description"]))) ?><?php else: ?><span class="text-muted">No description provided for this deck.</span><?php endif; ?></div>
    </div>

    <h5 class="mb-3">Card Preview</h5>
    <table class="table table-striped card-preview-table">
        <thead>
            <tr>
                <th style="width:35%">Question</th>
                <th style="width:35%">Answer</th>
                <th style="width:15%">Type</th>
                <th style="width:15%">Difficulty</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cards_result->num_rows > 0) {
                while ($card = $cards_result->fetch_assoc()) {
                    $question = mb_substr(htmlspecialchars($card["question"]), 0, 100);
                    $answer = mb_substr(htmlspecialchars($card["answer"]), 0, 100);
                    $difficulty = htmlspecialchars($card["difficulty"] ?? "medium");
                    $card_type = htmlspecialchars($card["card_type"] ?? "basic");
                    echo "<tr>";
                    echo "<td>" . $question . "</td>";
                    echo "<td>" . $answer . "</td>";
                    echo "<td>" . $card_type . "</td>";
                    echo '<td><span class="badge badge-difficulty-' . $difficulty . '">' . $difficulty . "</span></td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="4" class="text-center text-muted">No cards in this deck yet, matey!</td></tr>';
            } ?>
        </tbody>
    </table>

    <?php if ($deck["total_cards"] > 5): ?>
        <p class="repo-banner-meta">
            Showing 5 of <?= intval($deck["total_cards"]) ?> cards. Fork or subscribe to see the rest.
        </p>
    <?php endif; ?>

    <a href="deck_browse.php" class="btn btn-link" style="color:#a0a8c8;">&#8592; Back to browsing</a>

</div>

<?php include "footer.php"; ?>
