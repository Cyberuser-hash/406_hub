<?php
// test_login.php - положите в корень и откройте в браузере
error_reporting(E_ALL);
ini_set('display_errors', 1);

$username = 'teacher';
$password = 'teacher123';

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=learning_system;charset=utf8mb4",
        "readnwrite",
        "38JKkre47QWETt",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $user_sql = "SELECT u.* FROM users u WHERE u.login = ?";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$username]);
    
    if ($user_stmt->rowCount() === 0) {
        die("Пользователь не найден");
    }

    $user = $user_stmt->fetch();
    
    echo "<h2>Данные пользователя:</h2>";
    echo "Логин: " . $user['login'] . "<br>";
    echo "Роль: " . $user['role'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Password hash: " . $user['password_hash'] . "<br>";
    
    $password_verify = password_verify($password, $user['password_hash']);
    echo "Password verify: " . ($password_verify ? "TRUE" : "FALSE") . "<br>";
    
    if ($password_verify) {
        echo "<h3 style='color: green;'>Пароль верный! Можно входить.</h3>";
    } else {
        echo "<h3 style='color: red;'>Пароль неверный!</h3>";
    }

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
