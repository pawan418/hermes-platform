<?php
// lsxpl/submit_lead.php - AJAX API Endpoint for LSXPL AI Lab submissions

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Common validation
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
        
        $stmt = $db->prepare("
            INSERT INTO leads (name, email, phone, type, service_selected, message)
            VALUES (?, ?, ?, 'registration', ?, ?)
        ");
        $stmt->execute([$name, $email, $phone, $course_name, $message]);
        
        echo json_encode(['status' => 'success', 'message' => 'Registration received.']);
        exit;

    } elseif ($action === 'submit_estimator') {
        $service = isset($_POST['service']) ? trim($_POST['service']) : '';
        $scale = isset($_POST['scale']) ? trim($_POST['scale']) : '';
        $timeline = isset($_POST['timeline']) ? trim($_POST['timeline']) : '';
        $budget = isset($_POST['estimated_budget']) ? trim($_POST['estimated_budget']) : '₹0';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        // Dynamic lookup of service/solution title from database
        $readable_service = '';
        try {
            $stmt_find = $db->prepare("SELECT title FROM services WHERE slug = ? LIMIT 1");
            $stmt_find->execute([$service]);
            $row_find = $stmt_find->fetch();
            if ($row_find) {
                $readable_service = $row_find['title'];
            } else {
                $stmt_find = $db->prepare("SELECT title FROM industries WHERE slug = ? LIMIT 1");
                $stmt_find->execute([$service]);
                $row_find = $stmt_find->fetch();
                if ($row_find) {
                    $readable_service = $row_find['title'];
                }
            }
        } catch (Exception $e) {
            // ignore
        }

        if (empty($readable_service)) {
            $services_map = [
                'ai_chatbots' => 'AI Conversational Chatbots',
                'ai_calling' => 'AI Voice & Calling Agents',
                'ai_seo' => 'AI-Powered & Hybrid SEO',
                'saas_platform' => 'Custom SaaS Platform Design',
                'llm_rag' => 'Custom LLM & Agent Tuning',
                'cyber_audit' => 'Cybersecurity & AI Security Audits'
            ];
            $readable_service = isset($services_map[$service]) ? $services_map[$service] : $service;
        }
        $readable_duration = "Scale: " . ucfirst($scale) . " | Timeline: " . ucfirst($timeline);

        $stmt = $db->prepare("
            INSERT INTO leads (name, email, phone, type, service_selected, duration_selected, message, budget)
            VALUES (?, ?, ?, 'estimator', ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $phone, $readable_service, $readable_duration, $message, $budget]);

        echo json_encode(['status' => 'success', 'message' => 'Estimate submission received.']);
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
