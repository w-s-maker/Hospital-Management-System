<?php
include 'db_connect.php'; 

try {
    $stmt = $pdo->query("SELECT DISTINCT department FROM doctors WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($departments)) {
        throw new Exception("No departments found in the database.");
    }

    $response = [
        'success' => true,
        'departments' => $departments
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>