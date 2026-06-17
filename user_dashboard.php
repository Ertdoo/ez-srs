<?php include ("connect.php"); ?>
<?php include ("header.php"); ?>

<head>
    <title>Dashboard</title>
</head>
<body>
    <main class="container mt-5">
        <h2>Dashboard</h2>
        <p>Here yer plunder, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <ul>
            <li><a href="study.php" class="button-class" style="color: Tomato">Study!</a></li>
            <li><a href="decks.php" class="button-class" style="color: Orange">Manage decks</a></li>
            <li><a href="deck_create.php" class="button-class" style="color: MediumSeaGreen">Create new deck</a></li>
        </ul>
    </main>
</body>

<?php include ("footer.php"); ?>