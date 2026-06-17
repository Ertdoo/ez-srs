<?php 
include ("connect.php"); 

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // if user is not found, we still run a dummy hash to confuse the hacker scallywags
    $DUMMY_HASH = '$2y$10$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'; // A secure, non-working hash
    $user = null;
    $hash_to_check = $DUMMY_HASH;

    $username = trim($_POST["username"] ?? '');
    $form_password = $_POST["password"] ?? '';
    // session login attempt check
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
        if (time() - $_SESSION['login_last_attempt'] < 300) { // in secs
            $_SESSION['message'] = "Too many failed attempts! Ye be locked out 'til the next tide (5 minutes)!";
            Header ("Location: land_login.php");
            exit;
        } else {
            unset($_SESSION['login_attempts']);
        }
    }
    
    if (empty($message)) {
        
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hash_to_check = $user["password_hash"];
        }
        
        $stmt->close();


        if (password_verify($form_password, $hash_to_check)) {
            
            // If the hash check passed and a real user was found
            if ($user !== null) {
                
                // regen the session id
                session_regenerate_id(true);
                
                // clear any failed attempts log
                unset($_SESSION['login_attempts']);
                unset($_SESSION['login_last_attempt']);
                
                $_SESSION['user_id'] = $user['id']; // store user id!
                $_SESSION['username'] = $user['username'];
                $_SESSION['session_logged'] = true; 
                
                header("Location: user_dashboard.php");
                exit;
            }
        }

        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_last_attempt'] = time();

        $_SESSION['message'] = "Ye be mistaken, matey! Invalid ship name or wrong password!";
        Header ('Location: land_login.php');
        exit;
    }
    
    $conn->close();
}
?>
