<?php
/*
 * card_delete_action.php
 * to delete a card after the user presses the button and all validation
 * by emerson
 */

$conn = include "connect.php";

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
} else {
    header("Location: login.php");
    exit();
}

if (isset($_POST["card_id"])) {
    $card_id = intval($_POST["card_id"]);
    $deck_id = intval($_POST["deck_id"]);

    $stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
    $stmt->bind_param("i", $card_id);
    $stmt->execute();

    header("Location: 'deck_details.php?deck_id=' . $deck_id");
    exit();
}

?>
