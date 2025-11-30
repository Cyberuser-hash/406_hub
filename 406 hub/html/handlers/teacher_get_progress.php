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

    $sql = "SELECT 
                u.id as student_id,
                u.login as student_login,
                CONCAT(sp.first_name, ' ', sp.last_name) as student_name,
                c.id as course_id,
                c.title as course_title,
                COUNT(DISTINCT a.id) as total_assignments,
                COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.id END) as completed_assignments,
                ROUND(
                    COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.id END) * 100.0 / 
                    GREATEST(COUNT(DISTINCT a.id), 1)
                ) as progress,
                ROUND(AVG(s.score)) as average_score
            FROM course_users cu
            JOIN users u ON cu.user_id = u.id
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            JOIN courses c ON cu.course_id = c.id
            LEFT JOIN course_modules cm ON c.id = cm.course_id
            LEFT JOIN assignments a ON cm.id = a.module_id
            LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = u.id
            WHERE u.role = 'student'
            GROUP BY u.id, c.id
            ORDER BY student_name, c.title";

    $stmt = $pdo->query($sql);
    $progress_data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $progress_data
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
