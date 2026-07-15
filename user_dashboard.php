<?php include ("connect.php"); ?>
<?php include ("header.php"); ?>
<head>
    <title>Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #0d47a1;
            --secondary: #00796b;
            --background: #0d101f;
            --card-bg: #1c1f26; /* kept for anywhere else in the app that still uses it */
            --text: #f0f0f0;
            --text-light: #b0b0b0;
            --accent: #c62828;
            --green: #7ec87e;
            --border-color: rgba(255, 255, 255, 0.14);
            --card-fill: rgba(255, 255, 255, 0.03);
            --heatmap-empty: rgba(255, 255, 255, 0.08);
        }
        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            align-items: start;
            margin-top: 20px;
        }
        /* ---------- LEFT COLUMN ---------- */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .action-buttons .button-class {
            display: block;
            position: relative;
            padding: 10px 20px;
            border: 2px solid currentColor;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .action-buttons .button-class:hover {
            background-color: currentColor;
            color: #fff;
        }
        .badge-alert {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent);
            color: #fff;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            line-height: 1;
        }
        /* Compact "cards due" panel, left column */
        .due-panel {
            background-color: var(--card-fill);
            backdrop-filter: blur(6px);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 14px 16px;
        }
        .due-panel h5 {
            margin: 0 0 10px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-light);
        }
        .due-list {
            max-height: 260px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .due-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 2px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.85rem;
        }
        .due-row:last-child {
            border-bottom: none;
        }
        .due-row .deck-name {
            color: var(--text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 8px;
        }
        .due-row .deck-count {
            font-weight: 700;
            color: var(--green);
            flex-shrink: 0;
            min-width: 22px;
            text-align: right;
        }
        .due-empty {
            color: var(--text-light);
            font-style: italic;
            font-size: 0.85rem;
        }
        /* thin scrollbar for the due list */
        .due-list::-webkit-scrollbar { width: 5px; }
        .due-list::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }

        /* ---------- RIGHT COLUMN ---------- */
        .analytics-column {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .analytics-card {
            background-color: var(--card-fill);
            backdrop-filter: blur(6px);
            color: var(--text);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
        }
        .analytics-card h4 {
            margin-bottom: 14px;
            font-size: 1rem;
            color: var(--text);
        }
        .analytics-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .analytics-card-header h4 {
            margin-bottom: 0;
        }
        .deck-select {
            background-color: var(--card-fill);
            color: var(--text);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.8rem;
            max-width: 150px;
        }
        .deck-select:focus {
            outline: 1px solid var(--secondary);
        }
        /* Streak + heatmap */
        .streak-label {
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--green);
        }
        .heatmap-grid {
            display: grid;
            grid-template-rows: repeat(7, 12px);
            grid-auto-flow: column;
            grid-auto-columns: 12px;
            gap: 3px;
            overflow-x: auto;
            padding-bottom: 6px;
        }
        .heatmap-cell {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            background-color: var(--heatmap-empty);
        }
        .heatmap-cell:not([data-level]) {
            visibility: hidden;
        }
        .heatmap-cell[data-level="1"] { background-color: #196127; }
        .heatmap-cell[data-level="2"] { background-color: var(--secondary); }
        .heatmap-cell[data-level="3"] { background-color: #7bc96f; }
        .heatmap-cell[data-level="4"] { background-color: #c6e6c1; }
        /* Pie chart */
        .pie-wrapper {
            max-width: 260px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <main class="container mt-5">
        <h2>Dashboard</h2>
        <p>Here yer plunder, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <?php
        $user_id = $_SESSION['user_id'];
        // --- Proposals badge count ---
        $proposal_count = 0;
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt
             FROM card_proposals cp
             JOIN decks d ON cp.deck_id = d.id
             WHERE d.user_id = ? AND cp.status = 'pending'"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $proposal_count = (int) $row['cnt'];
        }
        $stmt->close();
        // --- Heatmap data: reviews per day for the last year ---
        $heatmap_data = []; // 'Y-m-d' => count
        $stmt = $conn->prepare(
            "SELECT DATE(reviewed_at) AS day, COUNT(*) AS reviews
             FROM study_sessions
             WHERE user_id = ? AND reviewed_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
             GROUP BY DATE(reviewed_at)"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $heatmap_data[$row['day']] = (int) $row['reviews'];
        }
        $stmt->close();

        // Build a 53-week x 7-day grid, aligned to Sundays, ending today
        $today = new DateTime('today');
        $year = $today->format('Y');

        $gridStart = new DateTime("$year-01-01");
        $gridStart->modify('-' . $gridStart->format('w') . ' days'); // snap back to Sunday

        $gridEnd = new DateTime("$year-12-31");

        $weeks = [];
        $cursor = clone $gridStart;
        while ($cursor <= $gridEnd) {
            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $dateStr = $cursor->format('Y-m-d');
                $count = $heatmap_data[$dateStr] ?? 0;
                $days[] = [
                    'date' => $dateStr,
                    'count' => $count,
                    'future' => $cursor > $today,
                    'outside_year' => $cursor->format('Y') != $year, // padding days from snapping to Sunday
                ];
                $cursor->modify('+1 day');
            }
            $weeks[] = $days;
        }
        function heatmap_level($count) {
            if ($count <= 0) return 0;
            if ($count <= 5) return 1;
            if ($count <= 15) return 2;
            if ($count <= 25) return 3;
            return 4;
        }
        // Streak: walk backward from today counting consecutive studied days
        $streak = 0;
        $cursor = clone $today;
        $todayStr = $today->format('Y-m-d');

        if (($heatmap_data[$todayStr] ?? 0) == 0) {
            // haven't studied today yet — start counting from yesterday instead
            $cursor->modify('-1 day');
        }

        while (true) {
            $dateStr = $cursor->format('Y-m-d');
            if (($heatmap_data[$dateStr] ?? 0) > 0) {
                $streak++;
                $cursor->modify('-1 day');
            } else {
                break;
            }
        }
        // --- Cards due, grouped by deck (includes never-studied cards as due) ---
        $due_by_deck = [];
        $stmt = $conn->prepare(
            "SELECT d.title,
                    SUM(CASE WHEN ss.card_id IS NULL OR ss.next_due_date <= CURDATE() THEN 1 ELSE 0 END) AS due_count
             FROM decks d
             JOIN cards c ON c.deck_id = d.id
             LEFT JOIN (
                 SELECT card_id, MAX(id) AS max_id FROM study_sessions WHERE user_id = ? GROUP BY card_id
             ) latest ON latest.card_id = c.id
             LEFT JOIN study_sessions ss ON ss.id = latest.max_id
             WHERE d.user_id = ?
             GROUP BY d.id, d.title
             HAVING due_count > 0
             ORDER BY due_count DESC"
        );
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $due_by_deck[] = $row;
        }
        $stmt->close();
        // --- Card counts by state (New / Learning / Young / Mature) — overall ---
        $card_states = ['New' => 0, 'Learning' => 0, 'Young' => 0, 'Mature' => 0];
        $stmt = $conn->prepare(
            "SELECT
                CASE
                    WHEN ss.card_id IS NULL THEN 'New'
                    WHEN ss.interval_days < 1 THEN 'Learning'
                    WHEN ss.interval_days < 21 THEN 'Young'
                    ELSE 'Mature'
                END AS state,
                COUNT(*) AS cnt
             FROM cards c
             JOIN decks d ON c.deck_id = d.id
             LEFT JOIN (
                 SELECT card_id, MAX(id) AS max_id FROM study_sessions WHERE user_id = ? GROUP BY card_id
             ) latest ON latest.card_id = c.id
             LEFT JOIN study_sessions ss ON ss.id = latest.max_id
             WHERE d.user_id = ?
             GROUP BY state"
        );
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $card_states[$row['state']] = (int) $row['cnt'];
        }
        $stmt->close();
        // --- Card counts by state, PER DECK (for the pie chart dropdown) ---
        $deck_states = []; // deck_id => ['title' => ..., 'states' => ['New'=>x, ...]]
        $stmt = $conn->prepare(
            "SELECT d.id AS deck_id, d.title,
                CASE
                    WHEN ss.card_id IS NULL THEN 'New'
                    WHEN ss.interval_days < 1 THEN 'Learning'
                    WHEN ss.interval_days < 21 THEN 'Young'
                    ELSE 'Mature'
                END AS state,
                COUNT(*) AS cnt
             FROM cards c
             JOIN decks d ON c.deck_id = d.id
             LEFT JOIN (
                 SELECT card_id, MAX(id) AS max_id FROM study_sessions WHERE user_id = ? GROUP BY card_id
             ) latest ON latest.card_id = c.id
             LEFT JOIN study_sessions ss ON ss.id = latest.max_id
             WHERE d.user_id = ?
             GROUP BY d.id, d.title, state"
        );
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $did = (int) $row['deck_id'];
            if (!isset($deck_states[$did])) {
                $deck_states[$did] = [
                    'title' => $row['title'],
                    'states' => ['New' => 0, 'Learning' => 0, 'Young' => 0, 'Mature' => 0],
                ];
            }
            $deck_states[$did]['states'][$row['state']] = (int) $row['cnt'];
        }
        $stmt->close();
        ?>
        <div class="dashboard-layout">
            <!-- LEFT: action buttons + compact cards-due panel -->
            <div class="left-column">
                <div class="action-buttons">
                    <a href="study.php" class="button-class" style="color: Tomato">Study!</a>
                    <a href="decks.php" class="button-class" style="color: Orange">Manage decks</a>
                    <a href="deck_create.php" class="button-class" style="color: MediumSeaGreen">Create new deck</a>
                    <a href="deck_review_proposals.php" class="button-class" style="color: DodgerBlue">
                        Proposals
                        <?php if ($proposal_count > 0): ?>
                            <span class="badge-alert"><?php echo $proposal_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="due-panel">
                    <h5>Cards due</h5>
                    <?php if (empty($due_by_deck)): ?>
                        <p class="due-empty">Nothing due — nice work!</p>
                    <?php else: ?>
                        <div class="due-list">
                            <?php foreach ($due_by_deck as $deck): ?>
                                <div class="due-row">
                                    <span class="deck-name" title="<?php echo htmlspecialchars($deck['title']); ?>">
                                        <?php echo htmlspecialchars($deck['title']); ?>
                                    </span>
                                    <span class="deck-count"><?php echo (int) $deck['due_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- RIGHT: analytics -->
            <div class="analytics-column">
                <div class="analytics-card">
                    <div class="streak-label"><?php echo $streak; ?> day streak!</div>
                    <div class="heatmap-grid">
                        <?php foreach ($weeks as $week): ?>
                            <?php foreach ($week as $day): ?>
                                <div class="heatmap-cell"
                                     data-level="<?php echo $day['future'] ? '' : heatmap_level($day['count']); ?>"
                                     title="<?php echo htmlspecialchars($day['date'] . ': ' . $day['count'] . ' reviews'); ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h4>Card mastery</h4>
                        <select id="deckSelect" class="deck-select">
                            <option value="all">All decks</option>
                            <?php foreach ($deck_states as $did => $d): ?>
                                <option value="<?php echo $did; ?>"><?php echo htmlspecialchars($d['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pie-wrapper">
                        <canvas id="cardStatesPie"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        // All decks' state breakdown, keyed by deck id, plus an "all" total —
        // built server-side so the dropdown can switch instantly with no extra requests.
        const deckStateData = {
            all: {
                labels: ['New', 'Learning', 'Young', 'Mature'],
                data: [
                    <?php echo $card_states['New']; ?>,
                    <?php echo $card_states['Learning']; ?>,
                    <?php echo $card_states['Young']; ?>,
                    <?php echo $card_states['Mature']; ?>
                ]
            <?php foreach ($deck_states as $did => $d): ?>
            },
            "<?php echo $did; ?>": {
                labels: ['New', 'Learning', 'Young', 'Mature'],
                data: [
                    <?php echo $d['states']['New']; ?>,
                    <?php echo $d['states']['Learning']; ?>,
                    <?php echo $d['states']['Young']; ?>,
                    <?php echo $d['states']['Mature']; ?>
                ]
            <?php endforeach; ?>
            }
        };

        const ctx = document.getElementById('cardStatesPie');
        const pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: deckStateData.all.labels,
                datasets: [{
                    data: deckStateData.all.data,
                    backgroundColor: ['#4e9de6', '#f5a623', '#7bc96f', '#196127']
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        document.getElementById('deckSelect').addEventListener('change', function () {
            const selected = deckStateData[this.value] || deckStateData.all;
            pieChart.data.datasets[0].data = selected.data;
            pieChart.update();
        });
    </script>
</body>
<?php include ("footer.php"); ?>
