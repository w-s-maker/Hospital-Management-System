<?php
session_start();
require_once '../Backend/db_connect.php'; // Linking to your db_connect.php in Backend folder

// Check if patient is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header("Location: ../Backend/loginpage.php"); // Redirect to login if not a patient
    exit();
}

// Initialize variables
$patient_id = $_SESSION['patient_id']; // From session (set in login)
$patient_name = '';
$error_message = '';
$success_message = '';
$payment_methods = ['Card', 'M-Pesa']; // Available payment methods

// Fetch patient name from patients table
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if ($patient) {
    $patient_name = $patient['full_name'];
}

// Fetch billing records for the logged-in patient
$sql = "SELECT b.id AS billing_id, b.invoice_number, b.amount, b.payment_status, b.payment_method, 
               b.transaction_token, b.transaction_date, b.created_at, b.updated_at, b.appointment_id,
               a.appointment_date, a.appointment_time
        FROM billing b
        JOIN appointments a ON b.appointment_id = a.id
        WHERE b.patient_id = ?
        ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$billing_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PDF Generation for Invoice Download
if (isset($_GET['download']) && isset($_GET['billing_id'])) {
    $billing_id = filter_input(INPUT_GET, 'billing_id', FILTER_SANITIZE_NUMBER_INT);

    // Fetch billing details with patient and appointment info
    $sql = "SELECT b.id AS billing_id, b.invoice_number, b.amount, b.payment_status, b.transaction_date, b.appointment_id,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient_name, p.email AS patient_email, p.contact_number,
                   a.doctor_id
            FROM billing b
            JOIN patients p ON b.patient_id = p.id
            JOIN appointments a ON b.appointment_id = a.id
            WHERE b.id = ? AND b.patient_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$billing_id, $patient_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice) {
        // Fetch doctor details
        $doctor_query = "SELECT CONCAT(first_name, ' ', last_name) AS doctor_name 
                         FROM doctors 
                         WHERE id = ?";
        $doctor_stmt = $pdo->prepare($doctor_query);
        $doctor_stmt->execute([$invoice['doctor_id']]);
        $doctor = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
        $doctor_name = $doctor ? $doctor['doctor_name'] : 'N/A';

        // Itemize the total amount (unequal distribution)
        $totalAmount = $invoice['amount'];
        $items = [
            ['name' => 'Consultation Fee', 'description' => 'Doctor consultation', 'unit_cost' => $totalAmount * 0.40, 'quantity' => 1], // 40%
            ['name' => 'Lab Test', 'description' => 'Basic diagnostic test', 'unit_cost' => $totalAmount * 0.30, 'quantity' => 1], // 30%
            ['name' => 'Medication', 'description' => 'Prescribed drugs', 'unit_cost' => $totalAmount * 0.20, 'quantity' => 1], // 20%
            ['name' => 'Procedure Fee', 'description' => 'Minor procedure', 'unit_cost' => $totalAmount * 0.10, 'quantity' => 1] // 10%
        ];

        // Generate PDF using TCPDF
        require_once 'tcpdf/tcpdf.php'; // Ensure TCPDF is in the Patient folder or adjust path
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Afya Hospital');
        $pdf->SetTitle('Invoice #' . $invoice['invoice_number']);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $html = '
        <h1>Invoice #' . htmlspecialchars($invoice['invoice_number']) . '</h1>
        <p><strong>Afya Hospital</strong><br>123 Hospital Road, Nairobi, Kenya<br>+254 712 345 678<br>info@afyahospital.com</p>
        <p><strong>Invoice to:</strong><br>' . htmlspecialchars($invoice['patient_name']) . '<br>' . htmlspecialchars($invoice['contact_number']) . '<br>' . htmlspecialchars($invoice['patient_email']) . '<br>Attending Doctor: ' . htmlspecialchars($doctor_name) . '</p>
        <p><strong>Date:</strong> ' . htmlspecialchars($invoice['transaction_date'] ?: date('Y-m-d')) . '<br><strong>Due Date:</strong> ' . date('Y-m-d', strtotime(($invoice['transaction_date'] ?: date('Y-m-d')) . ' +30 days')) . '<br><strong>Payment Status:</strong> ' . htmlspecialchars($invoice['payment_status']) . '</p>
        <table border="1" cellpadding="5">
            <tr><th>#</th><th>Item</th><th>Description</th><th>Unit Cost (KES)</th><th>Quantity</th><th>Total (KES)</th></tr>';
        foreach ($items as $index => $item) {
            $html .= '<tr><td>' . ($index + 1) . '</td><td>' . htmlspecialchars($item['name']) . '</td><td>' . htmlspecialchars($item['description']) . '</td><td>' . number_format($item['unit_cost'], 2) . '</td><td>' . $item['quantity'] . '</td><td>' . number_format($item['unit_cost'] * $item['quantity'], 2) . '</td></tr>';
        }
        $html .= '</table>
        <p><strong>Total Due:</strong> KES ' . number_format($totalAmount, 2) . '</p>
        <p><strong>Payment Details:</strong><br>Bank: Equity Bank Kenya<br>Branch: Nairobi CBD<br>Account No: 1234567890123<br>SWIFT: EQBLKENA</p>';

        $pdf->writeHTML($html);
        $pdf->Output('invoice_' . $invoice['invoice_number'] . '.pdf', 'D');
        exit();
    } else {
        $error_message = "Billing record not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Afya Hospital</title>
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
        .container {
            max-width: 1000px;
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
        .error, .success {
            font-size: 0.9rem;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .billing-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .billing-table th, .billing-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .billing-table th {
            background-color: #007bff;
            color: #fff;
            font-weight: 600;
        }
        .billing-table tr:hover {
            background-color: #f1f3f5;
        }
        .payment-status {
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        .payment-status.Paid {
            background-color: #28a745;
            color: #fff;
        }
        .payment-status.Pending {
            background-color: #ffc107;
            color: #212529;
        }
        .payment-status.Failed {
            background-color: #dc3545;
            color: #fff;
        }
        .payment-status.Refunded {
            background-color: #6c757d;
            color: #fff;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-start;
            height: 100%;
        }
        .action-buttons a, .action-buttons button {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            line-height: 1;
        }
        .action-buttons .download-btn {
            background-color: #17a2b8;
            color: #fff;
            border: none;
        }
        .action-buttons .download-btn:hover {
            background-color: #138496;
        }
        .action-buttons .pay-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
        }
        .action-buttons .pay-btn:hover {
            background-color: #218838;
        }
        .no-records {
            text-align: center;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        @media (max-width: 768px) {
            .nav-links {
                padding: 15px;
            }
            .nav-links a {
                display: inline-block;
                margin-bottom: 10px;
            }
            .container {
                margin: 20px;
                padding: 20px;
            }
            .billing-table {
                font-size: 0.9rem;
            }
            .billing-table th, .billing-table td {
                padding: 8px;
                vertical-align: middle;
            }
            .action-buttons {
                gap: 8px;
            }
            .action-buttons a, .action-buttons button {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <a href="../Patient/index.html">Home</a> / 
        <a href="../Backend/loginpage.php">Log out</a>
    </div>

    <div class="container">
        <h2>Your Billing Information</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($billing_records)): ?>
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Appointment Date</th>
                        <th>Amount (KES)</th>
                        <th>Payment Status</th>
                        <th>Payment Method</th>
                        <th>Transaction Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billing_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['appointment_date'] . ' ' . $record['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($record['amount'], 2)); ?></td>
                            <td>
                                <span class="payment-status <?php echo htmlspecialchars($record['payment_status']); ?>">
                                    <?php echo htmlspecialchars($record['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['payment_method'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($record['transaction_date'] ?: 'N/A'); ?></td>
                            <td class="action-buttons">
                                <a href="?download=pdf&billing_id=<?php echo htmlspecialchars($record['billing_id']); ?>" class="download-btn">
                                    <i class="fas fa-download"></i> Invoice
                                </a>
                                <?php if ($record['payment_status'] === 'Pending'): ?>
                                    <a href="billinggateway.php?billing_id=<?php echo htmlspecialchars($record['billing_id']); ?>" class="pay-btn">
                                        <i class="fas fa-money-bill-wave"></i> Pay
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-records">
                <p>No billing records found. Book an appointment to get started.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>