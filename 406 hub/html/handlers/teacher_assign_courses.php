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

if (!$input || !isset($input['student_id']) || !isset($input['course_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
    exit;
}

$student_id = $input['student_id'];
$course_ids = $input['course_ids'];

if (!is_array($course_ids) || empty($course_ids)) {
    echo json_encode(['success' => false, 'message' => 'Не выбраны курсы для назначения']);
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

    $pdo->beginTransaction();

    // Удаляем старые назначения (опционально)
    $delete_sql = "DELETE FROM course_users WHERE user_id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([$student_id]);

    // Добавляем новые назначения
    $insert_sql = "INSERT INTO course_users (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())";
    $insert_stmt = $pdo->prepare($insert_sql);

    $assigned_count = 0;
    foreach ($course_ids as $course_id) {
        try {
            $insert_stmt->execute([$student_id, $course_id]);
            $assigned_count++;
        } catch (PDOException $e) {
            // Пропускаем дубликаты или ошибки
            continue;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Студенту назначено {$assigned_count} курсов",
        'assigned_count' => $assigned_count
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
