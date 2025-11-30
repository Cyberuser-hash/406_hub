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

if (!$input || !isset($input['student_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указан ID студента']);
    exit;
}

$student_id = $input['student_id'];

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

    $pdo->beginTransaction();

    // Получаем логин студента для удаления папки
    $login_sql = "SELECT login FROM users WHERE id = ?";
    $login_stmt = $pdo->prepare($login_sql);
    $login_stmt->execute([$student_id]);
    $student = $login_stmt->fetch();

    // Удаляем связанные данные
    $tables = [
        'assignment_submissions' => 'student_id',
        'course_users' => 'user_id', 
        'student_profiles' => 'user_id'
    ];

    foreach ($tables as $table => $column) {
        $delete_sql = "DELETE FROM $table WHERE $column = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$student_id]);
    }

    // Удаляем пользователя
    $delete_user_sql = "DELETE FROM users WHERE id = ? AND role = 'student'";
    $delete_user_stmt = $pdo->prepare($delete_user_sql);
    $delete_user_stmt->execute([$student_id]);

    if ($delete_user_stmt->rowCount() === 0) {
        throw new Exception('Студент не найден или не может быть удален');
    }

    $pdo->commit();

    // Удаляем папку студента (опционально)
    if ($student && file_exists("/var/www/html/student_work/" . $student['login'])) {
        // Внимание: раскомментируйте только если уверены!
        // deleteDirectory("/var/www/html/student_work/" . $student['login']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Студент успешно удален'
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Функция для рекурсивного удаления папки
function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    
    return rmdir($dir);
}
?>
