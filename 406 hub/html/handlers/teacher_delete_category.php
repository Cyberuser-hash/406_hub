<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['category_id']) || $input['category_id'] <= 0) {
        throw new Exception("Неверный ID категории");
    }

    if (!isset($input['teacher_id']) || $input['teacher_id'] <= 0) {
        throw new Exception("Неверный ID преподавателя");
    }

    $category_id = (int)$input['category_id'];
    $teacher_id = (int)$input['teacher_id'];

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Проверяем принадлежность категории преподавателю
    $check_sql = "SELECT id FROM teacher_categories WHERE id = ? AND teacher_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$category_id, $teacher_id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception("Категория не найдена или у вас нет доступа");
    }

    // Получаем файлы для удаления
    $files_sql = "SELECT file_path FROM teacher_materials WHERE category_id = ?";
    $files_stmt = $pdo->prepare($files_sql);
    $files_stmt->execute([$category_id]);
    $files = $files_stmt->fetchAll();

    // Удаляем физические файлы
    foreach ($files as $file) {
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }

    // Удаляем категорию (файлы удалятся каскадно из-за FOREIGN KEY)
    $delete_sql = "DELETE FROM teacher_categories WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$category_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Категория и все файлы успешно удалены'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
