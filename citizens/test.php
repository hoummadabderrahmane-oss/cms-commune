<?php
// sgc/citizens/test.php — diagnostic file (delete after debugging)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Citizens diagnostic</h3>";

// 1. What's in the session (shows if login sets admin_id or something else)
echo "<b>1. Session:</b><pre>" . print_r($_SESSION, true) . "</pre>";

// 2. Config path
$cfg = __DIR__ . '/../config/database.php';
echo "<b>2. Config path:</b> $cfg → " . (file_exists($cfg) ? "<span style='color:green'>FOUND</span>" : "<span style='color:red'>NOT FOUND</span>") . "<br>";
if (!file_exists($cfg)) die("<hr>STOP: fix the path to database.php");

require_once $cfg;
$pdo = getDB();
// 3. Does config create $pdo?
echo "<b>3. \$pdo object:</b> " . (isset($pdo) ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING — your database.php must create a PDO connection named \$pdo</span>") . "<br>";
if (!isset($pdo)) die("<hr>STOP: send me your config/database.php content");

// 4. Table check
try {
    $count = $pdo->query("SELECT COUNT(*) FROM citoyens")->fetchColumn();
    echo "<b>4. Table citoyens:</b> <span style='color:green'>OK ($count rows)</span><br>";
} catch (Throwable $e) {
    echo "<b>4. Table citoyens:</b> <span style='color:red'>ERROR → " . $e->getMessage() . "</span><br>";
}

// 5. Includes
echo "<b>5. header.php:</b> " . (file_exists(__DIR__ . '/../includes/header.php') ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING</span>");
echo " — <b>footer.php:</b> " . (file_exists(__DIR__ . '/../includes/footer.php') ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING</span>") . "<br>";

echo "<hr>If all 5 are green, index.php has no reason to fail.";
echo "<pre>" . print_r($pdo->query("SHOW COLUMNS FROM citoyens")->fetchAll(PDO::FETCH_ASSOC), true) . "</pre>";