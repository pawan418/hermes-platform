<?php
// longwaysoftronix/submit_lead.php - AJAX API Endpoint for LSPL Main Site submissions

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
    // Resolve contact email dynamically from settings
    $contact_email = 'info@longwaysoftronix.com';
    try {
        $stmt_email = $db->prepare("SELECT value FROM settings WHERE key = 'contact_email' LIMIT 1");
        $stmt_email->execute();
        $row_email = $stmt_email->fetch();
        if ($row_email && !empty($row_email['value'])) {
            $contact_email = $row_email['value'];
        }
    } catch (Exception $ex) {
        // Fallback to default email
    }

    if ($action === 'submit_contact') {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        $stmt = $db->prepare("
            INSERT INTO leads (name, email, phone, type, message)
            VALUES (?, ?, ?, 'contact', ?)
        ");
        $stmt->execute([$name, $email, $phone, $message]);
        
        // Trigger Email Notification
        $to = $contact_email;
        $subject = "New Contact Inquiry received on LSPL";
        $body = "New Contact Inquiry received on LSPL:\n\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Phone: $phone\n" .
                "Message: $message\n";
        $headers = "From: no-reply@longwaysoftronix.com\r\n" .
                   "Reply-To: $email\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        @mail($to, $subject, $body, $headers);
        
        echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
        exit;
        
    } elseif ($action === 'submit_franchise') {
        $partnership_type = isset($_POST['partnership_type']) ? trim($_POST['partnership_type']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        if (empty($partnership_type) || empty($location)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill out all required fields (Partnership Type, Location).']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO leads (name, email, phone, type, service_selected, duration_selected, message)
            VALUES (?, ?, ?, 'franchise', ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $phone, $partnership_type, $location, $message]);

        // Trigger Email Notification
        $to = $contact_email;
        $subject = "New Franchise Application received on LSPL";
        $body = "New Franchise Application received on LSPL:\n\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Phone: $phone\n" .
                "Partnership Type: $partnership_type\n" .
                "Proposed Location: $location\n" .
                "Message: $message\n";
        $headers = "From: no-reply@longwaysoftronix.com\r\n" .
                   "Reply-To: $email\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        @mail($to, $subject, $body, $headers);

        echo json_encode(['status' => 'success', 'message' => 'Application submitted successfully! Our team will contact you soon.']);
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
                'web_design' => 'Web Designing & UI/UX',
                'wordpress' => 'WordPress & CMS Development',
                'laravel' => 'Laravel & CodeIgniter Web Apps',
                'web_dev' => 'Web Application Development',
                'mobile_apps' => 'Mobile Application Development',
                'magento' => 'Magento Enterprise E-Commerce',
                'prestashop' => 'PrestaShop E-Commerce Solutions',
                'moodle' => 'Moodle LMS Customization',
                'custom_software' => 'Custom Software Solutions',
                'seo' => 'Search Engine Optimization (SEO)',
                'marketing' => 'Digital & Social Media Marketing',
                'cloud_servers' => 'Server Setup & Cloud Hosting'
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
