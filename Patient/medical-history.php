<?php
session_start();

// Include database connection
require_once '../Backend/db_connect.php';

// Check if user is logged in and has 'Patient' role
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'Patient') !== 0) {
    die("Access denied. Only patients can submit medical history.");
}

// Verify patient_id matches the logged-in user
$user_id = $_SESSION['user_id']; // From users.id
if (!isset($_SESSION['patient_id'])) {
    die("Error: Patient ID not set in session. Please log in again or contact support.");
}
$patient_id = $_SESSION['patient_id']; // From users.patient_id

try {
    // Validate patient_id against users and patients tables
    $stmt = $pdo->prepare("SELECT p.id FROM users u LEFT JOIN patients p ON u.patient_id = p.id WHERE u.id = ? AND u.patient_id = ?");
    $stmt->execute([$user_id, $patient_id]);
    if ($stmt->rowCount() === 0) {
        die("Invalid patient ID or user mismatch.");
    }

    // Encryption setup
    $encryption_key = '0589121e755e38401cc2a3a7ed0a8ec9dc8c4db7e0f94ffc46623074b8f33525'; // Same 32-byte key as patient_form.php
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');

    // Decryption function
    function decryptData($encrypted_data, $key, $cipher) {
        $data = base64_decode($encrypted_data);
        if (strpos($data, '::') !== false) {
            list($encrypted, $iv) = explode('::', $data, 2);
            $iv = base64_decode($iv);
            $decrypted = openssl_decrypt(
                $encrypted,
                $cipher,
                hex2bin($key),
                0,
                $iv
            );
            return $decrypted;
        }
        return false;
    }

    // Download Medical Records
    if (isset($_GET['download']) && $_GET['download'] === 'medical_records') {
        require_once 'tcpdf/tcpdf.php'; // TCPDF in Patient directory
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Afya Hospital');
        $pdf->SetTitle('Patient Medical Records');
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $html = '<h1>Patient Medical Records</h1>';
        $html .= '<p><strong>Afya Hospital</strong><br>123 Hospital Road, Nairobi, Kenya<br>+254 712 345 678<br>info@afyahospital.com</p>';

        // Fetch patient details
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name, email, contact_number FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        $html .= '<p><strong>Patient:</strong> ' . htmlspecialchars($patient['patient_name']) . '<br>';
        $html .= '<strong>Email:</strong> ' . htmlspecialchars($patient['email']) . '<br>';
        $html .= '<strong>Contact:</strong> ' . htmlspecialchars($patient['contact_number']) . '</p>';

        // Fetch medical records
        $stmt = $pdo->prepare("SELECT medical_history_text, uploaded_files, submitted_at FROM patient_records WHERE patient_id = ? ORDER BY submitted_at DESC");
        $stmt->execute([$patient_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($records) {
            foreach ($records as $index => $record) {
                $html .= '<h3>Record #' . ($index + 1) . ' - Submitted on ' . htmlspecialchars($record['submitted_at']) . '</h3>';

                // Decrypt medical history text
                $decrypted_history = decryptData($record['medical_history_text'], $encryption_key, 'aes-256-cbc');
                if ($decrypted_history !== false) {
                    $html .= '<p><strong>Medical History:</strong><br>';
                    $history_lines = explode("\n", $decrypted_history);
                    foreach ($history_lines as $line) {
                        $html .= htmlspecialchars($line) . '<br>';
                    }
                    $html .= '</p>';
                } else {
                    $html .= '<p><strong>Medical History:</strong> Unable to decrypt.</p>';
                }

                // Decrypt and list uploaded files as clickable links
                if (!empty($record['uploaded_files'])) {
                    $html .= '<p><strong>Uploaded Files:</strong><br>';
                    $files = explode(',', $record['uploaded_files']);
                    foreach ($files as $file) {
                        $file_name = basename($file);
                        // Create a clickable link to download the file
                        $html .= '<a href="' . htmlspecialchars($file) . '">' . htmlspecialchars($file_name) . '</a><br>';
                    }
                    $html .= '</p>';
                }
            }
        } else {
            $html .= '<p>No medical records found.</p>';
        }

        $pdf->writeHTML($html);
        $pdf->Output('patient_medical_records_' . $patient_id . '.pdf', 'D');
        exit;
    }

    // Download Visit Records
    if (isset($_GET['download']) && $_GET['download'] === 'visit_records') {
        require_once 'tcpdf/tcpdf.php'; // TCPDF in Patient directory
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Afya Hospital');
        $pdf->SetTitle('Patient Visit Records');
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $html = '<h1>Patient Visit Records</h1>';
        $html .= '<p><strong>Afya Hospital</strong><br>123 Hospital Road, Nairobi, Kenya<br>+254 712 345 678<br>info@afyahospital.com</p>';

        // Fetch patient details
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name, email, contact_number FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        $html .= '<p><strong>Patient:</strong> ' . htmlspecialchars($patient['patient_name']) . '<br>';
        $html .= '<strong>Email:</strong> ' . htmlspecialchars($patient['email']) . '<br>';
        $html .= '<strong>Contact:</strong> ' . htmlspecialchars($patient['contact_number']) . '</p>';

        // Fetch visit records
        $stmt = $pdo->prepare("SELECT visit_date, reason_for_visit, notes_outcome, created_at FROM visit_records WHERE patient_id = ? ORDER BY visit_date DESC");
        $stmt->execute([$patient_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($records) {
            foreach ($records as $index => $record) {
                $html .= '<h3>Visit #' . ($index + 1) . ' - ' . htmlspecialchars($record['visit_date']) . '</h3>';

                // Decrypt reason for visit
                $decrypted_reason = decryptData($record['reason_for_visit'], $encryption_key, 'aes-256-cbc');
                $html .= '<p><strong>Reason for Visit:</strong> ';
                $html .= ($decrypted_reason !== false) ? htmlspecialchars($decrypted_reason) : 'Unable to decrypt.';
                $html .= '</p>';

                // Decrypt notes/outcome
                $decrypted_notes = decryptData($record['notes_outcome'], $encryption_key, 'aes-256-cbc');
                $html .= '<p><strong>Notes/Outcome:</strong> ';
                $html .= ($decrypted_notes !== false) ? htmlspecialchars($decrypted_notes) : 'Unable to decrypt.';
                $html .= '</p>';

                $html .= '<p><strong>Created At:</strong> ' . htmlspecialchars($record['created_at']) . '</p>';
            }
        } else {
            $html .= '<p>No visit records found.</p>';
        }

        $pdf->writeHTML($html);
        $pdf->Output('patient_visit_records_' . $patient_id . '.pdf', 'D');
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Collect and format medical history fields
        $fields = [
            'Allergies' => $_POST['allergies'] ?? 'None',
            'Genetic Conditions' => $_POST['genetic_conditions'] ?? 'None',
            'Chronic Illnesses' => $_POST['chronic_illnesses'] ?? 'None',
            'Past Surgeries' => $_POST['past_surgeries'] ?? 'None',
            'Family Medical History' => $_POST['family_medical_history'] ?? 'None',
            'Current Medications' => $_POST['current_medications'] ?? 'None',
            'Lifestyle Factors' => $_POST['lifestyle_factors'] ?? 'None',
            'Immunization History' => $_POST['immunization_history'] ?? 'None',
            'Previous Hospitalizations' => $_POST['previous_hospitalizations'] ?? 'None',
            'Mental Health History' => $_POST['mental_health_history'] ?? 'None'
        ];

        // Format medical history text with each field on a new line
        $medical_history = '';
        foreach ($fields as $key => $value) {
            if (!empty($value) && $value !== 'None') {
                $medical_history .= "$key: $value\n";
            }
        }
        $medical_history = rtrim($medical_history, "\n");

        // Validate that at least one field is filled
        if (empty($medical_history) && empty($_FILES['documents']['name'][0])) {
            echo "<script>alert('Please provide at least one piece of medical history or upload a file.');</script>";
        } else {
            // Encrypt medical history
            $iv = openssl_random_pseudo_bytes($iv_length);
            $encrypted_history = openssl_encrypt(
                $medical_history,
                'aes-256-cbc',
                hex2bin($encryption_key),
                0,
                $iv
            );
            $encrypted_history = base64_encode($encrypted_history . '::' . base64_encode($iv));

            // Handle file uploads and encryption
            $file_paths = [];
            $upload_dir = "assets/fileuploads/patient_$patient_id/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!empty($_FILES['documents']['name'][0])) {
                foreach ($_FILES['documents']['tmp_name'] as $index => $tmp_name) {
                    if ($_FILES['documents']['error'][$index] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['documents']['name'][$index];
                        $original_file = $upload_dir . time() . "_" . $file_name;
                        if (move_uploaded_file($tmp_name, $original_file)) {
                            // Encrypt the file
                            $file_content = file_get_contents($original_file);
                            $iv_file = openssl_random_pseudo_bytes($iv_length);
                            $encrypted_content = openssl_encrypt(
                                $file_content,
                                'aes-256-cbc',
                                hex2bin($encryption_key),
                                0,
                                $iv_file
                            );
                            $encrypted_file = $upload_dir . 'enc_' . time() . "_" . $file_name . '.enc';
                            file_put_contents($encrypted_file, $encrypted_content . '::' . base64_encode($iv_file));
                            unlink($original_file);
                            $file_paths[] = $encrypted_file;
                        } else {
                            error_log("Failed to move uploaded file: $original_file");
                        }
                    } else {
                        error_log("Upload error for file $index: " . $_FILES['documents']['error'][$index]);
                    }
                }
            }
            $file_paths_str = !empty($file_paths) ? implode(',', $file_paths) : NULL;

            // Insert into patient_records
            $stmt = $pdo->prepare("INSERT INTO patient_records (patient_id, medical_history_text, uploaded_files, submitted_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$patient_id, $encrypted_history, $file_paths_str]);
            $record_id = $pdo->lastInsertId();

            // Log the action in audit_logs
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, timestamp) VALUES (?, 'INSERT', 'patient_records', ?, NOW())");
            $stmt->execute([$user_id, $record_id]);

            echo "<script>alert('Medical history submitted successfully!'); window.location.href='medical-history.php';</script>";
            exit;
        }
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical History - Afya Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        body {
            background-color: #f8f9fa;
            color: #343a40;
            line-height: 1.6;
        }
        .nav-links {
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links a {
            text-decoration: none;
            color: #007bff;
            font-weight: 500;
            margin-right: 15px;
            transition: color 0.3s ease;
        }
        .nav-links a:hover {
            color: #0056b3;
        }
        .download-buttons a {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            line-height: 1;
            background-color: #17a2b8;
            color: #fff;
            border: none;
            transition: transform 0.2s ease, background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .download-buttons a:hover {
            background-color: #138496;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        h2 {
            text-align: center;
            color: #007bff;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1a3c6d;
        }
        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            border: none;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0;
            font-size: 0.9rem;
            font-weight: 500;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-bottom: 1px solid #007bff;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px 0;
            font-size: 0.9rem;
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.3s ease, box-shadow 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        @media (max-width: 768px) {
            .nav-links {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-links a {
                margin-bottom: 10px;
            }
            .download-buttons {
                margin-top: 10px;
            }
            .container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <div>
            <a href="../Patient/index.html">Home</a> / 
            <a href="../Backend/loginpage.php">Log out</a>
        </div>
        <div class="download-buttons">
            <a href="?download=medical_records" class="download-btn"><i class="fas fa-download"></i> Download Medical Records</a>
            <a href="?download=visit_records" class="download-btn"><i class="fas fa-download"></i> Download Visit Records</a>
        </div>
    </div>

    <div class="container">
        <h2>Submit Your Medical History</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="allergies">Allergies (e.g., food, medication, environmental)</label>
                <input type="text" id="allergies" name="allergies" placeholder="e.g., Peanuts, Penicillin">
            </div>

            <div class="form-group">
                <label for="genetic_conditions">Genetic Conditions (e.g., Down syndrome, Sickle cell anemia)</label>
                <input type="text" id="genetic_conditions" name="genetic_conditions" placeholder="e.g., Cystic Fibrosis">
            </div>

            <div class="form-group">
                <label for="chronic_illnesses">Chronic Illnesses (e.g., Diabetes, Hypertension)</label>
                <input type="text" id="chronic_illnesses" name="chronic_illnesses" placeholder="e.g., Asthma">
            </div>

            <div class="form-group">
                <label for="past_surgeries">Past Surgeries (e.g., Appendectomy, Knee replacement)</label>
                <input type="text" id="past_surgeries" name="past_surgeries" placeholder="e.g., Tonsillectomy">
            </div>

            <div class="form-group">
                <label for="family_medical_history">Family Medical History (e.g., Heart disease in parents)</label>
                <input type="text" id="family_medical_history" name="family_medical_history" placeholder="e.g., Breast cancer in mother">
            </div>

            <div class="form-group">
                <label for="current_medications">Current Medications (e.g., Insulin, Antihypertensives)</label>
                <input type="text" id="current_medications" name="current_medications" placeholder="e.g., Metformin">
            </div>

            <div class="form-group">
                <label for="lifestyle_factors">Lifestyle Factors (e.g., Smoking, Alcohol use, Exercise habits)</label>
                <input type="text" id="lifestyle_factors" name="lifestyle_factors" placeholder="e.g., Smokes 5 cigarettes daily">
            </div>

            <div class="form-group">
                <label for="immunization_history">Immunization History (e.g., Tetanus, MMR)</label>
                <input type="text" id="immunization_history" name="immunization_history" placeholder="e.g., Fully vaccinated">
            </div>

            <div class="form-group">
                <label for="previous_hospitalizations">Previous Hospitalizations (e.g., Pneumonia in 2020)</label>
                <input type="text" id="previous_hospitalizations" name="previous_hospitalizations" placeholder="e.g., Fractured leg in 2018">
            </div>

            <div class="form-group">
                <label for="mental_health_history">Mental Health History (e.g., Depression, Anxiety)</label>
                <input type="text" id="mental_health_history" name="mental_health_history" placeholder="e.g., Diagnosed with anxiety in 2019">
            </div>

            <div class="form-group">
                <label for="documents">Upload Documents (if any, e.g., previous medical reports)</label>
                <input type="file" id="documents" name="documents[]" multiple>
            </div>

            <button type="submit">Submit Medical History</button>
        </form>
    </div>
</body>
</html>