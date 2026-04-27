<?php
session_start();
include '../Backend/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id']) || $_SESSION['role'] !== 'Doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id']; // From users.id
$appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$appointmentDate = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
$appointmentTime = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($appointmentId <= 0 || $patientId <= 0 || $doctorId <= 0 || empty($appointmentDate) || empty($appointmentTime) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

try {
    // Convert UI date (DD/MM/YYYY) to database format (YYYY-MM-DD)
    $dateTime = DateTime::createFromFormat('d/m/Y', $appointmentDate);
    if (!$dateTime || $dateTime->format('d/m/Y') !== $appointmentDate) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Expected DD/MM/YYYY (e.g., 15/04/2025)']);
        exit();
    }
    $appointmentDate = $dateTime->format('Y-m-d');

    // Validate time format (HH:MM) and convert to HH:MM:SS
    $timeParts = explode(':', $appointmentTime);
    if (count($timeParts) !== 2 || !is_numeric($timeParts[0]) || !is_numeric($timeParts[1]) ||
        (int)$timeParts[0] < 0 || (int)$timeParts[0] > 23 || (int)$timeParts[1] < 0 || (int)$timeParts[1] > 59) {
        echo json_encode(['success' => false, 'message' => 'Invalid time format. Expected HH:MM (e.g., 14:40)']);
        exit();
    }
    $appointmentTime = $appointmentTime . ':00';

    // Begin transaction
    $pdo->beginTransaction();

    // Insert the new appointment (use modified_by instead of created_by)
    $stmt = $pdo->prepare("
        INSERT INTO appointments (id, patient_id, doctor_id, appointment_date, appointment_time, status, modified_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $appointmentId,
        $patientId,
        $doctorId,
        $appointmentDate,
        $appointmentTime,
        $status,
        'Doctor'
    ]);

    // Fetch the corresponding users.id for the patient
    $patientStmt = $pdo->prepare("SELECT id FROM users WHERE patient_id = ?");
    $patientStmt->execute([$patientId]);
    $patientUserId = $patientStmt->fetchColumn();
    if (!$patientUserId) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'User ID not found for patient']);
        exit();
    }

    // Fetch the corresponding staff_id for the doctor, then find users.id
    $doctorStmt = $pdo->prepare("SELECT staff_id FROM doctors WHERE id = ?");
    $doctorStmt->execute([$doctorId]);
    $staffId = $doctorStmt->fetchColumn();
    if (!$staffId) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Staff ID not found for doctor']);
        exit();
    }

    $doctorUserStmt = $pdo->prepare("SELECT id FROM users WHERE staff_id = ?");
    $doctorUserStmt->execute([$staffId]);
    $doctorUserId = $doctorUserStmt->fetchColumn();
    if (!$doctorUserId) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'User ID not found for doctor']);
        exit();
    }

    // Fetch patient and doctor names for notifications
    $patientStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
    $patientStmt->execute([$patientId]);
    $patientName = $patientStmt->fetch()['name'];

    $doctorStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM doctors WHERE id = ?");
    $doctorStmt->execute([$doctorId]);
    $doctorName = $doctorStmt->fetch()['name'];

    // Format the date and time for notifications (dd MMM yyyy at HH:mm)
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $appointmentDate . ' ' . $appointmentTime);
    if (!$dateTime) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Invalid date or time format']);
        exit();
    }
    $appointmentIdFormatted = 'APT' . str_pad($appointmentId, 4, '0', STR_PAD_LEFT);
    $dateFormatted = $dateTime->format('d M Y');
    $timeFormatted = $dateTime->format('H:i');

    // Prepare notification messages
    $baseMessage = "Your appointment $appointmentIdFormatted has been scheduled on $dateFormatted at $timeFormatted.";
    $doctorBaseMessage = "Appointment $appointmentIdFormatted for $patientName has been scheduled on $dateFormatted at $timeFormatted.";

    $patientMessage = $message ? "$baseMessage\n\nAdditional Note: $message" : $baseMessage;
    $doctorMessage = $message ? "$doctorBaseMessage\n\nAdditional Note: $message" : $doctorBaseMessage;

    // Insert notifications for patient and doctor using users.id
    $notifyStmt = $pdo->prepare("
        INSERT INTO notifications (recipient_type, recipient_id, message, notification_type, is_read, created_at)
        VALUES (?, ?, ?, 'Alert', 0, NOW())
    ");
    $notifyStmt->execute(['Patient', $patientUserId, $patientMessage]);
    $notifyStmt->execute(['Doctor', $doctorUserId, $doctorMessage]);

    // Log the action in audit_logs
    $details = "Appointment created by Doctor ID: $doctorId. Values: Patient ID: $patientId, Date: $appointmentDate, Time: $appointmentTime, Status: $status";
    if ($message) {
        $details .= ", Message: $message";
    }
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, timestamp, details)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([
        $userId,
        'Appointment Created',
        'appointments',
        $appointmentId,
        $details
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Appointment added successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>