<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// Добавляем CORS заголовки
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешен. Используйте POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Логируем попытку добавления студента
error_log("Add student attempt from IP: " . $_SERVER['REMOTE_ADDR']);

// Получаем и валидируем входные данные
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Неверный формат JSON данных'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем обязательные поля
$required_fields = ['login', 'email', 'password'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Обязательное поле '$field' не заполнено"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Извлекаем и очищаем данные
$login = trim($input['login']);
$email = trim($input['email']);
$password = $input['password'];
$firstName = isset($input['first_name']) ? trim($input['first_name']) : '';
$lastName = isset($input['last_name']) ? trim($input['last_name']) : '';
$studentGroup = isset($input['student_group']) ? trim($input['student_group']) : '';

// Валидация данных
if (strlen($login) < 3) {
    echo json_encode([
        'success' => false,
        'message' => 'Логин должен содержать минимум 3 символа'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный формат email адреса'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Пароль должен содержать минимум 6 символов'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем сложность пароля
if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Пароль должен содержать буквы и цифры'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=learning_system;charset=utf8mb4",
        "readnwrite",
        "38JKkre47QWETt",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Проверяем существующего пользователя
    $check_sql = "SELECT id FROM users WHERE login = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$login, $email]);
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь с таким логином или email уже существует'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Хэшируем пароль
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Создаем пользователя
    $user_sql = "INSERT INTO users (login, email, password_hash, role) VALUES (?, ?, ?, 'student')";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([$login, $email, $password_hash]);
    
    $user_id = $pdo->lastInsertId();

    // Создаем профиль студента
    $profile_sql = "INSERT INTO student_profiles (user_id, first_name, last_name, student_group) VALUES (?, ?, ?, ?)";
    $profile_stmt = $pdo->prepare($profile_sql);
    $profile_stmt->execute([$user_id, $firstName, $lastName, $studentGroup]);

    // Создаем папку для студента
    $student_folder = "/var/www/html/student_work/" . $login;
    if (!file_exists($student_folder)) {
        if (!mkdir($student_folder, 0755, true)) {
            throw new Exception("Не удалось создать папку для студента: " . $student_folder);
        }
        // Создаем подпапки
        mkdir($student_folder . "/assignments", 0755, true);
        mkdir($student_folder . "/projects", 0755, true);
        mkdir($student_folder . "/documents", 0755, true);
        
        // Логируем создание папок
        error_log("Created student folders for: " . $login);
    }

    // Фиксируем транзакцию
    $pdo->commit();

    // Логируем успешное создание
    error_log("Student created successfully: " . $login . " (ID: " . $user_id . ")");

    echo json_encode([
        'success' => true, 
        'message' => 'Студент успешно добавлен в систему',
        'user_id' => (int)$user_id,
        'login' => $login,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Откатываем транзакцию в случае ошибки
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database error in teacher_add_student: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных при добавлении студента',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("General error in teacher_add_student: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
