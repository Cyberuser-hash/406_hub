<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $student_id = $_GET['student_id'] ?? 0;

    if ($student_id <= 0) {
        throw new Exception("Неверный ID студента");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Получаем базовую статистику студента
    $stats_sql = "SELECT 
                    (SELECT COUNT(*) FROM courses c 
                     JOIN course_users cu ON c.id = cu.course_id 
                     WHERE cu.user_id = ?) as total_courses,
                    (SELECT COUNT(*) FROM assignments a 
                     JOIN courses c ON a.course_id = c.id 
                     JOIN course_users cu ON c.id = cu.course_id 
                     WHERE cu.user_id = ?) as total_assignments,
                    (SELECT COUNT(*) FROM assignment_submissions asp 
                     WHERE asp.student_id = ? AND asp.status = 'completed') as completed_assignments";

    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$student_id, $student_id, $student_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Если нет данных, используем значения по умолчанию
    $stats = array_map(function($value) {
        return $value !== null ? (int)$value : 0;
    }, $stats);

    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => [
            'total_courses' => 0,
            'total_assignments' => 0,
            'completed_assignments' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
