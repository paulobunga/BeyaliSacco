<?PHP
/**
 * BeyaliSacco Modernized Start/Dashboard Page
 * Uses modern PHP with PDO, proper session management, and responsive HTML5.
 */

session_start();
require 'functions.php';
$db = getPdo();
getSettings($db);
getFees($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance Management</title>
    <link rel="shortcut icon" href="ico/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/mangoo.css" />
    <link rel="stylesheet" href="ico/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/stats.css">
</head>
<body>
    <!-- MENU -->
    <div class="menu">
        <div id="menu_header">
            <img src="ico/mangoo_logo_m.png" style="margin: 1em 0 0 .75em;"/>
            <div id="menu_logout">
                <ul>
                    <li><?php echo htmlspecialchars($_SESSION['log_user']); ?>
                        <ul>
                            <li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <div id="menu_tabs">
            <ul>
                <li<?php if($tab_no == 1) echo ' id="tab_selected"'; ?>><a href="start.php"><i class="fa fa-tachometer fa-fw"></i> Dashboard</a></li>
                <li<?php if($tab_no == 2) echo ' id="tab_selected"'; ?>><a href="cust_search.php"><i class="fa fa-group fa-fw"></i> Customers</a></li>
                <li<?php if($tab_no == 3) echo ' id="tab_selected"'; ?>><a href="loans_search.php"><i class="fa fa-percent fa-fw"></i> Loans</a></li>
                <li<?php if($tab_no == 4) echo ' id="tab_selected"'; ?>><a href="books_expense.php"><i class="fa fa-calculator fa-fw"></i> Accounting</a></li>
                <?php if($_SESSION['log_report'] == 1): ?>
                    <li<?php if($tab_no == 5) echo ' id="tab_selected"'; ?>><a href="rep_incomes.php"><i class="fa fa-line-chart fa-fw"></i> Reports</a></li>
                <?php endif; ?>
                <?php if($_SESSION['log_admin'] == 1): ?>
                    <li<?php if($tab_no == 6) echo ' id="tab_selected"'; ?>><a href="set_basic.php"><i class="fa fa-wrench fa-fw"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- MENU MAIN -->
    <div id="menu_main">
        <a href="cust_search.php">Search Customer</a>
        <a href="cust_new.php">New Customer</a>
        <a href="loans_search.php">Search Loan</a>
        <a href="loans_act.php">Active Loans</a>
        <a href="loans_pend.php">Pending Loans</a>
        <a href="loans_securities.php">Loan Securities</a>
    </div>

    <!-- Left Side of Dashboard -->
    <div class="content_left" style="width:50%;">
        <?PHP include $_SESSION['set_dashl']; ?>
    </div>

    <!-- Right Side of Dashboard -->
    <div class="content_right" style="width:50%;">
        <?PHP include $_SESSION['set_dashr']; ?>
    </div>

    <!-- Logout Reminder Message -->
    <?PHP checkLogout(); ?>

    <!-- Responsive container -->
    <style>
        .content_left, .content_right {
            float: left;
            padding: 10px;
            box-sizing: border-box;
        }
        .content_left + .content_right {
            margin-left: 50%;
        }
    </style>
</body>
</html>