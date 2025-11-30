<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// Добавляем CORS заголовки
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Логируем запрос
error_log("Teacher courses list requested from IP: " . $_SERVER['REMOTE_ADDR']);

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=learning_system;charset=utf8mb4",
        "readnwrite",
        "38JKkre47QWETt",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $sql = "SELECT 
                c.id,
                c.title,
                c.description,
                c.image_url,
                c.is_active,
                c.created_at,
                c.updated_at,
                u.login as created_by_login,
                COUNT(DISTINCT cm.id) as modules_count,
                COUNT(DISTINCT a.id) as assignments_count,
                COUNT(DISTINCT cu.user_id) as students_count
            FROM courses c
            LEFT JOIN users u ON c.created_by = u.id
            LEFT JOIN course_modules cm ON c.id = cm.course_id
            LEFT JOIN assignments a ON cm.id = a.module_id
            LEFT JOIN course_users cu ON c.id = cu.course_id
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll();

    // Приводим типы данных
    foreach ($courses as &$course) {
        $course['id'] = (int)$course['id'];
        $course['modules_count'] = (int)$course['modules_count'];
        $course['assignments_count'] = (int)$course['assignments_count'];
        $course['students_count'] = (int)$course['students_count'];
        $course['is_active'] = (bool)$course['is_active'];
    }

    // Логируем количество найденных курсов
    error_log("Found " . count($courses) . " active courses");

    echo json_encode([
        'success' => true,
        'data' => $courses,
        'count' => count($courses),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database error in teacher_get_courses: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных при получении списка курсов',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("General error in teacher_get_courses: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Внутренняя ошибка сервера',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
