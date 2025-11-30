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

if (!$input || !isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Все обязательные поля должны быть заполнены']);
    exit;
}

$username = trim($input['username']);
$email = trim($input['email']);
$password = $input['password'];
$firstName = $input['first_name'] ?? '';
$lastName = $input['last_name'] ?? '';
$studentGroup = $input['student_group'] ?? '';

// Валидация
if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Логин должен содержать минимум 3 символа']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Неверный формат email']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Пароль должен содержать минимум 6 символов']);
    exit;
}

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

    // Проверяем существующего пользователя
    $check_sql = "SELECT id FROM users WHERE login = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином или email уже существует']);
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    // Создаем пользователя с ролью student
    $user_sql = "INSERT INTO users (login, email, password_hash, role) VALUES (?, ?, ?, 'student')";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$username, $email, $password_hash]);
    
    $user_id = $pdo->lastInsertId();

    // Создаем профиль студента
    $profile_sql = "INSERT INTO student_profiles (user_id, first_name, last_name, student_group) VALUES (?, ?, ?, ?)";
    $profile_stmt = $pdo->prepare($profile_sql);
    $profile_stmt->execute([$user_id, $firstName, $lastName, $studentGroup]);

    // Создаем папку для студента
    $student_folder = "/var/www/html/student_work/" . $username;
    if (!file_exists($student_folder)) {
        mkdir($student_folder, 0755, true);
        mkdir($student_folder . "/assignments", 0755, true);
        mkdir($student_folder . "/projects", 0755, true);
        mkdir($student_folder . "/documents", 0755, true);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Регистрация успешна! Перенаправляем на страницу входа...',
        'user_id' => $user_id
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>
