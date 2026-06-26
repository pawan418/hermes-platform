<?php
// admin.php - Secure administration panel for managing website content
require_once __DIR__ . '/db.php';

session_start();

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'leads';
$success_message = '';
$error_message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    try {
        if ($action === 'update_lead_status') {
            $lead_id = (int)$_POST['lead_id'];
            $status = $_POST['status'];
            
            $stmt = $db->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $stmt->execute([$status, $lead_id]);
            $success_message = "Lead status updated successfully.";

        } elseif ($action === 'delete_lead') {
            $lead_id = (int)$_POST['lead_id'];
            
            $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$lead_id]);
            $success_message = "Lead deleted successfully.";

        } elseif ($action === 'add_service') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $icon = trim($_POST['icon']);
            $category = trim($_POST['category']);
            $tech_stack = trim($_POST['tech_stack']);
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                INSERT INTO services (title, description, icon, category, tech_stack, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $icon, $category, $tech_stack, $display_order]);
            $success_message = "New service added successfully.";

        } elseif ($action === 'edit_service') {
            $id = (int)$_POST['service_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $icon = trim($_POST['icon']);
            $category = trim($_POST['category']);
            $tech_stack = trim($_POST['tech_stack']);
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                UPDATE services 
                SET title = ?, description = ?, icon = ?, category = ?, tech_stack = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $icon, $category, $tech_stack, $display_order, $id]);
            $success_message = "Service updated successfully.";

        } elseif ($action === 'delete_service') {
            $id = (int)$_POST['service_id'];
            
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Service deleted successfully.";

        } elseif ($action === 'add_academy') {
            $title = trim($_POST['title']);
            $subtitle = trim($_POST['subtitle']);
            $duration = trim($_POST['duration']);
            $description = trim($_POST['description']);
            $features = trim($_POST['features']);
            $price = trim($_POST['price']);
            $type = trim($_POST['type']);
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                INSERT INTO academy (title, subtitle, duration, description, features, price, type, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $subtitle, $duration, $description, $features, $price, $type, $display_order]);
            $success_message = "Academy program added successfully.";

        } elseif ($action === 'edit_academy') {
            $id = (int)$_POST['academy_id'];
            $title = trim($_POST['title']);
            $subtitle = trim($_POST['subtitle']);
            $duration = trim($_POST['duration']);
            $description = trim($_POST['description']);
            $features = trim($_POST['features']);
            $price = trim($_POST['price']);
            $type = trim($_POST['type']);
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                UPDATE academy 
                SET title = ?, subtitle = ?, duration = ?, description = ?, features = ?, price = ?, type = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $subtitle, $duration, $description, $features, $price, $type, $display_order, $id]);
            $success_message = "Academy program updated successfully.";

        } elseif ($action === 'delete_academy') {
            $id = (int)$_POST['academy_id'];
            
            $stmt = $db->prepare("DELETE FROM academy WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Academy program deleted successfully.";

        } elseif ($action === 'update_settings') {
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            foreach ($_POST['settings'] as $k => $v) {
                $stmt->execute([$k, trim($v)]);
            }
            $success_message = "Site configuration settings updated successfully.";

        } elseif ($action === 'update_password') {
            $current_pw = $_POST['current_password'];
            $new_pw = $_POST['new_password'];
            $confirm_pw = $_POST['confirm_password'];

            if ($new_pw !== $confirm_pw) {
                $error_message = "New password and confirmation do not match.";
            } else {
                // Fetch user
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$_SESSION['admin_username']]);
                $user = $stmt->fetch();

                if ($user && password_verify($current_pw, $user['password'])) {
                    $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->execute([$new_hash, $user['id']]);
                    $success_message = "Password updated successfully.";
                } else {
                    $error_message = "Current password verify check failed.";
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "Database Action Error: " . $e->getMessage();
    }
}

