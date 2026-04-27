<?php
session_start();
include '../Backend/db_connect.php';
header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Debug function to log messages
function debugLog($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'debug.log');
}

if (!isset($_SESSION['doctor_id']) || $_SESSION['role'] !== 'Doctor') {
    debugLog('Unauthorized access attempt');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$doctorId = isset($_POST['doctor_id']) ? trim($_POST['doctor_id']) : '';
$visitDate = isset($_POST['visit_date']) ? trim($_POST['visit_date']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$notesOutcome = isset($_POST['notes_outcome']) ? trim($_POST['notes_outcome']) : '';

debugLog("Received form data: patient_id=$patientId, doctor_id=$doctorId, visit_date=$visitDate, reason=$reason, notes_outcome=$notesOutcome");

if ($patientId <= 0 || empty($doctorId) || empty($visitDate) || empty($reason)) {
    debugLog('Validation failed: Invalid input data');
    echo json_encode(['success' => false, 'message' => 'Invalid input data: Ensure all required fields are filled']);
    exit();
}

try {
    // Validate and format visit_date
    $dateTime = DateTime::createFromFormat('d/m/Y', $visitDate);
    if (!$dateTime || $dateTime->format('d/m/Y') !== $visitDate) {
        debugLog('Validation failed: Invalid date format. Expected DD/MM/YYYY');
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Expected DD/MM/YYYY']);
        exit();
    }
    // Append current time to the date
    $currentTime = date('H:i:s');
    $visitDateFormatted = $dateTime->format('Y-m-d') . ' ' . $currentTime;
    debugLog("Formatted visit_date: $visitDateFormatted");

    // Encryption setup
    $encryption_key = '0589121e755e38401cc2a3a7ed0a8ec9dc8c4db7e0f94ffc46623074b8f33525'; // 32-byte key
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');

    // Encrypt reason_for_visiting
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted_reason = openssl_encrypt(
        $reason,
        'aes-256-cbc',
        hex2bin($encryption_key),
        0,
        $iv
    );
    if ($encrypted_reason === false) {
        debugLog('Encryption failed for reason_for_visiting');
        throw new Exception('Encryption failed for reason_for_visiting');
    }
    $encrypted_reason = base64_encode($encrypted_reason . '::' . base64_encode($iv));
    debugLog('Encrypted reason_for_visiting successfully');

    // Encrypt notes_outcome (if not empty)
    $encrypted_notes = null;
    if ($notesOutcome) {
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted_notes = openssl_encrypt(
            $notesOutcome,
            'aes-256-cbc',
            hex2bin($encryption_key),
            0,
            $iv
        );
        if ($encrypted_notes === false) {
            debugLog('Encryption failed for notes_outcome');
            throw new Exception('Encryption failed for notes_outcome');
        }
        $encrypted_notes = base64_encode($encrypted_notes . '::' . base64_encode($iv));
        debugLog('Encrypted notes_outcome successfully');
    }

    // Insert into visit_records
    $stmt = $pdo->prepare("INSERT INTO visit_records (patient_id, doctor_id, visit_date, reason_for_visiting, notes_outcome, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NULL)");
    $stmt->execute([$patientId, $doctorId, $visitDateFormatted, $encrypted_reason, $encrypted_notes]);
    debugLog('Inserted into visit_records successfully');

    // Fetch patient name for the success message
    $patientNameStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
    $patientNameStmt->execute([$patientId]);
    $patientName = $patientNameStmt->fetch()['name'];
    if (!$patientName) {
        debugLog("Failed to fetch patient name for patient_id=$patientId");
        throw new Exception('Patient name not found');
    }
    debugLog("Fetched patient name: $patientName");

    echo json_encode(['success' => true, 'message' => "Visit recorded successfully for $patientName on $visitDateFormatted"]);
} catch (PDOException $e) {
    debugLog('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    debugLog('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>