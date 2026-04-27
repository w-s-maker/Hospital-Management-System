<?php
include 'db_connect.php';

try {
    // Fetch the 4 most recent patients
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, contact_number, medical_history, created_at
        FROM patients
        ORDER BY created_at DESC
        LIMIT 4
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the raw data to verify
    error_log("Fetched patients: " . print_r($patients, true));

    // Format patients data
    $formattedPatients = [];
    foreach ($patients as $patient) {
        $formattedPatients[] = [
            'id' => $patient['id'],
            'full_name' => $patient['first_name'] . ' ' . $patient['last_name'],
            'email' => $patient['email'],
            'contact_number' => $patient['contact_number'],
            'medical_history' => $patient['medical_history'] ?: 'N/A', // Default if null
            'created_at' => $patient['created_at'] // Include for debugging
        ];
    }

    // Debug: Log the formatted response
    error_log("Formatted patients: " . print_r($formattedPatients, true));

    $response = [
        'patients' => $formattedPatients,
        'error' => null
    ];
} catch (PDOException $e) {
    $response = ['error' => "Database error: " . $e->getMessage()];
    error_log("Database error: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
?>