<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Все поля обязательны']);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

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

    // Ищем пользователя по логину - БЕЗ привязки к student_profiles
    $user_sql = "SELECT u.* FROM users u WHERE u.login = ?";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$username]);

    if ($user_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }

    $user = $user_stmt->fetch();

    // Проверяем пароль
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Неверный пароль']);
        exit;
    }

    // Для студентов создаем папку если её нет
    if ($user['role'] === 'student') {
        $student_folder = "/var/www/html/student_work/" . $user['login'];
        if (!file_exists($student_folder)) {
            mkdir($student_folder, 0755, true);
            mkdir($student_folder . "/assignments", 0755, true);
            mkdir($student_folder . "/projects", 0755, true);
            mkdir($student_folder . "/documents", 0755, true);
        }
    }

    // Формируем ответ с данными пользователя
    echo json_encode([
        'success' => true, 
        'message' => 'Вход успешен!',
        'user' => [
            'id' => $user['id'],
            'login' => $user['login'],
            'email' => $user['email'],
            'role' => $user['role'],
            'first_name' => '', // Для преподавателя оставляем пустым
            'last_name' => ''   // Для преподавателя оставляем пустым
        ]
    ]);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
