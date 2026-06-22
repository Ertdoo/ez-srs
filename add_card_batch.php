<?php $conn = include "connect.php"; ?>
<?php // action template


if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
    $user_id = $_SESSION["user_id"];
} else {
    header("Location: login_invalid.php");
    exit();
}
$deck_id = $_POST["deck_id"]; //req
// if user owns this deck before adding cards
$stmt = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();
function processTags($conn, $card_id, $tags_string, $user_id)
{
    $tags = explode(",", $tags_string); // makes array from csv
    $tags = array_filter($tags);
    foreach ($tags as $tag_name) {
        if (empty($tag_name)) {
            continue;
        } // Check if tag exists for this user
        $stmt = $conn->prepare(
            "SELECT id FROM tags WHERE name = ? AND user_id = ?",
        );
        $stmt->bind_param("si", $tag_name, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $tag = $result->fetch_assoc();
            $tag_id = $tag["id"];
        } else {
            // Create new tag
            $stmt = $conn->prepare(
                "INSERT INTO tags (name, user_id) VALUES (?, ?)",
            );
            $stmt->bind_param("si", $tag_name, $user_id);
            $stmt->execute();
            $tag_id = $conn->insert_id;
        } // Link tag to card
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO card_tags (card_id, tag_id) VALUES (?, ?)",
        );
        $stmt->bind_param("ii", $card_id, $tag_id);
        $stmt->execute();
    }
}
if (!$deck || $deck["user_id"] != $_SESSION["user_id"]) {
    $_SESSION["card_insert_msg"] =
        "You don't have permission to add cards to this deck";
    header("Location: deck_details.php?id=$deck_id");
    exit();
}
function addBatchCardsF(
    $conn,
    $deck_id,
    $front,
    $back,
    $form_cardtype,
    $form_difficulty,
    $form_tags,
) {
    $stmt_insert = $conn->prepare(
        "INSERT INTO cards (deck_id, question, answer, card_type, difficulty) VALUES (?,?,?,?,?)",
    );
    $stmt_insert->bind_param(
        "issss",
        $deck_id,
        $front,
        $back,
        $form_cardtype,
        $form_difficulty,
    );
    if ($stmt_insert->execute()) {
        $new_card_id = $conn->insert_id;
        // FUCKING NEW CARD ID!!!
        if (!empty($form_tags)) {
            processTags($conn, $new_card_id, $form_tags, $_SESSION["user_id"]);
        }
    }
}
$form_batch_cards = trim($_POST["batch_cards"] ?? "");
$form_cardtype = trim($_POST["default_card_type"] ?? "");
$form_difficulty = trim($_POST["default_difficulty"] ?? "");
$form_tags = trim($_POST["default_tags"] ?? "");
$lines = explode("\n", $form_batch_cards);
$lines = array_filter(array_map("trim", $lines));
if (mb_strlen($form_batch_cards) > 10000) {
    $_SESSION["error"] = "Yer treasure be too large! (max 10,000 characters).";
    header("Location: deck_details.php?id=$deck_id");
    exit();
}
foreach ($lines as $line) {
    $card_parts = explode("|", $line); // seperate front and back using pipe
    if (count($card_parts) === 2) {
        $front = trim($card_parts[0]);
        $back = trim($card_parts[1]); //database insert
        addBatchCardsF(
            $conn,
            $deck_id,
            $front,
            $back,
            $form_cardtype,
            $form_difficulty,
            $form_tags,
        ); // debug echos
        // echo "<strong>Added:</strong> Q: $front | A: $back <br>";
        $_SESSION["success"] = "Card added successfully!";
    } else {
        $_SESSION[
            "card_insert_msg"
        ] = "<strong>Skipped:</strong> Line missing a pipe separator: $line <br>";
    }
} // exits if there is no error or skips cards
header("Location: deck_details.php?id=$deck_id");
exit();
 ?>
