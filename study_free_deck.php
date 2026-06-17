<?php
$conn = include "connect.php";

if (!isset($_SESSION["user_id"]) || !isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$deck_id = (int) $_GET["id"];

$auth = $conn->prepare("
    SELECT 1 FROM decks d
    LEFT JOIN deck_contributors dc ON d.id = dc.deck_id
    WHERE d.id = ? AND (d.user_id = ? OR (dc.user_id = ? AND dc.status = 'accepted'))
");
$auth->bind_param("iii", $deck_id, $user_id, $user_id);
$auth->execute();
if ($auth->get_result()->num_rows === 0) {
    die("Forbidden");
}

$stmt = $conn->prepare("SELECT question, answer FROM cards WHERE deck_id = ?");
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT title FROM decks WHERE id = ?");
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck_title = $stmt->get_result()->fetch_assoc()["title"];

// Process cards: Front replaces {{...}} with [...].
// Back replaces {{...}} with the actual string from the DB 'answer' column.
foreach ($cards as &$card) {
    $escaped_q = htmlspecialchars($card["question"]);
    $escaped_a = htmlspecialchars($card["answer"]);

    // Front: Hide the bracketed content behind the gold [...] placeholder
    $card["formatted_q"] = preg_replace(
        "/\{\{(.*?)\}\}+/",
        '<span style="color: #e8a14a; font-weight: bold;">[...]</span>',
        $escaped_q,
    );

    // Back: If it's a cloze card, swap the brackets out for the database answer field value
    if (preg_match("/\{\{(.*?)\}\}+/", $card["question"])) {
        $card["formatted_a"] = preg_replace(
            "/\{\{(.*?)\}\}+/",
            '<span style="color: #7ec87e; font-weight: bold; border-bottom: 2px dashed #7ec87e;">' .
                $escaped_a .
                "</span>",
            $escaped_q,
        );
    } else {
        // Standard card fallback: Just show the question on front and answer on back
        $card["formatted_a"] = nl2br($escaped_a);
    }
}
unset($card);

include "header.php";
?>

<style>
    body { background-color: #0d101f; color: #e8e8f0; }

    progress { width: 100%; height: 6px; border-radius: 99px; border: none; background: #161929; margin-bottom: 1rem; }
    progress::-webkit-progress-bar { background: #161929; border-radius: 99px; }
    progress::-webkit-progress-value { background: #7a9ee8; border-radius: 99px; }

    .study-card-scene { perspective: 1200px; cursor: pointer; height: 400px; margin-bottom: 1rem; }
    .card-inner { position: relative; width: 100%; height: 100%; transform-style: preserve-3d; transition: transform .25s ease; }
    .card-inner.flipped { transform: rotateY(180deg); }
    .card-face { position: absolute; inset: 0; backface-visibility: hidden; border-radius: 12px; background: #161929; border: 1px solid #2a2f4a; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }
    .card-back { transform: rotateY(180deg); }

    .card-tag { font-size: .7rem; letter-spacing: .08em; font-weight: 600; margin-bottom: .75rem; padding: 2px 10px; border-radius: 99px; }
    .tag-front { background: #1a1a2a; color: #7a9ee8; border: 1px solid #2a3a6a; }
    .tag-answer { background: #1a1a2a; color: #9a8ae8; border: 1px solid #3a2a6a; }
    .state-pill { font-size: .75rem; padding: 2px 10px; border-radius: 99px; font-weight: 500; background: #338d6225; color: #338d62; border: 1px solid #338d62; }

    .card-text { font-size: 1.4rem; line-height: 1.6; color: #e8e8f0; }
    .btn-nav { background: transparent; border: 1px solid #2a2f4a; color: #a0a8c8; padding: .6rem 1.5rem; border-radius: 8px; transition: background .15s, border-color .15s; }
    .btn-nav:hover { background: #161929; border-color: #4a4f7a; color: #e8e8f0; }
</style>

<div class="container py-4" style="max-width:900px">
    <br>
    <h4 class="mb-1" style="color:#e8e8f0">"<?= $deck_title ?>" - Free Mode</h4>
    <br>
    <progress id="prog" value="1" max="<?= count($cards) ?>"></progress>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="state-pill" id="counter">1 / <?= count($cards) ?></span>
    </div>

    <div class="study-card-scene" onclick="flip()">
        <div id="inner" class="card-inner">
            <div class="card-face">
                <span class="card-tag tag-front">FRONT</span>
                <div id="content" class="card-text"></div>
            </div>
            <div class="card-face card-back">
                <span class="card-tag tag-answer">ANSWER</span>
                <div id="answer-content" class="card-text"></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button onclick="change(-1)" class="btn-nav">&larr; Prev</button>
        <button onclick="change(1)" class="btn-nav">Next &rarr;</button>
    </div>
</div>

<script>
    const c = <?= json_encode($cards) ?>;
    let i = 0;
    const el = document.getElementById('content'), ansEl = document.getElementById('answer-content');
    const inner = document.getElementById('inner'), p = document.getElementById('prog'), cnt = document.getElementById('counter');

    function r() {
        el.innerHTML = c[i].formatted_q;
        ansEl.innerHTML = c[i].formatted_a;
        p.value = i + 1;
        cnt.textContent = (i + 1) + " / " + c.length;
    }

    function flip() { inner.classList.toggle('flipped'); }

    function change(dir) {
        inner.style.transition = 'none'; // Instant switch
        inner.classList.remove('flipped');
        i = (i + dir + c.length) % c.length;
        r();
        setTimeout(() => { inner.style.transition = ''; }, 50); // Re-engage animation rules
    }

    r(); // Initialize UI state

    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') change(1);
        if (e.key === 'ArrowLeft') change(-1);
        if (e.key === ' ' || e.key === 'ArrowUp' || e.key === 'ArrowDown') { e.preventDefault(); flip(); }
    });
</script>
<?php include "footer.php"; ?>
