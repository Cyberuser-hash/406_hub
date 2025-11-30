<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

try {
    // Получаем данные
    $input = json_decode(file_get_contents('php://input'), true);
    $course_id = $input['course_id'] ?? 0;
    $student_ids = $input['student_ids'] ?? [];

    if (!$course_id || empty($student_ids)) {
        throw new Exception("Не выбраны курс или студенты");
    }

    // Подключаемся к базе
    $pdo = new PDO(
        "mysql:host=localhost;dbname=learning_system;charset=utf8mb4",
        "readnwrite",
        "38JKkre47QWETt",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $assigned_count = 0;
    
    // Назначаем курс каждому студенту
    foreach ($student_ids as $student_id) {
        try {
            $sql = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id, $course_id]);
            $assigned_count++;
        } catch (PDOException $e) {
            // Игнорируем ошибку дублирования (уже записан)
            if ($e->getCode() != 23000) {
                throw $e;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Курс назначен $assigned_count студентам",
        'assigned_count' => $assigned_count
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Bulk assign error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка назначения курса: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
