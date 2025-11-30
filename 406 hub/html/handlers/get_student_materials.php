<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    session_start();
    
    // СПОСОБ 1: Через сессию (если настроена авторизация)
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student') {
        $student_id = (int)$_SESSION['user_id'];
    }
    // СПОСОБ 2: Через GET параметр (для тестирования)
    else if (isset($_GET['student_id'])) {
        $student_id = (int)$_GET['student_id'];
    }
    // СПОСОБ 3: Через POST или другой метод
    else {
        // Если нет сессии, используем тестового студента для демонстрации
        $student_id = 18; // psychoban234324
    }

    if ($student_id <= 0) {
        throw new Exception("Неверный ID студента");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Проверим что студент существует
    $student_check = "SELECT id, login FROM users WHERE id = ? AND role = 'student'";
    $student_stmt = $pdo->prepare($student_check);
    $student_stmt->execute([$student_id]);
    
    if ($student_stmt->rowCount() === 0) {
        throw new Exception("Студент не найден");
    }

    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    // Получаем категории, доступные студенту
    $categories_sql = "SELECT 
                        tc.id, 
                        tc.name, 
                        tc.description,
                        tc.teacher_id,
                        tc.created_at,
                        tc.visibility,
                        u.login as teacher_login
                      FROM teacher_categories tc
                      LEFT JOIN category_students cs ON tc.id = cs.category_id
                      LEFT JOIN users u ON tc.teacher_id = u.id
                      WHERE tc.visibility = 'all' 
                         OR (tc.visibility = 'selected' AND cs.student_id = ?)
                      ORDER BY tc.created_at DESC";
    
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute([$student_id]);
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
        'student' => [
            'id' => $student['id'],
            'login' => $student['login']
        ],
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
