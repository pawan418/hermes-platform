<?php
// api.php - API for LSPL Mobile App

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database paths
$dbs = [
    'enterprise' => __DIR__ . '/longwaysoftronix_v2/lspl_main_v2.sqlite',
    'academy' => __DIR__ . '/lspl.xyz_v2/lspl_academy_v2.sqlite',
    'ai' => __DIR__ . '/lsxpl_v2/lsxpl_ai_v2.sqlite'
];

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_data') {
        $response = [];
        
        foreach ($dbs as $key => $db_path) {
            $response[$key] = [
                'services' => [],
                'industries' => [],
                'blogs' => []
            ];
            
            if (!file_exists($db_path)) {
                continue;
            }
            
            try {
                $db = new PDO("sqlite:" . $db_path);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Fetch Services
                try {
                    $stmt = $db->query("SELECT * FROM services ORDER BY display_order ASC, id ASC");
                    $response[$key]['services'] = $stmt->fetchAll();
                } catch (Exception $e) {
                    // Table might not exist or empty
                }
                
                // Fetch Industries / Solutions if table exists
                try {
                    $stmt = $db->query("SELECT * FROM industries ORDER BY display_order ASC, id ASC");
                    $response[$key]['industries'] = $stmt->fetchAll();
                } catch (Exception $e) {
                    // Academy does not have industries
                }
                
                // Fetch Blogs
                try {
                    $stmt = $db->query("SELECT * FROM blogs WHERE status = 'published' ORDER BY created_at DESC, id DESC");
                    $response[$key]['blogs'] = $stmt->fetchAll();
                } catch (Exception $e) {
                    // Table might not exist
                }
                
                $db = null;
            } catch (Exception $e) {
                $response[$key]['error'] = $e->getMessage();
            }
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Supported actions: get_data']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'submit_lead') {
        // Read input (can be JSON or application/x-www-form-urlencoded)
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $_POST;
        }
        
        $portal = $input['portal'] ?? ''; // enterprise, academy, ai
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $service_selected = $input['service_selected'] ?? '';
        $duration_selected = $input['duration_selected'] ?? ''; // for academy
        $message = $input['message'] ?? '';
        $budget = $input['budget'] ?? '';
        $type = $input['type'] ?? 'General';
        
        if (empty($portal) || !isset($dbs[$portal])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid portal specified. Must be one of: enterprise, academy, ai']);
            exit;
        }
        
        if (empty($name) || empty($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and email are required.']);
            exit;
        }
        
        $db_path = $dbs[$portal];
        if (!file_exists($db_path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Target portal database does not exist.']);
            exit;
        }
        
        try {
            $db = new PDO("sqlite:" . $db_path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check leads table columns to adapt
            $stmt = $db->prepare("
                INSERT INTO leads (name, email, phone, type, service_selected, duration_selected, message, budget, status, created_at)
                VALUES (:name, :email, :phone, :type, :service_selected, :duration_selected, :message, :budget, 'New', datetime('now'))
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':type' => $type,
                ':service_selected' => $service_selected,
                ':duration_selected' => $duration_selected,
                ':message' => $message,
                ':budget' => $budget
            ]);
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Lead submitted successfully!']);
            $db = null;
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Supported actions: submit_lead']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
exit;
