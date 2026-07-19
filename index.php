<?php include ("connect.php"); ?>
<?php include "header.php"; ?>
<head>
    <title>Landing page</title>
</head>
<body>
    <main class="container py-5 text-center">
        <h2 class="text mb-2">Ahoy there, tis the landing page</h2>
        <p class="text-secondary">Login or register to get yer hands on the plunder</p>

        <div class="d-flex justify-content-center gap-5 flex-wrap my-4">
            <a href="land_about.php" class="btn btn-outline-primary px-4">About</a>
        </div>
        <br>
        <div class="d-flex justify-content-center gap-3">
            <a href="land_register.php" class="btn btn-outline-danger btn-lg px-4">Register</a>
            <a href="land_login.php" class="btn btn-outline-success btn-lg px-4">Login</a>
        </div>
        <br>
        <div class="d-flex justify-content-center gap-5 flex-wrap my-4">
            <a href="deck_browse.php" class="btn btn-outline-warning px-4">Explore decks</a>
        </div>

    </main>
</body>
<?php include ("footer.php");
// ver_1.909._
?>
