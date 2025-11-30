<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $teacher_id = $input['teacher_id'] ?? 0;

    if (empty($title) || $teacher_id <= 0) {
        throw new Exception("Название курса и ID преподавателя обязательны");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    $sql = "INSERT INTO courses (title, description, created_by, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $description, $teacher_id]);

    $course_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Курс успешно создан',
        'course_id' => $course_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
