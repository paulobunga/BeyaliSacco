<?PHP
/**
 * BeyaliSacco Modernized Database Layer
 * Uses PDO with prepared statements for security and modern best practices.
 */

// Database configuration
$dbHost = '127.0.0.1';
$dbName = 'mangoo';
$dbUser = 'root';
$dbPass = '';
$charset = 'utf8';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=$charset", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    header('Location: setup.php');
    exit;
}

/**
 * Get PDO instance
 */
function getPdo() {
    global $pdo;
    return $pdo;
}

/**
 * Execute prepared statement
 */
function db_query($sql, $params = [], $mode = 'fetch') {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    switch ($mode) {
        case 'fetch': return $stmt->fetch(PDO::FETCH_ASSOC);
        case 'fetchAll': return $stmt->fetchAll(PDO::FETCH_ASSOC);
        case 'execute': return $stmt;
        case 'insertId': return $pdo->lastInsertId();
        default: return false;
    }
}

/**
 * Secure input sanitization
 */
function sanitize($var) {
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $var = stripslashes($var);
    }
    return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate session fingerprint
 */
function fingerprint() {
    return md5($_SERVER['REMOTE_ADDR'] . 'modern-salt-2026-' . $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Check login status
 */
function checkLogin() {
    $fingerprint = fingerprint();
    session_start();
    if (!isset($_SESSION['log_user']) || $_SESSION['log_fingerprint'] != $fingerprint) logout();
    session_regenerate_id(true);
}

/**
 * Admin permission check
 */
function checkPermissionAdmin() {
    if ($_SESSION['log_admin'] !== '1') {
        header('Location: start.php');
        die();
    }
}

/**
 * Delete permission check
 */
function checkPermissionDelete() {
    if ($_SESSION['log_delete'] !== '1') {
        header('Location: start.php');
        die();
    }
}

/**
 * Report permission check
 */
function checkPermissionReport() {
    if ($_SESSION['log_report'] !== '1') {
        header('Location: start.php');
        die();
    }
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header('Location: logout_success.php');
    die;
}

/**
 * Execute SQL and check result
 */
function checkResult($stmt) {
    if (!$stmt) {
        error_log("SQL failed: " . implode(' ', $pdo->errorInfo()));
        return false;
    }
    return true;
}

/**
 * Load system settings
 */
function getSettings($pdo) {
    $sql = "SELECT * FROM settings";
    $stmt = db_query($sql, [], 'fetchAll');
    if (!$stmt) return;
    foreach ($stmt as $row) {
        if (isset($row['set_value'])) {
            $_SESSION[$row['set_short']] = $row['set_value'];
        }
    }
}

/**
 * Load fee settings
 */
function getFees($pdo) {
    $sql = "SELECT * FROM fees";
    $stmt = db_query($sql, [], 'fetchAll');
    if (!$stmt) return;
    foreach ($stmt as $row) {
        if (isset($row['fee_value'])) {
            $_SESSION[$row['fee_short']] = $row['fee_value'];
        }
        if (isset($row['fee_name'])) {
            $_SESSION[$row['fee_short'] . '_name'] = $row['fee_name'];
        }
    }
}

/**
 * Get current share value
 */
function getShareValue($pdo) {
    $sql = "SELECT shareval_value FROM shareval WHERE shareval_id = (SELECT MAX(shareval_id) FROM shareval)";
    $row = db_query($sql, [], 'fetch');
    if ($row) {
        $_SESSION['share_value'] = $row['shareval_value'];
    }
}

/**
 * Get savings balance
 */
function getSavingsBalance($pdo, $cust_id) {
    $sql = "SELECT savbal_balance FROM savbalance WHERE cust_id = :cust_id";
    $row = db_query($sql, [':cust_id' => $cust_id], 'fetch');
    return $row ? $row['savbal_balance'] : 0;
}

/**
 * Get fixed savings
 */
function getSavingsFixed($pdo, $cust_id) {
    $sql = "SELECT savbal_fixed FROM savbalance WHERE cust_id = :cust_id";
    $row = db_query($sql, [':cust_id' => $cust_id], 'fetch');
    return $row ? $row['savbal_fixed'] : 0;
}

/**
 * Update savings balance for specific customer
 */
function updateSavingsBalance($pdo, $cust_id) {
    $timestamp = time();
    $sql = "UPDATE savbalance 
            SET savbal_balance = (SELECT SUM(sav_amount) FROM savings WHERE cust_id = :cust_id),
                savbal_fixed = (SELECT SUM(sav_amount) FROM savings WHERE cust_id = :cust_id AND sav_fixed > :timestamp)
            WHERE cust_id = :cust_id";
    db_query($sql, [':cust_id' => $cust_id, ':timestamp' => $timestamp], 'execute');
}

/**
 * Update savings balance for all customers
 */
function updateSavingsBalanceAll($pdo) {
    $sql = "UPDATE savbalance SET savbal_balance = (SELECT SUM(sav_amount) FROM savings WHERE savings.cust_id = savbalance.cust_id)";
    db_query($sql, [], 'execute');
}

/**
 * Get share balance
 */
function getShareBalance($pdo, $cust_id) {
    $sql = "SELECT share_amount, share_value FROM shares WHERE cust_id = :cust_id";
    $stmt = db_query($sql, [':cust_id' => $cust_id], 'fetchAll');
    $bal = ["amount" => "0", "value" => "0"];
    if ($stmt) {
        foreach ($stmt as $row) {
            $bal['amount'] += $row['share_amount'];
            $bal['value'] += $row['share_value'];
        }
    }
    return $bal;
}

/**
 * Get customer details
 */
function getCustomer($pdo, $custID) {
    $sql = "SELECT c.*, ssex.type AS sex_type, smarried.status AS married_status 
            FROM customer c 
            LEFT JOIN custsex ssex ON c.custsex_id = ssex.custsex_id 
            LEFT JOIN custmarried smarried ON c.custmarried_id = smarried.custmarried_id 
            LEFT JOIN custsick sickness ON c.custsick_id = sickness.custsick_id 
            LEFT JOIN user u ON c.user_id = u.user_id 
            WHERE c.cust_id = :cust_id";
    return db_query($sql, [':cust_id' => $custID], 'fetch') ?: [];
}

/**
 * Get customers excluding current
 */
function getCustOther($pdo) {
    $sql = "SELECT * FROM customer LEFT JOIN custsex ON custsex.custsex_id = customer.custsex_id WHERE cust_id NOT IN (0, :current) ORDER BY cust_id";
    return db_query($sql, [':current' => $_SESSION['cust_id']], 'fetchAll') ?: [];
}

/**
 * Get active customers
 */
function getCustAct($pdo) {
    $sql = "SELECT * FROM customer LEFT JOIN custsex ON custsex.custsex_id = customer.custsex_id WHERE cust_id != 0 AND cust_active = 1 ORDER BY cust_id";
    return db_query($sql, [], 'fetchAll') ?: [];
}

/**
 * Get inactive customers
 */
function getCustInact($pdo) {
    $sql = "SELECT * FROM customer LEFT JOIN custsex ON custsex.custsex_id = customer.custsex_id WHERE cust_id != 0 AND cust_active != 1 ORDER BY cust_id";
    return db_query($sql, [], 'fetchAll') ?: [];
}

/**
 * Get all customers
 */
function getCustAll($pdo) {
    $sql = "SELECT * FROM customer LEFT JOIN custsex ON custsex.custsex_id = customer.custsex_id WHERE cust_id != 0";
    return db_query($sql, [], 'fetchAll') ?: [];
}

/**
 * Get overdue customers
 */
function getCustOverdue($pdo) {
    $lastSubscr = time() - 31536000;
    $sql = "SELECT * FROM customer WHERE cust_active = 1 AND cust_lastsub < :last_subscr ORDER BY cust_lastsub, cust_id";
    return db_query($sql, [':last_subscr' => $lastSubscr], 'fetchAll') ?: [];
}

/**
 * Build customer number
 */
function buildCustNo($pdo) {
    $sql = "SELECT MAX(cust_id) AS maxid FROM customer";
    $row = db_query($sql, [], 'fetch');
    $maxId = $row ? $row['maxid'] : 0;
    
    $cnParts = explode("%", $_SESSION['set_cno'] ?? '1%N');
    $cnCount = count($cnParts);
    $custNo = "";
    
    for ($i = 1; $i < $cnCount; $i++) {
        switch ($cnParts[$i]) {
            case "N": $custNo .= ($maxId + 1); break;
            case "Y": $custNo .= date("Y", time()); break;
            case "M": $custNo .= date("m", time()); break;
            case "D": $custNo .= date("d", time()); break;
            default: $custNo .= $cnParts[$i];
        }
    }
    return $custNo;
}

/**
 * Get employee details
 */
function getEmployee($pdo, $empl_id) {
    $sql = "SELECT e.*, u.* FROM employee e LEFT JOIN user u ON e.empl_id = u.empl_id WHERE e.empl_id = :empl_id";
    return db_query($sql, [':empl_id' => $empl_id], 'fetch') ?: [];
}

/**
 * Get current employees
 */
function getEmplCurr($pdo) {
    $timestamp = time();
    $sql = "SELECT * FROM employee e LEFT JOINemplsex s ON e.emplsex_id = s.emplsex_id LEFT JOINemplmarried m ON e.emplmarried_id = m.emplmarried_id WHERE e.empl_id != 0 AND (e.empl_out > :timestamp OR e.empl_out IS NULL) ORDER BY e.empl_id";
    return db_query($sql, [':timestamp' => $timestamp], 'fetchAll') ?: [];
}

/**
 * Get past employees
 */
function getEmplPast($pdo) {
    $timestamp = time();
    $sql = "SELECT * FROM employee e LEFT JOINemplsex s ON e.emplsex_id = s.emplsex_id LEFT JOINemplmarried m ON e.emplmarried_id = m.emplmarried_id WHERE e.empl_id != 0 AND e.empl_out < :timestamp ORDER BY e.empl_id";
    return db_query($sql, [':timestamp' => $timestamp], 'fetchAll') ?: [];
}

/**
 * Build employee number
 */
function buildEmplNo($pdo) {
    $sql = "SELECT MAX(empl_id) AS maxid FROM employee";
    $row = db_query($sql, [], 'fetch');
    $maxId = $row ? $row['maxid'] : 0;
    
    $enParts = explode("%", $_SESSION['set_eno'] ?? '1%N');
    $enCount = count($enParts);
    $emplNo = "";
    
    for ($i = 1; $i < $enCount; $i++) {
        switch ($enParts[$i]) {
            case "N": $emplNo .= ($maxId + 1); break;
            case "Y": $emplNo .= date("Y", time()); break;
            case "M": $emplNo .= date("m", time()); break;
            case "D": $emplNo .= date("d", time()); break;
            default: $emplNo .= $enParts[$i];
        }
    }
    return $emplNo;
}

/**
 * Get overdue loans
 */
function getLtransOverdue($pdo) {
    $timestamp = time();
    $sql = "SELECT * FROM ltrans LEFT JOIN loans ON ltrans.loan_id = loans.loan_id LEFT JOIN customer ON loans.cust_id = customer.cust_id WHERE ltrans_due <= :timestamp AND ltrans_date IS NULL AND loanstatus_id = 2 ORDER BY ltrans_due";
    return db_query($sql, [':timestamp' => $timestamp], 'fetchAll') ?: [];
}

/**
 * Get loan securities
 */
function getLoanSecurities($pdo, $loan_id) {
    $sql = "SELECT * FROM securities WHERE loan_id = :loan_id";
    $stmt = db_query($sql, [':loan_id' => $loan_id], 'fetchAll');
    $securities = [];
    if ($stmt) foreach ($stmt as $row) $securities[] = $row;
    return $securities;
}

/**
 * Get security details
 */
function getSecurity($pdo, $sec_id) {
    $sql = "SELECT s.*, l.loan_name, c.cust_name FROM securities s LEFT JOIN loans l ON s.loan_id = l.loan_id LEFT JOIN customer c ON s.cust_id = c.cust_id WHERE s.sec_id = :sec_id";
    return db_query($sql, [':sec_id' => $sec_id], 'fetch') ?: [];
}
