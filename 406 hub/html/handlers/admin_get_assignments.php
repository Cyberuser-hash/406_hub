<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

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

    $sql = "SELECT a.id, a.title, a.description, a.deadline, a.max_score,
                   u.login as student_login, sp.first_name, sp.last_name,
                   s.status, s.file_name, s.file_path, s.score, s.teacher_feedback,
                   c.title as course_title
            FROM assignments a
            LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
            LEFT JOIN users u ON s.student_id = u.id
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            LEFT JOIN course_modules cm ON a.module_id = cm.id
            LEFT JOIN courses c ON cm.course_id = c.id
            ORDER BY a.created_at DESC";

    $stmt = $pdo->query($sql);
    $assignments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $assignments
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
