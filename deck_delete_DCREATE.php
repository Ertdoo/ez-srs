<?php
/*
 * deck_delete_DCREATE.php
 * to delete a deck after the user presses the button FROM DECK CREATE
 * by emerson
 */

$conn = include "connect.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
} else {
    header("Location: login.php");
    exit();
}

if (isset($_POST["deck_id"])) {
    $deck_id = intval($_POST["deck_id"]); // force integer type to prevent text injection

    $stmt = $conn->prepare("DELETE FROM decks WHERE id = ?");
    $stmt->bind_param("i", $deck_id);
    $stmt->execute();

    header("Location: deck_create.php");
    exit();
}

?>
