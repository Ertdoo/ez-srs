<?php $conn = include "connect.php"; ?>
<?php
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
    // This else handles the case where variables might be set but empty
    $message = "";
    $message_success = ""; // Clean up empty session vars if they exist so they don't persist
    if (isset($_SESSION["deck_create_error"])) {
        unset($_SESSION["deck_create_error"]);
    }
    if (isset($_SESSION["deck_create_success"])) {
        unset($_SESSION["deck_create_success"]);
    }
}
$deck_id = $_GET["id"] ?? 0;
$sql = "SELECT * FROM decks WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();
if (!$deck) {
    die(
        "<h1>Deck not found!</h1><a href='user_dashboard.php'>Back to safety</a>"
    );
}
if ($deck["user_id"] != $user_id) {
    die(
        "<h1>Avast!</h1><p>You don't have permission to edit someone else's deck.</p><a href='user_dashboard.php'>Return to Dashboard</a>"
    );
}
// user is confirm'd the cap'n
$deck1 = $deck; // trash dumping
// Get deck ID from URL
$deck_id = $_GET["id"] ?? 0;
$sql =
    "SELECT title, description, is_public, created_at, user_id FROM decks WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$result = $stmt->get_result();
$deck1 = $result->fetch_assoc(); // Fetch deck info
$sql = "SELECT * FROM decks WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc(); // Check if deck exists
if (!$deck) {
    echo "Deck not found!";
    //change this error message
    exit();
} // Check if user owns this deck
$user_id = $_SESSION["user_id"] ?? 0;
$can_edit = $deck["user_id"] == $user_id; // Count cards in this deck
$count_sql = "SELECT COUNT(*) as card_count FROM cards WHERE deck_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $deck_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$card_count = $count_result->fetch_assoc()["card_count"] ?? 0;
?>

<?php include "header.php"; ?>
<head>
    <title>Deck details</title>
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

