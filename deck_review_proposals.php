<?php
// deck_review_proposals.php
$conn = include "connect.php"; // session_start() is handled in here

if (!isset($_SESSION["user_id"])) {
    die('<br><h1>Yer not logged in matey!</h1><br><a href="land_login.php">Go to login</a>');
}

$user_id = (int) $_SESSION["user_id"];
$deck_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Determine if we are in "Global" mode (all decks) or "Specific" mode (one deck)
$global_mode = ($deck_id === 0);
$deck = null;

// If specific mode, verify ownership of that deck
if (!$global_mode) {
    $stmt = $conn->prepare("SELECT * FROM decks WHERE id = ?");
    $stmt->bind_param("i", $deck_id);
    $stmt->execute();
    $deck = $stmt->get_result()->fetch_assoc();

    if (!$deck || $deck["user_id"] != $user_id) {
        die("<h1>Avast!</h1><p>Ye don't have permission to review this deck.</p><a href='decks.php'>Return to Dashboard</a>");
    }
}

// Handle session messages
$message = "";
$message_success = "";
if (!empty($_SESSION["deck_review_error"])) {
    $message = $_SESSION["deck_review_error"];
    unset($_SESSION["deck_review_error"]);
} elseif (!empty($_SESSION["deck_review_success"])) {
    $message_success = $_SESSION["deck_review_success"];
    unset($_SESSION["deck_review_success"]);
}

// Fetch pending proposals based on mode
if (!$global_mode) {
    $prop_sql = "
        SELECT cp.*, u.username, c.question as current_question, c.answer as current_answer
        FROM card_proposals cp
        JOIN users u ON cp.user_id = u.id
        LEFT JOIN cards c ON cp.card_id = c.id
        WHERE cp.deck_id = ? AND cp.status = 'pending'
        ORDER BY cp.created_at ASC
    ";
    $prop_stmt = $conn->prepare($prop_sql);
    $prop_stmt->bind_param("i", $deck_id);
} else {
    $prop_sql = "
        SELECT cp.*, d.title as deck_title, d.id as deck_id, u.username, c.question as current_question, c.answer as current_answer
        FROM card_proposals cp
        JOIN decks d ON cp.deck_id = d.id
        JOIN users u ON cp.user_id = u.id
        LEFT JOIN cards c ON cp.card_id = c.id
        WHERE d.user_id = ? AND cp.status = 'pending'
        ORDER BY d.title ASC, cp.created_at ASC
    ";
    $prop_stmt = $conn->prepare($prop_sql);
    $prop_stmt->bind_param("i", $user_id);
}

$prop_stmt->execute();
$proposals = $prop_stmt->get_result();
?>

<?php include "header.php"; ?>
<head>
    <title><?= $global_mode ? "All Pending Proposals" : "Review Proposals - " . htmlspecialchars($deck["title"]) ?></title>
</head>

