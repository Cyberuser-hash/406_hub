<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 1;

    echo "<h2>=== ОТЛАДКА КАТЕГОРИЙ ===</h2>";
    echo "Teacher ID: " . $teacher_id . "<br>";

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    echo "✅ Подключение к базе: УСПЕХ<br>";

    // Проверяем существование таблиц (исправленный синтаксис для MariaDB)
    $tables = ['teacher_categories', 'teacher_materials', 'users'];
    foreach ($tables as $table) {
        $check_sql = "SHOW TABLES LIKE '$table'";
        $check_stmt = $pdo->query($check_sql);
        if ($check_stmt->rowCount() > 0) {
            echo "✅ Таблица $table: СУЩЕСТВУЕТ<br>";
        } else {
            echo "❌ Таблица $table: НЕ СУЩЕСТВУЕТ<br>";
        }
    }

    // Проверяем существование преподавателя
    $teacher_sql = "SELECT id, login FROM users WHERE id = ? AND role = 'teacher'";
    $teacher_stmt = $pdo->prepare($teacher_sql);
    $teacher_stmt->execute([$teacher_id]);
    
    if ($teacher_stmt->rowCount() > 0) {
        $teacher = $teacher_stmt->fetch();
        echo "✅ Преподаватель найден: " . $teacher['login'] . " (ID: " . $teacher['id'] . ")<br>";
    } else {
        echo "❌ Преподаватель не найден<br>";
        
        // Покажем всех преподавателей
        $all_teachers_sql = "SELECT id, login FROM users WHERE role = 'teacher'";
        $all_teachers_stmt = $pdo->prepare($all_teachers_sql);
        $all_teachers_stmt->execute();
        $teachers = $all_teachers_stmt->fetchAll();
        
        echo "Доступные преподаватели:<br>";
        foreach ($teachers as $t) {
            echo "- " . $t['login'] . " (ID: " . $t['id'] . ")<br>";
        }
    }

    // Пробуем получить категории
    $categories_sql = "SELECT * FROM teacher_categories WHERE teacher_id = ?";
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute([$teacher_id]);
    $categories = $categories_stmt->fetchAll();

    echo "📁 Категорий найдено: " . count($categories) . "<br>";

    foreach ($categories as $category) {
        echo "&nbsp;&nbsp;📂 Категория: " . $category['name'] . " (ID: " . $category['id'] . ")<br>";
        
        // Получаем файлы для категории
        $files_sql = "SELECT * FROM teacher_materials WHERE category_id = ?";
        $files_stmt = $pdo->prepare($files_sql);
        $files_stmt->execute([$category['id']]);
        $files = $files_stmt->fetchAll();
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;📄 Файлов в категории: " . count($files) . "<br>";
        foreach ($files as $file) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;📎 " . $file['title'] . " (" . $file['file_name'] . ")<br>";
        }
    }

    if (count($categories) === 0) {
        echo "ℹ️ Категорий нет. Создайте первую категорию через панель преподавателя.<br>";
    }

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
    echo "<br>Подробности: " . $e->getTraceAsString();
}
?>
