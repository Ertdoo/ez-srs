<?php include ("connect.php"); ?>
<?php include "header.php"; ?>
<head>
    <title>Landing page</title>
</head>
<body>
    <main class="container py-5 text-center">
        <h2 class="text mb-2">Ahoy there, tis the landing page</h2>
        <p class="text-secondary">Login or register to get yer hands on the plunder</p>

        <?php
        $card_count = 0;
        $deck_count = 0;
        $subject_count = 0;

        if ($result = $conn->query("SELECT COUNT(*) AS cnt FROM cards")) {
            $card_count = (int) $result->fetch_assoc()['cnt'];
        }
        if ($result = $conn->query("SELECT COUNT(*) AS cnt FROM decks")) {
            $deck_count = (int) $result->fetch_assoc()['cnt'];
        }
        ?>

        <div class="d-flex justify-content-center gap-5 flex-wrap my-4">
            <div>
                <span class="d-block fs-2 fw-bold"><?php echo number_format($card_count); ?></span>
                <span class="text-uppercase small text-secondary">Cards</span>
            </div>
            <div>
                <span class="d-block fs-2 fw-bold"><?php echo number_format($deck_count); ?></span>
                <span class="text-uppercase small text-secondary">Decks</span>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3">
            <a href="land_register.php" class="btn btn-primary btn-lg px-4">Register</a>
            <a href="land_login.php" class="btn btn-outline-primary btn-lg px-4">Login</a>
        </div>
    </main>
</body>
<?php include ("footer.php");
// ver_1.909._
?>
