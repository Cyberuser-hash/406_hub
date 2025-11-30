<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_oDIRIR__ . '/../config/database.php';

try {
    $teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 1;

    echo "=== ОТЛАДКА КАТЕГОРИЙ ===<br>";
    echo "Teacher ID: " . $teacher_id . "<br>";

    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    echo "✅ Подключение к базе: УСПЕХ<br>";

    // Проверяем существование таблиц
    $tables = ['teacher_categories', 'teacher_materials', 'users'];
    foreach ($tables as $table) {
        $check_sql = "SHOW TABLES LIKE ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$table]);
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
    }

    // Пробуем получить категории
    $categories_sql = "SELECT * FROM teacher_categories WHERE teacher_id = ?";
    $categories_stmt = $pdo->prepare($categories_sql);
    $categories_stmt->execute([$teacher_id]);
    $categories = $categories_stmt->fetchAll();

    echo "✅ Категорий найдено: " . count($categories) . "<br>";

    foreach ($categories as $category) {
        echo "📁 Категория: " . $category['name'] . " (ID: " . $category['id'] . ")<br>";
    }
    } catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage();
}
?>
