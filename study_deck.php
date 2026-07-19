<?php
$conn = include "connect.php";
/** @var mysqli $conn */
require_once "classes/DeckManager.php";

if (!isset($_SESSION["username"])) {
    header("Location: login_invalid.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$deck_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($deck_id === 0) {
    header("Location: study.php?error=invalid_deck");
    exit();
}

$access_check = $conn->prepare("SELECT title FROM decks WHERE id = ?");
$access_check->bind_param("i", $deck_id);
$access_check->execute();
$deck = $access_check->get_result()->fetch_assoc();
if (!$deck) {
    header("Location: study.php?error=deck_not_found");
    exit();
}
$deck_title = htmlspecialchars($deck["title"]);

$deckManager = new DeckManager($conn);
$limit = $deckManager->getUserDailyLimit($user_id);
$queues = $deckManager->getStudyQueues($user_id, $deck_id, $limit);

// Separate queues
$learn_copy = $queues["learn"];
$due_copy = $queues["due"];
$new_copy = $queues["new"];

// Main queue: interleave 5 due : 1 new — learning cards are NOT in here
$main_cards = [];
while (!empty($due_copy) || !empty($new_copy)) {
    for ($i = 0; $i < 5 && !empty($due_copy); $i++) {
        $main_cards[] = array_shift($due_copy);
    }
    if (!empty($new_copy)) {
        $main_cards[] = array_shift($new_copy);
    }
}
$main_count = count($main_cards);

// Learning cards are appended to the DOM after main cards so JS can reference them
// by DOM index, but they are NOT stepped through by mainQueueIndex.
$all_dom_cards = array_merge($main_cards, $learn_copy);
$total_cards = count($all_dom_cards); // used for the "no cards at all" guard

// Build the JS seed for learningQueue so cards with past dueAt get immediate priority
// and cards with future dueAt wait naturally inside the main-queue flow.
$learn_js = [];
foreach ($learn_copy as $idx => $card) {
    $due_ms =
        !empty($card["learning_due_at"]) &&
        $card["learning_due_at"] !== "0000-00-00 00:00:00"
            ? strtotime($card["learning_due_at"]) * 1000
            : time() * 1000; // treat missing timestamp as due right now
    $learn_js[] = [
        "cardIndex" => $main_count + $idx,
        "step" => intval($card["interval_days"]),
        "dueAt" => $due_ms,
    ];
}

$new_count = count($queues["new"]);
$due_count = count($queues["due"]);
$learn_count = count($queues["learn"]);

include "header.php";
?>

<style>
  body { background-color: #0d101f; color: #e8e8f0; }
  .study-card-scene { perspective: 1200px; cursor: pointer; height: 400px; margin-bottom: 1rem; }
  .card-inner { position: relative; width: 100%; height: 100%; transform-style: preserve-3d; transition: transform .25s ease; }
  .study-card-scene.flipped .card-inner { transform: rotateY(180deg); }
  .card-face { position: absolute; inset: 0; backface-visibility: hidden; border-radius: 12px; background: #161929; border: 1px solid #2a2f4a; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }
  .card-back { transform: rotateY(180deg); }

  .card-face .card-tag { font-size: .7rem; letter-spacing: .08em; font-weight: 600; margin-bottom: .75rem; padding: 2px 10px; border-radius: 99px; }
  .tag-new     { background: #1a2a1a; color: #7ec87e; border: 1px solid #3a6a3a; }
  .tag-due     { background: #2a1f10; color: #e8a14a; border: 1px solid #5a3a10; }
  .tag-learning{ background: #1a1a2a; color: #7a9ee8; border: 1px solid #2a3a6a; }
  .tag-answer  { background: #1a1a2a; color: #9a8ae8; border: 1px solid #3a2a6a; }

  .card-face .card-text { font-size: 1.4rem; line-height: 1.6; color: #e8e8f0; }
  .card-face .card-hint { font-size: .8rem; color: #4a4f6a; margin-top: 1rem; }

  .btn-show { background: transparent; border: 1px solid #2a2f4a; color: #a0a8c8; width: 100%; padding: .6rem; border-radius: 8px; transition: background .15s, border-color .15s; margin-bottom: 1rem; }
  .btn-show:hover { background: #161929; border-color: #4a4f7a; color: #e8e8f0; }

  .rating-row .btn-rate { width: 100%; padding: .6rem .4rem; border-radius: 8px; font-size: .85rem; font-weight: 600; border: 1px solid; background: transparent; display: flex; flex-direction: column; align-items: center; gap: 2px; transition: background .15s, transform .1s; }
  .rating-row .btn-rate:active { transform: scale(0.97); }
  .btn-rate small { font-weight: 400; font-size: .75rem; opacity: .8; }

  .btn-again { color: #e8614a; border-color: #5a2010; }
  .btn-again:hover { background: #2a1410; }
  .btn-hard  { color: #e8a14a; border-color: #5a3a10; }
  .btn-hard:hover  { background: #2a1f10; }
  .btn-good  { color: #7a9ee8; border-color: #1a2a5a; }
  .btn-good:hover  { background: #111828; }
  .btn-easy  { color: #7ec87e; border-color: #1a4a1a; }
  .btn-easy:hover  { background: #101e10; }

  .state-pill { font-size: .75rem; padding: 2px 10px; border-radius: 99px; font-weight: 500; }
  .complete-icon { font-size: 3rem; color: #7ec87e; }
</style>

<main class="container py-4" style="max-width:900px">

<?php if ($total_cards === 0): ?>
  <div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh">
    <div class="complete-icon mb-3">✓</div>
    <h4 class="mb-1">Aye, all aboard!</h4>
    <p style="color:#4a4f6a">No cards due for <strong style="color:#e8e8f0"><?= $deck_title ?></strong>.</p>
    <a href="study.php" class="btn-show mt-4 text-center text-decoration-none d-inline-block" style="width:auto;padding:.6rem 1.5rem">
      Sail back t'yer study page
    </a>
  </div>
<?php else: ?>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <br>
      <h4 class="mb-1" style="color:#e8e8f0"><?= $deck_title ?></h4>
      <br>
        <div class="d-flex gap-2">
          <span class="state-pill" style="background: #c6664922;color: #c66649;border:1px solid #c66649"><span id="count-new"><?= $new_count ?></span> new</span>
          <span class="state-pill" style="background: #e8a14a26;color: #e8a14a;border:1px solid #e8a14a"><span id="count-learn"><?= $learn_count ?></span> learning</span>
          <span class="state-pill" style="background: #338d6225;color: #338d62;border:1px solid #338d62"><span id="count-due"><?= $due_count ?></span> due</span>
        </div>
    </div>
    <a href="study.php" style="color:#4a4f6a;text-decoration:none;font-size:1.2rem">✕</a>
  </div>

  <div id="card-container">
  <?php foreach ($all_dom_cards as $i => $c):

      $queue = $c["queue"];
      $tag_class = match ($queue) {
          "new" => "tag-new",
          "due" => "tag-due",
          "learning" => "tag-learning",
          default => "tag-due",
      };
      $tag_label = match ($queue) {
          "new" => "NEW",
          "due" => "REVIEW",
          "learning" => "LEARNING",
          default => "REVIEW",
      };

      $escaped_question = htmlspecialchars($c["question"]);

      $question_display = preg_replace(
          "/\{\{(.*?)\}\}+/",
          '<span style="color: #e8a14a; font-weight: bold;">[...]</span>',
          $escaped_question,
      );

      if (preg_match("/\{\{(.*?)\}\}+/", $c["question"])) {
          $answer_display = preg_replace(
              "/\{\{(.*?)\}\}+/",
              '<span style="color: #7ec87e; font-weight: bold; border-bottom: 2px dashed #7ec87e;">$1</span>',
              $escaped_question,
          );
      } else {
          $answer_display = htmlspecialchars($c["answer"]);
      }
      ?>
    <div class="study-card d-none"
        data-index="<?= $i ?>"
        data-card-id="<?= (int) $c["id"] ?>"
        data-ease="<?= floatval($c["ease_factor"]) ?>"
        data-interval="<?= intval($c["interval_days"]) ?>"
        data-next-due="<?= htmlspecialchars($c["next_due_date"] ?? '') ?>"
        data-queue="<?= htmlspecialchars($queue) ?>">

    <div class="study-card-scene" id="scene-<?= $i ?>" onclick="flipCard()">
      <div class="card-inner">
        <div class="card-face">
          <span class="card-tag <?= $tag_class ?>"><?= $tag_label ?></span>
          <div class="card-text"><?= $question_display ?></div>
          <div class="card-hint">Tap or press <kbd style="background:#1e2235;border:1px solid #2a2f4a;color:#7a7f9a;border-radius:4px;padding:1px 5px;font-size:.75rem">Space</kbd></div>
        </div>
        <div class="card-face card-back">
          <span class="card-tag tag-answer">ANSWER</span>
          <div class="card-text"><?= $answer_display ?></div>
        </div>
      </div>
    </div>

    <button class="btn-show show-btn" onclick="flipCard()">Show answer</button>

    <div class="row g-2 rating-row d-none">
      <div class="col-3"><button class="btn-rate btn-again" onclick="rate('again')">Again <small></small></button></div>
      <div class="col-3"><button class="btn-rate btn-hard" onclick="rate('hard')">Hard <small></small></button></div>
      <div class="col-3"><button class="btn-rate btn-good" onclick="rate('good')">Good <small></small></button></div>
      <div class="col-3"><button class="btn-rate btn-easy" onclick="rate('easy')">Easy <small></small></button></div>
    </div>
  </div>
  <?php
  endforeach; ?>
  </div>

  <div id="complete-screen" class="d-none d-flex flex-column align-items-center justify-content-center" style="min-height:50vh">
    <div class="complete-icon mb-3">✓</div>
    <h4 class="mb-1">Session complete!</h4>
    <p style="color:#4a4f6a">You reviewed <strong style="color:#e8e8f0" id="reviewed-count">0</strong> cards.</p>
    <a href="study.php" class="btn-show mt-3 text-center text-decoration-none d-inline-block" style="width:auto;padding:.6rem 1.5rem">
      Back to study page
    </a>
  </div>
<?php endif; ?>
</main>

<script>
// ========== STATE ==========
// `total` = main-queue size (due + new interleaved). Learning cards live in
// learningQueue only and are never stepped through by mainQueueIndex.
const total = <?= $main_count ?>;
const LEARN_AHEAD_LIMIT_MS = 20 * 60 * 1000; // 20 minutes

let mainQueueIndex = 0;   // Progress through the interleaved due/new array
let activeCardIndex = -1; // DOM index of the card currently on screen
let reviewed = 0;
let flipped = false;
let isSaving = false;

// Counts for pills
let countNew   = <?= $new_count ?>;
let countLearn = <?= $learn_count ?>;
let countDue   = <?= $due_count ?>;

// Learning queue pre-seeded from backend. Overdue cards (dueAt <= Date.now())
// will fire immediately in nextCard() priority check; future ones wait naturally.
let learningQueue = <?= json_encode($learn_js) ?>;

// ========== UI HELPERS ==========
function updatePills() {
    document.getElementById('count-new').textContent = countNew;
    document.getElementById('count-learn').textContent = countLearn;
    document.getElementById('count-due').textContent = countDue;
}

function formatInterval(days) {
    if (days < 30) return days + 'd';
    if (days < 365) return (days / 30).toFixed(1) + 'mo';
    return (days / 365).toFixed(1) + 'yr';
}

function showScreen(name) {
    document.getElementById('card-container').classList.toggle('d-none', name !== 'cards');
    document.getElementById('complete-screen').classList.toggle('d-none', name !== 'complete');
}

function getCurrentEl() {
    if (activeCardIndex === -1) return null;
    return document.querySelector(`.study-card[data-index="${activeCardIndex}"]`);
}

function updateCardButtons(el) {
    const queue = el.dataset.queue;
    const ease = parseFloat(el.dataset.ease);
    const interval = parseInt(el.dataset.interval);
    const nextDueStr = el.dataset.nextDue;

    const btnA = el.querySelector('.btn-again small');
    const btnH = el.querySelector('.btn-hard small');
    const btnG = el.querySelector('.btn-good small');
    const btnE = el.querySelector('.btn-easy small');

    if (queue === 'new' || (queue === 'learning' && interval === 0)) {
        btnA.innerText = '1m'; btnH.innerText = '6m'; btnG.innerText = '10m'; btnE.innerText = '4d';
    } else if (queue === 'learning' && interval === 1) {
        btnA.innerText = '1m'; btnH.innerText = '10m'; btnG.innerText = '1d'; btnE.innerText = '4d';
    } else { // review (due)
        const ivl = Math.max(1, interval);
        btnA.innerText = '10m';

        // FIX: Calculate delayDays exactly like the backend
        let delayDays = 0;
        if (nextDueStr && nextDueStr !== '0000-00-00' && nextDueStr !== '') {
            const today = new Date(); today.setHours(0,0,0,0);
            const dueDate = new Date(nextDueStr); dueDate.setHours(0,0,0,0);
            const diffTime = today - dueDate;
            delayDays = Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
        }

        // FIX: Match backend's exact formulas including the +1 protection
        const hardIvl = Math.max(Math.round(ivl * 1.2), ivl + 1, 1);
        const goodIvl = Math.max(Math.round((ivl + Math.floor(delayDays / 2)) * ease), hardIvl + 1, 1);
        const easyIvl = Math.max(Math.round((ivl + delayDays) * ease * 1.3), goodIvl + 1, 1);

        btnH.innerText = formatInterval(hardIvl);
        btnG.innerText = formatInterval(goodIvl);
        btnE.innerText = formatInterval(easyIvl);
    }
}

// ========== CARD NAVIGATION ==========
function showCard(index) {
    const el = document.querySelector(`.study-card[data-index="${index}"]`);
    if (!el) return;

    // Reset flip animations
    const scene = el.querySelector('.study-card-scene');
    scene.style.transition = 'none';
    scene.classList.remove('flipped');
    scene.offsetHeight; // force reflow
    scene.style.transition = '';

    el.querySelector('.show-btn').classList.remove('d-none');
    el.querySelector('.rating-row').classList.add('d-none');
    el.classList.remove('d-none');
    flipped = false;
    showScreen('cards');
}

function showLearningCard(queueIndex) {
    const [entry] = learningQueue.splice(queueIndex, 1);
    activeCardIndex = entry.cardIndex;

    const el = document.querySelector(`.study-card[data-index="${activeCardIndex}"]`);
    el.dataset.queue = 'learning';
    el.dataset.interval = entry.step; // For learning cards, interval tracks the step

    updateCardButtons(el);
    showCard(activeCardIndex);
}

function nextCard() {
    const currentEl = getCurrentEl();
    if (currentEl) currentEl.classList.add('d-none');

    const now = Date.now();

    // 1. PRIORITY: Are there any learning cards whose delay has already passed?
    let dueLearningIndices = [];
    for (let i = 0; i < learningQueue.length; i++) {
        if (learningQueue[i].dueAt <= now) {
            dueLearningIndices.push(i);
        }
    }

    if (dueLearningIndices.length > 0) {
        // Show the most overdue learning card
        let oldestQueueIdx = dueLearningIndices[0];
        for (let i = 1; i < dueLearningIndices.length; i++) {
            let qIdx = dueLearningIndices[i];
            if (learningQueue[qIdx].dueAt < learningQueue[oldestQueueIdx].dueAt) {
                oldestQueueIdx = qIdx;
            }
        }
        showLearningCard(oldestQueueIdx);
        return;
    }

    // 2. MAIN QUEUE: Continue pulling from the original interleaved array
    if (mainQueueIndex < total) {
        activeCardIndex = mainQueueIndex;
        mainQueueIndex++;
        showCard(activeCardIndex);
        return;
    }

    // 3. LEARN AHEAD: The main queue is empty. Are there ANY learning cards left?
    if (learningQueue.length > 0) {
        // With max 10m steps and 20m limit, ALL remaining cards bypass their delay.
        // Just find the one due earliest and pull it forward.
        let earliestIdx = 0;
        for (let i = 1; i < learningQueue.length; i++) {
            if (learningQueue[i].dueAt < learningQueue[earliestIdx].dueAt) {
                earliestIdx = i;
            }
        }
        showLearningCard(earliestIdx);
        return;
    }

    // 4. COMPLETION: No due cards, no main queue cards, no learning cards waiting.
    document.getElementById('reviewed-count').textContent = reviewed;
    showScreen('complete');
}

// ========== FLIP & RATE ==========
function flipCard() {
    if (flipped || isSaving) return;
    flipped = true;
    const el = getCurrentEl();
    el.querySelector('.study-card-scene').classList.add('flipped');
    el.querySelector('.show-btn').classList.add('d-none');
    el.querySelector('.rating-row').classList.remove('d-none');
}

async function rate(outcome) {
    if (!flipped || isSaving) return;
    isSaving = true;

    const el = getCurrentEl();
    const cardId = parseInt(el.dataset.cardId);
    const queue = el.dataset.queue;
    const interval = parseInt(el.dataset.interval);

    // Send rating to server
    try {
        const res = await fetch('study_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `card_id=${cardId}&outcome=${outcome}&deck_id=<?= $deck_id ?>`
        });
        const data = await res.json();
        if (data.status !== 'ok') throw new Error("Server error");
        countNew = data.counts.new;
        countLearn = data.counts.in_progress;
        countDue = data.counts.review;
        updatePills();
    } catch (err) {
        alert("Failed to save rating. Check your connection.");
        isSaving = false;
        return;
    }

    reviewed++;

    // Local Matrix evaluation for the next loop
    let pushToLearning = false;
    let newStep = 0;
    let delayMs = 0;

    if (queue === 'new') {
        if (outcome === 'again') { pushToLearning = true; newStep = 0; delayMs = 1 * 60 * 1000; }
        // FIX: Hard keeps it at step 0, matching the backend's $newInterval = 0
        else if (outcome === 'hard') { pushToLearning = true; newStep = 0; delayMs = 6 * 60 * 1000; }
        else if (outcome === 'good') { pushToLearning = true; newStep = 1; delayMs = 10 * 60 * 1000; }
        // easy skips learning and graduates instantly
    } else if (queue === 'learning' && interval === 0) {
        if (outcome === 'again') { pushToLearning = true; newStep = 0; delayMs = 1 * 60 * 1000; }
        else if (outcome === 'hard') { pushToLearning = true; newStep = 1; delayMs = 6 * 60 * 1000; }
        else if (outcome === 'good') { pushToLearning = true; newStep = 1; delayMs = 10 * 60 * 1000; }
        // easy skips remaining steps and graduates
    } else if (queue === 'learning' && interval === 1) {
        if (outcome === 'again') { pushToLearning = true; newStep = 0; delayMs = 1 * 60 * 1000; }
        else if (outcome === 'hard') { pushToLearning = true; newStep = 1; delayMs = 10 * 60 * 1000; }
        // good or easy graduate
    } else if (queue === 'due') {
        if (outcome === 'again') { pushToLearning = true; newStep = 1; delayMs = 10 * 60 * 1000; }
        // hard, good, easy reschedule for ≥ 1 day
    }

    // Append to local JS queue if the card wasn't graduated
    if (pushToLearning) {
        learningQueue.push({
            cardIndex: activeCardIndex, // Note: We save the DOM index, not main queue progress
            step: newStep,
            dueAt: Date.now() + delayMs
        });
    }

    isSaving = false;
    nextCard();
}

// ========== KEYBOARD SHORTCUTS ==========
document.addEventListener('keydown', e => {
    if (e.code === 'Space') { e.preventDefault(); flipCard(); }
    if (e.code === 'Enter' || e.code === 'NumpadEnter') { e.preventDefault(); flipCard(); }

    if (e.code === 'Digit1' || e.code === 'Numpad1') rate('again');
    if (e.code === 'Digit2' || e.code === 'Numpad2') rate('hard');
    if (e.code === 'Digit3' || e.code === 'Numpad3') rate('good');
    if (e.code === 'Digit4' || e.code === 'Numpad4') rate('easy');
});

// ========== INIT ==========
// Set initial button mappings
document.querySelectorAll('.study-card').forEach(el => updateCardButtons(el));

// Start Session — kick off even if only learning cards are queued
if (total > 0 || learningQueue.length > 0) {
    nextCard();
} else {
    showScreen('complete');
}
</script>

</body>
