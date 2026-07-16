<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "connect.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
    $user_id = (int) $_SESSION["user_id"];
} else {
    die('<br><h1>Yer not logged in matey!</h1><br><a href="land_login.php">Go to login</a>');
}

// Double-check connection object health to prevent unexpected 500 errors
if (!isset($conn) || !$conn instanceof mysqli) {
    die("<h1>Database Connection Error</h1><p>Please check your connect.php setup.</p>");
}

$message = "";
$message_success = "";
if (!empty($_SESSION["deck_create_error"])) {
    $message = $_SESSION["deck_create_error"];
    unset($_SESSION["deck_create_error"]);
} elseif (!empty($_SESSION["deck_create_success"])) {
    $message_success = $_SESSION["deck_create_success"];
    unset($_SESSION["deck_create_success"]);
}

// 1. Fetch Deck and Check Permissions
$deck_id = (int) ($_GET["id"] ?? 0);
$stmt = $conn->prepare("SELECT * FROM decks WHERE id = ?");
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();

if (!$deck) {
    die("<h1>Deck not found!</h1><a href='decks.php'>Back to safety</a>");
}

$is_owner = ($deck["user_id"] == $user_id);
$is_editor = false;

if (!$is_owner) {
    $check_role = $conn->prepare("SELECT role, status FROM deck_contributors WHERE deck_id = ? AND user_id = ?");
    if ($check_role) {
        $check_role->bind_param("ii", $deck_id, $user_id);
        $check_role->execute();
        $res = $check_role->get_result();

        // FIX 2: Safely check if results exist before assigning properties
        if ($res && $res->num_rows > 0) {
            $contributor = $res->fetch_assoc();
            if ($contributor && ($contributor["status"] ?? '') === "accepted" && ($contributor["role"] ?? '') === "editor") {
                $is_editor = true;
            }
        }
    }
}

if (!$is_owner && !$is_editor) {
    die("<h1>Avast!</h1><p>You don't have permission to view or edit this deck.</p><a href='decks.php'>Return to Dashboard</a>");
}

$can_edit = $is_owner; // Only the owner can change deck title/description or edit cards directly

// 2. Count cards for pagination
$count_sql = "SELECT COUNT(*) as card_count FROM cards WHERE deck_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $deck_id);
$count_stmt->execute();
$card_count_res = $count_stmt->get_result()->fetch_assoc();
$card_count = $card_count_res["card_count"] ?? 0;

// 3. Pagination setup
$limit = 10;
$page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
$total_pages = max(1, ceil($card_count / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;

$cards_sql = "SELECT * FROM cards WHERE deck_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
$cards_stmt = $conn->prepare($cards_sql);
$cards_stmt->bind_param("iii", $deck_id, $limit, $offset);
$cards_stmt->execute();
$cards_result = $cards_stmt->get_result();
?>

<?php include "header.php"; ?>
<head>
    <title>Deck Details</title>
    <style>
        #create_deck_list td {
            vertical-align: middle;
            height: 45px;
        }
        /* Allow text to wrap nicely in question/answer columns */
        #create_deck_list td:nth-child(1),
        #create_deck_list td:nth-child(2) {
            white-space: normal;
            word-wrap: break-word;
            max-width: 250px;
        }
    </style>
</head>

