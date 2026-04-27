<?php
require_once 'db_connect.php';

try {
    $query = "SELECT DISTINCT department FROM doctors WHERE department IS NOT NULL ORDER BY department";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json');
    echo json_encode(['departments' => $departments]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
exit;