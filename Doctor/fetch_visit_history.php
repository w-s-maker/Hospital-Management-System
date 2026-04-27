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

$patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($patientId <= 0) {
    debugLog('Invalid patient_id');
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit();
}

// Fetch the doctor's staff_id using the user_id from the session
$doctorId = $_SESSION['doctor_id'];
try {
    $stmt = $pdo->prepare("SELECT staff_id FROM users WHERE id = ?");
    $stmt->execute([$doctorId]);
    $staffId = $stmt->fetchColumn();

    if (!$staffId) {
        debugLog("No staff_id found for user_id: $doctorId");
        echo json_encode(['success' => false, 'message' => 'Doctor staff ID not found']);
        exit();
    }
    debugLog("Fetched doctor staff_id: $staffId");
} catch (PDOException $e) {
    debugLog("Error fetching staff_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching doctor staff ID']);
    exit();
}

try {
    // Fetch visit history for the patient with the specific doctor
    $stmt = $pdo->prepare("SELECT visit_date, reason_for_visiting, notes_outcome 
                           FROM visit_records 
                           WHERE patient_id = ? AND doctor_id = ? 
                           ORDER BY visit_date DESC");
    $stmt->execute([$patientId, $staffId]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $decryptedVisits = [];
    $encryption_key = '0589121e755e38401cc2a3a7ed0a8ec9dc8c4db7e0f94ffc46623074b8f33525'; // 32-byte key

    foreach ($visits as $visit) {
        // Decrypt reason_for_visiting
        $reason_parts = explode('::', base64_decode($visit['reason_for_visiting']));
        if (count($reason_parts) !== 2) {
            debugLog('Invalid encrypted reason_for_visiting format for visit_date: ' . $visit['visit_date']);
            continue;
        }
        $encrypted_reason = $reason_parts[0];
        $iv = base64_decode($reason_parts[1]);
        $decrypted_reason = openssl_decrypt(
            $encrypted_reason,
            'aes-256-cbc',
            hex2bin($encryption_key),
            0,
            $iv
        );
        if ($decrypted_reason === false) {
            debugLog('Decryption failed for reason_for_visiting for visit_date: ' . $visit['visit_date']);
            continue;
        }

        // Decrypt notes_outcome (if not empty)
        $decrypted_notes = null;
        if ($visit['notes_outcome']) {
            $notes_parts = explode('::', base64_decode($visit['notes_outcome']));
            if (count($notes_parts) === 2) {
                $encrypted_notes = $notes_parts[0];
                $iv = base64_decode($notes_parts[1]);
                $decrypted_notes = openssl_decrypt(
                    $encrypted_notes,
                    'aes-256-cbc',
                    hex2bin($encryption_key),
                    0,
                    $iv
                );
                if ($decrypted_notes === false) {
                    debugLog('Decryption failed for notes_outcome for visit_date: ' . $visit['visit_date']);
                    $decrypted_notes = null;
                }
            }
        }

        $decryptedVisits[] = [
            'visit_date' => $visit['visit_date'],
            'reason_for_visiting' => $decrypted_reason,
            'notes_outcome' => $decrypted_notes
        ];
    }

    debugLog("Fetched and decrypted " . count($decryptedVisits) . " visits for patient_id=$patientId and doctor_id=$staffId");
    echo json_encode(['success' => true, 'visits' => $decryptedVisits]);
} catch (PDOException $e) {
    debugLog('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>