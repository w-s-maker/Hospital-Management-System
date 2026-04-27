<?php
include 'db_connect.php';

try {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $appointmentDate = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
        $appointmentTime = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        // Log incoming data
        error_log("POST Data: " . json_encode($_POST));

        // Validate required fields
        if ($appointmentId <= 0 || $patientId <= 0 || $doctorId <= 0 || empty($appointmentDate) || empty($appointmentTime) || empty($status)) {
            $missing = [];
            if ($appointmentId <= 0) $missing[] = 'appointment_id';
            if ($patientId <= 0) $missing[] = 'patient_id';
            if ($doctorId <= 0) $missing[] = 'doctor_id';
            if (empty($appointmentDate)) $missing[] = 'appointment_date';
            if (empty($appointmentTime)) $missing[] = 'appointment_time';
            if (empty($status)) $missing[] = 'status';
            throw new Exception('Missing fields: ' . implode(', ', $missing));
        }

        // Validate date and time format
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', "$appointmentDate $appointmentTime");
        if (!$dateTime || $dateTime->format('Y-m-d H:i') !== "$appointmentDate $appointmentTime") {
            throw new Exception("Invalid date/time: $appointmentDate $appointmentTime");
        }

        // Validate status
        if (!in_array($status, ['Scheduled', 'Completed', 'Cancelled'])) {
            throw new Exception("Invalid status value. Must be Scheduled, Completed, or Cancelled.");
        }

        // Check if the appointment exists
        $checkStmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $checkStmt->execute([$appointmentId]);
        $appointment = $checkStmt->fetch();
        if (!$appointment) {
            throw new Exception("Appointment not found.");
        }

        // Update the appointment
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET patient_id = ?, doctor_id = ?, appointment_date = ?, appointment_time = ?, status = ?, updated_at = NOW(), modified_by = 'Admin'
            WHERE id = ?
        ");
        $stmt->execute([$patientId, $doctorId, $appointmentDate, $appointmentTime, $status, $appointmentId]);

        // Determine the new status for doctor_schedule based on appointment status
        $scheduleStatus = ($status === 'Scheduled') ? 'Busy' : 'Available';

        // Update the corresponding doctor_schedule record
        $scheduleStmt = $pdo->prepare("
            UPDATE doctor_schedule 
            SET doctor_id = ?, schedule_date = ?, start_time = ?, status = ?, updated_at = NOW()
            WHERE appointment_id = ?
        ");
        $scheduleStmt->execute([$doctorId, $appointmentDate, $appointmentTime, $scheduleStatus, $appointmentId]);

        // Check if the doctor_schedule record was updated
        if ($scheduleStmt->rowCount() === 0) {
            // If no record was updated, it might not exist; you could log this or handle it as needed
            error_log("No doctor_schedule record found for appointment_id: $appointmentId");
        }

        // Create notifications (similar to add_appointment.php)
        $displayAppointmentId = 'APT' . str_pad($appointmentId, 4, '0', STR_PAD_LEFT);

        $patientStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
        $patientStmt->execute([$patientId]);
        $patientName = $patientStmt->fetch()['name'];

        $doctorStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM doctors WHERE id = ?");
        $doctorStmt->execute([$doctorId]);
        $doctorName = $doctorStmt->fetch()['name'];

        $dateFormatted = $dateTime->format('d M Y');
        $timeFormatted = $dateTime->format('H:i');
        $baseMessage = "Your appointment $displayAppointmentId has been updated to $dateFormatted at $timeFormatted with status $status.";
        $doctorBaseMessage = "Appointment $displayAppointmentId for $patientName has been updated to $dateFormatted at $timeFormatted with status $status.";
        $patientMessage = $message ? "$baseMessage\n\nAdditional Note: $message" : $baseMessage;
        $doctorMessage = $message ? "$doctorBaseMessage\n\nAdditional Note: $message" : $doctorBaseMessage;

        // Insert into notifications
        $notifyStmt = $pdo->prepare("
            INSERT INTO notifications (recipient_type, recipient_id, message, notification_type, is_read, created_at)
            VALUES (?, ?, ?, 'Alert', 0, NOW())
        ");
        $notifyStmt->execute(['Patient', $patientId, $patientMessage]);
        $notifyStmt->execute(['Doctor', $doctorId, $doctorMessage]);

        $response = [
            'success' => true,
            'message' => 'Appointment updated successfully.'
        ];
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>