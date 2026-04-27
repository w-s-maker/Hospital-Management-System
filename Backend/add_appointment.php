<?php
include 'db_connect.php';

try {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if (isset($_GET['action']) && $_GET['action'] === 'get_next_id') {
        $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM appointments");
        $result = $stmt->fetch();
        $maxId = $result['max_id'] ? (int)$result['max_id'] : 0;
        $nextId = $maxId + 1;
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'nextId' => $nextId]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $appointmentDate = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
        $appointmentTime = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : ''; // grab message for notifications

        // Log incoming data
        error_log("POST Data: " . json_encode($_POST));

        // Validate required fields for appointments
        if ($appointmentId <= 0 || $patientId <= 0 || $doctorId <= 0 || empty($appointmentDate) || empty($appointmentTime) || empty($status)) {
            $missing = [];
            if ($appointmentId <= 0) $missing[] = 'appointment_id';
            if ($patientId <= 0) $missing[] = 'patient_id';
            if ($doctorId <= 0) $missing[] = 'doctor_id';
            if (empty($appointmentDate)) $missing[] = 'appointment_date';
            if (empty($appointmentTime)) $missing[] = 'appointment_time';
            if (empty($status)) $missing[] = 'status';
            http_response_code(400);
            echo json_encode(['error' => 'Missing fields: ' . implode(', ', $missing)]);
            exit;
        }

        $dateTime = DateTime::createFromFormat('Y-m-d H:i', "$appointmentDate $appointmentTime");
        if (!$dateTime || $dateTime->format('Y-m-d H:i') !== "$appointmentDate $appointmentTime") {
            http_response_code(400);
            echo json_encode(['error' => "Invalid date/time: $appointmentDate $appointmentTime"]);
            exit;
        }

        $checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ?");
        $checkStmt->execute([$appointmentId]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Appointment ID already exists']);
            exit;
        }

        // Insert into appointments
        $stmt = $pdo->prepare("
            INSERT INTO appointments (id, patient_id, doctor_id, appointment_date, appointment_time, status, created_at, updated_at, modified_by)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Admin')
        ");
        $stmt->execute([$appointmentId, $patientId, $doctorId, $appointmentDate, $appointmentTime, $status]);

        // Insert into doctor_schedule
        $scheduleStmt = $pdo->prepare("
            INSERT INTO doctor_schedule (doctor_id, schedule_date, start_time, end_time, status, appointment_id, notes, created_at, updated_at)
            VALUES (?, ?, ?, NULL, 'Busy', ?, 'appointment with patient', NOW(), NOW())
        ");
        $scheduleStmt->execute([$doctorId, $appointmentDate, $appointmentTime, $appointmentId]);

        // Proceed with notifications
        $displayAppointmentId = 'APT' . str_pad($appointmentId, 4, '0', STR_PAD_LEFT);

        $patientStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
        $patientStmt->execute([$patientId]);
        $patientName = $patientStmt->fetch()['name'];

        $doctorStmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM doctors WHERE id = ?");
        $doctorStmt->execute([$doctorId]);
        $doctorName = $doctorStmt->fetch()['name'];

        $dateFormatted = $dateTime->format('d M Y');
        $timeFormatted = $dateTime->format('H:i');
        $baseMessage = "Your appointment $displayAppointmentId has been created on $dateFormatted at $timeFormatted as $status.";
        $doctorBaseMessage = "Appointment $displayAppointmentId for $patientName has been created on $dateFormatted at $timeFormatted as $status.";
        $patientMessage = $message ? "$baseMessage\n\nAdditional Note: $message" : $baseMessage;
        $doctorMessage = $message ? "$doctorBaseMessage\n\nAdditional Note: $message" : $doctorBaseMessage;

        // Insert into notifications
        $notifyStmt = $pdo->prepare("
            INSERT INTO notifications (recipient_type, recipient_id, message, notification_type, is_read, created_at)
            VALUES (?, ?, ?, 'Alert', 0, NOW())
        ");
        $notifyStmt->execute(['Patient', $patientId, $patientMessage]);
        $notifyStmt->execute(['Doctor', $doctorId, $doctorMessage]);

        http_response_code(200);
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PDOException in add_appointment.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("General Exception in add_appointment.php: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>