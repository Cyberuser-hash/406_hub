<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

try {
    $db = new DatabaseConfig();
    $pdo = $db->getConnection();

    $sql = "SELECT 
                tm.*,
                u_student.login as student_login,
                u_student.email as student_email,
                u_teacher.login as teacher_login
            FROM teacher_materials tm
            JOIN users u_student ON tm.student_id = u_student.id
            LEFT JOIN users u_teacher ON tm.teacher_id = u_teacher.id
            ORDER BY tm.uploaded_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $materials = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $materials
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
