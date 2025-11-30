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

    // Заглушка - возвращаем пустой массив курсов
    echo json_encode([
        'success' => true,
        'data' => []
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
