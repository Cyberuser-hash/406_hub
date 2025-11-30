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

if (!$input || !isset($input['assignment_id']) || !isset($input['status'])) {
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

    $sql = "UPDATE assignment_submissions 
            SET status = ?, score = ?, teacher_feedback = ?, graded_at = NOW()
            WHERE assignment_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['status'],
        $input['score'] ?? null,
        $input['feedback'] ?? null,
        $input['assignment_id']
    ]);

    // Логируем действие
    $admin_id = $input['admin_id'] ?? 1; // В реальной системе берем из сессии
    $log_sql = "INSERT INTO admin_logs (admin_id, action, target_user, details) VALUES (?, ?, ?, ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        $admin_id,
        "Изменен статус задания",
        $input['student_login'] ?? 'unknown',
        "Задание ID: {$input['assignment_id']}, новый статус: {$input['status']}"
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Статус задания обновлен'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