<body>
    <main class="container mt-5">
        <!-- Breadcrumbs for UX -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="user_dashboard.php">Dashboard</a></li>
                <?php if (!$global_mode): ?>
                    <li class="breadcrumb-item"><a href="deck_details.php?id=<?= $deck_id ?>"><?= htmlspecialchars(mb_substr($deck["title"], 0, 20)) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= $global_mode ? "All Proposals" : "Review Proposals" ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?= $global_mode ? "Review All Pending Proposals" : "Review Proposals for: " . htmlspecialchars($deck["title"]) ?></h2>
            <a href="<?= $global_mode ? 'user_dashboard.php' : 'deck_details.php?id=' . $deck_id ?>" class="btn btn-outline-secondary">
                <?= $global_mode ? "Back to Dashboard" : "Back to Deck" ?>
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($message_success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message_success) ?></div>
        <?php endif; ?>

        <?php if ($proposals->num_rows === 0): ?>
            <div class="alert alert-info text-center">
                <h4 class="alert-heading">All clear, Cap'n!</h4>
                <p>There are no pending proposals for your decks right now.</p>
                <a href="decks.php" class="btn btn-primary mt-2">Manage Your Decks</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php
                $current_deck_title = "";
                while ($prop = $proposals->fetch_assoc()):
                    // In global mode, print a deck header when the deck changes
                    if ($global_mode && $prop['deck_title'] !== $current_deck_title) {
                        $current_deck_title = $prop['deck_title'];
                        echo '<div class="col-12 mt-4 mb-2"><h4 class="text-primary border-bottom pb-2">' . htmlspecialchars($current_deck_title) . ' <a href="deck_review_proposals.php?id=' . $prop['deck_id'] . '" class="btn btn-sm btn-outline-primary ms-2">Manage this deck</a></h4></div>';
                    }

                    // Determine badge color based on action
                    $action_badge = match($prop['action_type']) {
                        'add' => '<span class="badge bg-success">Add</span>',
                        'edit' => '<span class="badge bg-info text-dark">Edit</span>',
                        'delete' => '<span class="badge bg-danger">Delete</span>',
                    };
                ?>
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Suggested by:</strong> <?= htmlspecialchars($prop['username']) ?>
                                <span class="text-muted small">on <?= date('M d, Y', strtotime($prop['created_at'])) ?></span>
                            </div>
                            <div><?= $action_badge ?></div>
                        </div>
                        <div class="card-body">
                            <?php if ($prop['action_type'] === 'add'): ?>
                                <h6 class="text-muted">Proposed New Card:</h6>
                                <div class="p-3 border rounded mb-3">
                                    <p class="mb-1"><strong>Q:</strong> <?= nl2br(htmlspecialchars($prop['proposed_question'])) ?></p>
                                    <p class="mb-0"><strong>A:</strong> <?= nl2br(htmlspecialchars($prop['proposed_answer'])) ?></p>
                                </div>
                                <small class="text-muted">Type: <?= ucfirst($prop['proposed_card_type']) ?> | Difficulty: <?= ucfirst($prop['proposed_difficulty']) ?></small>

                            <?php elseif ($prop['action_type'] === 'edit'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-danger">Current Version:</h6>
                                        <div class="p-3 rounded border border-danger">
                                            <p class="mb-1"><strong>Q:</strong> <?= nl2br(htmlspecialchars($prop['current_question'])) ?></p>
                                            <p class="mb-0"><strong>A:</strong> <?= nl2br(htmlspecialchars($prop['current_answer'])) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success">Proposed Version:</h6>
                                        <div class="p-3 rounded border border-success">
                                            <p class="mb-1"><strong>Q:</strong> <?= nl2br(htmlspecialchars($prop['proposed_question'])) ?></p>
                                            <p class="mb-0"><strong>A:</strong> <?= nl2br(htmlspecialchars($prop['proposed_answer'])) ?></p>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($prop['action_type'] === 'delete'): ?>
                                <h6 class="text-danger">Card to be Deleted:</h6>
                                <div class="p-3 rounded border border-danger">
                                    <p class="mb-1"><strong>Q:</strong> <?= nl2br(htmlspecialchars($prop['current_question'])) ?></p>
                                    <p class="mb-0"><strong>A:</strong> <?= nl2br(htmlspecialchars($prop['current_answer'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <form action="merge_proposal.php" method="POST" style="display:inline;">
                                <input type="hidden" name="proposal_id" value="<?= $prop['id'] ?>">
                                <input type="hidden" name="deck_id" value="<?= $prop['deck_id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Reject this suggestion?');">Reject</button>
                            </form>
                            <form action="merge_proposal.php" method="POST" style="display:inline;">
                                <input type="hidden" name="proposal_id" value="<?= $prop['id'] ?>">
                                <input type="hidden" name="deck_id" value="<?= $prop['deck_id'] ?>">
                                <input type="hidden" name="action" value="merge">
                                <button type="submit" class="btn btn-success">Merge Proposal</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
<?php include "footer.php"; ?>
