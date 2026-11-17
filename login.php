<?PHP
/**
 * BeyaliSacco Modernized Login Page
 * Uses PDO, prepared statements, and modern security practices.
 */

session_start();

// Include database configuration and functions
require 'functions.php';
$db = getPdo();

// Include password pepper for additional security
require 'config/pepper.php';

// CSRF token protection
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

// Sanitize user input
$log_user = sanitize($_POST['log_user']);
$log_pw = sanitize($_POST['log_pw']);

// Select user details from USER table using prepared statement
$sql = "SELECT * FROM user u JOIN ugroup ug ON u.ugroup_id = ug.ugroup_id WHERE user_name = :user_name";
$stmt = $db->prepare($sql);
$stmt->execute([':user_name' => $log_user]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result && password_verify($log_pw . $pepper, $result['user_pw'])) {
    // Define Session Variables for this User
    $_SESSION['log_user'] = $log_user;
    $_SESSION['log_time'] = time();
    $_SESSION['log_id'] = $result['user_id'];
    $_SESSION['log_ugroup'] = $result['ugroup_name'];
    $_SESSION['log_admin'] = $result['ugroup_admin'];
    $_SESSION['log_delete'] = $result['ugroup_delete'];
    $_SESSION['log_report'] = $result['ugroup_report'];
    
    // Generate new fingerprint to prevent session fixation
    $fingerprint = fingerprint();
    $_SESSION['log_fingerprint'] = $fingerprint;
    
    // Record login event
    $loginTime = time();
    $sql = "INSERT INTO logrec (user_id, logrec_start, logrec_logout) VALUES (:user_id, :login_time, '0')";
    $db->prepare($sql)->execute([':user_id' => $result['user_id'], ':login_time' => $loginTime]);
    
    // Forward to start.php
    header('Location: start.php');
    exit;
} else {
    showMessage('Authentication failed! Wrong Username and/or Password!');
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Microfinance Management - Login</title>
    <link rel="shortcut icon" href="ico/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/mangoo.css" />
    <link rel="stylesheet" href="ico/font-awesome/css/font-awesome.min.css">
</head>
<body>
    <div class="content_center" style="width:100%; margin-top:15em">
        <!-- LEFT SIDE: mangoO Logo -->
        <div class="content_left" style="width:50%; padding-right:5em; text-align:right;">
            <img src="ico/mangoo_l.png" style="width:75%;">
        </div>
        <!-- RIGHT SIDE: Login Form -->
        <div class="content_right" style="width:50%; padding-left:5em; text-align:left;">
            <p class="heading" style="margin:0; text-align:left;">Please login...</p>
            <form action="login.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <table id="tb_fields" style="margin:0; border-spacing:0em 1.25em;">
                    <tr>
                        <td>
                            <input type="text" name="log_user" placeholder="Username" required />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="password" name="log_pw" placeholder="Password" required />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" name="login" value="Login">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</body>
</html>