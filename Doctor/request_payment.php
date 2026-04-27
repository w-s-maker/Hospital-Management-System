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
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.00;
$paymentReason = isset($_POST['payment_reason']) ? trim($_POST['payment_reason']) : '';

debugLog("Received payment request data: patient_id=$patientId, amount=$amount, payment_reason=$paymentReason");

if ($patientId <= 0 || $amount <= 0) {
    debugLog('Validation failed: Invalid input data');
    echo json_encode(['success' => false, 'message' => 'Invalid input data: Ensure all required fields are filled']);
    exit();
}

try {
    // Fetch the latest appointment_id for the patient
    $appointmentStmt = $pdo->prepare("SELECT id FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC LIMIT 1");
    $appointmentStmt->execute([$patientId]);
    $appointmentId = $appointmentStmt->fetchColumn();
    if ($appointmentId === false) {
        debugLog("No appointment found for patient_id=$patientId");
        $appointmentId = null; // Allow NULL if no appointment exists
    } else {
        debugLog("Fetched appointment_id: $appointmentId");
    }

    // Generate the invoice_number
    $lastInvoiceStmt = $pdo->query("SELECT invoice_number FROM billing ORDER BY id DESC LIMIT 1");
    $lastInvoice = $lastInvoiceStmt->fetchColumn();
    if ($lastInvoice) {
        // Extract the number part (e.g., "001" from "#INV-01-001-2025")
        preg_match('/#INV-01-(\d{3})-2025/', $lastInvoice, $matches);
        $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
        $newNumber = $lastNumber + 1;
        $invoiceNumber = sprintf('#INV-01-%03d-2025', $newNumber);
    } else {
        $invoiceNumber = '#INV-01-001-2025'; // First invoice
    }
    debugLog("Generated invoice_number: $invoiceNumber");

    // Get the current date and time for transaction_date
    $transactionDate = date('Y-m-d H:i:s');
    debugLog("Transaction date: $transactionDate");

    // Insert into billing table
    $stmt = $pdo->prepare("INSERT INTO billing (patient_id, appointment_id, invoice_number, amount, payment_method, payment_status, transaction_token, transaction_date, created_at, updated_at) VALUES (?, ?, ?, ?, NULL, 'Pending', NULL, ?, NOW(), NULL)");
    $stmt->execute([$patientId, $appointmentId, $invoiceNumber, $amount, $transactionDate]);
    debugLog('Inserted into billing successfully');

    // Fetch the patient's user_id for notifications
    $patientStmt = $pdo->prepare("SELECT id FROM users WHERE patient_id = ?");
    $patientStmt->execute([$patientId]);
    $patientUserId = $patientStmt->fetchColumn();
    if ($patientUserId === false) {
        debugLog("Failed to fetch patient user ID for patient_id=$patientId");
        throw new Exception('Patient user ID not found');
    }
    debugLog("Fetched patient user ID: $patientUserId");

    // Fetch patient name for the notification message
    $patientNameStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
    $patientNameStmt->execute([$patientId]);
    $patientName = $patientNameStmt->fetch()['name'];
    if (!$patientName) {
        debugLog("Failed to fetch patient name for patient_id=$patientId");
        throw new Exception('Patient name not found');
    }
    debugLog("Fetched patient name: $patientName");

    // Insert notification for the patient
    $message = "Dear $patientName, you have a pending bill of $amount for $paymentReason. Please settle it at your earliest convenience.";
    $notifyStmt = $pdo->prepare("INSERT INTO notifications (recipient_type, recipient_id, message, notification_type, is_read, created_at) VALUES (?, ?, ?, 'Alert', 0, NOW())");
    $notifyStmt->execute(['Patient', $patientUserId, $message]);
    debugLog('Inserted notification successfully');

    echo json_encode(['success' => true, 'message' => "Payment invoice #$invoiceNumber requested successfully for $patientName."]);
} catch (PDOException $e) {
    debugLog('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    debugLog('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>