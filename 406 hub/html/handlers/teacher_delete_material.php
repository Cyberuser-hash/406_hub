<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $material_id = $input['material_id'] ?? 0;
    
    if ($material_id <= 0) {
        throw new Exception("Неверный ID материала");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Получаем информацию о материале
    $material_sql = "SELECT file_path FROM teacher_materials WHERE id = ?";
    $material_stmt = $pdo->prepare($material_sql);
    $material_stmt->execute([$material_id]);
    
    if ($material_stmt->rowCount() === 0) {
        throw new Exception("Материал не найден");
    }

    $material = $material_stmt->fetch();
    $file_path = $material['file_path'];

    // Удаляем файл
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            throw new Exception("Не удалось удалить файл");
        }
    }

    // Удаляем запись из базы
    $delete_sql = "DELETE FROM teacher_materials WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$material_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно удален'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
