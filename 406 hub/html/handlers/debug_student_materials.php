<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    // Тестовый студент ID (замените на реальный ID студента)
    $test_student_id = 1; 
    
    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // 1. Проверим существование студента
    $student_sql = "SELECT id, login, role FROM users WHERE id = ? AND role = 'student'";
    $student_stmt = $pdo->prepare($student_sql);
    $student_stmt->execute([$test_student_id]);
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Студент с ID $test_student_id не найден");
    }

    // 2. Проверим категории с visibility='all'
    $all_categories_sql = "SELECT id, name, teacher_id FROM teacher_categories WHERE visibility = 'all'";
    $all_categories = $pdo->query($all_categories_sql)->fetchAll(PDO::FETCH_ASSOC);

    // 3. Проверим категории с visibility='selected' для этого студента
    $selected_categories_sql = "SELECT tc.id, tc.name, tc.teacher_id 
                               FROM teacher_categories tc
                               JOIN category_students cs ON tc.id = cs.category_id
                               WHERE tc.visibility = 'selected' AND cs.student_id = ?";
    $selected_stmt = $pdo->prepare($selected_categories_sql);
    $selected_stmt->execute([$test_student_id]);
    $selected_categories = $selected_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Объединим все доступные категории
    $all_accessible_categories = array_merge($all_categories, $selected_categories);
    
    // 5. Проверим файлы в доступных категориях
    $files_data = [];
    if (!empty($all_accessible_categories)) {
        $category_ids = array_column($all_accessible_categories, 'id');
        $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
        
        $files_sql = "SELECT tm.id, tm.title, tm.category_id, tc.name as category_name 
                      FROM teacher_materials tm
                      JOIN teacher_categories tc ON tm.category_id = tc.id
                      WHERE tm.category_id IN ($placeholders)";
        $files_stmt = $pdo->prepare($files_sql);
        $files_stmt->execute($category_ids);
        $files_data = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'debug_info' => [
            'student' => $student,
            'all_categories_count' => count($all_categories),
            'all_categories' => $all_categories,
            'selected_categories_count' => count($selected_categories),
            'selected_categories' => $selected_categories,
            'total_accessible_categories' => count($all_accessible_categories),
            'accessible_categories' => $all_accessible_categories,
            'files_count' => count($files_data),
            'files' => $files_data,
            'queries' => [
                'all_categories' => $all_categories_sql,
                'selected_categories' => $selected_categories_sql
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
