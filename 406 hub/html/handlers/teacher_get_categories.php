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

    // Получаем категории преподавателя
    $categories_sql = "SELECT 
                        tc.id, 
                        tc.name, 
                        tc.description, 
                        tc.visibility,
                        tc.created_at
                      FROM teacher_categories tc 
                      WHERE tc.teacher_id = ? 
                      ORDER BY tc.created_at DESC";
    
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute([$teacher_id]);
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Для каждой категории получаем файлы
    foreach ($categories as &$category) {
        $files_sql = "SELECT 
                        tm.id,
                        tm.title,
                        tm.description,
                        tm.file_name,
                        tm.file_path,
                        tm.file_size,
                        tm.type,
                        tm.uploaded_at
                      FROM teacher_materials tm 
                      WHERE tm.category_id = ? 
                      ORDER BY tm.uploaded_at DESC";
        
        $files_stmt = $pdo->prepare($files_sql);
        $files_stmt->execute([$category['id']]);
        $category['files'] = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
