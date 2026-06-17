<?php include ("connect.php"); ?>
<?php 
/* 
add_card_action.php
*/
if (isset($_SESSION['username'])) {
    $username = ($_SESSION['username']);
} else {
    header('Location: login_invalid.php');
    exit;
}
// 
function processTags($conn, $card_id, $tags_string, $user_id) {
    $tags = explode(',', $tags_string); // makes array from csv
    $tags = array_filter($tags);
    foreach ($tags as $tag_name) {
        if (empty($tag_name)) continue;
        
        // Check if tag exists for this user
        $stmt = $conn->prepare("SELECT id FROM tags WHERE name = ? AND user_id = ?");
        $stmt->bind_param("si", $tag_name, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $tag = $result->fetch_assoc();
            $tag_id = $tag['id'];
        } else {
            // Create new tag
            $stmt = $conn->prepare("INSERT INTO tags (name, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $tag_name, $user_id);
            $stmt->execute();
            $tag_id = $conn->insert_id;
        }
        
        // Link tag to card
        $stmt = $conn->prepare("INSERT IGNORE INTO card_tags (card_id, tag_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $card_id, $tag_id);
        $stmt->execute();
    }

}

$deck_id = $_POST['deck_id']; //req

// if user owns this deck before adding cards
$stmt = $conn->prepare("SELECT user_id FROM decks WHERE id = ?");
$stmt->bind_param("i", $deck_id);
$stmt->execute();
$deck = $stmt->get_result()->fetch_assoc();

if (!$deck || $deck['user_id'] != $_SESSION['user_id']) {
    $_SESSION['card_insert_msg'] = "You don't have permission to add cards to this deck";
    header("Location: deck_details.php?id=$deck_id");
    exit;
}

$form_front = trim($_POST["question"] ?? ''); //req
$form_back = trim($_POST['answer'] ??''); //req
$form_cardtype = trim($_POST['card_type'] ??'');
$form_difficulty = trim($_POST['difficulty'] ??'');
$form_tags = trim($_POST['tags'] ??'');

if (strlen($form_front) > 1000) {
    $_SESSION['error'] = "Question too long (max 1000 characters)";
    header("Location: deck_details.php?id=$deck_id");
    exit;
}

if (strlen($form_back) > 1000) {
    $_SESSION['error'] = "Answer too long (max 2000 characters)";
    header("Location: deck_details.php?id=$deck_id");
    exit;
}

if ($form_front == '' || $form_front == '') {
    // make an actual error msg but for now ill kill it
    $_SESSION['card_insert_msg'] = "Not fully filled";
    header("Location: deck_details.php?id=$deck_id");
    exit;
}

$stmt_insert = $conn->prepare("INSERT INTO cards (deck_id, question, answer, card_type, difficulty) VALUES (?,?,?,?,?)");
$stmt_insert->bind_param("issss", $deck_id, $form_front, $form_back, $form_cardtype, $form_difficulty);

if ($stmt_insert->execute()) {
    $new_card_id = $conn->insert_id; // GET THE NEW CARD ID!
    
    if (!empty($form_tags)) {
        processTags($conn, $new_card_id, $form_tags, $_SESSION['user_id']);
    }
    
    $_SESSION['success'] = "Card added successfully!";
    header("Location: deck_details.php?id=$deck_id");
    exit;
}




?>