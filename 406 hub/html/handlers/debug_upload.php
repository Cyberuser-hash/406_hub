<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    echo "<h2>=== ДЕБАГ ЗАГРУЗКИ ФАЙЛОВ ===</h2>";
    
    // Проверяем доступность папки
    $teacher_id = 11;
    $materials_folder = "/var/www/html/teacher_materials/" . $teacher_id . "/";
    
    echo "1. Проверяем папку: " . $materials_folder . "<br>";
    
    if (!file_exists($materials_folder)) {
        echo "❌ Папка не существует. Пытаемся создать...<br>";
        if (mkdir($materials_folder, 0755, true)) {
            echo "✅ Папка создана успешно<br>";
        } else {
            echo "❌ Не удалось создать папку<br>";
        }
    } else {
        echo "✅ Папка существует<br>";
    }
    
    // Проверяем права доступа
    echo "2. Права доступа к папке: " . substr(sprintf('%o', fileperms($materials_folder)), -4) . "<br>";
    
    // Проверяем владельца папки
    $owner = posix_getpwuid(fileowner($materials_folder));
    echo "3. Владелец папки: " . $owner['name'] . "<br>";
    
    // Проверяем подключение к базе
    echo "4. Проверяем подключение к базе...<br>";
    $db = new DatabaseConfig();
    $pdo = $db->getConnection();
    echo "✅ Подключение к базе успешно<br>";
    
    // Проверяем существование категорий
    $categories_sql = "SELECT * FROM teacher_categories WHERE teacher_id = ?";
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute([$teacher_id]);
    $categories = $categories_stmt->fetchAll();
    
    echo "5. Категории преподавателя (ID: $teacher_id): " . count($categories) . "<br>";
    foreach ($categories as $cat) {
        echo "&nbsp;&nbsp;- " . $cat['name'] . " (ID: " . $cat['id'] . ")<br>";
    }
    
    // Проверяем настройки загрузки файлов в PHP
    echo "<h3>Настройки PHP для загрузки файлов:</h3>";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
    echo "post_max_size: " . ini_get('post_max_size') . "<br>";
    echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
    echo "memory_limit: " . ini_get('memory_limit') . "<br>";
    
    echo "<h3>✅ Проверка завершена</h3>";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>
