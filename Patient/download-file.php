<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: error.php?message=Invalid record ID');
    exit;
}

$record_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch record details
$stmt = $conn->prepare("SELECT pr.*, p.id as patient_id FROM patient_records pr JOIN patients p ON pr.patient_id = p.id WHERE pr.id = ?");
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: error.php?message=Record not found');
    exit;
}

$record = $result->fetch_assoc();
$stmt->close();

// Check if the record belongs to the logged-in user
$stmt = $conn->prepare("SELECT patient_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user['patient_id'] !== $record['patient_id']) {
    header('Location: error.php?message=Unauthorized access');
    exit;
}

// Check if file exists
if (empty($record['uploaded_files']) || !file_exists($record['uploaded_files'])) {
    header('Location: error.php?message=File not found');
    exit;
}

// Log the access
$action = "DOWNLOAD_PATIENT_FILE";
$stmt = $conn->prepare("INSERT INTO data_access_logs (user_id, patient_id, action) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $record['patient_id'], $action);
$stmt->execute();
$stmt->close();

// Get file content
$encrypted_content = file_get_contents($record['uploaded_files']);

// Decrypt the file
$encryption_key = getenv('ENCRYPTION_KEY') ?: 'default_encryption_key';
$decrypted_content = openssl_decrypt($encrypted_content, 'AES-256-CBC', $encryption_key, 0, substr(hash('sha256', $encryption_key), 0, 16));

// Get original filename from the encrypted filename
$filename = basename($record['uploaded_files']);
$original_filename = preg_replace('/^enc_\d+_(.+)\.enc$/', '$1', $filename);

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($decrypted_content));

// Output file content
echo $decrypted_content;
exit;
?>