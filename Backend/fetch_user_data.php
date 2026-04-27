<?php
include 'db_connect.php';

// Start the session
session_start();

try {
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Get user ID from the session
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($userId <= 0) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'User not logged in or invalid session']);
        exit;
    }

    // Fetch user data
    $stmt = $pdo->prepare("
        SELECT id, profile_pic, first_name, last_name, date_of_birth, gender, address, email, contact_number, role, staff_id, patient_id
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Prepend the profile picture path
    $profilePicture = $user['profile_pic'] ? 'assets/img/' . $user['profile_pic'] : 'assets/img/user.jpg';

    $response = [
        'user' => [
            'id' => $user['id'],
            'profile_pic' => $profilePicture,
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'date_of_birth' => $user['date_of_birth'],
            'gender' => $user['gender'],
            'address' => $user['address'],
            'email' => $user['email'],
            'contact_number' => $user['contact_number'],
            'role' => $user['role'], // Will be "Admin", "Doctor", etc.
            'staff_id' => $user['staff_id'],
            'patient_id' => $user['patient_id']
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PDOException in fetch_user_data.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("General Exception in fetch_user_data.php: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>