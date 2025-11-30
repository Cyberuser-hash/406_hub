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

    // Статистика студентов
    $students_stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $total_students = $students_stmt->fetch()['count'];

    // Статистика заданий
    $assignments_stmt = $pdo->query("SELECT COUNT(*) as count FROM assignments");
    $total_assignments = $assignments_stmt->fetch()['count'];

    // Сданные работы
    $submitted_stmt = $pdo->query("SELECT COUNT(*) as count FROM assignment_submissions WHERE status IN ('uploaded', 'in_review', 'completed')");
    $submitted_works = $submitted_stmt->fetch()['count'];

    // На проверке
    $review_stmt = $pdo->query("SELECT COUNT(*) as count FROM assignment_submissions WHERE status = 'in_review'");
    $pending_reviews = $review_stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_students' => $total_students,
            'total_assignments' => $total_assignments,
            'submitted_works' => $submitted_works,
            'pending_reviews' => $pending_reviews
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
