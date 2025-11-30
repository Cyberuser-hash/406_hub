<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    $student_id = 18; // psychoban234324

    echo "<h3>ДИАГНОСТИКА ДЛЯ СТУДЕНТА psychoban234324 (ID 18)</h3>";

    // Проверим студента
    $student_sql = "SELECT id, login, email FROM users WHERE id = ?";
    $student_stmt = $pdo->prepare($student_sql);
    $student_stmt->execute([$student_id]);
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

    echo "<p><strong>Студент:</strong> {$student['login']} (ID: {$student['id']})</p>";

    // Проверим связи этого студента
    $links_sql = "SELECT cs.category_id, tc.name, tc.visibility 
                  FROM category_students cs 
                  JOIN teacher_categories tc ON cs.category_id = tc.id 
                  WHERE cs.student_id = ?";
    $links_stmt = $pdo->prepare($links_sql);
    $links_stmt->execute([$student_id]);
    $links = $links_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h4>Связи студента с категориями:</h4>";
    if (empty($links)) {
        echo "<p style='color: red;'>Нет связей с категориями!</p>";
    } else {
        foreach ($links as $link) {
            echo "<p>Категория: {$link['name']} (ID: {$link['category_id']}, Видимость: {$link['visibility']})</p>";
        }
    }

    // Проверим что возвращает get_student_materials.php для этого студента
    $materials_sql = "SELECT DISTINCT
                        tc.id, 
                        tc.name, 
                        tc.visibility,
                        COUNT(tm.id) as files_count
                      FROM teacher_categories tc
                      LEFT JOIN category_students cs ON tc.id = cs.category_id
                      LEFT JOIN teacher_materials tm ON tc.id = tm.category_id
                      WHERE tc.visibility = 'all' 
                         OR (tc.visibility = 'selected' AND cs.student_id = ?)
                      GROUP BY tc.id
                      ORDER BY tc.created_at DESC";
    
    $materials_stmt = $pdo->prepare($materials_sql);
    $materials_stmt->execute([$student_id]);
    $materials = $materials_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h4>Что вернет get_student_materials.php:</h4>";
    if (empty($materials)) {
        echo "<p style='color: red;'>Не видит ни одной категории!</p>";
    } else {
        foreach ($materials as $cat) {
            echo "<p>Категория: {$cat['name']} (Файлов: {$cat['files_count']})</p>";
        }
    }

    // Проверим файлы в категории 13
    $files_sql = "SELECT id, title FROM teacher_materials WHERE category_id = 13";
    $files = $pdo->query($files_sql)->fetchAll(PDO::FETCH_ASSOC);

    echo "<h4>Файлы в категории syt22 (ID 13):</h4>";
    if (empty($files)) {
        echo "<p style='color: red;'>Нет файлов в категории!</p>";
    } else {
        foreach ($files as $file) {
            echo "<p>Файл: {$file['title']} (ID: {$file['id']})</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
}
?>
