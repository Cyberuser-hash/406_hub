<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

// Проверяем загрузку файла
if (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла']);
    exit;
}

$student_login = $_POST['student_login'] ?? '';
$assignment_id = $_POST['assignment_id'] ?? '';
$assignment_title = $_POST['assignment_title'] ?? '';

if (empty($student_login) || empty($assignment_id)) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
    exit;
}

// Проверяем и создаем папку студента
$student_folder = "/var/www/html/student_work/" . $student_login;
if (!file_exists($student_folder)) {
    mkdir($student_folder, 0755, true);
}

$upload_dir = $student_folder . "/assignments/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Обрабатываем файл
$file = $_FILES['assignment_file'];
$file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file['name']);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$safe_file_name = "assignment_" . $assignment_id . "_" . time() . "." . $file_extension;
$file_path = $upload_dir . $safe_file_name;

// Проверяем тип файла
$allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'py', 'java', 'cpp', 'c', 'js', 'html', 'css', 'jpg', 'jpeg', 'png'];
if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимый тип файла. Разрешены: ' . implode(', ', $allowed_extensions)]);
    exit;
}

// Проверяем размер файла (максимум 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Файл слишком большой. Максимальный размер: 10MB']);
    exit;
}

// Перемещаем файл
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    
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

        // Обновляем статус задания в базе
        $update_sql = "INSERT INTO assignment_submissions (assignment_id, student_id, file_name, file_path, status, submitted_at) 
                       VALUES (?, (SELECT id FROM users WHERE login = ?), ?, ?, 'uploaded', NOW())
                       ON DUPLICATE KEY UPDATE file_name = ?, file_path = ?, status = 'uploaded', submitted_at = NOW(), version = version + 1";
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            $assignment_id, 
            $student_login, 
            $file_name, 
            $file_path,
            $file_name,
            $file_path
        ]);

        echo json_encode([
            'success' => true, 
            'message' => 'Файл успешно загружен!',
            'file_path' => $file_path,
            'file_name' => $file_name
        ]);

    } catch (PDOException $e) {
        // Удаляем файл если ошибка базы данных
        unlink($file_path);
        error_log("Upload database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных при сохранении информации о файле']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении файла']);
}
?>
