<?php
// pms_v2/dashboard.php - Project Management System Dashboard
if (!defined('LSPL_SECURE_ROUTE')) {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    header("Location: " . $base_path);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['pms_role'] ?? '';
$user_username = $_SESSION['pms_username'] ?? '';

// Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ' . $base_path);
    exit;
}

$success_msg = '';
$error_msg = '';

// Helper function to send invoice/estimate/proposal emails (SMTP or Mock)
function send_pms_email($db, $site, $recipient, $subject, $body, $attachment_type = null) {
    $smtp_host = $site['smtp_host'] ?? '';
    $smtp_port = $site['smtp_port'] ?? '587';
    $smtp_user = $site['smtp_user'] ?? '';
    $smtp_pass = $site['smtp_pass'] ?? '';
    $from_email = $site['contact_email'] ?? 'billing@longwaysoftronix.com';
    $company_name = $site['company_name'] ?? 'Longway Softronix Pvt. Ltd.';

    $sent = false;
    $log_message = "Mocked Email Sent: Local SMTP config empty.";

    // If SMTP details are populated, we try real SMTP mail sending
    if (!empty($smtp_host) && !empty($smtp_user) && !empty($smtp_pass)) {
        try {
            // Very simple native PHP SMTP sending implementation
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . $company_name . " <" . $from_email . ">\r\n";
            $headers .= "Reply-To: " . $from_email . "\r\n";
            
            // On Linux/aaPanel, native mail() uses configured postfix/sendmail
            $sent = mail($recipient, $subject, $body, $headers);
            if ($sent) {
                $log_message = "Real email sent successfully via PHP mail().";
            } else {
                $log_message = "PHP mail() returned false. Falling back to log.";
            }
        } catch (Exception $e) {
            $log_message = "Real email sending exception: " . $e->getMessage() . ". Falling back to log.";
        }
    }

    // Always log the email to email_logs for local visibility/testing!
    $stmt = $db->prepare("INSERT INTO email_logs (recipient, subject, body, attachment_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$recipient, $subject, $body, $attachment_type]);
    
    return $log_message;
}

// -------------------------------------------------------------
// POST REQUEST HANDLER
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        if ($action === 'add_client') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $company = trim($_POST['company']);
            $address = trim($_POST['address']);
            
            if (empty($name) || empty($email)) { throw new Exception("Name and Email are required."); }
            
            $stmt = $db->prepare("INSERT INTO clients (name, email, phone, company, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $company, $address]);
            $success_msg = "Client added successfully.";
            
        } elseif ($action === 'edit_client') {
            $id = (int)$_POST['client_id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $company = trim($_POST['company']);
            $address = trim($_POST['address']);
            
            if (empty($name) || empty($email)) { throw new Exception("Name and Email are required."); }
            
            $stmt = $db->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, company = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $company, $address, $id]);
            $success_msg = "Client updated successfully.";
            
        } elseif ($action === 'delete_client') {
            $id = (int)$_POST['client_id'];
            $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = "Client deleted successfully.";
            
        } elseif ($action === 'add_project') {
            $client_id = (int)$_POST['client_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            $total_budget = (float)$_POST['total_budget'];
            
            if (empty($title)) { throw new Exception("Project title is required."); }
            
            $stmt = $db->prepare("INSERT INTO projects (client_id, title, description, status, total_budget) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $title, $description, $status, $total_budget]);
            $success_msg = "Project created successfully.";
            
        } elseif ($action === 'edit_project') {
            $id = (int)$_POST['project_id'];
            $client_id = (int)$_POST['client_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            $total_budget = (float)$_POST['total_budget'];
            
            if (empty($title)) { throw new Exception("Project title is required."); }
            
            $stmt = $db->prepare("UPDATE projects SET client_id = ?, title = ?, description = ?, status = ?, total_budget = ? WHERE id = ?");
            $stmt->execute([$client_id, $title, $description, $status, $total_budget, $id]);
            $success_msg = "Project details updated.";
            
        } elseif ($action === 'delete_project') {
            $id = (int)$_POST['project_id'];
            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = "Project deleted successfully.";
            
        } elseif ($action === 'add_milestone') {
            $project_id = (int)$_POST['project_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $amount = (float)$_POST['amount'];
            $due_date = trim($_POST['due_date']);
            
            if (empty($title)) { throw new Exception("Milestone title is required."); }
            
            $stmt = $db->prepare("INSERT INTO milestones (project_id, title, description, amount, due_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$project_id, $title, $description, $amount, $due_date]);
            $success_msg = "Milestone created.";
            
        } elseif ($action === 'edit_milestone') {
            $id = (int)$_POST['milestone_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $amount = (float)$_POST['amount'];
            $due_date = trim($_POST['due_date']);
            
            if (empty($title)) { throw new Exception("Milestone title is required."); }
            
            $stmt = $db->prepare("UPDATE milestones SET title = ?, description = ?, amount = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$title, $description, $amount, $due_date, $id]);
            $success_msg = "Milestone details updated.";
            
        } elseif ($action === 'delete_milestone') {
            $id = (int)$_POST['milestone_id'];
            $stmt = $db->prepare("DELETE FROM milestones WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = "Milestone deleted.";
            
        } elseif ($action === 'complete_milestone') {
            $id = (int)$_POST['milestone_id'];
            
            // Load milestone details
            $mst_stmt = $db->prepare("SELECT * FROM milestones WHERE id = ?");
            $mst_stmt->execute([$id]);
            $milestone = $mst_stmt->fetch();
            
            if (!$milestone) { throw new Exception("Milestone not found."); }
            if ($milestone['status'] === 'Completed') { throw new Exception("Milestone is already marked completed."); }
            
            $db->beginTransaction();
            
            // 1. Generate unique invoice number
            $invoice_number = "INV-" . date("Ymd") . "-" . mt_rand(100, 999);
            
            // 2. Create Invoice
            $inv_stmt = $db->prepare("INSERT INTO invoices (invoice_number, amount, status) VALUES (?, ?, 'Unpaid')");
            $inv_stmt->execute([$invoice_number, $milestone['amount']]);
            $invoice_id = $db->lastInsertId();
            
            // 3. Update Milestone status & link invoice
            $update_mst = $db->prepare("UPDATE milestones SET status = 'Completed', completed_at = CURRENT_TIMESTAMP, invoice_id = ? WHERE id = ?");
            $update_mst->execute([$invoice_id, $id]);
            
            $db->commit();
            $success_msg = "Milestone marked completed! Invoice " . $invoice_number . " generated automatically.";
            
        } elseif ($action === 'send_invoice_email') {
            $invoice_id = (int)$_POST['invoice_id'];
            
            // Fetch invoice, milestone, project, and client details
            $q = $db->prepare("
                SELECT i.*, m.title as milestone_title, p.title as project_title, c.name as client_name, c.email as client_email, c.company as client_company
                FROM invoices i
                JOIN milestones m ON m.invoice_id = i.id
                JOIN projects p ON m.project_id = p.id
                JOIN clients c ON p.client_id = c.id
                WHERE i.id = ?
            ");
            $q->execute([$invoice_id]);
            $inv_data = $q->fetch();
            
            if (!$inv_data) { throw new Exception("Invoice details not found."); }
            if (isset($inv_data['is_verified']) && (int)$inv_data['is_verified'] !== 1) {
                throw new Exception("Invoice cannot be sent to the client because it has not been verified yet.");
            }
            
            // Generate beautiful HTML invoice body
            $subject = "Invoice " . $inv_data['invoice_number'] . " from " . ($site['company_name'] ?? 'LSXPL');
            
            $logo_html = "";
            if (!empty($site['company_logo_url'])) {
                $logo_html = "<img src='" . htmlspecialchars($site['company_logo_url']) . "' alt='" . htmlspecialchars($site['company_name'] ?? 'Company Logo') . "' style='max-height: 50px; max-width: 200px; object-fit: contain; margin-bottom: 10px;'>";
            } else {
                $logo_html = "
                <div style='display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 8px; background: linear-gradient(135deg, #a855f7, #6366f1); color: #ffffff; font-weight: bold; font-size: 18px; margin-bottom: 8px;'>
                    " . strtoupper(substr($site['company_name'] ?? 'L', 0, 1)) . "
                </div>
                ";
            }

            $body = "
            <div style='font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); color: #2d3748;'>
                <!-- Header Section -->
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 25px;'>
                    <tr>
                        <td style='vertical-align: top;'>
                            " . $logo_html . "
                            <div style='font-size: 1.15rem; font-weight: bold; color: #1a202c;'>" . htmlspecialchars($site['company_name'] ?? 'Project Hub') . "</div>
                            <div style='font-size: 0.85rem; color: #718096; margin-top: 2px;'>" . htmlspecialchars($site['contact_email'] ?? '') . "</div>
                        </td>
                        <td style='vertical-align: top; text-align: right;'>
                            <div style='font-size: 1.75rem; font-weight: 300; color: #4a5568; letter-spacing: 0.05em;'>INVOICE</div>
                            <div style='font-size: 0.9rem; color: #4a5568; margin-top: 5px;'>Invoice Number: <strong>" . htmlspecialchars($inv_data['invoice_number']) . "</strong></div>
                            <div style='font-size: 0.85rem; color: #718096; margin-top: 3px;'>Date: " . date("M d, Y", strtotime($inv_data['created_at'])) . "</div>
                        </td>
                    </tr>
                </table>

                <!-- Highlighted Amount Due Banner (PayPal Style) -->
                <div style='background: linear-gradient(135deg, #003087 0%, #0079c1 100%); border-radius: 8px; padding: 25px; color: #ffffff; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0, 48, 135, 0.15);'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td>
                                <div style='font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.8);'>Amount Due</div>
                                <div style='font-size: 2.25rem; font-weight: bold; margin-top: 5px;'>$" . number_format($inv_data['amount'], 2) . " USD</div>
                            </td>
                            <td style='text-align: right; vertical-align: middle;'>
                                <a href='#' style='display: inline-block; background-color: #ffc439; color: #111111; font-weight: bold; text-decoration: none; padding: 12px 24px; border-radius: 30px; font-size: 0.95rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>Pay Invoice</a>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Billing Information -->
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>
                    <tr>
                        <td style='width: 50%; vertical-align: top;'>
                            <div style='font-size: 0.85rem; font-weight: bold; text-transform: uppercase; color: #a0aec0; margin-bottom: 8px; letter-spacing: 0.05em;'>Bill To</div>
                            <div style='font-size: 0.95rem; font-weight: bold; color: #2d3748;'>" . htmlspecialchars($inv_data['client_name']) . "</div>
                            <div style='font-size: 0.9rem; color: #4a5568; margin-top: 2px;'>" . htmlspecialchars($inv_data['client_company']) . "</div>
                            <div style='font-size: 0.85rem; color: #718096; margin-top: 2px;'><a href='mailto:" . htmlspecialchars($inv_data['client_email']) . "' style='color: #0070ba; text-decoration: none;'>" . htmlspecialchars($inv_data['client_email']) . "</a></div>
                        </td>
                        <td style='width: 50%; vertical-align: top; text-align: right;'>
                        </td>
                    </tr>
                </table>

                <!-- Itemized Description Table -->
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 35px;'>
                    <thead>
                        <tr style='background-color: #f7fafc; border-bottom: 2px solid #edf2f7;'>
                            <th style='padding: 12px 10px; text-align: left; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; color: #718096;'>Description</th>
                            <th style='padding: 12px 10px; text-align: right; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; color: #718096; width: 120px;'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style='border-bottom: 1px solid #edf2f7;'>
                            <td style='padding: 16px 10px; vertical-align: top;'>
                                <div style='font-weight: bold; font-size: 0.95rem; color: #2d3748;'>" . htmlspecialchars($inv_data['milestone_title']) . "</div>
                                <div style='font-size: 0.8rem; color: #718096; margin-top: 4px;'>Project: " . htmlspecialchars($inv_data['project_title']) . "</div>
                            </td>
                            <td style='padding: 16px 10px; text-align: right; vertical-align: top; font-weight: bold; font-size: 0.95rem; color: #2d3748;'>
                                $" . number_format($inv_data['amount'], 2) . "
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 15px 10px; text-align: right; font-size: 0.9rem; color: #718096;'>Total:</td>
                            <td style='padding: 15px 10px; text-align: right; font-weight: bold; font-size: 1.1rem; color: #2d3748; border-top: 1px solid #edf2f7;'>
                                $" . number_format($inv_data['amount'], 2) . " USD
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer Section -->
                <div style='border-top: 1px solid #edf2f7; padding-top: 20px; text-align: center; font-size: 0.8rem; color: #a0aec0;'>
                    <p style='margin-bottom: 5px;'>If you have any questions, please reach out to us at <a href='mailto:" . htmlspecialchars($site['contact_email'] ?? '') . "' style='color: #0070ba; text-decoration: none;'>" . htmlspecialchars($site['contact_email'] ?? '') . "</a></p>
                    <p style='margin-top: 0;'>Thank you for your business!</p>
                </div>
            </div>
            ";
            
            $log_info = send_pms_email($db, $site, $inv_data['client_email'], $subject, $body, 'Invoice');
            
            // Mark invoice sent
            $upd = $db->prepare("UPDATE invoices SET sent_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$invoice_id]);
            
            $success_msg = "Invoice sent to client " . $inv_data['client_email'] . " successfully! (" . $log_info . ")";
            
        } elseif ($action === 'mark_invoice_paid') {
            $invoice_id = (int)$_POST['invoice_id'];
            $stmt = $db->prepare("UPDATE invoices SET status = 'Paid' WHERE id = ?");
            $stmt->execute([$invoice_id]);
            $success_msg = "Invoice marked paid.";
            
        } elseif ($action === 'verify_invoice') {
            $invoice_id = (int)$_POST['invoice_id'];
            $stmt = $db->prepare("UPDATE invoices SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$invoice_id]);
            $success_msg = "Invoice marked as verified and is ready to send.";
            
        } elseif ($action === 'edit_invoice') {
            $invoice_id = (int)$_POST['invoice_id'];
            $invoice_number = trim($_POST['invoice_number']);
            $amount = (float)$_POST['amount'];
            $status = $_POST['status'];
            $is_verified = (int)$_POST['is_verified'];
            
            if (empty($invoice_number)) { throw new Exception("Invoice number is required."); }
            
            $stmt = $db->prepare("UPDATE invoices SET invoice_number = ?, amount = ?, status = ?, is_verified = ? WHERE id = ?");
            $stmt->execute([$invoice_number, $amount, $status, $is_verified, $invoice_id]);
            $success_msg = "Invoice details updated successfully.";
            
        } elseif ($action === 'save_estimate') {
            $project_id = (int)$_POST['project_id'];
            $items_desc = $_POST['item_desc'] ?? [];
            $items_qty = $_POST['item_qty'] ?? [];
            $items_rate = $_POST['item_rate'] ?? [];
            
            $items_array = [];
            $total_amount = 0.0;
            
            for ($i = 0; $i < count($items_desc); $i++) {
                if (empty($items_desc[$i])) continue;
                $qty = (float)$items_qty[$i];
                $rate = (float)$items_rate[$i];
                $amount = $qty * $rate;
                $total_amount += $amount;
                
                $items_array[] = [
                    'description' => trim($items_desc[$i]),
                    'qty' => $qty,
                    'rate' => $rate,
                    'amount' => $amount
                ];
            }
            
            if (empty($items_array)) { throw new Exception("At least one estimate item is required."); }
            
            $items_json = json_encode($items_array);
            
            // Check if estimate already exists for the project
            $chk = $db->prepare("SELECT id FROM estimates WHERE project_id = ? LIMIT 1");
            $chk->execute([$project_id]);
            $exist = $chk->fetch();
            
            if ($exist) {
                $stmt = $db->prepare("UPDATE estimates SET items = ?, total_amount = ?, status = 'Draft' WHERE id = ?");
                $stmt->execute([$items_json, $total_amount, $exist['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO estimates (project_id, items, total_amount, status) VALUES (?, ?, ?, 'Draft')");
                $stmt->execute([$project_id, $items_json, $total_amount]);
            }
            
            $success_msg = "Project estimate saved successfully.";
            
        } elseif ($action === 'send_estimate') {
            $estimate_id = (int)$_POST['estimate_id'];
            
            $q = $db->prepare("
                SELECT e.*, p.title as project_title, c.name as client_name, c.email as client_email
                FROM estimates e
                JOIN projects p ON e.project_id = p.id
                JOIN clients c ON p.client_id = c.id
                WHERE e.id = ?
            ");
            $q->execute([$estimate_id]);
            $est = $q->fetch();
            
            if (!$est) { throw new Exception("Estimate details not found."); }
            
            $items = json_decode($est['items'], true);
            $items_html = "";
            foreach ($items as $item) {
                $items_html .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #e2e8f0;'>" . htmlspecialchars($item['description']) . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: center;'>" . $item['qty'] . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: right;'>$" . number_format($item['rate'], 2) . "</td>
                    <td style='padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: right;'>$" . number_format($item['amount'], 2) . "</td>
                </tr>
                ";
            }
            
            $subject = "Project Estimate: " . $est['project_title'];
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px;'>Budget Proposal &amp; Estimate</h2>
                <p>Hello <strong>" . htmlspecialchars($est['client_name']) . "</strong>,</p>
                <p>Please find the estimate for your project <strong>" . htmlspecialchars($est['project_title']) . "</strong> detailed below:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <thead>
                        <tr style='background-color: #f8fafc;'>
                            <th style='padding: 8px; text-align: left; border-bottom: 2px solid #e2e8f0;'>Description</th>
                            <th style='padding: 8px; text-align: center; border-bottom: 2px solid #e2e8f0;'>Qty</th>
                            <th style='padding: 8px; text-align: right; border-bottom: 2px solid #e2e8f0;'>Rate</th>
                            <th style='padding: 8px; text-align: right; border-bottom: 2px solid #e2e8f0;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $items_html . "
                        <tr style='font-weight: bold;'>
                            <td colspan='3' style='padding: 10px; text-align: right;'>Total Estimated Cost:</td>
                            <td style='padding: 10px; text-align: right; color: #6366f1;'>$" . number_format($est['total_amount'], 2) . "</td>
                        </tr>
                    </tbody>
                </table>
                
                <p>To accept this estimate and initiate the project milestones, please respond to this email.</p>
                <p>Best Regards,<br><strong>" . htmlspecialchars($site['company_name'] ?? 'LSXPL') . "</strong></p>
            </div>
            ";
            
            $log_info = send_pms_email($db, $site, $est['client_email'], $subject, $body, 'Estimate');
            
            $upd = $db->prepare("UPDATE estimates SET status = 'Sent' WHERE id = ?");
            $upd->execute([$estimate_id]);
            
            $success_msg = "Estimate sent to client " . $est['client_email'] . "! (" . $log_info . ")";
            
        } elseif ($action === 'ajax_generate_proposal') {
            header('Content-Type: application/json');
            $project_id = (int)$_POST['project_id'];
            
            // Load project and client data
            $q = $db->prepare("
                SELECT p.*, c.name as client_name, c.company as client_company
                FROM projects p
                JOIN clients c ON p.client_id = c.id
                WHERE p.id = ?
            ");
            $q->execute([$project_id]);
            $proj = $q->fetch();
            
            if (!$proj) { throw new Exception("Project details not found."); }
            
            require_once __DIR__ . '/ai_service.php';
            $proposal_text = "";
            $used_ai = false;
            
            $provider = $site['ai_provider'] ?? 'Disabled';
            $key = $site['ai_api_key'] ?? '';
            $model_id = $site['ai_model_id'] ?? '';
            $endpoint = $site['ai_endpoint'] ?? '';
            
            // Backwards compatibility fallback if they only configured the old gemini_api_key setting
            if ($provider === 'Disabled' && !empty($site['gemini_api_key'])) {
                $provider = 'Gemini';
                $key = $site['gemini_api_key'];
                $model_id = 'gemini-1.5-flash';
            }
            
            if ($provider !== 'Disabled' && (!empty($key) || $provider === 'Ollama')) {
                try {
                    $prompt = "Write a professional software development project proposal for " . $proj['client_name'] . " from company '" . $proj['client_company'] . "' based on this project title '" . $proj['title'] . "' and project description: " . $proj['description'] . ". Output in clean HTML using tags like <h3>, <p>, <ul>, <li>, <strong>, without full page boilerplate code. Include: Executive Summary, Project Goals, Scope of Work, and Project Workflow Timeline.";
                    
                    $ai_result = PmsAiService::generateText($provider, $key, $model_id, $prompt, $endpoint);
                    if (!empty($ai_result)) {
                        $proposal_text = $ai_result;
                        $used_ai = true;
                    }
                } catch (Exception $e) {
                    error_log("AI generation exception: " . $e->getMessage());
                }
            }
            
            // Fallback template-based proposal if API call failed or key is missing
            if (!$used_ai) {
                $proposal_text = "
                <div class='proposal-template'>
                    <h3>Executive Summary</h3>
                    <p>We are delighted to submit this project proposal to <strong>" . htmlspecialchars($proj['client_name']) . "</strong> representing <strong>" . htmlspecialchars($proj['client_company']) . "</strong>. Our team is fully committed to delivering high-quality custom technical solutions for <strong>" . htmlspecialchars($proj['title']) . "</strong>.</p>
                    
                    <h3>Project Objectives</h3>
                    <p>Based on your description: <em>" . htmlspecialchars($proj['description']) . "</em>, we have identified the following core deliverables:</p>
                    <ul>
                        <li><strong>Scalability:</strong> Ensuring the architecture supports future business growth.</li>
                        <li><strong>Performance:</strong> Achieving fast load-times and optimized backend database lookups.</li>
                        <li><strong>Security:</strong> Implementing state-of-the-art authentication protocols and API route protection.</li>
                    </ul>
                    
                    <h3>Proposed Development Lifecycle</h3>
                    <p>Our lifecycle consists of 4 distinct phases:</p>
                    <ol>
                        <li><strong>Phase 1 (Requirements Discovery):</strong> Wireframing, database schema planning, and asset definition.</li>
                        <li><strong>Phase 2 (Development Sprint):</strong> Implementing core code architecture, routing rules, and frontend styling.</li>
                        <li><strong>Phase 3 (Testing &amp; QA):</strong> Code linting, security testing, and integration verification.</li>
                        <li><strong>Phase 4 (Deployment &amp; Handover):</strong> Subdomain DNS updates, site caching, and final launch.</li>
                    </ol>
                    
                    <h3>Total Estimated Budget</h3>
                    <p>The total estimated budget for this project is <strong>$" . number_format($proj['total_budget'], 2) . "</strong>, billed progressively on milestone completions.</p>
                </div>
                ";
            }
            
            // Insert/Replace proposal record
            $chk = $db->prepare("SELECT id FROM proposals WHERE project_id = ? LIMIT 1");
            $chk->execute([$project_id]);
            $exist = $chk->fetch();
            
            if ($exist) {
                $stmt = $db->prepare("UPDATE proposals SET content = ?, status = 'Draft' WHERE id = ?");
                $stmt->execute([$proposal_text, $exist['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO proposals (project_id, content, status) VALUES (?, ?, 'Draft')");
                $stmt->execute([$proposal_text, $project_id]);
            }
            
            echo json_encode([
                'success' => true,
                'proposal_content' => $proposal_text,
                'message' => $used_ai ? 'AI proposal generated using Gemini API!' : 'Template-based proposal generated (Gemini key not configured).'
            ]);
            exit;
            
        } elseif ($action === 'update_settings') {
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            foreach ($_POST['settings'] as $k => $v) {
                $stmt->execute([$k, trim($v)]);
            }
            $success_msg = "System configuration settings updated successfully.";
        }
    } catch (Exception $e) {
        // If it's an AJAX request, throw JSON response
        if ($action === 'ajax_generate_proposal') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        $error_msg = $e->getMessage();
    }
}

// -------------------------------------------------------------
// DATA RETRIEVAL FOR RENDERING
// -------------------------------------------------------------
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
if ($user_role === 'account_manager') {
    $allowed_tabs = ['overview', 'invoices', 'emails'];
} else {
    $allowed_tabs = ['overview', 'clients', 'projects', 'invoices', 'emails', 'settings'];
}
if (!in_array($current_tab, $allowed_tabs)) {
    $current_tab = $user_role === 'account_manager' ? 'invoices' : 'overview';
}

// Load Overview Statistics
$client_count = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$project_count = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$total_revenue = $db->query("SELECT SUM(amount) FROM invoices WHERE status = 'Paid'")->fetchColumn() ?: 0.0;
$pending_invoices = $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'Unpaid'")->fetchColumn();

// Load Lists
$clients = $db->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();
$projects = $db->query("
    SELECT p.*, c.name as client_name, c.company as client_company
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();
$email_logs = $db->query("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 50")->fetchAll();
$invoices = $db->query("
    SELECT i.*, m.title as milestone_title, p.title as project_title, c.name as client_name, c.company as client_company, c.email as client_email
    FROM invoices i
    JOIN milestones m ON m.invoice_id = i.id
    JOIN projects p ON m.project_id = p.id
    JOIN clients c ON p.client_id = c.id
    ORDER BY i.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site['site_title'] ?? 'LSXPL Project Hub'); ?> | Workspace</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="brand-section">
                <i data-lucide="layout-dashboard" style="color: hsl(var(--primary)); width: 24px; height: 24px;"></i>
                <span><?php echo htmlspecialchars($site['site_title'] ?? 'LSXPL Project Hub'); ?></span>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
                    <a href="?tab=overview"><i data-lucide="activity"></i> Console Overview</a>
                </li>
                <?php if ($user_role !== 'account_manager'): ?>
                    <li class="nav-item <?php echo $current_tab === 'clients' ? 'active' : ''; ?>">
                        <a href="?tab=clients"><i data-lucide="users"></i> Client Contacts</a>
                    </li>
                    <li class="nav-item <?php echo $current_tab === 'projects' ? 'active' : ''; ?>">
                        <a href="?tab=projects"><i data-lucide="folder-kanban"></i> Projects &amp; CRM</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item <?php echo $current_tab === 'invoices' ? 'active' : ''; ?>">
                    <a href="?tab=invoices"><i data-lucide="receipt"></i> Invoices &amp; Billings</a>
                </li>
                <li class="nav-item <?php echo $current_tab === 'emails' ? 'active' : ''; ?>">
                    <a href="?tab=emails"><i data-lucide="mail"></i> Outbox Email Log</a>
                </li>
                <?php if ($user_role !== 'account_manager'): ?>
                    <li class="nav-item <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                        <a href="?tab=settings"><i data-lucide="settings"></i> System Config</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item" style="margin-top: auto;">
                    <a href="?action=logout" style="color: hsl(var(--destructive));"><i data-lucide="log-out"></i> Log Out</a>
                </li>
            </ul>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="app-header">
                <div>
                    <span style="font-size: 0.85rem; color: hsl(var(--muted-foreground));">Active System Workspace</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_username, 0, 1)); ?></div>
                    <div style="font-size: 0.9rem;">
                        <strong><?php echo htmlspecialchars($user_username); ?></strong> 
                        <span style="font-size:0.75rem; color:hsl(var(--muted-foreground));">(<?php echo htmlspecialchars($user_role); ?>)</span>
                    </div>
                </div>
            </header>

            <div class="page-container">
                <!-- Notifications -->
                <?php if (!empty($success_msg)): ?>
                    <div class="alert-box success">
                        <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                        <span><?php echo htmlspecialchars($success_msg); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert-box error">
                        <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                        <span><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- ------------------------------------------------------------- -->
                <!-- OVERVIEW TAB -->
                <!-- ------------------------------------------------------------- -->
                <?php if ($current_tab === 'overview'): ?>
                    <div class="stats-grid">
                        <div class="glass-card">
                            <p>Total Revenue (Paid)</p>
                            <div class="card-stat-value" style="color: #10b981;">$<?php echo number_format($total_revenue, 2); ?></div>
                        </div>
                        <div class="glass-card">
                            <p>Active Clients</p>
                            <div class="card-stat-value"><?php echo $client_count; ?></div>
                        </div>
                        <div class="glass-card">
                            <p>Projects Ongoing</p>
                            <div class="card-stat-value"><?php echo $project_count; ?></div>
                        </div>
                        <div class="glass-card">
                            <p>Pending Invoices</p>
                            <div class="card-stat-value" style="color: #ef4444;"><?php echo $pending_invoices; ?></div>
                        </div>
                    </div>

                    <div class="content-grid">
                        <!-- Left Content: Projects Summary -->
                        <div class="glass-card">
                            <h3>Project Summary</h3>
                            <div class="table-container">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Client</th>
                                            <th>Budget</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($projects)): ?>
                                            <tr><td colspan="4" style="text-align: center; color: var(--muted-foreground);">No projects active. Create one under the Projects &amp; CRM tab.</td></tr>
                                        <?php else: ?>
                                            <?php foreach (array_slice($projects, 0, 5) as $p): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($p['client_company']); ?></td>
                                                    <td>$<?php echo number_format($p['total_budget'], 2); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $p['status'])); ?>">
                                                            <?php echo $p['status']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Right Content: Quick Actions -->
                        <div class="glass-card">
                            <h3>Quick Actions</h3>
                            <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
                                <button class="btn btn-primary" onclick="openModal('addClientModal')"><i data-lucide="user-plus"></i> Add Client</button>
                                <button class="btn btn-secondary" onclick="openModal('addProjectModal')"><i data-lucide="plus-circle"></i> Create Project</button>
                            </div>
                        </div>
                    </div>

                <!-- ------------------------------------------------------------- -->
                <!-- CLIENTS TAB -->
                <!-- ------------------------------------------------------------- -->
                <?php elseif ($current_tab === 'clients'): ?>
                    <div class="glass-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>Client Contacts &amp; Database</h3>
                            <button class="btn btn-primary" onclick="openModal('addClientModal')"><i data-lucide="user-plus"></i> Add Client</button>
                        </div>

                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Company</th>
                                        <th>Address</th>
                                        <th style="width: 100px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($clients)): ?>
                                        <tr><td colspan="6" style="text-align: center;">No client profiles found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($clients as $c): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                                <td><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>" style="color: hsl(var(--primary));"><?php echo htmlspecialchars($c['email']); ?></a></td>
                                                <td><?php echo htmlspecialchars($c['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($c['company']); ?></td>
                                                <td style="font-size:0.8rem;"><?php echo htmlspecialchars($c['address']); ?></td>
                                                <td style="display: flex; gap: 0.5rem; justify-content: center;">
                                                    <button class="btn btn-outline" style="padding: 0.25rem 0.5rem;" onclick='openEditClientModal(<?php echo json_encode($c); ?>)'><i data-lucide="edit-3" style="width:14px; height:14px;"></i></button>
                                                    <form method="POST" onsubmit="return confirm('Delete client and all linked projects?');" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_client">
                                                        <input type="hidden" name="client_id" value="<?php echo $c['id']; ?>">
                                                        <button type="submit" class="btn btn-destructive" style="padding: 0.25rem 0.5rem;"><i data-lucide="trash-2" style="width:14px; height:14px;"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- ------------------------------------------------------------- -->
                <!-- PROJECTS & CRM TAB -->
                <!-- ------------------------------------------------------------- -->
                <?php elseif ($current_tab === 'projects'): ?>
                    <div class="glass-card" style="margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>Project Lifecycles &amp; Billings</h3>
                            <button class="btn btn-primary" onclick="openModal('addProjectModal')"><i data-lucide="plus-circle"></i> Create Project</button>
                        </div>

                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Project Details</th>
                                        <th>Assigned Client</th>
                                        <th>Total Budget</th>
                                        <th>Status</th>
                                        <th>Management Workspace</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($projects)): ?>
                                        <tr><td colspan="5" style="text-align: center;">No projects created.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($projects as $p): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                                                    <div style="font-size: 0.8rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                                                        <?php echo htmlspecialchars(substr($p['description'], 0, 80)); ?><?php echo strlen($p['description']) > 80 ? '...' : ''; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($p['client_name']); ?></strong>
                                                    <div style="font-size: 0.75rem; color: var(--muted-foreground);"><?php echo htmlspecialchars($p['client_company']); ?></div>
                                                </td>
                                                <td>$<?php echo number_format($p['total_budget'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $p['status'])); ?>">
                                                        <?php echo $p['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                        <button class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="triggerGenerateProposal(<?php echo $p['id']; ?>)">
                                                            <i data-lucide="wand-2" style="width:14px; height:14px;"></i> AI Proposal
                                                        </button>
                                                        <button class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="openEstimateBuilder(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['title'])); ?>')">
                                                            <i data-lucide="calculator" style="width:14px; height:14px;"></i> Estimate
                                                        </button>
                                                        <button class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="openMilestonesWorkspace(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['title'])); ?>')">
                                                            <i data-lucide="milestone" style="width:14px; height:14px;"></i> Milestones
                                                        </button>
                                                        <button class="btn btn-outline" style="padding: 0.35rem 0.5rem;" onclick='openEditProjectModal(<?php echo json_encode($p); ?>)'><i data-lucide="edit" style="width:14px; height:14px;"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- ------------------------------------------------------------- -->
                <!-- OUTBOX EMAIL LOG TAB -->
                <!-- ------------------------------------------------------------- -->
                <?php elseif ($current_tab === 'emails'): ?>
                    <div class="glass-card">
                        <h3>Outbox Email Log (Local Trace)</h3>
                        <p style="margin-bottom: 1.5rem; font-size: 0.85rem;">This log displays all billing estimates and invoices generated and dispatched from the project workspace. Verify attachments and styling here.</p>
                        
                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Date Sent</th>
                                        <th>Recipient Client</th>
                                        <th>Subject</th>
                                        <th>Attachment</th>
                                        <th style="width: 100px; text-align: center;">View Body</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($email_logs)): ?>
                                        <tr><td colspan="5" style="text-align: center;">Outbox is currently empty. Complete milestones or send estimates to log communications.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($email_logs as $log): ?>
                                            <tr>
                                                <td style="font-size: 0.85rem;"><?php echo $log['sent_at']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($log['recipient']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($log['subject']); ?></td>
                                                <td>
                                                    <span class="badge" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                                                        <i data-lucide="file-text" style="width: 12px; height:12px; margin-right: 0.25rem;"></i> <?php echo htmlspecialchars($log['attachment_type'] ?: 'Standard Message'); ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: center;">
                                                    <button class="btn btn-outline" style="padding: 0.25rem 0.5rem;" onclick='viewEmailBody(<?php echo json_encode($log); ?>)'><i data-lucide="eye" style="width: 14px; height: 14px;"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- ------------------------------------------------------------- -->
                <!-- INVOICES & BILLINGS TAB -->
                <!-- ------------------------------------------------------------- -->
                <?php elseif ($current_tab === 'invoices'): ?>
                    <div class="glass-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>All Generated Invoices</h3>
                        </div>

                        <div class="table-container">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Project / Client</th>
                                        <th>Amount</th>
                                        <th>Date Generated</th>
                                        <th>Verification</th>
                                        <th>Payment Status</th>
                                        <th style="width: 180px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                        <tr><td colspan="7" style="text-align: center;">No invoices found in database. Complete milestone phases to auto-generate invoices.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($invoices as $inv): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($inv['project_title']); ?></strong>
                                                    <div style="font-size: 0.75rem; color: var(--muted-foreground);"><?php echo htmlspecialchars($inv['client_company'] ?: $inv['client_name']); ?></div>
                                                </td>
                                                <td style="font-weight: bold; color: hsl(var(--primary));">$<?php echo number_format($inv['amount'], 2); ?></td>
                                                <td style="font-size: 0.8rem;"><?php echo $inv['created_at']; ?></td>
                                                <td>
                                                    <?php if ((int)($inv['is_verified'] ?? 0) === 1): ?>
                                                        <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981;">Verified</span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b;">Pending Verification</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo strtolower($inv['status']); ?>">
                                                        <?php echo $inv['status']; ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: center;">
                                                    <div style="display: flex; gap: 0.35rem; justify-content: center; align-items: center;">
                                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem;" title="Edit Invoice Details" onclick='openEditInvoiceModal(<?php echo json_encode($inv); ?>)'>
                                                            <i data-lucide="edit-3" style="width:14px; height:14px;"></i>
                                                        </button>
                                                        
                                                        <?php if ((int)($inv['is_verified'] ?? 0) === 0): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="action" value="verify_invoice">
                                                                <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                                                <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;" title="Verify Invoice">
                                                                    <i data-lucide="shield-check" style="width:14px; height:14px;"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="action" value="send_invoice_email">
                                                                <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                                                <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem;" title="Email Invoice Attachment">
                                                                    <i data-lucide="mail" style="width:14px; height:14px;"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($inv['status'] === 'Unpaid'): ?>
                                                            <form method="POST" action="" style="display: inline;">
                                                                <input type="hidden" name="action" value="mark_invoice_paid">
                                                                <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                                                <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;" title="Mark Paid">
                                                                    <i data-lucide="dollar-sign" style="width:14px; height:14px;"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- ------------------------------------------------------------- -->
                <!-- SYSTEM CONFIG TAB -->
                <!-- ------------------------------------------------------------- -->
                <?php elseif ($current_tab === 'settings'): ?>
                    <div class="glass-card">
                        <h3>Workspace System Configurations</h3>
                        <p style="margin-bottom: 1.5rem; font-size: 0.85rem;">Configure SMTP keys for real email output or seed the Gemini API Key to activate real-time AI proposal generation capabilities.</p>
                        
                        <form method="POST" action="" class="edit-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <h4 class="full-width" style="color: hsl(var(--primary)); border-bottom: 1px dashed var(--glass-border); padding-bottom: 0.5rem; margin-top: 1rem;">1. General Business Branding</h4>
                            
                            <div class="form-group">
                                <label for="site_title">System Workspace Title</label>
                                <input type="text" name="settings[site_title]" class="form-control" value="<?php echo htmlspecialchars($site['site_title'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" name="settings[company_name]" class="form-control" value="<?php echo htmlspecialchars($site['company_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Billing Support Email</label>
                                <input type="email" name="settings[contact_email]" class="form-control" value="<?php echo htmlspecialchars($site['contact_email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="company_logo_url">Company Logo URL (HTML Emails & Invoices)</label>
                                <input type="text" name="settings[company_logo_url]" class="form-control" value="<?php echo htmlspecialchars($site['company_logo_url'] ?? ''); ?>" placeholder="https://yourcompany.com/logo.png">
                            </div>

                            <h4 class="full-width" style="color: hsl(var(--primary)); border-bottom: 1px dashed var(--glass-border); padding-bottom: 0.5rem; margin-top: 1.5rem;">2. Multi-Model AI Service Configurations</h4>
                            
                            <div class="form-group">
                                <label for="ai_provider">Active AI Provider</label>
                                <select name="settings[ai_provider]" id="ai_provider" class="form-control">
                                    <option value="Disabled" <?php echo ($site['ai_provider'] ?? '') === 'Disabled' ? 'selected' : ''; ?>>Disabled / Mock Placeholder</option>
                                    <option value="Gemini" <?php echo ($site['ai_provider'] ?? '') === 'Gemini' ? 'selected' : ''; ?>>Google Gemini API</option>
                                    <option value="ChatGPT" <?php echo ($site['ai_provider'] ?? '') === 'ChatGPT' ? 'selected' : ''; ?>>OpenAI ChatGPT API</option>
                                    <option value="Groq" <?php echo ($site['ai_provider'] ?? '') === 'Groq' ? 'selected' : ''; ?>>Groq Cloud API</option>
                                    <option value="Ollama" <?php echo ($site['ai_provider'] ?? '') === 'Ollama' ? 'selected' : ''; ?>>Local Ollama Integration</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="ai_model_id">Target Model ID</label>
                                <input type="text" name="settings[ai_model_id]" class="form-control" value="<?php echo htmlspecialchars($site['ai_model_id'] ?? ''); ?>" placeholder="e.g. gemini-1.5-flash, gpt-4o-mini, llama3">
                            </div>
                            
                            <div class="form-group">
                                <label for="ai_api_key">AI Provider Secret Key</label>
                                <input type="password" name="settings[ai_api_key]" class="form-control" value="<?php echo htmlspecialchars($site['ai_api_key'] ?? ''); ?>" placeholder="API Secret Key (Keep empty if using local Ollama)">
                            </div>

                            <div class="form-group">
                                <label for="ai_endpoint">Connection Endpoint URL</label>
                                <input type="text" name="settings[ai_endpoint]" class="form-control" value="<?php echo htmlspecialchars($site['ai_endpoint'] ?? ''); ?>" placeholder="e.g. http://localhost:11434/api/generate">
                            </div>

                            <h4 class="full-width" style="color: hsl(var(--primary)); border-bottom: 1px dashed var(--glass-border); padding-bottom: 0.5rem; margin-top: 1.5rem;">3. Real SMTP Mail Server Configuration (Optional)</h4>
                            
                            <div class="form-group">
                                <label for="smtp_host">SMTP Server Host</label>
                                <input type="text" name="settings[smtp_host]" class="form-control" value="<?php echo htmlspecialchars($site['smtp_host'] ?? ''); ?>" placeholder="smtp.mailgun.org">
                            </div>
                            <div class="form-group">
                                <label for="smtp_port">SMTP Server Port</label>
                                <input type="text" name="settings[smtp_port]" class="form-control" value="<?php echo htmlspecialchars($site['smtp_port'] ?? '587'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="smtp_user">SMTP Username</label>
                                <input type="text" name="settings[smtp_user]" class="form-control" value="<?php echo htmlspecialchars($site['smtp_user'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="smtp_pass">SMTP Password</label>
                                <input type="password" name="settings[smtp_pass]" class="form-control" value="<?php echo htmlspecialchars($site['smtp_pass'] ?? ''); ?>">
                            </div>
                            
                            <div class="full-width" style="margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Save System Config</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ------------------------------------------------------------- -->
    <!-- MODALS AND POPUPS -->
    <!-- ------------------------------------------------------------- -->

    <!-- Add/Edit Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3 id="clientModalTitle">Add New Client Contact</h3>
                <button class="modal-close-btn" onclick="closeModal('addClientModal')"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="hidden" name="action" id="clientFormAction" value="add_client">
                <input type="hidden" name="client_id" id="clientFormId" value="">
                
                <div class="form-group">
                    <label for="c_name">Client Contact Name</label>
                    <input type="text" name="name" id="c_name" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="c_email">Client Email Address</label>
                    <input type="email" name="email" id="c_email" class="form-control" placeholder="e.g. john@company.com" required>
                </div>
                
                <div class="form-group">
                    <label for="c_phone">Client Phone Number</label>
                    <input type="text" name="phone" id="c_phone" class="form-control" placeholder="e.g. +1 555 1234">
                </div>
                
                <div class="form-group">
                    <label for="c_company">Company Name</label>
                    <input type="text" name="company" id="c_company" class="form-control" placeholder="e.g. Acme Corporation">
                </div>
                
                <div class="form-group">
                    <label for="c_address">Physical Billing Address</label>
                    <textarea name="address" id="c_address" class="form-control" rows="2" placeholder="e.g. 123 Main St, New York"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem; justify-content: center;">Save Client Record</button>
            </form>
        </div>
    </div>

    <!-- Add/Edit Project Modal -->
    <div id="addProjectModal" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3 id="projectModalTitle">Create Project Account</h3>
                <button class="modal-close-btn" onclick="closeModal('addProjectModal')"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="hidden" name="action" id="projectFormAction" value="add_project">
                <input type="hidden" name="project_id" id="projectFormId" value="">
                
                <div class="form-group">
                    <label for="p_client">Assign Client Profile</label>
                    <select name="client_id" id="p_client" class="form-control" required>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company'] ? $c['company'] . " (" . $c['name'] . ")" : $c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="p_title">Project Workspace Title</label>
                    <input type="text" name="title" id="p_title" class="form-control" placeholder="e.g. SaaS Portal Development" required>
                </div>
                
                <div class="form-group">
                    <label for="p_description">Description / Technical Specifications</label>
                    <textarea name="description" id="p_description" class="form-control" rows="3" placeholder="Core requirements, API integration specs, and goals..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="p_status">Lifecycle Status</label>
                    <select name="status" id="p_status" class="form-control">
                        <option value="Planning">Planning</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="p_budget">Project Total Budget ($)</label>
                    <input type="number" step="0.01" name="total_budget" id="p_budget" class="form-control" value="0.00">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem; justify-content: center;">Save Project</button>
            </form>
        </div>
    </div>

    <!-- Milestones Workspace Modal -->
    <div id="milestonesModal" class="modal">
        <div class="modal-content glass-panel" style="max-width: 800px;">
            <div class="modal-header">
                <div>
                    <span style="font-size: 0.8rem; color: var(--muted-foreground);">Project milestones workspace</span>
                    <h3 id="milestoneProjectTitle">Project Milestones</h3>
                </div>
                <button class="modal-close-btn" onclick="closeModal('milestonesModal')"><i data-lucide="x"></i></button>
            </div>
            
            <!-- Milestone Creator Form (Toggle) -->
            <div class="glass-card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1rem;"><i data-lucide="plus" style="width:16px; height:16px; display:inline-block; vertical-align:middle;"></i> Create Project Phase Milestone</h4>
                <form method="POST" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; align-items: end;">
                    <input type="hidden" name="action" value="add_milestone">
                    <input type="hidden" name="project_id" id="milestoneProjectFormId" value="">
                    
                    <div class="form-group">
                        <label>Milestone Title / Phase</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Database Setup &amp; Design" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Billing Budget ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group full-width" style="grid-column: 1 / -2;">
                        <label>Scope / Task List Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Phase core tasks...">
                    </div>
                    
                    <div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; height: 2.5rem; justify-content: center;">Save Milestone</button>
                    </div>
                </form>
            </div>
            
            <h4 style="margin-bottom: 0.5rem; font-size: 1rem;">Project Phase Milestones Checklist</h4>
            <div class="table-container" style="max-height: 250px; overflow-y: auto;">
                <table class="custom-table" id="milestonesTable">
                    <thead>
                        <tr>
                            <th>Phase Description</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status / Billing</th>
                            <th style="width: 60px; text-align: center;">Delete</th>
                        </tr>
                    </thead>
                    <tbody id="milestonesTableBody">
                        <!-- Loaded dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Estimate Builder Modal -->
    <div id="estimateModal" class="modal">
        <div class="modal-content glass-panel" style="max-width: 800px;">
            <div class="modal-header">
                <div>
                    <span style="font-size: 0.8rem; color: var(--muted-foreground);">Project Estimate Worksheet</span>
                    <h3 id="estimateProjectTitle">Estimate Builder</h3>
                </div>
                <button class="modal-close-btn" onclick="closeModal('estimateModal')"><i data-lucide="x"></i></button>
            </div>
            
            <form method="POST" action="" id="estimateForm">
                <input type="hidden" name="action" value="save_estimate">
                <input type="hidden" name="project_id" id="estimateProjectFormId" value="">
                
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1.5rem;">
                    <table class="custom-table" id="estimateItemsTable">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th style="width: 100px; text-align: center;">Quantity</th>
                                <th style="width: 150px; text-align: right;">Unit Rate ($)</th>
                                <th style="width: 120px; text-align: right;">Total ($)</th>
                                <th style="width: 50px; text-align: center;">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="estimateItemsBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; border-top: 1px solid var(--glass-border); padding-top: 1.25rem;">
                    <button type="button" class="btn btn-outline" onclick="addEstimateRow()"><i data-lucide="plus"></i> Add Estimate Item Line</button>
                    
                    <div style="text-align: right;">
                        <span style="font-size: 0.85rem; color: var(--muted-foreground);">Total Projected Budget: </span>
                        <strong id="estimateTotalLabel" style="font-size: 1.25rem; color: hsl(var(--primary)); margin-left: 0.5rem;">$0.00</strong>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('estimateModal')">Cancel</button>
                    <button type="submit" class="btn btn-secondary"><i data-lucide="save"></i> Save Estimate Sheets</button>
                </div>
            </form>
        </div>
    </div>

    <!-- AI Proposal Viewer Modal -->
    <div id="proposalModal" class="modal">
        <div class="modal-content glass-panel" style="max-width: 800px;">
            <div class="modal-header">
                <div>
                    <span style="font-size: 0.8rem; color: var(--muted-foreground);">Interactive Project Scope Document</span>
                    <h3>Project Scope Proposal</h3>
                </div>
                <button class="modal-close-btn" onclick="closeModal('proposalModal')"><i data-lucide="x"></i></button>
            </div>
            
            <div id="proposalLoading" style="text-align: center; padding: 3rem 0; display: none;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid var(--glass-border); border-top-color: hsl(var(--primary)); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem;"></div>
                <p>Generating custom proposal structure via Gemini AI model...</p>
            </div>
            
            <div id="proposalContainer" style="max-height: 400px; overflow-y: auto; background: rgba(15,23,42,0.4); border-radius: var(--radius-md); padding: 1.5rem; border: 1px solid var(--glass-border); line-height: 1.6;">
                <!-- Proposal Content Loaded Here -->
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <button class="btn btn-primary" onclick="closeModal('proposalModal')">Close Document</button>
            </div>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div id="editInvoiceModal" class="modal">
        <div class="modal-content glass-panel" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Adjust Invoice Details</h3>
                <button class="modal-close-btn" onclick="closeModal('editInvoiceModal')"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <input type="hidden" name="action" value="edit_invoice">
                <input type="hidden" name="invoice_id" id="editInvoiceId" value="">
                
                <div class="form-group">
                    <label for="editInvoiceNum">Invoice Number</label>
                    <input type="text" name="invoice_number" id="editInvoiceNum" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editInvoiceAmount">Invoice Billing Amount ($)</label>
                    <input type="number" step="0.01" name="amount" id="editInvoiceAmount" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editInvoiceStatus">Payment Status</label>
                    <select name="status" id="editInvoiceStatus" class="form-control">
                        <option value="Unpaid">Unpaid</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editInvoiceVerified">Verification Status</label>
                    <select name="is_verified" id="editInvoiceVerified" class="form-control">
                        <option value="0">Pending Verification</option>
                        <option value="1">Verified / Ready to Send</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem; justify-content: center; height: 2.5rem;">Save Adjustments</button>
            </form>
        </div>
    </div>

    <!-- Email Viewer Modal -->
    <div id="emailViewModal" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3 id="emailViewSubject">Sent Email Details</h3>
                <button class="modal-close-btn" onclick="closeModal('emailViewModal')"><i data-lucide="x"></i></button>
            </div>
            
            <div style="font-size: 0.85rem; color: var(--muted-foreground); margin-bottom: 1.5rem;">
                <p>Recipient: <strong id="emailViewRecipient" style="color: hsl(var(--foreground));"></strong></p>
                <p>Sent At: <span id="emailViewDate"></span></p>
            </div>
            
            <div id="emailViewBody" style="background: white; color: #1e293b; padding: 2rem; border-radius: var(--radius-md); max-height: 350px; overflow-y: auto; font-family: sans-serif; line-height: 1.5;">
                <!-- Email body HTML loaded here -->
            </div>
        </div>
    </div>

    <!-- Toast Toast -->
    <div id="toast">Proposal saved successfully!</div>

    <!-- ------------------------------------------------------------- -->
    <!-- PMS API ENDPOINTS IN DASHBOARD FOR DYNAMIC LOADS -->
    <!-- ------------------------------------------------------------- -->
    <?php
    // If dynamic load triggers via GET parameters
    if (isset($_GET['api_action'])) {
        $api_action = $_GET['api_action'];
        header('Content-Type: application/json');
        
        try {
            if ($api_action === 'get_milestones') {
                $proj_id = (int)$_GET['project_id'];
                $stmt = $db->prepare("
                    SELECT m.*, i.invoice_number, i.status as invoice_status, i.id as invoice_id, i.is_verified as invoice_verified
                    FROM milestones m
                    LEFT JOIN invoices i ON m.invoice_id = i.id
                    WHERE m.project_id = ?
                    ORDER BY m.due_date ASC
                ");
                $stmt->execute([$proj_id]);
                echo json_encode(['success' => true, 'milestones' => $stmt->fetchAll()]);
                exit;
            } elseif ($api_action === 'get_estimate') {
                $proj_id = (int)$_GET['project_id'];
                $stmt = $db->prepare("SELECT * FROM estimates WHERE project_id = ? LIMIT 1");
                $stmt->execute([$proj_id]);
                $est = $stmt->fetch();
                echo json_encode([
                    'success' => true, 
                    'exists' => $est ? true : false,
                    'items' => $est ? json_decode($est['items'], true) : []
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    ?>

    <!-- Main JS logic -->
    <script src="<?php echo htmlspecialchars($base_path); ?>app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
