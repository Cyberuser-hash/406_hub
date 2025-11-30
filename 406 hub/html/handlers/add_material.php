<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$module_id = $_POST['module_id'] ?? '';
$type = $_POST['type'] ?? '';
$title = $_POST['title'] ?? '';

if (empty($module_id) || empty($type) || empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
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

    // Подготовка данных в зависимости от типа материала
    $content = '';
    $file_path = '';

    if ($type === 'text') {
        $content = $_POST['content'] ?? '';
    } elseif ($type === 'video') {
        $content = $_POST['video_url'] ?? '';
    } elseif ($type === 'link') {
        $content = $_POST['link_url'] ?? '';
    } elseif ($type === 'file') {
        // Обработка загрузки файла
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "/var/www/html/course_materials/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $_FILES['material_file']['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $file_path)) {
                $content = $file_path;
            }
        }
    }

    $sql = "INSERT INTO course_materials (module_id, type, title, description, content, order_index) 
            VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $module_id,
        $type,
        $title,
        $_POST['description'] ?? '',
        $content
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Материал успешно добавлен'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
