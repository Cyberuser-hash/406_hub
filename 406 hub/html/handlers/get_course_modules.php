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

    // Получаем модули курса
    $modules_sql = "SELECT id, title, description FROM modules WHERE course_id = ? ORDER BY created_at";
    $modules_stmt = $pdo->prepare($modules_sql);
    $modules_stmt->execute([$course_id]);
    $modules = $modules_stmt->fetchAll();

    // Для каждого модуля получаем задания
    foreach ($modules as &$module) {
        $assignments_sql = "SELECT id, title, description, max_score, deadline, teacher_signature 
                           FROM assignments WHERE module_id = ? ORDER BY created_at";
        $assignments_stmt = $pdo->prepare($assignments_sql);
        $assignments_stmt->execute([$module['id']]);
        $module['assignments'] = $assignments_stmt->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'data' => $modules
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
