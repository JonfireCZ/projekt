<?php
error_reporting(0);
ini_set('display_errors', 0);

$host = 'sql206.infinityfree.com';
$db   = 'if0_42145778_locekrroom';
$user = 'if0_42145778';
$pass = 'HeslokWebu1234';

$charset = 'utf8mb4'; 
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Omlouváme se, nepodařilo se připojit k databázi.");
}
?>