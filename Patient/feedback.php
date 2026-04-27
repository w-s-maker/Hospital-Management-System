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
$feedback_text = '';
$rating = '';
$error_message = '';
$success_message = '';

// Fetch patient name from users table
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if ($patient) {
    $patient_name = $patient['full_name'];
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_text = filter_input(INPUT_POST, 'feedback_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);

    // Validate input
    if (empty($feedback_text)) {
        $error_message = "Feedback text cannot be empty.";
    } elseif (empty($rating)) {
        $error_message = "Please select a rating.";
    } elseif (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        $error_message = "Rating must be between 1 and 5.";
    } else {
        // Insert feedback record
        $sql = "INSERT INTO feedback (patient_id, feedback_text, rating, submitted_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id, $feedback_text, $rating]);

        if ($stmt->rowCount() > 0) {
            $success_message = "Thank you for your feedback!";
            $feedback_text = $rating = ''; // Reset fields
        } else {
            $error_message = "Error submitting feedback. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Afya Hospital</title>
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
            max-width: 700px;
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
            margin-bottom: 25px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #1a3c6d;
        }
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        input[type="text"]:disabled {
            background-color: #e9ecef;
            color: #6c757d;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
            padding: 12px 15px;
        }
        select {
            appearance: none;
            background: url('data:image/svg+xml;charset=UTF-8,%3csvg fill="%23343a40" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"%3e%3cpath d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/%3e%3c/svg%3e') no-repeat right 1rem center;
            background-size: 1rem;
            cursor: pointer;
        }
        select:invalid {
            color: #6c757d;
        }
        select option {
            color: #343a40;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            outline: none;
        }
        .error {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 10px;
            text-align: center;
        }
        .success {
            color: #28a745;
            font-size: 0.9rem;
            margin-top: 10px;
            text-align: center;
        }
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        @media (max-width: 600px) {
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
            h2 {
                font-size: 1.5rem;
            }
            button[type="submit"] {
                font-size: 1rem;
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
        <h2>We Value Your Feedback!</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="patient_name">Patient Name:</label>
                <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($patient_name); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="feedback_text">Your Feedback:</label>
                <textarea id="feedback_text" name="feedback_text" required><?php echo htmlspecialchars($feedback_text); ?></textarea>
            </div>

            <div class="form-group">
                <label for="rating">Rating (1 - Poor, 5 - Excellent):</label>
                <select id="rating" name="rating" required>
                    <option value="" disabled selected>Select a rating</option>
                    <option value="1" <?php if ($rating == 1) echo 'selected'; ?>>1 - Poor</option>
                    <option value="2" <?php if ($rating == 2) echo 'selected'; ?>>2 - Fair</option>
                    <option value="3" <?php if ($rating == 3) echo 'selected'; ?>>3 - Average</option>
                    <option value="4" <?php if ($rating == 4) echo 'selected'; ?>>4 - Good</option>
                    <option value="5" <?php if ($rating == 5) echo 'selected'; ?>>5 - Excellent</option>
                </select>
            </div>

            <button type="submit">Submit Feedback</button>
        </form>
    </div>
</body>
</html>