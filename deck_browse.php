<?php $conn = include "connect.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
    $user_id = $_SESSION["user_id"];
} else {
    die(
        '<br><h1>Yer not logged in matey!</h1><br><a href="land_login.php">Go to login</a>'
    );
}

// Pagination setup
$decks_per_page = 12;
$page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
$offset = ($page - 1) * $decks_per_page;

// Search setup
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$search_param = "%" . $search . "%";

// Build WHERE clause depending on search
$where_sql = "WHERE decks.is_public = 1";
if ($search !== "") {
    $where_sql .= " AND (decks.title LIKE ? OR decks.description LIKE ?)";
}

// Count total matching public decks
$count_query = "
SELECT COUNT(DISTINCT decks.id) as total
FROM decks
$where_sql
";
$stmt_count = $conn->prepare($count_query);
if ($search !== "") {
    $stmt_count->bind_param("ss", $search_param, $search_param);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result()->fetch_assoc();
$total_decks = $count_result["total"] ?? 0;
$total_pages = ceil($total_decks / $decks_per_page);

// Main query: public decks, creator username, card count
$deck_query = "
SELECT
    decks.id AS deck_id,
    decks.title,
    decks.description,
    decks.created_at,
    users.username AS creator,
    COUNT(DISTINCT cards.id) AS total_cards
FROM decks
LEFT JOIN users ON decks.user_id = users.id
LEFT JOIN cards ON decks.id = cards.deck_id
$where_sql
GROUP BY decks.id, decks.title, decks.description, decks.created_at, users.username
ORDER BY decks.created_at DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($deck_query);
if ($search !== "") {
    $stmt->bind_param(
        "ssii",
        $search_param,
        $search_param,
        $decks_per_page,
        $offset,
    );
} else {
    $stmt->bind_param("ii", $decks_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include "header.php"; ?>
<head>
    <title>Browse public decks</title>
</head>

<style>
    .repo-row {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    .repo-row:hover {
        background-color: rgba(126, 200, 126, 0.08);
    }
    .repo-title {
        color: #7ec87e;
        font-weight: 600;
        text-decoration: none;
    }
    .repo-title:hover {
        text-decoration: underline;
        color: #9be09b;
    }
    .repo-desc {
        color: #a0a8c8;
        font-size: 0.9rem;
    }
    .repo-meta {
        color: #6e7390;
        font-size: 0.8rem;
    }
    .repo-card-icon {
        color: #7ec87e;
        margin-right: 4px;
    }
    #public_deck_list td {
        vertical-align: middle;
    }
</style>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-sm-12 d-flex justify-content-between align-items-center flex-wrap" style="gap:10px">
            <h3 class="mb-0">Browse public decks</h3>

            <form action="deck_browse.php" method="GET" class="d-flex" style="gap:6px;max-width:400px;width:100%;">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search decks by title or description..."
                    value="<?= htmlspecialchars($search) ?>"
                    style="background:#161929;border-color:#2a2f4a;color:#e6e9f5;"
                >
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <?php if ($search !== ""): ?>
                    <a href="deck_browse.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">

            <?php if ($search !== ""): ?>
                <p class="repo-meta mb-3">
                    Showing results for "<strong><?= htmlspecialchars(
                        $search,
                    ) ?></strong>" &mdash; <?= $total_decks ?> deck<?= $total_decks ==
 1
     ? ""
     : "s" ?> found.
                </p>
            <?php endif; ?>

            <table class="table table-striped" id="public_deck_list">
                <thead>
                    <tr>
                        <th style="width: 35%">Deck</th>
                        <th style="width: 35%">Description</th>
                        <th style="width: 15%">Creator</th>
                        <th style="width: 10%">Cards</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $deck_id = intval($row["deck_id"]);
                            $title = htmlspecialchars($row["title"]);
                            $desc = htmlspecialchars($row["description"] ?? "");
                            $desc_short =
                                mb_substr($desc, 0, 80) .
                                (mb_strlen($desc) > 80 ? "..." : "");
                            $creator = htmlspecialchars(
                                $row["creator"] ?? "Unknown",
                            );
                            $created = date(
                                "M j, Y",
                                strtotime($row["created_at"]),
                            );
                            echo '<tr class="repo-row" onclick="window.location=\'view-deck.php?id=' .
                                $deck_id .
                                '\'">';
                            echo "<td>";
                            echo '<a href="view-deck.php?id=' .
                                $deck_id .
                                '" class="repo-title">' .
                                $title .
                                "</a><br>";
                            echo '<span class="repo-meta">Created ' .
                                $created .
                                "</span>";
                            echo "</td>";
                            echo '<td><span class="repo-desc">' .
                                ($desc_short !== ""
                                    ? $desc_short
                                    : '<em class="repo-meta">No description provided</em>') .
                                "</span></td>";
                            echo "<td>" . $creator . "</td>";
                            echo "<td>" . intval($row["total_cards"]) . "</td>";
                            echo '<td><a href="deck_view.php?id=' .
                                $deck_id .
                                '" class="btn btn-sm btn-outline-primary">View</a></td>';
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center text-muted">No public decks found, matey!</td></tr>';
                    } ?>
                </tbody>
            </table>

            <?php
            $query_extra = "";
            if ($search !== "") {
                $query_extra = "&search=" . urlencode($search);
            }
            $prev_disabled = $page <= 1 ? "disabled" : "";
            $next_disabled = $page >= $total_pages ? "disabled" : "";
            $prev_page = $page - 1;
            $next_page = $page + 1;
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
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4" style="margin:0 auto">
                    <ul class="pagination pagination-sm" style="gap:4px">

                        <li class="page-item <?= $prev_disabled ?>">
                            <a class="page-link rounded" href="?page=<?= $prev_page,
                                $query_extra ?>"
                              style="background:#161929;border-color:#2a2f4a;color:#a0a8c8">
                                &#8592;
                            </a>
                        </li>

                        <?php foreach ($display as $p): ?>
                            <?php if ($p === null): ?>
                                <li class="page-item disabled">
                                    <span class="page-link rounded"
                                          style="background:#161929;border-color:#2a2f4a;color:#4a4f6a">
                                        &hellip;
                                    </span>
                                </li>
                            <?php else: ?>
                                <?php
                                $is_active = $page == $p;
                                $item_class = $is_active ? "active" : "";
                                $link_style = $is_active
                                    ? "background:#1a3a1a;border-color:#7ec87e;color:#7ec87e;font-weight:600"
                                    : "background:#161929;border-color:#2a2f4a;color:#a0a8c8";
                                ?>
                                <li class="page-item <?= $item_class ?>">
                                    <a class="page-link rounded" href="?page=<?= $p,
                                        $query_extra ?>"
                                      style="<?= $link_style ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <li class="page-item <?= $next_disabled ?>">
                            <a class="page-link rounded" href="?page=<?= $next_page,
                                $query_extra ?>"
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
