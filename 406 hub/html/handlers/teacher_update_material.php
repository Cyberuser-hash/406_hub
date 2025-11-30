<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

try {
    $material_id = $_POST['material_id'] ?? 0;
    
    if ($material_id <= 0) {
        throw new Exception("Неверный ID материала");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Получаем текущие данные материала
    $current_sql = "SELECT * FROM teacher_materials WHERE id = ?";
    $current_stmt = $pdo->prepare($current_sql);
    $current_stmt->execute([$material_id]);
    
    if ($current_stmt->rowCount() === 0) {
        throw new Exception("Материал не найден");
    }

    $current_material = $current_stmt->fetch();
    $old_file_path = $current_material['file_path'];

    // Подготавливаем данные для обновления
    $title = trim($_POST['title']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $signature = isset($_POST['signature']) ? trim($_POST['signature']) : '';
    $is_visible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 1;
    $can_download = isset($_POST['can_download']) ? (int)$_POST['can_download'] : 1;

    $update_fields = [
        'title = ?',
        'description = ?',
        'signature = ?',
        'is_visible = ?',
        'can_download = ?',
        'updated_at = NOW()'
    ];
    $params = [
        $title,
        $description,
        $signature,
        $is_visible,
        $can_download
    ];

    // Обрабатываем новый файл если загружен
    $new_file_path = $old_file_path;
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['material_file'];
        
        // Проверяем тип файла
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4', 'avi', 'mov'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Недопустимый тип файла");
        }

        // Генерируем новое имя файла
        $new_file_name = "material_" . time() . "_" . uniqid() . "." . $file_extension;
        $new_file_path = dirname($old_file_path) . "/" . $new_file_name;

        // Перемещаем новый файл
        if (!move_uploaded_file($file['tmp_name'], $new_file_path)) {
            throw new Exception("Не удалось сохранить новый файл");
        }

        // Удаляем старый файл
        if (file_exists($old_file_path)) {
            unlink($old_file_path);
        }

        $update_fields[] = 'file_name = ?';
        $update_fields[] = 'file_path = ?';
        $update_fields[] = 'file_size = ?';
        
        $params[] = $file['name'];
        $params[] = $new_file_path;
        $params[] = $file['size'];
    }

    $params[] = $material_id;

    // Обновляем запись в базе
    $update_sql = "UPDATE teacher_materials SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно обновлен'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Удаляем новый файл в случае ошибки
    if (isset($new_file_path) && $new_file_path !== $old_file_path && file_exists($new_file_path)) {
        unlink($new_file_path);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