// Fetch general system configurations for forms
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSPL Control Center | Administrative Workspace</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Specific adjustments for clean administration tables and forms */
        .badge-type {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-type.contact { background: hsla(200 90% 50% / 0.15); color: hsl(200 90% 50%); }
        .badge-type.registration { background: hsla(280 90% 50% / 0.15); color: hsl(280 90% 50%); }
        .badge-type.estimator { background: hsla(160 90% 40% / 0.15); color: hsl(160 90% 40%); }
        
        .alert-box {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        .alert-box.success {
            background: hsla(var(--success) / 0.15);
            border: 1px solid hsla(var(--success) / 0.25);
            color: hsl(var(--success));
        }
        .alert-box.error {
            background: hsla(var(--destructive) / 0.15);
            border: 1px solid hsla(var(--destructive) / 0.25);
            color: hsl(var(--destructive));
        }
        .action-icon-btn {
            background: transparent;
            border: 1px solid hsl(var(--border));
            color: hsl(var(--foreground));
            padding: 0.4rem;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        .action-icon-btn:hover {
            background: hsl(var(--muted));
        }
        .action-icon-btn.delete:hover {
            background: hsla(var(--destructive) / 0.15);
            color: hsl(var(--destructive));
            border-color: hsla(var(--destructive) / 0.25);
        }
        .edit-form-panel {
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: var(--radius-lg);
            border: 1px dashed hsl(var(--primary) / 0.3);
            background: hsla(var(--primary) / 0.02);
        }
    </style>
</head>
<body class="admin-body">
    <!-- Admin Sidebar Navigation -->
    <div class="admin-sidebar glass-panel">
        <a href="index.php" class="logo">
            <div class="logo-icon">L</div>
            <span>LSPL<span class="logo-dot">.</span></span>
        </a>
        
        <ul class="admin-nav">
            <li class="admin-nav-item <?php echo $current_tab === 'leads' ? 'active' : ''; ?>">
                <a href="?tab=leads"><i data-lucide="inbox" style="width: 18px; height: 18px;"></i> Leads & Queries</a>
            </li>
            <li class="admin-nav-item <?php echo $current_tab === 'services' ? 'active' : ''; ?>">
                <a href="?tab=services"><i data-lucide="layers" style="width: 18px; height: 18px;"></i> Services Manager</a>
            </li>
            <li class="admin-nav-item <?php echo $current_tab === 'academy' ? 'active' : ''; ?>">
                <a href="?tab=academy"><i data-lucide="graduation-cap" style="width: 18px; height: 18px;"></i> Academy Manager</a>
            </li>
            <li class="admin-nav-item <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                <a href="?tab=settings"><i data-lucide="settings" style="width: 18px; height: 18px;"></i> Site Configuration</a>
            </li>
            <li class="admin-nav-item <?php echo $current_tab === 'account' ? 'active' : ''; ?>">
                <a href="?tab=account"><i data-lucide="shield" style="width: 18px; height: 18px;"></i> Change Password</a>
            </li>
            <li class="admin-nav-item" style="margin-top: auto;">
                <a href="logout.php" style="color: hsl(var(--destructive));"><i data-lucide="log-out" style="width: 18px; height: 18px;"></i> Log Out</a>
            </li>
        </ul>
    </div>

    <!-- Main Workspace -->
    <main class="admin-main">
        <div class="admin-header">
            <div>
                <span class="badge badge-primary">Workspace Management</span>
                <h1 style="font-size: 2rem; margin-top: 0.25rem;">
                    <?php
                    $tab_names = [
                        'leads' => 'Client Leads & Workshop Enrolls',
                        'services' => 'Services Portfolio CRUD Manager',
                        'academy' => 'Academy Training Programs',
                        'settings' => 'Site Global Config & Copywriting',
                        'account' => 'Administrator Password Vault'
                    ];
                    echo isset($tab_names[$current_tab]) ? $tab_names[$current_tab] : 'Workspace';
                    ?>
                </h1>
            </div>
            <div style="font-size: 0.9rem; color: hsl(var(--muted-foreground));">
                Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
            </div>
        </div>

        <!-- System Alerts -->
        <?php if (!empty($success_message)): ?>
            <div class="alert-box success">
                <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert-box error">
                <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- ==========================================
             TAB CONTENT: LEADS CRM
             ========================================== -->
        <?php if ($current_tab === 'leads'): ?>
            <?php
            // Fetch total counts for stats
            $leads_count = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            $new_count = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'New'")->fetchColumn();
            $contacted_count = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'Contacted'")->fetchColumn();
            
            // Query all leads
            $leads_stmt = $db->query("SELECT * FROM leads ORDER BY created_at DESC");
            $leads = $leads_stmt->fetchAll();
            ?>
            <div class="dash-stats">
                <div class="glass-card dash-stat-card">
                    <div class="dash-stat-icon"><i data-lucide="inbox"></i></div>
                    <div>
                        <h4 style="font-size: 1.5rem;"><?php echo $leads_count; ?></h4>
                        <span style="font-size: 0.75rem; color: var(--muted-foreground)">Total Submissions</span>
                    </div>
                </div>
                <div class="glass-card dash-stat-card" style="border-color: hsla(var(--primary) / 0.3);">
                    <div class="dash-stat-icon" style="background: hsla(var(--primary) / 0.15); color: hsl(var(--primary));"><i data-lucide="zap"></i></div>
                    <div>
                        <h4 style="font-size: 1.5rem;"><?php echo $new_count; ?></h4>
                        <span style="font-size: 0.75rem; color: var(--muted-foreground)">Unprocessed (New)</span>
                    </div>
                </div>
                <div class="glass-card dash-stat-card" style="border-color: hsla(var(--warning) / 0.3);">
                    <div class="dash-stat-icon" style="background: hsla(var(--warning) / 0.15); color: hsl(var(--warning));"><i data-lucide="message-square"></i></div>
                    <div>
                        <h4 style="font-size: 1.5rem;"><?php echo $contacted_count; ?></h4>
                        <span style="font-size: 0.75rem; color: var(--muted-foreground)">In Progress (Contacted)</span>
                    </div>
                </div>
            </div>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Recent lead submissions</h3>
                
                <?php if (empty($leads)): ?>
                    <p style="text-align: center; padding: 3rem 0; color: var(--muted-foreground);">No leads received yet.</p>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Client / Trainee</th>
                                    <th>Type</th>
                                    <th>Service / Course</th>
                                    <th>Estimated Budget</th>
                                    <th>Message / Details</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($lead['name']); ?></div>
                                            <div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.15rem;">
                                                <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" style="text-decoration: underline;"><?php echo htmlspecialchars($lead['email']); ?></a><br>
                                                <?php echo htmlspecialchars($lead['phone']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-type <?php echo htmlspecialchars($lead['type']); ?>">
                                                <?php echo htmlspecialchars($lead['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; font-size: 0.85rem; max-width: 180px;"><?php echo htmlspecialchars($lead['service_selected'] ?? 'N/A'); ?></div>
                                            <?php if (!empty($lead['duration_selected'])): ?>
                                                <div style="font-size: 0.7rem; color: var(--muted-foreground); margin-top: 0.15rem;">
                                                    <?php echo htmlspecialchars($lead['duration_selected']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight: 700; color: hsl(var(--accent));">
                                            <?php echo htmlspecialchars($lead['budget'] ?? 'N/A'); ?>
                                        </td>
                                        <td style="font-size: 0.82rem; max-width: 200px; max-height: 80px; overflow-y: auto;">
                                            <?php echo nl2br(htmlspecialchars($lead['message'] ?? '')); ?>
                                        </td>
                                        <td style="font-size: 0.8rem; white-space: nowrap;">
                                            <?php echo date('d M Y, h:i A', strtotime($lead['created_at'])); ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="margin: 0; display: flex; align-items: center; gap: 0.25rem;">
                                                <input type="hidden" name="action" value="update_lead_status">
                                                <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                <select name="status" class="form-control" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; width: 110px;" onchange="this.form.submit()">
                                                    <option value="New" <?php echo $lead['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                    <option value="Contacted" <?php echo $lead['status'] === 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                    <option value="Closed" <?php echo $lead['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this lead? This cannot be undone.')">
                                                <input type="hidden" name="action" value="delete_lead">
                                                <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete" title="Delete Lead"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <!-- ==========================================
             TAB CONTENT: SERVICES portfolio CRUD
             ========================================== -->
        <?php elseif ($current_tab === 'services'): ?>
            <?php
            // Edit check
            $edit_service = null;
            if (isset($_GET['edit_service_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_service_id']]);
                $edit_service = $e_stmt->fetch();
            }

            // Fetch services
            $s_stmt = $db->query("SELECT * FROM services ORDER BY display_order ASC");
            $services = $s_stmt->fetchAll();
            ?>

            <!-- Edit Form panel (shows only when edit_service_id is in URL) -->
            <?php if ($edit_service): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="edit"></i> Modify Service: <?php echo htmlspecialchars($edit_service['title']); ?>
                    </h3>
                    <form method="POST" action="admin.php?tab=services" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_service">
                        <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Service Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_service['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" class="form-control">
                                <option value="AI & Automation" <?php echo $edit_service['category'] === 'AI & Automation' ? 'selected' : ''; ?>>AI & Automation</option>
                                <option value="SaaS Development" <?php echo $edit_service['category'] === 'SaaS Development' ? 'selected' : ''; ?>>SaaS Development</option>
                                <option value="Web & Software" <?php echo $edit_service['category'] === 'Web & Software' ? 'selected' : ''; ?>>Web & Software</option>
                                <option value="Marketing & Search" <?php echo $edit_service['category'] === 'Marketing & Search' ? 'selected' : ''; ?>>Marketing & Search</option>
                                <option value="Infrastructure" <?php echo $edit_service['category'] === 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="icon">Lucide Icon Name (<a href="https://lucide.dev/icons" target="_blank" style="text-decoration: underline; color: hsl(var(--primary));">View list</a>)</label>
                            <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($edit_service['icon']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order (Sorting)</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $edit_service['display_order']; ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="tech_stack">Technology Tags (Comma separated list)</label>
                            <input type="text" name="tech_stack" class="form-control" value="<?php echo htmlspecialchars($edit_service['tech_stack']); ?>" placeholder="e.g. React, Node.js, SQLite" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Short Description copy</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($edit_service['description']); ?></textarea>
                        </div>

                        <div class="full-width" style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="admin.php?tab=services" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Add New Service form panel -->
            <?php if (!$edit_service): ?>
                <div class="glass-card edit-form-panel" style="border-color: hsla(var(--success) / 0.3); background: hsla(var(--success) / 0.01);">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="plus-circle"></i> Create New Service Card
                    </h3>
                    <form method="POST" action="admin.php?tab=services" class="edit-form-grid">
                        <input type="hidden" name="action" value="add_service">
                        
                        <div class="form-group">
                            <label for="title">Service Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Chatbot Automation" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" class="form-control">
                                <option value="AI & Automation">AI & Automation</option>
                                <option value="SaaS Development">SaaS Development</option>
                                <option value="Web & Software">Web & Software</option>
                                <option value="Marketing & Search">Marketing & Search</option>
                                <option value="Infrastructure">Infrastructure</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="icon">Lucide Icon Name (e.g. message-square, phone-call, layers)</label>
                            <input type="text" name="icon" class="form-control" placeholder="message-square" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order (Sorting)</label>
                            <input type="number" name="display_order" class="form-control" value="0" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="tech_stack">Technology Tags (Comma separated list)</label>
                            <input type="text" name="tech_stack" class="form-control" placeholder="e.g. React, Node.js, SQLite" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Short Description copy</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter service overview..." required></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem;">
                            <button type="submit" class="btn btn-secondary">Create Service Card</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Services Listing -->
            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Current Service Portfolio</h3>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Icon</th>
                                <th>Service Title</th>
                                <th>Category</th>
                                <th>Technology Stacks</th>
                                <th>Description Copy</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $srv): ?>
                                <tr>
                                    <td><strong><?php echo $srv['display_order']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 6px; background: hsl(var(--muted));">
                                            <i data-lucide="<?php echo htmlspecialchars($srv['icon']); ?>" style="width: 18px; height: 18px; color: hsl(var(--primary));"></i>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($srv['title']); ?></td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($srv['category']); ?></span>
                                    </td>
                                    <td style="max-width: 180px;">
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                            <?php
                                            $tags = explode(',', $srv['tech_stack']);
                                            foreach ($tags as $tag) {
                                                echo '<span class="tech-tag" style="font-size: 0.7rem;">' . htmlspecialchars(trim($tag)) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.8rem; max-width: 250px; color: var(--muted-foreground);"><?php echo htmlspecialchars($srv['description']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="admin.php?tab=services&edit_service_id=<?php echo $srv['id']; ?>" class="action-icon-btn" title="Edit Service"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this service card?')">
                                                <input type="hidden" name="action" value="delete_service">
                                                <input type="hidden" name="service_id" value="<?php echo $srv['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete" title="Delete Service"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- ==========================================
             TAB CONTENT: ACADEMY CRUD
             ========================================== -->
        <?php elseif ($current_tab === 'academy'): ?>
            <?php
            // Edit check
            $edit_academy = null;
            if (isset($_GET['edit_academy_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM academy WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_academy_id']]);
                $edit_academy = $e_stmt->fetch();
            }

            // Fetch programs
            $a_stmt = $db->query("SELECT * FROM academy ORDER BY display_order ASC");
            $academy_items = $a_stmt->fetchAll();
            ?>

            <!-- Edit Form panel -->
            <?php if ($edit_academy): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="edit"></i> Modify Program: <?php echo htmlspecialchars($edit_academy['title']); ?>
                    </h3>
                    <form method="POST" action="admin.php?tab=academy" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_academy">
                        <input type="hidden" name="academy_id" value="<?php echo $edit_academy['id']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Program Name / Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_academy['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Program Type</label>
                            <select name="type" class="form-control">
                                <option value="workshop" <?php echo $edit_academy['type'] === 'workshop' ? 'selected' : ''; ?>>Specialized Workshop</option>
                                <option value="bootcamp" <?php echo $edit_academy['type'] === 'bootcamp' ? 'selected' : ''; ?>>Professional Bootcamp</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration copy</label>
                            <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($edit_academy['duration']); ?>" placeholder="e.g. 5 Days / 12 Weeks" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Pricing Details</label>
                            <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($edit_academy['price']); ?>" placeholder="e.g. Free / ₹1,499 / Contact Us" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order (Sorting)</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $edit_academy['display_order']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="subtitle">Subtitle / Badge</label>
                            <input type="text" name="subtitle" class="form-control" value="<?php echo htmlspecialchars($edit_academy['subtitle']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="features">Key Features (Comma separated list)</label>
                            <input type="text" name="features" class="form-control" value="<?php echo htmlspecialchars($edit_academy['features']); ?>" placeholder="e.g. Free Toolkit, Certification, Placements" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Detailed Description copy</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($edit_academy['description']); ?></textarea>
                        </div>

                        <div class="full-width" style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="admin.php?tab=academy" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Add New Academy Course panel -->
            <?php if (!$edit_academy): ?>
                <div class="glass-card edit-form-panel" style="border-color: hsla(var(--success) / 0.3); background: hsla(var(--success) / 0.01);">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i data-lucide="plus-circle"></i> Create New Academy Program
                    </h3>
                    <form method="POST" action="admin.php?tab=academy" class="edit-form-grid">
                        <input type="hidden" name="action" value="add_academy">
                        
                        <div class="form-group">
                            <label for="title">Program Name / Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. MERN Fullstack" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Program Type</label>
                            <select name="type" class="form-control">
                                <option value="workshop">Specialized Workshop</option>
                                <option value="bootcamp">Professional Bootcamp</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="duration">Duration copy</label>
                            <input type="text" name="duration" class="form-control" placeholder="e.g. 3 Days / 12 Weeks" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Pricing Details</label>
                            <input type="text" name="price" class="form-control" placeholder="e.g. Free / ₹1,499 / Contact Us" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order (Sorting)</label>
                            <input type="number" name="display_order" class="form-control" value="0" required>
                        </div>

                        <div class="form-group">
                            <label for="subtitle">Subtitle / Badge</label>
                            <input type="text" name="subtitle" class="form-control" placeholder="Web Design & Coding Foundations" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="features">Key Features (Comma separated list)</label>
                            <input type="text" name="features" class="form-control" placeholder="e.g. Free Kit, Certification, Live Projects" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Detailed Description copy</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter program description..." required></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem;">
                            <button type="submit" class="btn btn-secondary">Create Program Card</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Academy Listings -->
            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Academy Programs List</h3>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Program Title</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Pricing</th>
                                <th>Features List</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($academy_items as $itm): ?>
                                <tr>
                                    <td><strong><?php echo $itm['display_order']; ?></strong></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($itm['title']); ?></div>
                                        <div style="font-size:0.75rem; color: var(--muted-foreground); margin-top: 0.15rem;"><?php echo htmlspecialchars($itm['subtitle']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $itm['type'] === 'workshop' ? 'badge-primary' : 'badge-secondary'; ?>">
                                            <?php echo htmlspecialchars($itm['type']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($itm['duration']); ?></td>
                                    <td style="font-weight: 700; color: hsl(var(--accent));"><?php echo htmlspecialchars($itm['price']); ?></td>
                                    <td style="max-width: 200px; font-size: 0.8rem;">
                                        <ul style="padding-left: 1rem; color: var(--muted-foreground);">
                                            <?php
                                            $feats = explode(',', $itm['features']);
                                            foreach ($feats as $f) {
                                                echo '<li>' . htmlspecialchars(trim($f)) . '</li>';
                                            }
                                            ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="admin.php?tab=academy&edit_academy_id=<?php echo $itm['id']; ?>" class="action-icon-btn" title="Edit Program"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this academy program?')">
                                                <input type="hidden" name="action" value="delete_academy">
                                                <input type="hidden" name="academy_id" value="<?php echo $itm['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete" title="Delete Program"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- ==========================================
             TAB CONTENT: SETTINGS
             ========================================== -->
        <?php elseif ($current_tab === 'settings'): ?>
            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Redesign Landing Page Settings</h3>
                <form method="POST" action="admin.php?tab=settings" class="edit-form-grid">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <h4 class="full-width" style="margin-top: 1rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">1. Meta Configurations & SEO</h4>
                    
                    <div class="form-group">
                        <label for="site_title">Site Title (Page Title bar)</label>
                        <input type="text" name="settings[site_title]" class="form-control" value="<?php echo htmlspecialchars($site['site_title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_tagline">Tagline</label>
                        <input type="text" name="settings[site_tagline]" class="form-control" value="<?php echo htmlspecialchars($site['site_tagline'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="meta_description">Meta Description (For SEO indexing)</label>
                        <textarea name="settings[meta_description]" class="form-control" rows="2" required><?php echo htmlspecialchars($site['meta_description'] ?? ''); ?></textarea>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">2. Hero Layout Copy</h4>

                    <div class="form-group full-width">
                        <label for="hero_title">Hero Heading Title</label>
                        <input type="text" name="settings[hero_title]" class="form-control" value="<?php echo htmlspecialchars($site['hero_title'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="hero_subtitle">Hero Paragraph Description</label>
                        <textarea name="settings[hero_subtitle]" class="form-control" rows="3" required><?php echo htmlspecialchars($site['hero_subtitle'] ?? ''); ?></textarea>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">3. Stats Metrics counter</h4>

                    <div class="form-group">
                        <label for="stats_projects">Projects Value (e.g. 500+)</label>
                        <input type="text" name="settings[stats_projects]" class="form-control" value="<?php echo htmlspecialchars($site['stats_projects'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stats_students">Trained Students (e.g. 10,000+)</label>
                        <input type="text" name="settings[stats_students]" class="form-control" value="<?php echo htmlspecialchars($site['stats_students'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stats_technologies">Tech Stacks (e.g. 25+)</label>
                        <input type="text" name="settings[stats_technologies]" class="form-control" value="<?php echo htmlspecialchars($site['stats_technologies'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stats_experience">Years (e.g. 8+ Years)</label>
                        <input type="text" name="settings[stats_experience]" class="form-control" value="<?php echo htmlspecialchars($site['stats_experience'] ?? ''); ?>" required>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">4. Contact Information & Support</h4>

                    <div class="form-group">
                        <label for="contact_email">Public Email</label>
                        <input type="email" name="settings[contact_email]" class="form-control" value="<?php echo htmlspecialchars($site['contact_email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Public Phone</label>
                        <input type="text" name="settings[contact_phone]" class="form-control" value="<?php echo htmlspecialchars($site['contact_phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="contact_address">Physical Address</label>
                        <input type="text" name="settings[contact_address]" class="form-control" value="<?php echo htmlspecialchars($site['contact_address'] ?? ''); ?>" required>
                    </div>

                    <div class="full-width" style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Save Site Configuration</button>
                    </div>
                </form>
            </div>

        <!-- ==========================================
             TAB CONTENT: SECURITY PASSWORD
             ========================================== -->
        <?php elseif ($current_tab === 'account'): ?>
            <div class="glass-card admin-card" style="max-width: 500px; margin: 0 auto;">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="key"></i> Update Credentials
                </h3>
                <form method="POST" action="admin.php?tab=account">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
                        Update Password Hash <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <!-- Scripts -->
    <script src="app.js"></script>
    <script>
        // Trigger Lucide mapping
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
