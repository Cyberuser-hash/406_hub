<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $course_id = $_GET['course_id'] ?? 0;
    
    if ($course_id <= 0) {
        throw new Exception("Неверный ID курса");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    $sql = "SELECT id, title, description FROM courses WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Курс не найден");
    }

    $course = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => $course
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
