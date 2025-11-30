<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$assignment_id = $_GET['assignment_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';

if (empty($assignment_id)) {
    echo json_encode(['success' => false, 'message' => 'ID задания не указан']);
    exit;
}

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

    $sql = "SELECT a.*, 
                   s.status as student_status,
                   s.score as student_score,
                   s.teacher_feedback,
                   s.file_name,
                   s.file_path,
                   s.submitted_at,
                   c.title as course_title
            FROM assignments a
            LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
            LEFT JOIN course_modules cm ON a.module_id = cm.id
            LEFT JOIN courses c ON cm.course_id = c.id
            WHERE a.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $assignment_id]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        echo json_encode(['success' => false, 'message' => 'Задание не найдено']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $assignment
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
