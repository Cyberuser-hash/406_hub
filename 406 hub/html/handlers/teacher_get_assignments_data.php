<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

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

    // Статистика по назначениям
    $stats_sql = "SELECT 
                    COUNT(DISTINCT u.id) as total_students,
                    COUNT(DISTINCT c.id) as total_courses,
                    COUNT(DISTINCT cu.id) as total_assignments,
                    ROUND(COUNT(DISTINCT cu.id) * 100.0 / GREATEST(COUNT(DISTINCT u.id) * COUNT(DISTINCT c.id), 1)) as assignment_rate
                  FROM users u
                  CROSS JOIN courses c
                  LEFT JOIN course_users cu ON u.id = cu.user_id AND c.id = cu.course_id
                  WHERE u.role = 'student' AND c.is_active = 1";

    $stats = $pdo->query($stats_sql)->fetch();

    // Популярные курсы
    $popular_sql = "SELECT 
                    c.title,
                    COUNT(cu.user_id) as student_count
                  FROM courses c
                  LEFT JOIN course_users cu ON c.id = cu.course_id
                  GROUP BY c.id
                  ORDER BY student_count DESC
                  LIMIT 5";

    $popular_courses = $pdo->query($popular_sql)->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'popular_courses' => $popular_courses
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
