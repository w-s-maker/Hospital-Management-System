<?php
include 'db_connect.php';
error_log("Database connection established: " . (isset($pdo) ? "Yes" : "No"));
error_log("Received POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = strtolower(trim($_POST['firstName'])); 
    $last_name = strtolower(trim($_POST['lastName'])); 
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = trim($_POST['role']);
    $staff_id = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : NULL;

    error_log("Role received: $role");

    if ($password !== $confirm_password) {
        die("Error: Passwords do not match.");
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        die("Error: Email is already registered in the system.");
    }

    if ($role === 'hospital_staff') {
        $role = 'Hospital Staff';
        $table = 'hospital_staffs';
    } elseif ($role === 'doctor') {
        $role = 'Doctor';
        $table = 'doctors';
    } elseif ($role === 'nurse') {
        $role = 'Nurse';
        $table = 'nurses';
    }

    if (in_array($role, ['Doctor', 'Nurse', 'Hospital Staff']) && !empty($staff_id)) {
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM $table WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();

        if (!$staff || strtolower($staff['first_name']) !== $first_name || strtolower($staff['last_name']) !== $last_name || strtolower($staff['email']) !== $email) {
            $pdo->rollBack();
            die("Error: Invalid Staff ID or details do not match.");
        }
    }

    $patient_id = NULL;
    if (strtolower($role) === 'patient') {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
        $stmt->execute([$email]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient && isset($patient['id'])) {
            $patient_id = $patient['id'];
            error_log("Existing patient found with id: $patient_id for email: $email");
        } else {
            $stmt = $pdo->prepare("INSERT INTO patients (first_name, last_name, date_of_birth, gender, contact_number, email, address, medical_history, insurance, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            try {
                $success = $stmt->execute([$first_name, $last_name, $date_of_birth, $gender, $phone, $email, $address, NULL, NULL]);
                if (!$success) {
                    throw new PDOException("Execution failed");
                }
            } catch (PDOException $e) {
                error_log("PDO Error: " . $e->getMessage());
                $pdo->rollBack();
                die("Error: Failed to insert patient record. Check logs.");
            }
            $patient_id = $pdo->lastInsertId();
            if (!$patient_id) {
                $pdo->rollBack();
                die("Error: Failed to retrieve new patient id after insertion.");
            }
            error_log("New patient created with id: $patient_id for email: $email");
        }
    }

    error_log("Inserting into users with patient_id: " . ($patient_id ?? 'NULL'));

    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, date_of_birth, gender, address, password_hash, role, email, contact_number, created_at, staff_id, patient_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    $success = $stmt->execute([$first_name, $last_name, $date_of_birth, $gender, $address, password_hash($password, PASSWORD_BCRYPT), $role, $email, $phone, $staff_id, $patient_id]);

    if ($success) {
        $user_id = $pdo->lastInsertId();
        error_log("User created with user_id: $user_id, patient_id: " . ($patient_id ?? 'NULL'));

        if (in_array($role, ['Doctor', 'Nurse', 'Hospital Staff'])) {
            if (empty($staff_id)) {
                $pdo->rollBack();
                die("Error: Staff ID is required for $role role.");
            }
            $stmt = $pdo->prepare("SELECT id FROM $table WHERE staff_id = ?");
            $stmt->execute([$staff_id]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                die("Error: Invalid Staff ID for $role.");
            }
        }

        $message = "New user signed up: $first_name $last_name";
        $notification_type = "signup";
        $stmt = $pdo->prepare("INSERT INTO admin_notifications (user_id, message, notification_type) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $message, $notification_type]);

        $pdo->commit();
        header("Location: loginpage.php?success=1");
        exit();
    } else {
        $pdo->rollBack();
        echo "Error: Registration failed. Check logs.";
    }
}
?>