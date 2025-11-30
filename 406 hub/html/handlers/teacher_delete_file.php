<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['file_id']) || $input['file_id'] <= 0) {
        throw new Exception("Неверный ID файла");
    }

    if (!isset($input['teacher_id']) || $input['teacher_id'] <= 0) {
        throw new Exception("Неверный ID преподавателя");
    }

    $file_id = (int)$input['file_id'];
    $teacher_id = (int)$input['teacher_id'];

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Проверяем принадлежность файла преподавателю
    $check_sql = "SELECT file_path FROM teacher_materials WHERE id = ? AND teacher_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$file_id, $teacher_id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception("Файл не найден или у вас нет доступа");
    }

    $file_data = $check_stmt->fetch();
    $file_path = $file_data['file_path'];

    // Удаляем физический файл
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Удаляем запись из базы
    $delete_sql = "DELETE FROM teacher_materials WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$file_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Файл успешно удален'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
