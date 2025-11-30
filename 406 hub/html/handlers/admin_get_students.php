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

    $sql = "SELECT u.id, u.login, u.email, u.role, u.is_active, u.created_at,
                   sp.first_name, sp.last_name, sp.phone, sp.student_group,
                   (SELECT COUNT(*) FROM assignment_submissions WHERE student_id = u.id) as assignment_count
            FROM users u
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            WHERE u.role = 'student'
            ORDER BY u.created_at DESC";

    $stmt = $pdo->query($sql);
    $students = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $students
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
