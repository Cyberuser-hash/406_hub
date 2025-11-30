<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['teacher_id']) || !isset($input['selected_students'])) {
        throw new Exception("Все поля обязательны");
    }

    $name = trim($input['name']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    $teacher_id = (int)$input['teacher_id'];
    $selected_students = $input['selected_students'];

    if (empty($name)) {
        throw new Exception("Название категории не может быть пустым");
    }

    if (empty($selected_students)) {
        throw new Exception("Выберите хотя бы одного студента");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Создаем категорию (всегда 'selected' - только для выбранных студентов)
    $category_sql = "INSERT INTO teacher_categories (name, description, teacher_id, visibility) VALUES (?, ?, ?, 'selected')";
    $category_stmt = $pdo->prepare($category_sql);
    $category_stmt->execute([$name, $description, $teacher_id]);
    
    $category_id = $pdo->lastInsertId();

    // Добавляем связи с выбранными студентами
    $student_sql = "INSERT INTO category_students (category_id, student_id) VALUES (?, ?)";
    $student_stmt = $pdo->prepare($student_sql);
    
    foreach ($selected_students as $student_id) {
        $student_stmt->execute([$category_id, $student_id]);
    }

    // Коммитим транзакцию
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Категория успешно создана и назначена студентам',
        'category_id' => (int)$category_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
