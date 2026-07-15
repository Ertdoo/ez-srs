<?php
ini_set('display_errors', 1); // TEMPORARY DEBUG ONLY
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = include "connect.php";
if (!$conn || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: land_login.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"]; // Cast to int explicitly
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'shared' ? 'shared' : 'my_decks';

// ... rest of queries using "i" for user_id binding

// QUERY 1: My Decks (Owned by user)
$get_my_decks = $conn->prepare("
    SELECT
        d.id AS deck_id, d.title, d.description, d.is_public, d.created_at,
        COUNT(c.id) AS total_cards
    FROM decks d
    LEFT JOIN cards c ON d.id = c.deck_id
    WHERE d.user_id = ?
    GROUP BY d.id
    ORDER BY d.created_at DESC
");
$get_my_decks->bind_param("i", $user_id);
$get_my_decks->execute();
$result_my = $get_my_decks->get_result();

// QUERY 2: Shared With Me (Accepted contributors)
// QUERY 2: Shared With Me (Accepted contributors)
$get_shared_decks = $conn->prepare("
    SELECT
        d.id AS deck_id,
        d.title,
        d.description,
        d.is_public,
        d.created_at,
        u.username AS owner_name,
        MAX(dc.role) AS role,
        COUNT(c.id) AS total_cards
    FROM deck_contributors dc
    INNER JOIN decks d ON dc.deck_id = d.id
    INNER JOIN users u ON d.user_id = u.id
    LEFT JOIN cards c ON d.id = c.deck_id
    WHERE dc.user_id = ? AND dc.status = 'accepted'
    GROUP BY d.id, d.title, d.description, d.is_public, d.created_at, u.username
    ORDER BY d.created_at DESC
");
$get_shared_decks->bind_param("i", $user_id);
$get_shared_decks->execute();
$result_shared = $get_shared_decks->get_result();
?>

<?php include "header.php"; ?>
<head>
    <title>Manage Decks</title>
    <style>
        .nav-tabs .nav-link.active { font-weight: bold; border-bottom: 3px solid #0d6efd; }
        .role-badge { font-size: 0.75em; padding: 2px 8px; border-radius: 12px; }
        .role-editor { background-color: #e9ecef; color: #495057; }
        .role-viewer { background-color: #f8f9fa; color: #6c757d; }
    </style>
</head>

<div class="container mt-5">
    <h3>Manage Decks</h3>

    <!-- TAB NAVIGATION -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab === 'my_decks' ? 'active' : ''; ?>"
               href="?tab=my_decks">
               My Decks (<?php echo $result_my->num_rows; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab === 'shared' ? 'active' : ''; ?>"
               href="?tab=shared">
               Shared With Me (<?php echo $result_shared->num_rows; ?>)
            </a>
        </li>
    </ul>

    <!-- MY DECKS TAB CONTENT -->
    <?php if ($active_tab === 'my_decks'): ?>
        <table class="table table-striped" id="create_deck_list">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Public</th>
                    <th>Cards</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_my->fetch_assoc()):
                    // DAP LOGIC: Check pending proposals only for owned decks
                    $pending_count = 0;
                    $pend_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM card_proposals WHERE deck_id = ? AND status = 'pending'");
                    $pend_stmt->bind_param("i", $row["deck_id"]);
                    $pend_stmt->execute();
                    $pending_count = $pend_stmt->get_result()->fetch_assoc()["cnt"];
                    $pend_stmt->close();
                ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(mb_substr($row["title"], 0, 30)) . (mb_strlen($row["title"]) > 30 ? "..." : "") ?>
                            <?php if ($pending_count > 0): ?>
                                <span class="badge bg-warning text-dark ms-2" title="<?= $pending_count ?> pending proposal(s)">️ <?= $pending_count ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(mb_substr($row["description"] ?? '', 0, 30)) . (mb_strlen($row["description"] ?? '') > 30 ? "..." : "") ?></td>
                        <td><?= $row["is_public"] ? "✓" : "—" ?></td>
                        <td><?= $row["total_cards"] ?></td>
                        <td><a href='deck_details.php?id=<?= $row["deck_id"] ?>' class="btn btn-sm btn-primary">Manage Deck</a></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($result_my->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted">You haven't created any decks yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <!-- SHARED WITH ME TAB CONTENT -->
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Owner</th>
                    <th>Your Role</th>
                    <th>Cards</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_shared->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(mb_substr($row["title"], 0, 30)) . (mb_strlen($row["title"]) > 30 ? "..." : "") ?></td>
                        <td><?= htmlspecialchars($row["owner_name"]) ?></td>
                        <td>
                            <span class="role-badge <?= $row['role'] === 'editor' ? 'role-editor' : 'role-viewer' ?>">
                                <?= ucfirst($row['role']) ?>
                            </span>
                        </td>
                        <td><?= $row["total_cards"] ?></td>
                        <td>
                            <?php if ($row['role'] === 'editor'): ?>
                                <a href='deck_details.php?id=<?= $row["deck_id"] ?>' class="btn btn-sm btn-outline-primary">View & Suggest</a>
                            <?php else: ?>
                                <a href='deck_details.php?id=<?= $row["deck_id"] ?>' class="btn btn-sm btn-outline-secondary">View Only</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($result_shared->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted">You aren't subscribed to any shared decks yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include "footer.php"; ?>
