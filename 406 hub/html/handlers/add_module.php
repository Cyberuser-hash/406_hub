<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['course_id']) || !isset($input['title'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
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

    $sql = "INSERT INTO course_modules (course_id, title, description, order_index) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['course_id'],
        $input['title'],
        $input['description'] ?? '',
        $input['order_index'] ?? 1
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Модуль успешно добавлен',
        'module_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