<body>
    <main class="container mt-5">
    <div class='row'>
        <div class='col-sm-5'>
        <h2>Deck details: <?php echo $deck["title"]; ?></h2>
        <p>Required *</p>
            <form action="deck_edit_action.php" method="POST" class="w-100" style="max-width: 400px;margin-bottom: 20px;">
                <?php if ($message): ?>
                <br>
                <?php $alert_class = "alert-danger"; ?>
                <div class="alert <?php echo $alert_class; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if ($message_success): ?>
                <br>
                <?php $alert_class = "alert-success"; ?>
                <div class="alert <?php echo $alert_class; ?>">
                    <?php echo htmlspecialchars($message_success); ?>
                </div>
                <?php endif; ?>
                <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">
                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label for="deck_title" class="form-label">Deck title: *</label>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" id="deck_title" name="deck_title" class="form-control" style="width: 300px;" maxlength="30" value="<?php echo $deck[
                            "title"
                        ]; ?>" required>
                    </div>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label for="deck_desc" class="form-label">Description:</label>
                    </div>
                    <div class="col-sm-8">
                        <textarea id="deck_desc" name="deck_desc" class="form-control" style="height: 100px; width: 300px;" maxlength="1000"><?php echo $deck1[
                            "description"
                        ]; ?></textarea>
                    </div>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-4">
                        <label class="form-label" for="make_public">Make deck public? *</label>
                    </div>
                    <div class="col-sm-8">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="make_public" name="make_public" <?php if (
                                $deck1["is_public"] === 1
                            ) {
                                echo "checked";
                            } else {
                                echo "";
                            } ?>>
                        </div>
                    </div>
                </div>
                <button type="submit" name="deck_detail_edit_btn" class="btn btn-outline-primary">Edit deck details</button>
            </form>
            <form action="deck_delete_DDETAILS.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure matey?');">
                <input type="hidden" name="deck_id" value="<?php echo intval(
                    $deck_id,
                ); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </div>
        <?php // Pagination setup (6 items per page)


        $limit = 10;
        $page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
        $total_pages = max(1, ceil($card_count / $limit));
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $offset = ($page - 1) * $limit; // Paginated Query
        $cards_sql =
            "SELECT * FROM cards WHERE deck_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $cards_stmt = $conn->prepare($cards_sql);
        $cards_stmt->bind_param("iii", $deck_id, $limit, $offset);
        $cards_stmt->execute();
        $cards_result = $cards_stmt->get_result();
        ?>

        <div class="col-sm-7">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Cards:</h3>
                <button
                type="button"
                class="btn btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#addCardModal">
                + Create New Card
                </button>
            </div>

            <table class="table table-striped" id="create_deck_list">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Answer</th>
                        <th>Type</th>
                        <!-- <th>Media URL</th> -->
                        <th>Difficulty</th>
                        <th>Tags</th>
                        <th></th> <!-- the header text -->
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cards_result->num_rows > 0) {
                        while ($card = $cards_result->fetch_assoc()) {
                            echo "<tr>";
                            // Question
                            echo "<td>" .
                                htmlspecialchars(
                                    substr($card["question"], 0, 30),
                                ) .
                                (strlen($card["question"]) > 30 ? "..." : "") .
                                "</td>";
                            // Answer
                            echo "<td>" .
                                htmlspecialchars(
                                    substr($card["answer"], 0, 30),
                                ) .
                                (strlen($card["answer"]) > 30 ? "..." : "") .
                                "</td>";
                            // Card Type
                            echo "<td>" .
                                htmlspecialchars($card["card_type"]) .
                                "</td>";
                            // Media URL
                            /* echo '<td>';
                            if (!empty($card['media_url'])) {
                                echo htmlspecialchars(substr($card['media_url'], 0, 20));
                                echo strlen($card['media_url']) > 20 ? '...' : '';
                            } else {
                                echo '—';
                            }
                            echo '</td>'; */ echo "<td>";
                            $difficulty_badge = match ($card["difficulty"]) {
                                "easy"
                                    => '<span class="badge bg-success">Easy</span>',
                                "medium"
                                    => '<span class="badge bg-warning">Medium</span>',
                                "hard"
                                    => '<span class="badge bg-danger">Hard</span>',
                                default
                                    => '<span class="badge bg-secondary">Unknown</span>',
                            };
                            echo $difficulty_badge;
                            echo "</td>";
                            // Tags
                            echo "<td>";
                            $tags_sql = "
                                SELECT t.name
                                FROM tags t
                                JOIN card_tags ct ON t.id = ct.tag_id
                                WHERE ct.card_id = ?
                            ";
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
                                    implode(
                                        '</span> <span class="badge bg-secondary">',
                                        array_map("htmlspecialchars", $tags),
                                    ) .
                                    "</span>";
                            } else {
                                echo "—";
                            }
                            echo "</td>";
                            echo "<td>";
                            echo '<a href="edit_card.php?id=' .
                                $card["id"] .
                                '"
                                class="btn btn-sm btn-outline-primary">
                                Edit
                                </a>';
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center text-muted">
                                Argh, no cards in this deck yet, matey!
                            </td></tr>';
                    } ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4" style="margin:0 auto">
                    <ul class="pagination pagination-sm" style="gap:4px">

                        <li class="page-item <?= $page <= 1
                            ? "disabled"
                            : "" ?>">
                            <a class="page-link rounded" href="?id=<?= $deck_id ?>&page=<?= $page -
    1 ?>"
                              style="background:#161929;border-color:#2a2f4a;color:#a0a8c8">
                                &#8592;
                            </a>
                        </li>

                        <?php // --- SLIDING WINDOW LOGIC ---


                        $range = 1; // Number of pages to show on each side of the current page
                        $display = [];
                        if ($total_pages <= 5 + $range * 2) {
                            // If total pages are small enough, just show them all
                            $display = range(1, $total_pages);
                        } else {
                            // Always include the first page
                            $display[] = 1; // Check if we need a left ellipsis
                            if ($page - $range > 2) {
                                $display[] = null;
                            } // Determine middle block boundaries
                            $start = max(2, $page - $range);
                            $end = min($total_pages - 1, $page + $range); // Adjust boundaries if we are too close to the beginning or end
                            if ($page <= $range + 2) {
                                $end = 2 + $range * 2;
                            } elseif ($page >= $total_pages - $range - 1) {
                                $start = $total_pages - 1 - $range * 2;
                            }
                            // Fill the middle pages
                            for ($i = $start; $i <= $end; $i++) {
                                $display[] = $i;
                            } // Check if we need a right ellipsis
                            if ($page + $range < $total_pages - 1) {
                                $display[] = null;
                            } // Always include the last page
                            $display[] = $total_pages;
                        }

                // --- END LOGIC ---
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
                                    <a class="page-link rounded" href="?id=<?= $deck_id ?>&page=<?= $p ?>"
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
                            <a class="page-link rounded" href="?id=<?= $deck_id ?>&page=<?= $page +
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
    </main>
</body>

<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCardModalLabel">Add Cards to Deck</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-3">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab"
                    data-bs-target="#singleTab">Single Card</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab"
                    data-bs-target="#batchTab">Batch Add</button>
          </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
          <!-- SINGLE CARD TAB -->
          <div class="tab-pane show active" id="singleTab">
            <form id="singleCardForm" action="add_card_action.php" method="POST">
              <input type="hidden" name="deck_id" value="<?= $deck_id ?>">

              <div class="mb-3">
                <label class="form-label">Question *</label>
                <textarea class="form-control" name="question" rows="3"
                          placeholder="Enter the question or front of the card" maxlength="1000" required></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label">Answer *</label>
                <textarea class="form-control" name="answer" rows="3"
                          placeholder="Enter the answer or back of the card" maxlength="1000" required></textarea>
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
                <input type="text" class="form-control" name="tags"  maxlength="1000"
                       placeholder="e.g., vocabulary, math, history">
                <small class="text-muted">Separate multiple tags with commas</small>
              </div>
            </form>
          </div>

          <!-- BATCH TAB -->
          <div class="tab-pane" id="batchTab">
            <form id="batchCardForm" action="add_card_batch.php" method="POST">
              <input type="hidden" name="deck_id" value="<?= $deck_id ?>">

              <div class="mb-3" id="drop-container">
                <label class="form-label">Drag & drop .psv files here or</label>
                <button type="button" class="btn btn-outline-light btn-sm" id="browse-btn">click to upload .psv</button>
                <input type="file" id="file-input" accept=".psv" hidden>
                <br>
                <textarea id='batch-cards' class="form-control" name="batch_cards" rows="10" maxlength="10000"
                          placeholder="Or you can type them out! e.g:

