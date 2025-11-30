<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    // Проверяем ID файла
    if (!isset($_GET['file_id']) || empty($_GET['file_id'])) {
        throw new Exception("ID файла не указан");
    }

    $file_id = (int)$_GET['file_id'];
    
    if ($file_id <= 0) {
        throw new Exception("Неверный ID файла");
    }

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    // Получаем информацию о файле
    $sql = "SELECT file_name, file_path, title FROM teacher_materials WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$file_id]);
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        throw new Exception("Файл не найден");
    }

    $file_path = $file['file_path'];
    $original_name = $file['file_name'];
    $title = $file['title'];

    // Проверяем существование файла
    if (!file_exists($file_path)) {
        throw new Exception("Файл не найден на сервере: " . $file_path);
    }

    // Проверяем права доступа
    if (!is_readable($file_path)) {
        throw new Exception("Нет доступа к файлу");
    }

    // Устанавливаем заголовки для скачивания
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $original_name . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));

    // Очищаем буфер вывода
    ob_clean();
    flush();

    // Читаем и отправляем файл
    readfile($file_path);
    exit;

} catch (Exception $e) {
    // Логируем ошибку
    error_log("Download error: " . $e->getMessage());
    
    // Показываем понятное сообщение пользователю
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при скачивании файла: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
