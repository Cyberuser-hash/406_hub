<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $login = $input['login'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    // Валидация
    if (empty($login) || empty($email) || empty($password)) {
        throw new Exception("Все обязательные поля должны быть заполнены");
    }

    if (strlen($password) < 6) {
        throw new Exception("Пароль должен содержать минимум 6 символов");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Проверяем уникальность логина и email
    $check_sql = "SELECT id FROM users WHERE login = ? OR email = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$login, $email]);
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception("Пользователь с таким логином или email уже существует");
    }

    // Создаем преподавателя (используем только существующие поля)
    $sql = "INSERT INTO users (login, email, password_hash, role, created_at) 
            VALUES (?, ?, ?, 'teacher', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $login,
        $email,
        password_hash($password, PASSWORD_DEFAULT)
    ]);

    $teacher_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Преподаватель успешно создан',
        'teacher_id' => $teacher_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Teacher creation error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