<body>
    <main class="container mt-5">
        <div class='row'>
            <!-- LEFT COLUMN: Deck Details Form -->
            <div class='col-lg-5 mb-4'>
                <h2>Deck Details: <?= htmlspecialchars($deck["title"]) ?></h2>
                <p class="text-muted">* Required</p>


                <form action="deck_edit_action.php" method="POST" class="w-100" style="max-width: 400px; margin-bottom: 20px;">
                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <?php if ($message_success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message_success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION["deck_edit_warning"])): ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($_SESSION["deck_edit_warning"]) ?>
                        </div>
                        <?php unset($_SESSION["deck_edit_warning"]); ?>
                    <?php endif; ?>

                    <input type="hidden" name="deck_id" value="<?= $deck_id ?>">

                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-4">
                            <label for="deck_title" class="form-label">Deck title: *</label>
                        </div>
                        <div class="col-sm-8">
                            <input type="text" id="deck_title" name="deck_title" class="form-control"
                                   maxlength="100" value="<?= htmlspecialchars($deck["title"]) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-4">
                            <label for="deck_desc" class="form-label">Description:</label>
                        </div>
                        <div class="col-sm-8">
                            <textarea id="deck_desc" name="deck_desc" class="form-control"
                                      style="height: 100px;" maxlength="1000"><?= htmlspecialchars($deck["description"]) ?></textarea>
                        </div>
                    </div>

                    <?php if ($can_edit): ?>
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-4">
                            <label for="deck_collab" class="form-label">Collaborators:</label>
                        </div>
                        <div class="col-sm-8">
                            <input type="text" id="deck_collab" name="deck_collab" class="form-control"
                                   maxlength="100" placeholder="Usernames separated by commas">
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-4">
                            <label class="form-label" for="make_public">Make deck public? *</label>
                        </div>
                        <div class="col-sm-8">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="make_public" name="make_public"
                                       <?= $deck["is_public"] == 1 ? "checked" : "" ?>>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($can_edit): ?>
                        <button type="submit" name="deck_detail_edit_btn" class="btn btn-outline-primary">Edit deck details</button>
                    <?php else: ?>
                        <div class="alert alert-info py-2">Ye be an editor, matey. Suggest tweaks t’ these cards below. (btw the text boxes will do nothing since u arent owner, i cannot be bothered fixing)</div>
                    <?php endif; ?>
                </form>



                <?php if ($can_edit): ?>
                <form action="deck_delete_DDETAILS.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure matey? This cannot be undone.');">
                    <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete Deck</button>
                </form>
                <?php endif; ?>

                <?php if ($can_edit):
                    // Check pending count for badge
                    $pending_check = $conn->prepare("SELECT COUNT(*) as count FROM card_proposals WHERE deck_id = ? AND status = 'pending'");
                    $pending_check->bind_param("i", $deck_id);
                    $pending_check->execute();
                    $pending_count = $pending_check->get_result()->fetch_assoc()['count'];
                ?>
                    <div class="mt-3 pt-3 border-top">
                        <a href="deck_review_proposals.php?id=<?= $deck_id ?>" class="btn btn-warning w-100 text-start d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-inbox-fill me-2"></i> Review Pending Proposals</span>
                            <?php if ($pending_count > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $pending_count ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>



            <!-- RIGHT COLUMN: Cards List -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Cards (<?= $card_count ?>)</h3>

                    <?php if ($can_edit): ?>
                        <!-- Owner sees the direct create button -->
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCardModal">
                            + Create New Card
                        </button>
                    <?php elseif ($is_editor): ?>
                        <!-- Editor sees the suggest button -->
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#propose_add_modal">
                            + Suggest New Card
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="create_deck_list">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Type</th>
                                <th>Difficulty</th>
                                <th>Tags</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($cards_result->num_rows > 0): ?>
                                <?php while ($card = $cards_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(mb_substr($card["question"], 0, 50)) . (mb_strlen($card["question"]) > 50 ? "..." : "") ?></td>
                                        <td><?= htmlspecialchars(mb_substr($card["answer"], 0, 50)) . (mb_strlen($card["answer"]) > 50 ? "..." : "") ?></td>
                                        <td><?= htmlspecialchars(ucfirst($card["card_type"])) ?></td>
                                        <td>
                                            <?php
                                            $difficulty_badge = match ($card["difficulty"]) {
                                                "easy" => '<span class="badge bg-success">Easy</span>',
                                                "medium" => '<span class="badge bg-warning text-dark">Medium</span>',
                                                "hard" => '<span class="badge bg-danger">Hard</span>',
                                                default => '<span class="badge bg-secondary">Unknown</span>',
                                            };
                                            echo $difficulty_badge;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $tags_sql = "SELECT t.name FROM tags t JOIN card_tags ct ON t.id = ct.tag_id WHERE ct.card_id = ?";
                                            $tags_stmt = $conn->prepare($tags_sql);
                                            $tags_stmt->bind_param("i", $card["id"]);
                                            $tags_stmt->execute();
                                            $tags_result = $tags_stmt->get_result();
                                            $tags = [];
                                            while ($tag = $tags_result->fetch_assoc()) {
                                                $tags[] = $tag["name"];
                                            }
                                            if (!empty($tags)) {
                                                echo '<span class="badge bg-secondary">' .
                                                     implode('</span> <span class="badge bg-secondary">', array_map("htmlspecialchars", $tags)) .
                                                     "</span>";
                                            } else {
                                                echo '<span class="text-muted">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($can_edit): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary owner-edit-btn"
                                                    data-card-id="<?= $card["id"] ?>"
                                                    data-question="<?= htmlspecialchars($card["question"], ENT_QUOTES) ?>"
                                                    data-answer="<?= htmlspecialchars($card["answer"], ENT_QUOTES) ?>"
                                                    data-type="<?= htmlspecialchars($card["card_type"]) ?>"
                                                    data-difficulty="<?= htmlspecialchars($card["difficulty"]) ?>"
                                                    data-bs-toggle="modal" data-bs-target="#edit_card_modal">
                                                Edit
                                            </button>
                                            <?php endif; ?>

                                            <?php if ($is_editor): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info suggest-edit-btn"
                                                        data-card-id="<?= $card["id"] ?>"
                                                        data-question="<?= htmlspecialchars($card["question"], ENT_QUOTES) ?>"
                                                        data-answer="<?= htmlspecialchars($card["answer"], ENT_QUOTES) ?>"
                                                        data-type="<?= htmlspecialchars($card["card_type"]) ?>"
                                                        data-difficulty="<?= htmlspecialchars($card["difficulty"]) ?>"
                                                        data-bs-toggle="modal" data-bs-target="#propose_edit_modal">
                                                    Suggest Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger suggest-delete-btn"
                                                        data-card-id="<?= $card["id"] ?>"
                                                        data-question="<?= htmlspecialchars($card["question"], ENT_QUOTES) ?>"
                                                        data-bs-toggle="modal" data-bs-target="#propose_delete_modal">
                                                    Suggest Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Argh, no cards in this deck yet, matey!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <ul class="pagination pagination-sm" style="gap:4px">
                            <li class="page-item <?= $page <= 1 ? "disabled" : "" ?>">
                                <a class="page-link rounded" href="?id=<?= $deck_id ?>&page=<?= $page - 1 ?>" style="background:#161929;border-color:#2a2f4a;color:#a0a8c8">&#8592;</a>
                            </li>
                            <?php
                            $range = 1;
                            $display = [];
                            if ($total_pages <= 5 + $range * 2) {
                                $display = range(1, $total_pages);
                            } else {
                                $display[] = 1;
                                if ($page - $range > 2) $display[] = null;
                                $start = max(2, $page - $range);
                                $end = min($total_pages - 1, $page + $range);
                                if ($page <= $range + 2) $end = 2 + $range * 2;
                                elseif ($page >= $total_pages - $range - 1) $start = $total_pages - 1 - $range * 2;
                                for ($i = $start; $i <= $end; $i++) $display[] = $i;
                                if ($page + $range < $total_pages - 1) $display[] = null;
                                $display[] = $total_pages;
                            }
                            foreach ($display as $p):
                                if ($p === null): ?>
                                    <li class="page-item disabled"><span class="page-link rounded" style="background:#161929;border-color:#2a2f4a;color:#4a4f6a">&hellip;</span></li>
                                <?php else: ?>
                                <li class="page-item <?= $page == $p ? "active" : "" ?>">
                                    <a class="page-link rounded" href="?id=<?= $deck_id ?>&page=<?= $p ?>" style="<?= $page == $p ? "background:#1a3a1a;border-color:#7ec87e;color:#7ec87e;font-weight:600" : "background:#161929;border-color:#2a2f4a;color:#a0a8c8" ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                                <?php endif;
                            endforeach; ?>
                            <li class="page-item <?= $page >= $total_pages ? "disabled" : "" ?>">
                                <a class="page-link rounded" href="?id=<?= $deck_id ?>&page=<?= $page + 1 ?>" style="background:#161929;border-color:#2a2f4a;color:#a0a8c8">&#8594;</a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- ADD CARD MODAL (Owner Only) -->
    <div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addCardModalLabel">Add Cards to Deck</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#singleTab">Single Card</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#batchTab">Batch Add</button></li>
            </ul>
            <div class="tab-content">
              <div class="tab-pane show active" id="singleTab">
                <form id="singleCardForm" action="add_card_action.php" method="POST">
                  <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                  <div class="mb-3">
                    <label class="form-label">Question *</label>
                    <textarea class="form-control" name="question" rows="3" maxlength="1000" required></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Answer *</label>
                    <textarea class="form-control" name="answer" rows="3" maxlength="1000" required></textarea>
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Card Type</label>
                      <select class="form-select" name="card_type">
                        <option value="basic" selected>Basic (Q & A)</option>
                        <option value="cloze">Cloze (Fill-in-blank)</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Difficulty</label>
                      <select class="form-select" name="difficulty">
                        <option value="medium" selected>Medium</option>
                        <option value="easy">Easy</option>
                        <option value="hard">Hard</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Tags (optional)</label>
                    <input type="text" class="form-control" name="tags" maxlength="1000" placeholder="e.g., vocabulary, math">
                  </div>
                </form>
              </div>
              <div class="tab-pane" id="batchTab">
                <form id="batchCardForm" action="add_card_batch.php" method="POST">
                  <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                  <div class="mb-3" id="drop-container">
                    <label class="form-label">Drag & drop .psv files here or</label>
                    <button type="button" class="btn btn-outline-light btn-sm" id="browse-btn">click to upload .psv</button>
                    <input type="file" id="file-input" accept=".psv" hidden>
                    <br>
                    <textarea id="batch-cards" class="form-control" name="batch_cards" rows="10" maxlength="10000" placeholder="Capital of France? | Paris&#10;2+2 | 4"></textarea>
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Default Card Type</label>
                      <select class="form-select" name="default_card_type"><option value="basic" selected>Basic</option></select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Default Difficulty</label>
                      <select class="form-select" name="default_difficulty"><option value="medium" selected>Medium</option><option value="easy">Easy</option><option value="hard">Hard</option></select>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="singleCardForm" class="btn btn-primary" id="submitSingle">Add Card</button>
            <button type="submit" form="batchCardForm" class="btn btn-primary d-none" id="submitBatch">Add All Cards</button>
          </div>
        </div>
      </div>
    </div>


    <!-- EDIT CARD MODAL (Owner Only) -->
    <div class="modal fade" id="edit_card_modal" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form action="card_edit_action.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="editCardModalLabel">Edit Card</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
              <input type="hidden" name="card_id" id="edit_modal_card_id">

              <div class="mb-3">
                <label class="form-label">Question *</label>
                <textarea class="form-control" name="question" id="edit_modal_question" rows="3" maxlength="1000" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Answer *</label>
                <textarea class="form-control" name="answer" id="edit_modal_answer" rows="3" maxlength="1000" required></textarea>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Card Type</label>
                  <select class="form-select" name="card_type" id="edit_modal_card_type">
                    <option value="basic">Basic</option>
                    <option value="cloze">Cloze</option>
                    <option value="image">Image</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Difficulty</label>
                  <select class="form-select" name="difficulty" id="edit_modal_difficulty">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PROPOSE EDIT MODAL (Editor Only) -->
    <div class="modal fade" id="propose_edit_modal" tabindex="-1" aria-labelledby="proposeEditModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form action="propose_card_change.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="proposeEditModalLabel">Suggest Card Edit</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
              <input type="hidden" name="card_id" id="modal_card_id">
              <input type="hidden" name="action_type" value="edit">

              <div class="mb-3">
                <label class="form-label">Proposed Question *</label>
                <textarea class="form-control" name="question" id="modal_question" rows="3" maxlength="1000" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Proposed Answer *</label>
                <textarea class="form-control" name="answer" id="modal_answer" rows="3" maxlength="1000" required></textarea>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Card Type</label>
                  <select class="form-select" name="card_type" id="modal_card_type">
                    <option value="basic">Basic</option>
                    <option value="cloze">Cloze</option>
                    <option value="image">Image</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Difficulty</label>
                  <select class="form-select" name="difficulty" id="modal_difficulty">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Submit Proposal</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PROPOSE DELETE MODAL (Editor Only) -->
    <div class="modal fade" id="propose_delete_modal" tabindex="-1" aria-labelledby="proposeDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="propose_card_change.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="proposeDeleteModalLabel">Suggest Card Deletion</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
              <input type="hidden" name="card_id" id="modal_delete_card_id">
              <input type="hidden" name="action_type" value="delete">
              <p>Are you sure you want to suggest deleting this card?</p>
              <div class="alert alert-secondary">
                <strong>Current Question:</strong> <span id="modal_delete_question"></span>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Suggest Deletion</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PROPOSE ADD CARD MODAL (Editor Only) -->
    <div class="modal fade" id="propose_add_modal" tabindex="-1" aria-labelledby="proposeAddModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="proposeAddModalLabel">Suggest New Card(s)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#proposeSingleTab">Single Card</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#proposeBatchTab">Batch Add</button>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
              <!-- SINGLE CARD TAB -->
              <div class="tab-pane show active" id="proposeSingleTab">
                <form action="propose_card_change.php" method="POST">
                  <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                  <input type="hidden" name="action_type" value="add">

                  <div class="mb-3">
                    <label class="form-label">Proposed Question *</label>
                    <textarea class="form-control" name="question" rows="3" maxlength="1000" required></textarea>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Proposed Answer *</label>
                    <textarea class="form-control" name="answer" rows="3" maxlength="1000" required></textarea>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Card Type</label>
                      <select class="form-select" name="card_type">
                        <option value="basic" selected>Basic</option>
                        <option value="cloze">Cloze</option>
                        <option value="image">Image</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Difficulty</label>
                      <select class="form-select" name="difficulty">
                        <option value="medium" selected>Medium</option>
                        <option value="easy">Easy</option>
                        <option value="hard">Hard</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer px-0 pb-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Proposal</button>
                  </div>
                </form>
              </div>

              <!-- BATCH ADD TAB -->
              <div class="tab-pane" id="proposeBatchTab">
                <form action="propose_card_batch.php" method="POST">
                  <input type="hidden" name="deck_id" value="<?= $deck_id ?>">
                  <input type="hidden" name="action_type" value="add">

                  <div class="mb-3">
                    <label class="form-label">Batch Cards *</label>
                    <textarea class="form-control" name="batch_cards" rows="8" maxlength="5000" required
                              placeholder="Capital of France? | Paris&#10;2 + 2 | 4&#10;Largest planet | Jupiter"></textarea>
                    <small class="text-muted">
                      Format: Question | Answer (one per line). Max 5,000 characters.
                    </small>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Default Card Type</label>
                      <select class="form-select" name="default_card_type">
                        <option value="basic" selected>Basic</option>
                        <option value="cloze">Cloze</option>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Default Difficulty</label>
                      <select class="form-select" name="default_difficulty">
                        <option value="medium" selected>Medium</option>
                        <option value="easy">Easy</option>
                        <option value="hard">Hard</option>
                      </select>
                    </div>
                  </div>

                  <div class="modal-footer px-0 pb-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Batch Proposal</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching logic for Add Card Modal
        const singleTab = document.querySelector('[data-bs-target="#singleTab"]');
        const batchTab = document.querySelector('[data-bs-target="#batchTab"]');
        const submitSingle = document.getElementById('submitSingle');
        const submitBatch = document.getElementById('submitBatch');

        function updateSubmitButton(activeTab) {
            if (activeTab === 'singleTab') {
                submitSingle.classList.remove('d-none');
                submitBatch.classList.add('d-none');
            } else {
                submitSingle.classList.add('d-none');
                submitBatch.classList.remove('d-none');
            }
        }
        updateSubmitButton('singleTab');
        singleTab.addEventListener('shown.bs.tab', function() { updateSubmitButton('singleTab'); });
        batchTab.addEventListener('shown.bs.tab', function() { updateSubmitButton('batchTab'); });

        // Cloze card helper
        const cardTypeSelect = document.querySelector('[name="card_type"]');
        const questionTextarea = document.querySelector('[name="question"]');
        cardTypeSelect.addEventListener('change', function() {
            if (this.value === 'cloze') {
                if (!questionTextarea.nextElementSibling?.classList.contains('cloze-hint')) {
                    const hint = document.createElement('small');
                    hint.className = 'text-muted cloze-hint d-block mt-1';
                    hint.textContent = 'Tip: Use {{cloze}} brackets for blanks. Example: "The {{capital}} of France is Paris."';
                    questionTextarea.parentNode.appendChild(hint);
                }
            } else {
                const hint = questionTextarea.nextElementSibling;
                if (hint?.classList.contains('cloze-hint')) hint.remove();
            }
        });

        // Populate Propose Edit Modal
        const suggestEditButtons = document.querySelectorAll('.suggest-edit-btn');
        suggestEditButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modal_card_id').value = this.getAttribute('data-card-id');
                document.getElementById('modal_question').value = this.getAttribute('data-question');
                document.getElementById('modal_answer').value = this.getAttribute('data-answer');
                document.getElementById('modal_card_type').value = this.getAttribute('data-type');
                document.getElementById('modal_difficulty').value = this.getAttribute('data-difficulty');
            });
        });

        // Populate Propose Delete Modal
        const suggestDeleteButtons = document.querySelectorAll('.suggest-delete-btn');
        suggestDeleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modal_delete_card_id').value = this.getAttribute('data-card-id');
                document.getElementById('modal_delete_question').textContent = this.getAttribute('data-question');
            });
        });

        // Batch file upload logic
        const dropContainer = document.getElementById('drop-container');
        const fileInput = document.getElementById('file-input');
        const browseBtn = document.getElementById('browse-btn');
        const textArea = document.getElementById('batch-cards');

        if (dropContainer && fileInput && browseBtn && textArea) {
            browseBtn.addEventListener('click', () => fileInput.click());
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(name => {
                dropContainer.addEventListener(name, e => { e.preventDefault(); e.stopPropagation(); });
            });
            ['dragenter', 'dragover'].forEach(name => {
                dropContainer.addEventListener(name, () => textArea.classList.add('border-primary'));
            });
            ['dragleave', 'drop'].forEach(name => {
                dropContainer.addEventListener(name, () => textArea.classList.remove('border-primary'));
            });
            const handleFile = (file) => {
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => { textArea.value = e.target.result; };
                    reader.readAsText(file);
                }
            };
            fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));
            dropContainer.addEventListener('drop', (e) => handleFile(e.dataTransfer.files[0]));
        }
    });

    // Populate Owner Edit Modal
    const ownerEditButtons = document.querySelectorAll('.owner-edit-btn');
    ownerEditButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_modal_card_id').value = this.getAttribute('data-card-id');
            document.getElementById('edit_modal_question').value = this.getAttribute('data-question');
            document.getElementById('edit_modal_answer').value = this.getAttribute('data-answer');
            document.getElementById('edit_modal_card_type').value = this.getAttribute('data-type');
            document.getElementById('edit_modal_difficulty').value = this.getAttribute('data-difficulty');
        });
    });
    </script>


<?php include "footer.php"; ?>
