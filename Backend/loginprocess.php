<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo "Please fill in all fields.";
        exit();
    }

    // Fetch user details including staff_id
    $stmt = $pdo->prepare("SELECT id, patient_id, staff_id, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Validate patient_id for Patient role
        if ($user['role'] === 'Patient' && is_null($user['patient_id'])) {
            echo "Error: Patient ID is missing for this user. Contact support.";
            exit();
        }

        // Validate staff_id for Doctor, Nurse, Hospital Staff roles
        if (in_array($user['role'], ['Doctor', 'Nurse', 'Hospital Staff']) && is_null($user['staff_id'])) {
            echo "Error: Staff ID is missing for this user. Contact support.";
            exit();
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Set patient_id for Patient role
        if ($user['role'] === 'Patient') {
            $_SESSION['patient_id'] = $user['patient_id'];
        }

        // For Doctor role, fetch doctors.id using users.staff_id
        if ($user['role'] === 'Doctor') {
            $stmt = $pdo->prepare("SELECT id FROM doctors WHERE staff_id = ?");
            $stmt->execute([$user['staff_id']]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($doctor) {
                $_SESSION['staff_id'] = $user['staff_id']; // Keep for visit_records
                $_SESSION['doctor_id'] = $doctor['id'];    // Use for other tables
                error_log("Set staff_id: " . $user['staff_id'] . ", doctor_id: " . $doctor['id'] . " for user_id: " . $user['id']);
            } else {
                echo "Error: Doctor record not found for this staff ID.";
                exit();
            }
        }

        // Role-based redirection
        switch ($user['role']) {
            case 'Patient':
                header("Location: ../Patient/index.html"); // Adjust path if needed
                break;
            case 'Doctor':
                header("Location: ../Doctor/doctordashboard.php");
                break;
            case 'Nurse':
                header("Location: ../Nurse/nursedashboard.php"); // Adjust if trimming dashboards
                break;
            case 'Hospital Staff':
                header("Location: ../HospitalStaff/hospitalstaffdashboard.php"); // Adjust if trimming
                break;
            case 'Admin':
                header("Location: admindashboard.html"); // Update to .php
                break;
            default:
                echo "Invalid user role.";
                exit();
        }
        exit();
    } else {
        echo "Invalid credentials.";
        exit();
    }
}
?>