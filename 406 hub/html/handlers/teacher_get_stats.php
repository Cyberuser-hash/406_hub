<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    if (!isset($_GET['teacher_id'])) {
        throw new Exception("Teacher ID not provided");
    }

    $teacher_id = (int)$_GET['teacher_id'];
    
    if ($teacher_id <= 0) {
        throw new Exception("Invalid teacher ID");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Получаем количество студентов
    $students_sql = "SELECT COUNT(*) as total_students FROM users WHERE role = 'student'";
    $students_stmt = $pdo->prepare($students_sql);
    $students_stmt->execute();
    $total_students = $students_stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

    // Получаем количество категорий преподавателя
    $categories_sql = "SELECT COUNT(*) as total_categories FROM teacher_categories WHERE teacher_id = ?";
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute([$teacher_id]);
    $total_categories = $categories_stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];

    // Получаем количество файлов и общий размер ТОЛЬКО ДЛЯ ДАННОГО ПРЕПОДАВАТЕЛЯ
    $files_sql = "SELECT 
                    COUNT(*) as total_files,
                    COALESCE(SUM(file_size), 0) as total_size
                  FROM teacher_materials 
                  WHERE teacher_id = ? AND file_size > 0";
    
    $files_stmt = $pdo->prepare($files_sql);
    $files_stmt->execute([$teacher_id]);
    $files_data = $files_stmt->fetch(PDO::FETCH_ASSOC);

    $total_files = $files_data['total_files'] ?? 0;
    $total_size = $files_data['total_size'] ?? 0;

    // Конвертируем байты в мегабайты
    $total_size_mb = round($total_size / (1024 * 1024), 2);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_students' => (int)$total_students,
            'total_categories' => (int)$total_categories,
            'total_files' => (int)$total_files,
            'total_size' => $total_size_mb
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => [
            'total_students' => 0,
            'total_categories' => 0,
            'total_files' => 0,
            'total_size' => 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
