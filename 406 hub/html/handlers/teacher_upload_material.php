<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    // Проверяем обязательные поля
    $required_fields = ['category_id', 'title', 'teacher_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Обязательное поле '$field' не заполнено");
        }
    }

    // Проверяем загрузку файла
    if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Ошибка загрузки файла. Код ошибки: " . $_FILES['material_file']['error']);
    }

    // Извлекаем данные
    $category_id = (int)$_POST['category_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $title = trim($_POST['title']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Валидация
    if ($category_id <= 0) {
        throw new Exception("Неверный ID категории");
    }

    if ($teacher_id <= 0) {
        throw new Exception("Неверный ID преподавателя");
    }

    // Обрабатываем файл
    $file = $_FILES['material_file'];
    $file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file['name']);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Проверяем тип файла
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4', 'avi', 'mov'];
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Недопустимый тип файла. Разрешены: " . implode(', ', $allowed_extensions));
    }

    // Проверяем размер файла
    $max_file_size = 50 * 1024 * 1024;
    if ($file['size'] > $max_file_size) {
        throw new Exception("Файл слишком большой. Максимальный размер: 50MB");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Проверяем существование категории и принадлежность преподавателю
    $category_sql = "SELECT id FROM teacher_categories WHERE id = ? AND teacher_id = ?";
    $category_stmt = $pdo->prepare($category_sql);
    $category_stmt->execute([$category_id, $teacher_id]);
    if ($category_stmt->rowCount() === 0) {
        throw new Exception("Категория не найдена или у вас нет доступа");
    }

    // Создаем папку для файлов преподавателя
    $materials_folder = "/var/www/html/teacher_materials/" . $teacher_id . "/";
    if (!file_exists($materials_folder)) {
        if (!mkdir($materials_folder, 0755, true)) {
            throw new Exception("Не удалось создать папку для материалов");
        }
    }

    // Генерируем уникальное имя файла
    $safe_file_name = "material_" . time() . "_" . uniqid() . "." . $file_extension;
    $file_path = $materials_folder . $safe_file_name;

    // Перемещаем файл
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception("Не удалось сохранить файл");
    }

    // ИСПРАВЛЕННЫЙ SQL ЗАПРОС - используем NULL для student_id
    $material_sql = "INSERT INTO teacher_materials
                    (category_id, student_id, title, description, file_name, file_path, file_size, type, teacher_id, uploaded_at)
                    VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $material_stmt = $pdo->prepare($material_sql);
    $material_stmt->execute([
        $category_id,
        $title,
        $description,
        $file_name,
        $file_path,
        $file['size'],
        $file_extension,
        $teacher_id
    ]);

    $material_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Задание успешно загружено',
        'material_id' => (int)$material_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Удаляем файл в случае ошибки
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
