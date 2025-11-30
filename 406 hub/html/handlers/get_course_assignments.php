<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$course_id = $_GET['course_id'] ?? '';

if (empty($course_id)) {
    echo json_encode(['success' => false, 'message' => 'ID курса не указан']);
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

    $sql = "SELECT a.*, cm.title as module_title
            FROM assignments a
            JOIN course_modules cm ON a.module_id = cm.id
            WHERE cm.course_id = ?
            ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id]);
    $assignments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $assignments
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
