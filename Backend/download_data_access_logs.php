<?php
// Include the database connection
require_once 'db_connect.php';

// Fetch all data access logs
$query = "
    SELECT 
        CONCAT(u.first_name, ' ', u.last_name) AS user_name,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        dal.access_time,
        dal.action
    FROM data_access_logs dal
    JOIN users u ON dal.user_id = u.id
    JOIN patients p ON dal.patient_id = p.id
    ORDER BY dal.access_time DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$logs = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="data_access_logs.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['User', 'Patient Name', 'Access Time', 'Action']);

// Write data rows
foreach ($logs as $log) {
    fputcsv($output, [
        $log['user_name'],
        $log['patient_name'],
        $log['access_time'],
        $log['action']
    ]);
}

// Close the output stream
fclose($output);
exit;