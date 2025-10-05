<?php
// db.php
$host = "localhost";
$username = "uczrllawgyzfy";
$password = "tmq3v2ylpxpl";
$dbname = "dbxfngey24pxcd";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
