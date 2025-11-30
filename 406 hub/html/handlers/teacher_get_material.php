<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $material_id = $_GET['material_id'] ?? 0;
    
    if ($material_id <= 0) {
        throw new Exception("Неверный ID материала");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    $sql = "SELECT * FROM teacher_materials WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$material_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Материал не найден");
    }

    $material = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => $material
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