Capital of France? | Paris
2+2 | 4
Biggest planet in our solar system | Jupiter"></textarea>
                <small class="text-muted">
                  Upload yer .psv files here, max length: 10,000 characters<br>
                  Example: "Capital of France? | Paris"
                </small>
              </div>

              <script>
              const dropContainer = document.getElementById('drop-container');
              const fileInput = document.getElementById('file-input');
              const browseBtn = document.getElementById('browse-btn');
              const textArea = document.getElementById('batch-cards');

              // 1. Open file dialog on button click
              browseBtn.addEventListener('click', (e) => {
                  fileInput.click();
              });

              // 2. Prevent default browser behavior for the ENTIRE container
              ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(name => {
                  dropContainer.addEventListener(name, e => {
                      e.preventDefault();
                      e.stopPropagation();
                  });
              });

              // 3. Visual feedback: Highlight textarea when dragging over
              ['dragenter', 'dragover'].forEach(name => {
                  dropContainer.addEventListener(name, () => textArea.classList.add('border-primary'));
              });

              ['dragleave', 'drop'].forEach(name => {
                  dropContainer.addEventListener(name, () => textArea.classList.remove('border-primary'));
              });

              // 4. Handle the file data
              const handleFile = (file) => {
                  if (file) {
                      const reader = new FileReader();
                      reader.onload = (e) => {
                          // Logic to append or overwrite.
                          // Change to textArea.value += e.target.result; to keep existing text
                          textArea.value = e.target.result;
                      };
                      reader.readAsText(file);
                  }
              };

              // Listen for file selection via button
              fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));

              // Listen for drops on the container (including the textarea)
              dropContainer.addEventListener('drop', (e) => {
                  const dt = e.dataTransfer;
                  const file = dt.files[0];
                  handleFile(file);
              });
              </script>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Default Card Type</label>
                  <select class="form-select" name="default_card_type">
                    <option value="basic" selected>Basic (Q & A)</option>
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

              <div class="mb-3">
                <label class="form-label">Tags (optional)</label>
                <input type="text" class="form-control" name="default_tags" maxlength="1000"
                       placeholder="e.g., vocabulary, math, history">
                <small class="text-muted">Applied to all cards in this batch</small>
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


<script>
// Switch submit button based on active tab
document.addEventListener('DOMContentLoaded', function() {
  const singleTab = document.querySelector('[data-bs-target="#singleTab"]');
  const batchTab = document.querySelector('[data-bs-target="#batchTab"]');
  const submitSingle = document.getElementById('submitSingle');
  const submitBatch = document.getElementById('submitBatch');

  // Show/hide appropriate submit button
  function updateSubmitButton(activeTab) {
    if (activeTab === 'singleTab') {
      submitSingle.classList.remove('d-none');
      submitBatch.classList.add('d-none');
    } else {
      submitSingle.classList.add('d-none');
      submitBatch.classList.remove('d-none');
    }
  }

  // Initial state
  updateSubmitButton('singleTab');

  // Listen for tab changes
  singleTab.addEventListener('shown.bs.tab', function() {
    updateSubmitButton('singleTab');
  });

  batchTab.addEventListener('shown.bs.tab', function() {
    updateSubmitButton('batchTab');
  });

  // Cloze card helper
  const cardTypeSelect = document.querySelector('[name="card_type"]');
  const questionTextarea = document.querySelector('[name="question"]');

  cardTypeSelect.addEventListener('change', function() {
    if (this.value === 'cloze') {
      // Show cloze hint
      if (!questionTextarea.nextElementSibling?.classList.contains('cloze-hint')) {
        const hint = document.createElement('small');
        hint.className = 'text-muted cloze-hint';
        hint.textContent = 'Tip: Use {{cloze}} brackets for blanks. Example: "The {{capital}} of France is Paris."';
        questionTextarea.parentNode.appendChild(hint);
      }
    } else {
      // Remove cloze hint
      const hint = questionTextarea.nextElementSibling;
      if (hint?.classList.contains('cloze-hint')) {
        hint.remove();
      }
    }
  });
});
</script>

<?php include "footer.php"; ?>
