<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $module_id = $input['module_id'] ?? 0;
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $max_score = $input['max_score'] ?? 100;
    $deadline = $input['deadline'] ?? null;
    $teacher_signature = $input['teacher_signature'] ?? '';

    if (empty($title) || $module_id <= 0) {
        throw new Exception("Название задания и ID модуля обязательны");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Форматируем дату если она указана
    if ($deadline) {
        $deadline = date('Y-m-d H:i:s', strtotime($deadline));
    }

    $sql = "INSERT INTO assignments (module_id, title, description, max_score, deadline, teacher_signature, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $module_id,
        $title,
        $description,
        $max_score,
        $deadline,
        $teacher_signature
    ]);

    $assignment_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Задание успешно добавлено',
        'assignment_id' => $assignment_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
