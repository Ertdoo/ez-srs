<?php
$conn = include "connect.php";
/* deck_create_action.php
 * - Strings (text): Used for variables like $form_deck_title, $form_deck_description,
 * and $form_collaborators. Text data types are necessary to accommodate alphanumeric
 * user input of arbitrary length while allowing string operations like trim() and explode().
 * - Integers (numeric): Used for $user_id, $new_deck_id, and $collab_user_id. Type casting
 * to (int) is explicitly enforced to match the relational database schema primary keys,
 * optimizing lookup performance and ensuring mathematical validity.
 * - Integers as Booleans: The $form_public variable acts as a boolean indicator (0 or 1).
 * This ensures compact bitwise storage efficiency within the database while allowing clean
 * conditional evaluation.

 * - Superglobals ($_SESSION, $_POST): Utilized because they provide globally accessible
 * key-value associative arrays. $_SESSION safely preserves user state and flash messages
 * across HTTP requests, while $_POST safely encapsulates incoming client-side form data.
 * - Arrays ($collab_usernames): Used to temporarily store split collaborator strings.
 * An indexed array allows the script to leverage high-performance array traversal methods
 * like array_map() and foreach loops to iterate dynamically through variable amounts of input data.

 * - Relational Database MySQL ($conn): Chosen as the persistent data source to ensure ACID
 * compliance, referential integrity, and efficient indexing across interconnected tables
 * ('decks', 'users', 'deck_contributors').
 * - Prepared Statements ($conn->prepare): Employed for all SQL queries. Separating SQL code
 * from data parameters strictly eliminates SQL Injection vulnerabilities, ensuring secure
 * data transactions.
 * - Input Validation & Sanitization: trim() is utilized to remove accidental whitespace,
 * and empty() checks prevent null pointer exceptions or empty database rows, meeting robustness criteria.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// get the user
if (isset($_SESSION["user_id"])) {
    $user_id = (int) $_SESSION["user_id"];
} else {
    header("Location: login_invalid.php");
    exit();
}

// ini vars
$_SESSION["deck_create_error"] = "";
$_SESSION["deck_create_success"] = "";
$form_deck_title = "";
$form_deck_description = "";
$form_public = 0;
$form_collaborators = "";

// get values from form
$form_deck_title = trim($_POST["deck_title"] ?? "");
$form_deck_description = trim($_POST["deck_desc"] ?? "");
$form_collaborators = trim($_POST["deck_collab"] ?? "");

if (isset($_POST["make_public"])) {
    $form_public = 1;
}

if (empty($form_deck_title)) {
    $_SESSION["deck_create_error"] = "Nothin' can be found in the title laddie!";
    header("Location: deck_create.php");
    exit();
}

// 1. Create the deck first
$stmt_deck_create = $conn->prepare(
    "INSERT INTO decks (user_id, title, description, is_public) VALUES (?, ?, ?, ?)"
);
$stmt_deck_create->bind_param("issi", $user_id, $form_deck_title, $form_deck_description, $form_public);

if (!$stmt_deck_create->execute()) {
    $_SESSION["deck_create_error"] = "Deck could not be installed to the ship...";
    error_log("db exe fail: " . $stmt_deck_create->error);
    $stmt_deck_create->close();
    header("Location: deck_create.php");
    exit();
}

// Get the ID of the newly created deck
$new_deck_id = $conn->insert_id;
$stmt_deck_create->close();

// 2. Process Collaborators
if (!empty($form_collaborators)) {
    // Split by comma and clean up whitespace
    $collab_usernames = array_map('trim', explode(',', $form_collaborators));

    // Prepare statement to find user IDs
    // Assuming your users table is named 'users' and has columns 'id' and 'username'
    $stmt_find_user = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");

    // Prepare statement to insert into deck_contributors
    // Assuming deck_contributors has columns: deck_id, user_id, status
    $stmt_add_contributor = $conn->prepare("INSERT INTO deck_contributors (deck_id, user_id, status) VALUES (?, ?, 'accepted')");

    foreach ($collab_usernames as $username) {
        // Skip empty entries (e.g., trailing commas)
        if (empty($username)) continue;

        // Don't add the owner as a contributor
        // We need to fetch the owner's username to compare, or just skip if we know the current user_id matches
        // For simplicity, let's assume we don't add the current user even if they type their own name

        $stmt_find_user->bind_param("s", $username);
        $stmt_find_user->execute();
        $result = $stmt_find_user->get_result();

        if ($row = $result->fetch_assoc()) {
            $collab_user_id = (int) $row['id'];

            // Prevent adding the owner as a contributor
            if ($collab_user_id == $user_id) {
                continue;
            }

            // Check if already exists to avoid duplicate key errors if you have a unique constraint
            // Optional: You might want to check if they are already a contributor
            $check_stmt = $conn->prepare("SELECT id FROM deck_contributors WHERE deck_id = ? AND user_id = ?");
            $check_stmt->bind_param("ii", $new_deck_id, $collab_user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows == 0) {
                $stmt_add_contributor->bind_param("ii", $new_deck_id, $collab_user_id);
                if (!$stmt_add_contributor->execute()) {
                    error_log("Failed to add contributor $username: " . $stmt_add_contributor->error);
                    // Continue adding others even if one fails
                }
            }
            $check_stmt->close();
        } else {
            // Username not found - optionally log or notify user
            error_log("Collaborator username not found: $username");
        }
    }

    $stmt_find_user->close();
    $stmt_add_contributor->close();
}

$conn->close();

$_SESSION["deck_create_success"] = "The deed is done! That deck be built, true and proper!";
header("Location: deck_create.php");
exit();
?>
