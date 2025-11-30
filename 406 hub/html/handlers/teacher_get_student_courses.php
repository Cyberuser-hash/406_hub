<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$student_id = $_GET['student_id'] ?? '';

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

    $sql = "SELECT c.id, c.title
            FROM course_users cu
            JOIN courses c ON cu.course_id = c.id
            WHERE cu.user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $courses
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
