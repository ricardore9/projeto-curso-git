<?php
// Database credentials
$db_host = 'localhost'; // Usually localhost, can be changed if needed
$db_name = 'covesa26_listaMateriais';
$db_user = 'covesa26_listaMateriais';
$db_pass = '@1defrc30';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>
