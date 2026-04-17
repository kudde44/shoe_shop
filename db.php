<?php
// db.php – подключение к базе данных MySQL через PDO
$host = 'localhost';      // хост, обычно localhost
$dbname = 'shoe_shop';    // имя базы данных (измените на своё)
$username = 'root';       // пользователь MySQL
$password = '';           // пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}
?>