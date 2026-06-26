<?php
// lspl.xyz/submit_lead.php - AJAX API Endpoint for LSPL Academy
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validation
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

if (empty($name) || empty($email) || empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill out all required fields (Name, Email, Phone).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    if ($action === 'submit_contact') {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        $stmt = $db->prepare("
            INSERT INTO leads (name, email, phone, type, message)
            VALUES (?, ?, ?, 'contact', ?)
        ");
        $stmt->execute([$name, $email, $phone, $message]);
        
        echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
        exit;
        
    } elseif ($action === 'register_academy') {
        $course_name = isset($_POST['course_name']) ? trim($_POST['course_name']) : 'General Academy';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $estimated_fee = isset($_POST['estimated_fee']) ? trim($_POST['estimated_fee']) : '₹0';
        
        $stmt = $db->prepare("
            INSERT INTO leads (name, email, phone, type, service_selected, duration_selected, message, budget)
            VALUES (?, ?, ?, 'registration', ?, ?, 'Academy enrollment registration form submitted.', ?)
        ");
        $stmt->execute([$name, $email, $phone, $course_name, $message, $estimated_fee]);
        
        echo json_encode(['status' => 'success', 'message' => 'Registration received.']);
        exit;

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
