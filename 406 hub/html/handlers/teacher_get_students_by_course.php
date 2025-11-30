<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$course_id = $_GET['course_id'] ?? '';

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
                u.id, u.login, u.email, u.is_active,
                sp.first_name, sp.last_name, sp.student_group,
                (SELECT COUNT(*) FROM assignment_submissions s WHERE s.student_id = u.id) as assignments_count
            FROM course_users cu
            JOIN users u ON cu.user_id = u.id
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            WHERE cu.course_id = ? AND u.role = 'student'
            ORDER BY sp.first_name, sp.last_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $students
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
