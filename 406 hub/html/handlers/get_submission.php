<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$submission_id = $_GET['submission_id'] ?? '';

if (empty($submission_id)) {
    echo json_encode(['success' => false, 'message' => 'ID работы не указан']);
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

    $sql = "SELECT s.*, 
                   a.title, a.description, a.deadline, a.max_score,
                   u.login as student_login, 
                   CONCAT(sp.first_name, ' ', sp.last_name) as student_name,
                   c.title as course_title
            FROM assignment_submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN users u ON s.student_id = u.id
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            LEFT JOIN course_modules cm ON a.module_id = cm.id
            LEFT JOIN courses c ON cm.course_id = c.id
            WHERE s.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch();

    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Работа не найдена']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $submission
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
