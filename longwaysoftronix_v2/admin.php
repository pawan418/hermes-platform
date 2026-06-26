<?php
// longwaysoftronix/admin.php - Secure administration panel for managing LSPL main brand
if (!defined('LSPL_SECURE_ROUTE')) {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    header("Location: " . $base_path);
    exit;
}
require_once __DIR__ . '/db.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolve base paths and URL
$base_path = $base_path ?? (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
$admin_slug = $admin_slug ?? ($site['admin_slug'] ?? 'admin');
$admin_url = $base_path . $admin_slug;

// Handle logout
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

// Auth check
if (!isset($_SESSION['lspl_admin_logged_in']) || $_SESSION['lspl_admin_logged_in'] !== true) {
    include __DIR__ . '/login.php';
    exit;
}
$user_role = $_SESSION['lspl_admin_role'] ?? '';
$user_username = $_SESSION['lspl_admin_username'] ?? '';
$user_id = $_SESSION['lspl_admin_user_id'] ?? 0;

$role_tabs = [
    'administrator' => ['leads', 'services', 'industries', 'pages', 'blogs', 'menus', 'reviews', 'users', 'settings', 'account'],
    'site_manager' => ['settings', 'industries', 'pages', 'blogs', 'leads', 'reviews', 'account'],
    'service_manager' => ['services', 'leads', 'account'],
    'blog_editor' => ['blogs', 'account']
];
$allowed_tabs = $role_tabs[$user_role] ?? ['account'];


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'ajax_upload_logo') {
        if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        try {
            $field = $_POST['field'] ?? 'logo';
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                if (strpos($_FILES[$field]['type'], 'image/') === 0) {
                    $filename = 'logo.png';
                    if ($field === 'logo_light') {
                        $filename = 'logo-light.png';
                    } elseif ($field === 'logo_dark') {
                        $filename = 'logo-dark.png';
                    }
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__ . '/' . $filename)) {
                        $response['success'] = true;
                        $response['url'] = $filename . '?v=' . time();
                    } else {
                        $response['message'] = 'Failed to save uploaded file.';
                    }
                } else {
                    $response['message'] = 'Uploaded file must be a valid image.';
                }
            } else {
                $response['message'] = 'File upload failed.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'update_menu_order') {
        if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        try {
            $order_data = json_decode($_POST['order_data'], true);
            if (is_array($order_data)) {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE header_menu_items SET parent_id = ?, display_order = ? WHERE id = ?");
                foreach ($order_data as $item) {
                    $parent_id = ($item['parent_id'] === 'null' || $item['parent_id'] === null || $item['parent_id'] === '') ? null : (int)$item['parent_id'];
                    $stmt->execute([$parent_id, (int)$item['display_order'], (int)$item['id']]);
                }
                $db->commit();
                $response['success'] = true;
            } else {
                $response['message'] = 'Invalid order data.';
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $response['message'] = $e->getMessage();
        }
        echo json_encode($response);
        exit;
    }
}


$current_tab = isset($_GET['tab']) ? $_GET['tab'] : $allowed_tabs[0];
if (!in_array($current_tab, $allowed_tabs)) {
    header('Location: ' . $admin_url . '?tab=' . $allowed_tabs[0]);
    exit;
}
$success_message = (isset($_GET['success']) && $_GET['success'] == '1') ? 'Settings updated successfully.' : '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    try {
        if ($action === 'update_lead_status') {
            if (!in_array($user_role, ['administrator', 'site_manager', 'service_manager'])) { throw new Exception('Unauthorized action.'); }
            $lead_id = (int)$_POST['lead_id'];
            $status = $_POST['status'];
            
            $stmt = $db->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $stmt->execute([$status, $lead_id]);
            $success_message = "Lead status updated successfully.";

        } elseif ($action === 'delete_lead') {
            if (!in_array($user_role, ['administrator', 'site_manager', 'service_manager'])) { throw new Exception('Unauthorized action.'); }
            $lead_id = (int)$_POST['lead_id'];
            
            $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$lead_id]);
            $success_message = "Lead deleted successfully.";

        } elseif ($action === 'add_service') {
            if (!in_array($user_role, ['administrator', 'service_manager'])) { throw new Exception('Unauthorized action.'); }
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $description = trim($_POST['description']);
            $content_val = trim($_POST['content']);
            $icon = trim($_POST['icon']);
            $category = trim($_POST['category']);
            $tech_stack = trim($_POST['tech_stack']);
            $display_order = (int)$_POST['display_order'];

            if (empty($title) || empty($slug)) { throw new Exception("Title and Slug are required."); }

            $stmt = $db->prepare("
                INSERT INTO services (title, slug, description, content, icon, category, tech_stack, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $description, $content_val, $icon, $category, $tech_stack, $display_order]);
            $success_message = "New service added successfully.";

        } elseif ($action === 'edit_service') {
            if (!in_array($user_role, ['administrator', 'service_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['service_id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $description = trim($_POST['description']);
            $content_val = trim($_POST['content']);
            $icon = trim($_POST['icon']);
            $category = trim($_POST['category']);
            $tech_stack = trim($_POST['tech_stack']);
            $display_order = (int)$_POST['display_order'];

            if (empty($title) || empty($slug)) { throw new Exception("Title and Slug are required."); }

            $stmt = $db->prepare("
                UPDATE services 
                SET title = ?, slug = ?, description = ?, content = ?, icon = ?, category = ?, tech_stack = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $description, $content_val, $icon, $category, $tech_stack, $display_order, $id]);
            $success_message = "Service updated successfully.";

        } elseif ($action === 'delete_service') {
            if (!in_array($user_role, ['administrator', 'service_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['service_id'];
            
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Service deleted successfully.";

        } elseif ($action === 'add_page') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = trim($_POST['content']);
            $display_in_nav = isset($_POST['display_in_nav']) ? (int)$_POST['display_in_nav'] : 1;
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                INSERT INTO pages (title, slug, content, display_in_nav, display_order)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $content, $display_in_nav, $display_order]);
            $success_message = "Custom page added successfully.";

        } elseif ($action === 'edit_page') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['page_id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $content = trim($_POST['content']);
            $display_in_nav = (int)$_POST['display_in_nav'];
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                UPDATE pages 
                SET title = ?, slug = ?, content = ?, display_in_nav = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $content, $display_in_nav, $display_order, $id]);
            $success_message = "Custom page updated successfully.";

        } elseif ($action === 'delete_page') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['page_id'];
            
            $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Custom page deleted successfully.";

        } elseif ($action === 'add_blog') {
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $summary = trim($_POST['summary']);
            $content = trim($_POST['content']);
            $author = trim($_POST['author']);
            $status = trim($_POST['status']);

            $image_url = null;
            if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
                if (strpos($_FILES['blog_image']['type'], 'image/') === 0) {
                    if (!file_exists(__DIR__ . '/uploads')) {
                        mkdir(__DIR__ . '/uploads', 0777, true);
                    }
                    $ext = pathinfo($_FILES['blog_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'uploads/blog_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['blog_image']['tmp_name'], __DIR__ . '/' . $filename)) {
                        $image_url = $filename;
                    }
                }
            }

            if (!in_array($user_role, ['administrator', 'site_manager', 'blog_editor'])) { throw new Exception('Unauthorized action.'); }
            if ($user_role === 'blog_editor') { $author = $user_username; }
            $stmt = $db->prepare("
                INSERT INTO blogs (title, slug, summary, content, author, status, image_url)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $summary, $content, $author, $status, $image_url]);
            $success_message = "Blog article created successfully.";
            } elseif ($action === 'edit_blog') {
            $id = (int)$_POST['blog_id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $summary = trim($_POST['summary']);
            $content = trim($_POST['content']);
            $author = trim($_POST['author']);
            $status = trim($_POST['status']);

            $existing_blog = $db->query("SELECT image_url FROM blogs WHERE id = " . $id)->fetch();
            $image_url = $existing_blog ? $existing_blog['image_url'] : null;

            if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
                if (strpos($_FILES['blog_image']['type'], 'image/') === 0) {
                    if (!file_exists(__DIR__ . '/uploads')) {
                        mkdir(__DIR__ . '/uploads', 0777, true);
                    }
                    if ($image_url && file_exists(__DIR__ . '/' . $image_url)) {
                        @unlink(__DIR__ . '/' . $image_url);
                    }
                    $ext = pathinfo($_FILES['blog_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'uploads/blog_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['blog_image']['tmp_name'], __DIR__ . '/' . $filename)) {
                        $image_url = $filename;
                    }
                }
            }

            if (!in_array($user_role, ['administrator', 'site_manager', 'blog_editor'])) { throw new Exception('Unauthorized action.'); }
            if ($user_role === 'blog_editor') {
                $existing_blog = $db->prepare("SELECT author FROM blogs WHERE id = ?");
                $existing_blog->execute([$id]);
                if ($existing_blog->fetchColumn() !== $user_username) { throw new Exception('You can only edit your own articles.'); }
                $author = $user_username;
            }
            $stmt = $db->prepare("
                UPDATE blogs 
                SET title = ?, slug = ?, summary = ?, content = ?, author = ?, status = ?, image_url = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $summary, $content, $author, $status, $image_url, $id]);
            $success_message = "Blog article updated successfully.";
            } elseif ($action === 'delete_blog') {
            $id = (int)$_POST['blog_id'];
            
            if (!in_array($user_role, ['administrator', 'site_manager', 'blog_editor'])) { throw new Exception('Unauthorized action.'); }
            if ($user_role === 'blog_editor') {
                $existing_blog = $db->prepare("SELECT author FROM blogs WHERE id = ?");
                $existing_blog->execute([$id]);
                if ($existing_blog->fetchColumn() !== $user_username) { throw new Exception('You can only delete your own articles.'); }
            }
            $stmt = $db->prepare("DELETE FROM blogs WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Blog article deleted successfully.";

        } elseif ($action === 'add_header_menu') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $title = trim($_POST['title']);
            $link_type = trim($_POST['link_type']);
            $page_slug = $link_type === 'page' ? trim($_POST['page_slug']) : null;
            $custom_url = $link_type === 'custom' ? trim($_POST['custom_url']) : null;
            $menu_type = $parent_id === null ? trim($_POST['menu_type']) : 'single_page';
            $column_name = ($parent_id !== null && !empty($_POST['column_name'])) ? trim($_POST['column_name']) : null;
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                INSERT INTO header_menu_items (parent_id, title, link_type, page_slug, custom_url, menu_type, column_name, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$parent_id, $title, $link_type, $page_slug, $custom_url, $menu_type, $column_name, $display_order]);
            $success_message = "Header menu item added successfully.";

        } elseif ($action === 'edit_header_menu') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['header_menu_id'];
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $title = trim($_POST['title']);
            $link_type = trim($_POST['link_type']);
            $page_slug = $link_type === 'page' ? trim($_POST['page_slug']) : null;
            $custom_url = $link_type === 'custom' ? trim($_POST['custom_url']) : null;
            $menu_type = $parent_id === null ? trim($_POST['menu_type']) : 'single_page';
            $column_name = ($parent_id !== null && !empty($_POST['column_name'])) ? trim($_POST['column_name']) : null;
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                UPDATE header_menu_items 
                SET parent_id = ?, title = ?, link_type = ?, page_slug = ?, custom_url = ?, menu_type = ?, column_name = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$parent_id, $title, $link_type, $page_slug, $custom_url, $menu_type, $column_name, $display_order, $id]);
            $success_message = "Header menu item updated successfully.";

        } elseif ($action === 'delete_header_menu') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['header_menu_id'];
            $stmt = $db->prepare("DELETE FROM header_menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Header menu item deleted successfully.";

        } elseif ($action === 'add_footer') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $column_name = trim($_POST['column_name']);
            $title = trim($_POST['title']);
            $link_type = trim($_POST['link_type']);
            $page_slug = $link_type === 'page' ? trim($_POST['page_slug']) : null;
            $custom_url = $link_type === 'custom' ? trim($_POST['custom_url']) : null;
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$column_name, $title, $link_type, $page_slug, $custom_url, $display_order]);
            $success_message = "Footer item added successfully.";

        } elseif ($action === 'edit_footer') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['footer_id'];
            $column_name = trim($_POST['column_name']);
            $title = trim($_POST['title']);
            $link_type = trim($_POST['link_type']);
            $page_slug = $link_type === 'page' ? trim($_POST['page_slug']) : null;
            $custom_url = $link_type === 'custom' ? trim($_POST['custom_url']) : null;
            $display_order = (int)$_POST['display_order'];

            $stmt = $db->prepare("
                UPDATE footer_items 
                SET column_name = ?, title = ?, link_type = ?, page_slug = ?, custom_url = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$column_name, $title, $link_type, $page_slug, $custom_url, $display_order, $id]);
            $success_message = "Footer item updated successfully.";

        } elseif ($action === 'delete_footer') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['footer_id'];
            $stmt = $db->prepare("DELETE FROM footer_items WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Footer item deleted successfully.";

        } elseif ($action === 'add_industry') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $description = trim($_POST['description']);
            $content_val = trim($_POST['content']);
            $icon = trim($_POST['icon']);
            $display_order = (int)$_POST['display_order'];

            if (empty($title) || empty($slug)) { throw new Exception("Title and Slug are required."); }

            $stmt = $db->prepare("INSERT INTO industries (title, slug, description, content, icon, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $description, $content_val, $icon, $display_order]);
            $success_message = "New industry solution added successfully.";

        } elseif ($action === 'edit_industry') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['industry_id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $description = trim($_POST['description']);
            $content_val = trim($_POST['content']);
            $icon = trim($_POST['icon']);
            $display_order = (int)$_POST['display_order'];

            if (empty($title) || empty($slug)) { throw new Exception("Title and Slug are required."); }

            $stmt = $db->prepare("UPDATE industries SET title = ?, slug = ?, description = ?, content = ?, icon = ?, display_order = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $description, $content_val, $icon, $display_order, $id]);
            $success_message = "Industry solution updated successfully.";

        } elseif ($action === 'delete_industry') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['industry_id'];
            $stmt = $db->prepare("DELETE FROM industries WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Industry solution deleted successfully.";

        } elseif ($action === 'update_settings') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $new_admin_slug = isset($_POST['settings']['admin_slug']) ? trim($_POST['settings']['admin_slug']) : '';
            if ($new_admin_slug !== '') {
                if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $new_admin_slug)) {
                    throw new Exception("Custom admin path slug can only contain alphanumeric characters, dashes, and underscores.");
                }
            }

            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            foreach ($_POST['settings'] as $k => $v) {
                $stmt->execute([$k, trim($v)]);
            }
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                if (strpos($_FILES['logo']['type'], 'image/') === 0) {
                    move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/logo.png');
                } else {
                    $error_message = "Uploaded logo file must be a valid image.";
                }
            }
            if ($error_message === '') {
                $success_message = "Site configuration and logo updated successfully.";
            
                if ($new_admin_slug !== '' && $new_admin_slug !== $admin_slug) {
                    $new_admin_url = $base_path . $new_admin_slug . '?tab=settings&success=1';
                    header('Location: ' . $new_admin_url);
                    exit;
                }
            }

                } elseif ($action === 'add_review') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $author_name = trim($_POST['author_name']);
            $review_text = trim($_POST['review_text']);
            $rating = (int)$_POST['rating'];
            $platform = trim($_POST['platform']);
            $project_title = trim($_POST['project_title']);

            if (empty($author_name) || empty($review_text) || empty($platform)) {
                throw new Exception("Author Name, Review Text, and Platform are required.");
            }

            $stmt = $db->prepare("
                INSERT INTO reviews (author_name, review_text, rating, platform, project_title)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$author_name, $review_text, $rating, $platform, $project_title ?: null]);
            $success_message = "Review added successfully.";

        } elseif ($action === 'edit_review') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['review_id'];
            $author_name = trim($_POST['author_name']);
            $review_text = trim($_POST['review_text']);
            $rating = (int)$_POST['rating'];
            $platform = trim($_POST['platform']);
            $project_title = trim($_POST['project_title']);

            if (empty($author_name) || empty($review_text) || empty($platform)) {
                throw new Exception("Author Name, Review Text, and Platform are required.");
            }

            $stmt = $db->prepare("
                UPDATE reviews 
                SET author_name = ?, review_text = ?, rating = ?, platform = ?, project_title = ?
                WHERE id = ?
            ");
            $stmt->execute([$author_name, $review_text, $rating, $platform, $project_title ?: null, $id]);
            $success_message = "Review updated successfully.";

        } elseif ($action === 'delete_review') {
            if (!in_array($user_role, ['administrator', 'site_manager'])) { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['review_id'];
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Review deleted successfully.";
        } elseif ($action === 'update_password') {
            $current_pw = $_POST['current_password'];
            $new_pw = $_POST['new_password'];
            $confirm_pw = $_POST['confirm_password'];

            if ($new_pw !== $confirm_pw) {
                $error_message = "New password and confirmation do not match.";
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$_SESSION['lspl_admin_username']]);
                $user = $stmt->fetch();

                if ($user && password_verify($current_pw, $user['password'])) {
                    $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->execute([$new_hash, $user['id']]);
                    $success_message = "Password updated successfully.";
                } else {
                    $error_message = "Current password verification failed.";
                }
            }
        } elseif ($action === 'add_user') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $role = trim($_POST['role']);

            if (empty($username) || empty($password) || empty($role)) {
                throw new Exception("Username, Password, and Role are required.");
            }
            if (!in_array($role, ['administrator', 'site_manager', 'service_manager', 'blog_editor'])) {
                throw new Exception("Invalid role specified.");
            }

            $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetchColumn() > 0) {
                throw new Exception("Username already exists.");
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $role]);
            $success_message = "User created successfully.";

        } elseif ($action === 'edit_user') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['user_id'];
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $role = trim($_POST['role']);

            if (empty($username) || empty($role)) {
                throw new Exception("Username and Role are required.");
            }
            if (!in_array($role, ['administrator', 'site_manager', 'service_manager', 'blog_editor'])) {
                throw new Exception("Invalid role specified.");
            }

            $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $chk->execute([$username, $id]);
            if ($chk->fetchColumn() > 0) {
                throw new Exception("Username already exists.");
            }

            if ($id === $user_id) {
                if ($role !== 'administrator') {
                    throw new Exception("You cannot change your own role from administrator to prevent lockout.");
                }
            }

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hash, $role, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $role, $id]);
            }
            $success_message = "User updated successfully.";

        } elseif ($action === 'delete_user') {
            if ($user_role !== 'administrator') { throw new Exception('Unauthorized action.'); }
            $id = (int)$_POST['user_id'];

            if ($id === $user_id) {
                throw new Exception("You cannot delete your own account.");
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "User deleted successfully.";
        }
    } catch (Exception $e) {
        $error_message = "Action Error: " . $e->getMessage();
    }
}

// Fetch settings
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
    <title>LSPL Workspace Admin | Control Panel</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .badge-type {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-type.contact { background: hsla(200 90% 50% / 0.15); color: hsl(200 90% 50%); }
        .badge-type.estimator { background: hsla(160 90% 40% / 0.15); color: hsl(160 90% 40%); }
        .badge-type.franchise { background: hsla(280 90% 50% / 0.15); color: hsl(280 90% 50%); }
        
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
    <!-- Admin Sidebar -->
    <div class="admin-sidebar glass-panel">
        <a href="index.php" class="logo">
            <img src="logo.png" alt="LSPL Logo">
        </a>
        
        <ul class="admin-nav">
            <?php if (in_array('leads', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'leads' ? 'active' : ''; ?>">
                <a href="?tab=leads"><i data-lucide="inbox" style="width: 18px; height: 18px;"></i> Leads CRM</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('services', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'services' ? 'active' : ''; ?>">
                <a href="?tab=services"><i data-lucide="layers" style="width: 18px; height: 18px;"></i> Services CRUD</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('industries', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'industries' ? 'active' : ''; ?>">
                <a href="?tab=industries"><i data-lucide="home" style="width: 18px; height: 18px;"></i> Industries CMS</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('pages', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'pages' ? 'active' : ''; ?>">
                <a href="?tab=pages"><i data-lucide="file-code" style="width: 18px; height: 18px;"></i> Pages CMS</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('menus', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'menus' ? 'active' : ''; ?>">
                <a href="?tab=menus"><i data-lucide="menu" style="width: 18px; height: 18px;"></i> Menu Manager</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('blogs', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'blogs' ? 'active' : ''; ?>">
                <a href="?tab=blogs"><i data-lucide="book-open" style="width: 18px; height: 18px;"></i> Blog CRUD</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('users', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'users' ? 'active' : ''; ?>">
                <a href="?tab=users"><i data-lucide="users" style="width: 18px; height: 18px;"></i> User Manager</a>
            </li>
            <?php endif; ?>
                        <?php if (in_array('reviews', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'reviews' ? 'active' : ''; ?>">
                <a href="?tab=reviews"><i data-lucide="star" style="width: 18px; height: 18px;"></i> Reviews CMS</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('settings', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                <a href="?tab=settings"><i data-lucide="settings" style="width: 18px; height: 18px;"></i> Site Settings</a>
            </li>
            <?php endif; ?>
            <?php if (in_array('account', $allowed_tabs)): ?>
            <li class="admin-nav-item <?php echo $current_tab === 'account' ? 'active' : ''; ?>">
                <a href="?tab=account"><i data-lucide="shield" style="width: 18px; height: 18px;"></i> Account Vault</a>
            </li>
            <?php endif; ?>
            <li class="admin-nav-item" style="margin-top: auto;">
                <a href="?action=logout" style="color: hsl(var(--destructive));"><i data-lucide="log-out" style="width: 18px; height: 18px;"></i> Log Out</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <div>
                <span class="badge badge-primary">LSPL Brand Console</span>
                <h1 style="font-size: 2rem; margin-top: 0.25rem;">
                    <?php
                    $tab_names = [
                        'reviews' => 'Customer Reviews & Feedback CMS',
                        'leads' => 'Inquiries and Project Estimations',
                        'services' => 'Services Portfolio CRUD',
                        'industries' => 'Industries & Sector Solutions (CMS)',
                        'pages' => 'Custom Website Pages (CMS)',
                        'menus' => 'Header Megamenu & Footer Custom Links',
                        'blogs' => 'Insights Blog Article CRUD',
                        'users' => 'User Accounts & Roles Manager',
                        'settings' => 'Site Global Config & Address',
                        'account' => 'Admin Credentials Settings'
                    ];
                    echo isset($tab_names[$current_tab]) ? $tab_names[$current_tab] : 'Workspace';
                    ?>
                </h1>
            </div>
            <div style="font-size: 0.9rem; color: hsl(var(--muted-foreground));">
                User: <strong><?php echo htmlspecialchars($_SESSION['lspl_admin_username']); ?></strong>
            </div>
        </div>

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

        <!-- TAB CONTENT: LEADS CRM -->
        <?php if ($current_tab === 'leads'): ?>
            <?php
            $leads_count = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            $new_count = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'New'")->fetchColumn();
            $contacted_count = $db->query("SELECT COUNT(*) FROM leads WHERE status = 'Contacted'")->fetchColumn();
            
            $leads = $db->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll();
            ?>
            <div class="dash-stats">
                <div class="glass-card dash-stat-card">
                    <div class="dash-stat-icon"><i data-lucide="inbox"></i></div>
                    <div>
                        <h4 style="font-size: 1.5rem;"><?php echo $leads_count; ?></h4>
                        <span style="font-size: 0.75rem; color: var(--muted-foreground)">Total Inquiries</span>
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
                        <span style="font-size: 0.75rem; color: var(--muted-foreground)">Active In-Progress</span>
                    </div>
                </div>
            </div>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Client Leads</h3>
                
                <?php if (empty($leads)): ?>
                    <p style="text-align: center; padding: 3rem 0; color: var(--muted-foreground);">No leads received yet.</p>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Service</th>
                                    <th>Budget</th>
                                    <th>Message Details</th>
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
                                        <td style="font-size: 0.82rem; max-width: 200px;">
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
                                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete lead entry?')">
                                                <input type="hidden" name="action" value="delete_lead">
                                                <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete" title="Delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <!-- TAB CONTENT: SERVICES CRUD -->
        <?php elseif ($current_tab === 'services'): ?>
            <?php
            $edit_service = null;
            if (isset($_GET['edit_service_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_service_id']]);
                $edit_service = $e_stmt->fetch();
            }
            $services = $db->query("SELECT * FROM services ORDER BY display_order ASC")->fetchAll();
            ?>

            <?php if ($edit_service): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="edit"></i> Modify Service: <?php echo htmlspecialchars($edit_service['title']); ?></h3>
                    <form method="POST" action="?tab=services" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_service">
                        <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Service Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_service['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Service Slug (SEO URL)</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_service['slug'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" class="form-control">
                                <option value="Web & Software" <?php echo $edit_service['category'] === 'Web & Software' ? 'selected' : ''; ?>>Web & Software</option>
                                <option value="E-Commerce Solution" <?php echo $edit_service['category'] === 'E-Commerce Solution' ? 'selected' : ''; ?>>E-Commerce Solution</option>
                                <option value="Marketing & Search" <?php echo $edit_service['category'] === 'Marketing & Search' ? 'selected' : ''; ?>>Marketing & Search</option>
                                <option value="Infrastructure" <?php echo $edit_service['category'] === 'Infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="icon">Lucide Icon Name</label>
                            <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($edit_service['icon']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $edit_service['display_order']; ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="tech_stack">Technology Tags (Comma separated)</label>
                            <input type="text" name="tech_stack" class="form-control" value="<?php echo htmlspecialchars($edit_service['tech_stack']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Short Description copy</label>
                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($edit_service['description']); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="editor-edit">Detailed SEO Content (HTML)</label>
                            <textarea name="content" id="editor-edit" class="form-control" rows="8"><?php echo htmlspecialchars($edit_service['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="full-width" style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="?tab=services" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="glass-card edit-form-panel" style="border-color: hsla(var(--success) / 0.3); background: hsla(var(--success) / 0.01);">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="plus-circle"></i> Create New Service Card</h3>
                    <form method="POST" action="?tab=services" class="edit-form-grid">
                        <input type="hidden" name="action" value="add_service">
                        
                        <div class="form-group">
                            <label for="title">Service Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Android Application Development" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Service Slug (SEO URL)</label>
                            <input type="text" name="slug" class="form-control" placeholder="e.g. android-app-development" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select name="category" class="form-control">
                                <option value="Web & Software">Web & Software</option>
                                <option value="E-Commerce Solution">E-Commerce Solution</option>
                                <option value="Marketing & Search">Marketing & Search</option>
                                <option value="Infrastructure">Infrastructure</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="icon">Lucide Icon Name</label>
                            <input type="text" name="icon" class="form-control" placeholder="smartphone" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="tech_stack">Technology Tags (Comma separated)</label>
                            <input type="text" name="tech_stack" class="form-control" placeholder="Kotlin, Android Studio, SDK" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Short Description copy</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter service description..." required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="editor-add">Detailed SEO Content (HTML)</label>
                            <textarea name="content" id="editor-add" class="form-control" rows="8" placeholder="Enter detailed HTML description..."></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem;">
                            <button type="submit" class="btn btn-secondary">Create Service Card</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Services Catalog</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Icon</th>
                                <th>Service Title</th>
                                <th>Category</th>
                                <th>Technology Stacks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $srv): ?>
                                <tr>
                                    <td><strong><?php echo $srv['display_order']; ?></strong></td>
                                    <td><i data-lucide="<?php echo htmlspecialchars($srv['icon']); ?>" style="color: hsl(var(--primary));"></i></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($srv['title']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($srv['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($srv['tech_stack']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?tab=services&edit_service_id=<?php echo $srv['id']; ?>" class="action-icon-btn"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete service?')">
                                                <input type="hidden" name="action" value="delete_service">
                                                <input type="hidden" name="service_id" value="<?php echo $srv['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- TAB CONTENT: PAGES CMS -->
        <?php elseif ($current_tab === 'pages'): ?>
            <?php
            $edit_page = null;
            if (isset($_GET['edit_page_id'])) {
                $p_stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
                $p_stmt->execute([(int)$_GET['edit_page_id']]);
                $edit_page = $p_stmt->fetch();
            }
            $pages = $db->query("SELECT * FROM pages ORDER BY display_order ASC")->fetchAll();
            ?>

            <?php if ($edit_page): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="edit"></i> Modify Page: <?php echo htmlspecialchars($edit_page['title']); ?></h3>
                    <form method="POST" action="?tab=pages" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_page">
                        <input type="hidden" name="page_id" value="<?php echo $edit_page['id']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Page Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_page['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Page URL Slug (Unique, no spaces)</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_page['slug']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="display_in_nav">Show in Main Nav Bar?</label>
                            <select name="display_in_nav" class="form-control">
                                <option value="1" <?php echo $edit_page['display_in_nav'] == 1 ? 'selected' : ''; ?>>Yes (Visible link)</option>
                                <option value="0" <?php echo $edit_page['display_in_nav'] == 0 ? 'selected' : ''; ?>>No (Hidden link)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Nav Bar Position Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $edit_page['display_order']; ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="content">Page Body Content (HTML formatting allowed)</label>
                            <textarea name="content" class="form-control" rows="12" required><?php echo htmlspecialchars($edit_page['content']); ?></textarea>
                        </div>

                        <div class="full-width" style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="?tab=pages" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="glass-card edit-form-panel" style="border-color: hsla(var(--success) / 0.3); background: hsla(var(--success) / 0.01);">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="plus-circle"></i> Create New Dynamic Page</h3>
                    <form method="POST" action="?tab=pages" class="edit-form-grid">
                        <input type="hidden" name="action" value="add_page">
                        
                        <div class="form-group">
                            <label for="title">Page Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Strategic Team" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Page URL Slug (Unique, lowercase, e.g. strategic-team)</label>
                            <input type="text" name="slug" class="form-control" placeholder="strategic-team" required>
                        </div>

                        <div class="form-group">
                            <label for="display_in_nav">Show in Nav?</label>
                            <select name="display_in_nav" class="form-control">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="content">Page Body Content (HTML allowed)</label>
                            <textarea name="content" class="form-control" rows="8" placeholder="<h3>Dynamic Subheading</h3><p>Enter page content body text here...</p>" required></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem;">
                            <button type="submit" class="btn btn-secondary">Create Dynamic Page</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Dynamic Pages List</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Page Title</th>
                                <th>Slug</th>
                                <th>Nav Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td><strong><?php echo $p['display_order']; ?></strong></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($p['title']); ?></td>
                                    <td style="color: hsl(var(--primary));"><code>page/<?php echo htmlspecialchars($p['slug']); ?></code></td>
                                    <td>
                                        <span class="badge <?php echo $p['display_in_nav'] == 1 ? 'badge-primary' : 'badge-outline'; ?>">
                                            <?php echo $p['display_in_nav'] == 1 ? 'Nav Visible' : 'Hidden'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?tab=pages&edit_page_id=<?php echo $p['id']; ?>" class="action-icon-btn"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete dynamic page?')">
                                                <input type="hidden" name="action" value="delete_page">
                                                <input type="hidden" name="page_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- TAB CONTENT: BLOGS CRUD -->
        <?php elseif ($current_tab === 'blogs'): ?>
            <?php
            $edit_blog = null;
            if (isset($_GET['edit_blog_id'])) {
                $b_stmt = $db->prepare("SELECT * FROM blogs WHERE id = ?");
                $b_stmt->execute([(int)$_GET['edit_blog_id']]);
                $edit_blog = $b_stmt->fetch();
            }
            $blogs = $db->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
            ?>

            <?php if ($edit_blog): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="edit"></i> Modify Article: <?php echo htmlspecialchars($edit_blog['title']); ?></h3>
                    <form method="POST" action="?tab=blogs" enctype="multipart/form-data" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_blog">
                        <input type="hidden" name="blog_id" value="<?php echo $edit_blog['id']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Article Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_blog['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">URL Slug (Unique, lowercase, e.g. laravel-mvc-speeds)</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_blog['slug']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="author">Author Name</label>
                            <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($edit_blog['author']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Publication Status</label>
                            <select name="status" class="form-control">
                                <option value="Published" <?php echo $edit_blog['status'] === 'Published' ? 'selected' : ''; ?>>Published</option>
                                <option value="Draft" <?php echo $edit_blog['status'] === 'Draft' ? 'selected' : ''; ?>>Draft / Hidden</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="summary">Short Summary (Shows in list view cards)</label>
                            <input type="text" name="summary" class="form-control" value="<?php echo htmlspecialchars($edit_blog['summary']); ?>" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="blog_image">Featured Image</label>
                            <?php if (!empty($edit_blog['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($edit_blog['image_url']); ?>?v=<?php echo filemtime($edit_blog['image_url']); ?>" style="max-height: 100px; width: auto; object-fit: contain; margin-bottom: 0.5rem; display: block; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <?php endif; ?>
                            <input type="file" name="blog_image" id="blog_image" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group full-width">
                            <label for="content">Article Full Content body (HTML allowed)</label>
                            <textarea name="content" id="editor-edit" class="form-control" rows="12"><?php echo htmlspecialchars($edit_blog['content']); ?></textarea>
                        </div>

                        <div class="full-width" style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="?tab=blogs" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="glass-card edit-form-panel" style="border-color: hsla(var(--success) / 0.3); background: hsla(var(--success) / 0.01);">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="plus-circle"></i> Create New Blog Post</h3>
                    <form method="POST" action="?tab=blogs" enctype="multipart/form-data" class="edit-form-grid">
                        <input type="hidden" name="action" value="add_blog">
                        
                        <div class="form-group">
                            <label for="title">Article Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Scaling Database Clusters" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">URL Slug</label>
                            <input type="text" name="slug" class="form-control" placeholder="scaling-database-clusters" required>
                        </div>

                        <div class="form-group">
                            <label for="author">Author Name</label>
                            <input type="text" name="author" class="form-control" value="Admin" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Publication Status</label>
                            <select name="status" class="form-control">
                                <option value="Published">Published</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="summary">Short Summary</label>
                            <input type="text" name="summary" class="form-control" placeholder="Enter a brief summary sentence..." required>
                        </div>

                        <div class="form-group full-width">
                            <label for="blog_image">Featured Image</label>
                            <input type="file" name="blog_image" id="blog_image" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group full-width">
                            <label for="content">Article Full Content (HTML allowed)</label>
                            <textarea name="content" id="editor-add" class="form-control" rows="8" placeholder="Enter article body content..."></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem;">
                            <button type="submit" class="btn btn-secondary">Publish Article</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Blog Articles List</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Article Title</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blogs as $b): ?>
                                <tr>
                                    <td style="white-space: nowrap; font-size: 0.82rem;"><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($b['title']); ?></td>
                                    <td><?php echo htmlspecialchars($b['author']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $b['status'] === 'Published' ? 'badge-primary' : 'badge-outline'; ?>">
                                            <?php echo htmlspecialchars($b['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?tab=blogs&edit_blog_id=<?php echo $b['id']; ?>" class="action-icon-btn"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete blog post?')">
                                                <input type="hidden" name="action" value="delete_blog">
                                                <input type="hidden" name="blog_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- TAB CONTENT: SETTINGS -->
        <?php elseif ($current_tab === 'settings'): ?>
            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Landing Page Global Settings</h3>
                <form method="POST" action="?tab=settings" enctype="multipart/form-data" class="edit-form-grid">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <h4 class="full-width" style="margin-top: 1rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">1. SEO Headers, Titles & Meta Configurations</h4>
                    
                    <div class="form-group">
                        <label for="site_title">Site Title</label>
                        <input type="text" name="settings[site_title]" class="form-control" value="<?php echo htmlspecialchars($site['site_title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_tagline">Tagline</label>
                        <input type="text" name="settings[site_tagline]" class="form-control" value="<?php echo htmlspecialchars($site['site_tagline'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="canonical_url">Canonical Base URL</label>
                        <input type="url" name="settings[canonical_url]" class="form-control" value="<?php echo htmlspecialchars($site['canonical_url'] ?? ''); ?>" placeholder="https://www.yourdomain.com">
                    </div>

                    <div class="form-group">
                        <label for="og_image_url">Default Open Graph Image URL</label>
                        <input type="text" name="settings[og_image_url]" class="form-control" value="<?php echo htmlspecialchars($site['og_image_url'] ?? ''); ?>" placeholder="logo.png or absolute URL">
                    </div>

                    <div class="form-group">
                        <label for="sitemap_enabled">XML Sitemap Enabled</label>
                        <select name="settings[sitemap_enabled]" class="form-control">
                            <option value="1" <?php echo ($site['sitemap_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Yes (Active)</option>
                            <option value="0" <?php echo ($site['sitemap_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>No (Deactivated)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="schema_markup">Custom JSON-LD Schema (Global)</label>
                        <textarea name="settings[schema_markup]" class="form-control" rows="3" placeholder='{"@context": "https://schema.org", ...}'><?php echo htmlspecialchars($site['schema_markup'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="settings[meta_description]" class="form-control" rows="2" required><?php echo htmlspecialchars($site['meta_description'] ?? ''); ?></textarea>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">2. Hero Copy</h4>

                    <div class="form-group full-width">
                        <label for="hero_title">Hero Heading Title</label>
                        <input type="text" name="settings[hero_title]" class="form-control" value="<?php echo htmlspecialchars($site['hero_title'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="hero_subtitle">Hero Paragraph Description</label>
                        <textarea name="settings[hero_subtitle]" class="form-control" rows="3" required><?php echo htmlspecialchars($site['hero_subtitle'] ?? ''); ?></textarea>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">3. Stats Metrics</h4>

                    <div class="form-group">
                        <label for="stats_projects">Projects Completed</label>
                        <input type="text" name="settings[stats_projects]" class="form-control" value="<?php echo htmlspecialchars($site['stats_projects'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stats_students">Students Trained</label>
                        <input type="text" name="settings[stats_students]" class="form-control" value="<?php echo htmlspecialchars($site['stats_students'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stats_technologies">Tech Stacks</label>
                        <input type="text" name="settings[stats_technologies]" class="form-control" value="<?php echo htmlspecialchars($site['stats_technologies'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stats_experience">Years Experience</label>
                        <input type="text" name="settings[stats_experience]" class="form-control" value="<?php echo htmlspecialchars($site['stats_experience'] ?? ''); ?>" required>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">4. Address & Support Info</h4>

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

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">5. Security & Admin Path Slug</h4>
                    
                    <div class="form-group">
                        <label for="admin_slug">Custom Admin Path Slug</label>
                        <input type="text" name="settings[admin_slug]" class="form-control" value="<?php echo htmlspecialchars($site['admin_slug'] ?? 'admin'); ?>" placeholder="e.g. admin, xyz, secure-panel" pattern="[a-zA-Z0-9\-_]+" title="Only alphanumeric characters, dashes, and underscores are allowed." required>
                        <small style="display: block; margin-top: 0.25rem; color: var(--muted-foreground);">Changing this updates the admin URL immediately (e.g. <code>pawan</code> makes the admin page accessible at <code>/pawan</code>).</small>
                    </div>

                    <h4 class="full-width" style="margin-top: 1.5rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.5rem; color: hsl(var(--primary));">6. Branding & Logo</h4>
                    
                    <div class="form-group full-width">
                        <label>Current Logo</label>
                        <?php if (file_exists(__DIR__ . '/logo.png')): ?>
                            <img src="logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" style="max-height: 50px; background: rgba(255,255,255,0.05); padding: 5px; border-radius: var(--radius-sm); display: block; margin-top: 0.5rem;">
                        <?php else: ?>
                            <span style="color: var(--muted-foreground);">No logo uploaded.</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="logo_file">Upload New Logo (Overwrites current logo.png)</label>
                        <input type="file" name="logo" id="logo_file" class="form-control" accept="image/*">
                    </div>

                    <div class="full-width" style="margin-top: 1.5rem;">
                        <p style="font-size:0.75rem; color: var(--muted-foreground); margin-bottom:0.5rem;">Registered address must match Kanpur (Founded 2014) as per company documents.</p>
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Save Configurations</button>
                    </div>
                </form>
            </div>

        <!-- TAB CONTENT: MENU MANAGER -->
                <?php elseif ($current_tab === 'menus'): ?>
            <!-- TAB CONTENT: MENU MANAGER -->
            
            <?php
            $all_cms_pages = $db->query("SELECT title, slug FROM pages ORDER BY title ASC")->fetchAll();
            
            $edit_header = null;
            if (isset($_GET['edit_header_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM header_menu_items WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_header_id']]);
                $edit_header = $e_stmt->fetch();
            }

            $edit_footer = null;
            if (isset($_GET['edit_footer_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM footer_items WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_footer_id']]);
                $edit_footer = $e_stmt->fetch();
            }

            $header_items = $db->query("SELECT * FROM header_menu_items ORDER BY display_order ASC, id ASC")->fetchAll();
            $footer_items = $db->query("SELECT * FROM footer_items ORDER BY column_name ASC, display_order ASC")->fetchAll();

            $top_menu = [];
            $sub_menu = [];
            foreach ($header_items as $item) {
                if ($item['parent_id'] === null || $item['parent_id'] == '') {
                    $top_menu[] = $item;
                } else {
                    $sub_menu[$item['parent_id']][] = $item;
                }
            }
            ?>

            <div style="margin-bottom: 4rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid hsl(var(--primary)); padding-bottom: 0.5rem; color: hsl(var(--primary));"><i data-lucide="menu"></i> Header Navigation Manager</h2>
                
                <?php if ($edit_header): ?>
                    <div class="glass-card edit-form-panel">
                        <h3 style="font-size: 1.15rem; margin-bottom: 1.5rem;"><i data-lucide="edit"></i> Modify Header Link: <?php echo htmlspecialchars($edit_header['title']); ?></h3>
                        <form method="POST" action="?tab=menus" class="edit-form-grid">
                            <input type="hidden" name="action" value="edit_header_menu">
                            <input type="hidden" name="header_menu_id" value="<?php echo $edit_header['id']; ?>">
                            
                            <div class="form-group">
                                <label for="parent_id_edit">Parent Item</label>
                                <select name="parent_id" id="parent_id_edit" class="form-control">
                                    <option value="">-- Top-Level Item --</option>
                                    <?php foreach ($top_menu as $top): 
                                        if ($top['id'] == $edit_header['id']) continue;
                                    ?>
                                        <option value="<?php echo $top['id']; ?>" data-menu-type="<?php echo htmlspecialchars($top['menu_type']); ?>" <?php echo $edit_header['parent_id'] == $top['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($top['title']); ?> (<?php echo htmlspecialchars($top['menu_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="title_edit">Link Display Label</label>
                                <input type="text" name="title" id="title_edit" class="form-control" value="<?php echo htmlspecialchars($edit_header['title']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="link_type_edit">Link Target Type</label>
                                <select name="link_type" id="link_type_edit" class="form-control">
                                    <option value="custom" <?php echo $edit_header['link_type'] === 'custom' ? 'selected' : ''; ?>>Custom URL / Static Path</option>
                                    <option value="page" <?php echo $edit_header['link_type'] === 'page' ? 'selected' : ''; ?>>CMS Dynamic Page</option>
                                    <option value="none" <?php echo $edit_header['link_type'] === 'none' ? 'selected' : ''; ?>>No Link (Dropdown Trigger)</option>
                                </select>
                            </div>

                            <div class="form-group" id="page_slug_group_edit">
                                <label for="page_slug_edit">Link to CMS Page</label>
                                <select name="page_slug" id="page_slug_edit" class="form-control">
                                    <option value="">-- Select Page --</option>
                                    <?php foreach ($all_cms_pages as $cp): ?>
                                        <option value="<?php echo htmlspecialchars($cp['slug']); ?>" <?php echo $edit_header['page_slug'] === $cp['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cp['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" id="custom_url_group_edit">
                                <label for="custom_url_edit">Link to Custom URL</label>
                                <input type="text" name="custom_url" id="custom_url_edit" class="form-control" value="<?php echo htmlspecialchars($edit_header['custom_url'] ?? ''); ?>">
                            </div>

                            <div class="form-group" id="menu_type_group_edit">
                                <label for="menu_type_edit">Menu Type (Top-Level Only)</label>
                                <select name="menu_type" id="menu_type_edit" class="form-control">
                                    <option value="single_page" <?php echo $edit_header['menu_type'] === 'single_page' ? 'selected' : ''; ?>>Single Link</option>
                                    <option value="dropdown" <?php echo $edit_header['menu_type'] === 'dropdown' ? 'selected' : ''; ?>>Standard Dropdown</option>
                                    <option value="megamenu" <?php echo $edit_header['menu_type'] === 'megamenu' ? 'selected' : ''; ?>>Megamenu</option>
                                </select>
                            </div>

                            <div class="form-group" id="column_name_group_edit">
                                <label for="column_name_edit">Column Group (Megamenu Only)</label>
                                <input type="text" name="column_name" id="column_name_edit" class="form-control" value="<?php echo htmlspecialchars($edit_header['column_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="display_order_edit">Display Order</label>
                                <input type="number" name="display_order" id="display_order_edit" class="form-control" value="<?php echo $edit_header['display_order']; ?>" required>
                            </div>

                            <div class="full-width" style="margin-top: 1rem; display: flex; gap: 1rem;">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="?tab=menus" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="glass-card edit-form-panel" style="border-color: hsla(var(--success) / 0.3); background: hsla(var(--success) / 0.01);">
                        <h3 style="font-size: 1.15rem; margin-bottom: 1.5rem;"><i data-lucide="plus-circle"></i> Add Header Menu Item</h3>
                        <form method="POST" action="?tab=menus" class="edit-form-grid">
                            <input type="hidden" name="action" value="add_header_menu">
                            
                            <div class="form-group">
                                <label for="parent_id_add">Parent Item</label>
                                <select name="parent_id" id="parent_id_add" class="form-control">
                                    <option value="">-- Top-Level Item --</option>
                                    <?php foreach ($top_menu as $top): ?>
                                        <option value="<?php echo $top['id']; ?>" data-menu-type="<?php echo htmlspecialchars($top['menu_type']); ?>">
                                            <?php echo htmlspecialchars($top['title']); ?> (<?php echo htmlspecialchars($top['menu_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="title_add">Link Display Label</label>
                                <input type="text" name="title" id="title_add" class="form-control" placeholder="e.g. Services" required>
                            </div>

                            <div class="form-group">
                                <label for="link_type_add">Link Target Type</label>
                                <select name="link_type" id="link_type_add" class="form-control">
                                    <option value="custom" selected>Custom URL / Static Path</option>
                                    <option value="page">CMS Dynamic Page</option>
                                    <option value="none">No Link (Dropdown Trigger)</option>
                                </select>
                            </div>

                            <div class="form-group" id="page_slug_group_add">
                                <label for="page_slug_add">Link to CMS Page</label>
                                <select name="page_slug" id="page_slug_add" class="form-control">
                                    <option value="">-- Select Page --</option>
                                    <?php foreach ($all_cms_pages as $cp): ?>
                                        <option value="<?php echo htmlspecialchars($cp['slug']); ?>"><?php echo htmlspecialchars($cp['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" id="custom_url_group_add">
                                <label for="custom_url_add">Link to Custom URL</label>
                                <input type="text" name="custom_url" id="custom_url_add" class="form-control" placeholder="e.g., index.php#estimator">
                            </div>

                            <div class="form-group" id="menu_type_group_add">
                                <label for="menu_type_add">Menu Type (Top-Level Only)</label>
                                <select name="menu_type" id="menu_type_add" class="form-control">
                                    <option value="single_page" selected>Single Link</option>
                                    <option value="dropdown">Standard Dropdown</option>
                                    <option value="megamenu">Megamenu</option>
                                </select>
                            </div>

                            <div class="form-group" id="column_name_group_add">
                                <label for="column_name_add">Column Group (Megamenu Only)</label>
                                <input type="text" name="column_name" id="column_name_add" class="form-control" placeholder="e.g. Web Services">
                            </div>

                            <div class="form-group">
                                <label for="display_order_add">Display Order</label>
                                <input type="number" name="display_order" id="display_order_add" class="form-control" value="0" required>
                            </div>

                            <div class="full-width" style="margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary">Add Header Menu Item</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="glass-card">
                    <h3 style="font-size: 1.15rem; margin-bottom: 1rem;">Header Menu Node Tree</h3>
                    <?php if (empty($header_items)): ?>
                        <p style="text-align: center; color: var(--muted-foreground); padding: 2rem 0;">No navigation menu nodes configured.</p>
                    <?php else: ?>
                        <div class="menu-builder-container" style="background: hsla(var(--card) / 0.5); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid hsl(var(--border));">
                            <p style="font-size: 0.8rem; color: var(--muted-foreground); margin-bottom: 1.5rem;"><i data-lucide="info" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> Drag and drop menu items vertically to reorder them, or drag right/left to indent or outdent them. You can also use the helper arrows on each item.</p>
                            <ul class="nested-sortable-list">
                                <?php 
                                foreach ($top_menu as $top): 
                                    $top_href = $top['link_type'] === 'page' ? 'page/' . $top['page_slug'] : $top['custom_url'];
                                    if ($top['link_type'] === 'none') $top_href = '-- No Link --';
                                ?>
                                    <li class="menu-item-node" draggable="true" data-id="<?php echo $top['id']; ?>">
                                        <div class="menu-item-card">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <i data-lucide="grip-vertical" class="drag-handle" style="cursor: move; color: var(--muted-foreground);"></i>
                                                <strong><?php echo htmlspecialchars($top['title']); ?></strong>
                                                <span style="font-size: 0.7rem; color: var(--muted-foreground);">type: <?php echo htmlspecialchars($top['link_type']); ?> (<?php echo htmlspecialchars($top['menu_type']); ?>)</span>
                                            </div>
                                            <div class="menu-item-actions" style="display: flex; align-items: center; gap: 0.25rem;">
                                                <button type="button" class="action-icon-btn" onclick="moveMenuUp(this)" title="Move Up"><i data-lucide="arrow-up" style="width: 14px; height: 14px;"></i></button>
                                                <button type="button" class="action-icon-btn" onclick="moveMenuDown(this)" title="Move Down"><i data-lucide="arrow-down" style="width: 14px; height: 14px;"></i></button>
                                                <button type="button" class="action-icon-btn" onclick="changeMenuIndent(this, 'indent')" title="Indent"><i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></button>
                                                <button type="button" class="action-icon-btn" onclick="changeMenuIndent(this, 'outdent')" title="Outdent"><i data-lucide="chevron-left" style="width: 14px; height: 14px;"></i></button>
                                                <a href="?tab=menus&edit_header_id=<?php echo $top['id']; ?>" class="action-icon-btn" title="Edit"><i data-lucide="edit-2" style="width: 14px; height: 14px;"></i></a>
                                                <form method="POST" action="?tab=menus" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this menu item and all submenus?');">
                                                    <input type="hidden" name="action" value="delete_header_menu">
                                                    <input type="hidden" name="header_menu_id" value="<?php echo $top['id']; ?>">
                                                    <button type="submit" class="action-icon-btn delete" title="Delete"><i data-lucide="trash-2" style="width: 14px; height: 14px;"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                        <ul class="submenu-list">
                                            <?php if (isset($sub_menu[$top['id']])): ?>
                                                <?php foreach ($sub_menu[$top['id']] as $sub): 
                                                    $sub_href = $sub['link_type'] === 'page' ? 'page/' . $sub['page_slug'] : $sub['custom_url'];
                                                ?>
                                                    <li class="menu-item-node" draggable="true" data-id="<?php echo $sub['id']; ?>">
                                                        <div class="menu-item-card" style="border-color: hsla(var(--primary) / 0.15); background: hsla(var(--primary) / 0.01);">
                                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                                <i data-lucide="grip-vertical" class="drag-handle" style="cursor: move; color: var(--muted-foreground);"></i>
                                                                <span><?php echo htmlspecialchars($sub['title']); ?></span>
                                                                <span style="font-size: 0.7rem; color: var(--muted-foreground);">type: <?php echo htmlspecialchars($sub['link_type']); ?><?php if ($sub['column_name']): ?> | column: <?php echo htmlspecialchars($sub['column_name']); ?><?php endif; ?></span>
                                                            </div>
                                                            <div class="menu-item-actions" style="display: flex; align-items: center; gap: 0.25rem;">
                                                                <button type="button" class="action-icon-btn" onclick="moveMenuUp(this)" title="Move Up"><i data-lucide="arrow-up" style="width: 14px; height: 14px;"></i></button>
                                                                <button type="button" class="action-icon-btn" onclick="moveMenuDown(this)" title="Move Down"><i data-lucide="arrow-down" style="width: 14px; height: 14px;"></i></button>
                                                                <button type="button" class="action-icon-btn" onclick="changeMenuIndent(this, 'indent')" title="Indent"><i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></button>
                                                                <button type="button" class="action-icon-btn" onclick="changeMenuIndent(this, 'outdent')" title="Outdent"><i data-lucide="chevron-left" style="width: 14px; height: 14px;"></i></button>
                                                                <a href="?tab=menus&edit_header_id=<?php echo $sub['id']; ?>" class="action-icon-btn" title="Edit"><i data-lucide="edit-2" style="width: 14px; height: 14px;"></i></a>
                                                                <form method="POST" action="?tab=menus" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this sub-menu link?');">
                                                                    <input type="hidden" name="action" value="delete_header_menu">
                                                                    <input type="hidden" name="header_menu_id" value="<?php echo $sub['id']; ?>">
                                                                    <button type="submit" class="action-icon-btn delete" title="Delete"><i data-lucide="trash-2" style="width: 14px; height: 14px;"></i></button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        <ul class="submenu-list"></ul>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
        <?php elseif ($current_tab === 'industries'): ?>
            <!-- TAB CONTENT: INDUSTRIES CMS -->
            <?php
            $edit_industry = null;
            if (isset($_GET['edit_industry_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM industries WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_industry_id']]);
                $edit_industry = $e_stmt->fetch();
            }
            $industries = $db->query("SELECT * FROM industries ORDER BY display_order ASC")->fetchAll();
            ?>

            <?php if ($edit_industry): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="edit"></i> Modify Industry Solution: <?php echo htmlspecialchars($edit_industry['title']); ?></h3>
                    <form method="POST" action="?tab=industries" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_industry">
                        <input type="hidden" name="industry_id" value="<?php echo $edit_industry['id']; ?>">
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_industry['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">Slug (SEO URL)</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_industry['slug']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="icon">Lucide Icon Name</label>
                            <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($edit_industry['icon']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $edit_industry['display_order']; ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Short Description copy</label>
                            <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($edit_industry['description']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="editor-edit">Detailed Content (HTML)</label>
                            <textarea name="content" id="editor-edit" class="form-control" rows="10"><?php echo htmlspecialchars($edit_industry['content']); ?></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="?tab=industries" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Industry Solutions Catalog</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Icon</th>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($industries as $ind): ?>
                                <tr>
                                    <td><strong><?php echo $ind['display_order']; ?></strong></td>
                                    <td><i data-lucide="<?php echo htmlspecialchars($ind['icon']); ?>" style="color: hsl(var(--primary));"></i></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($ind['title']); ?></td>
                                    <td><code><?php echo htmlspecialchars($ind['slug']); ?></code></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?tab=industries&edit_industry_id=<?php echo $ind['id']; ?>" class="action-icon-btn"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete industry solution?')">
                                                <input type="hidden" name="action" value="delete_industry">
                                                <input type="hidden" name="industry_id" value="<?php echo $ind['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card admin-card" style="margin-top: 2rem;">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="plus-circle"></i> Add New Industry Solution</h3>
                <form method="POST" action="?tab=industries" class="edit-form-grid">
                    <input type="hidden" name="action" value="add_industry">
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug (SEO URL)</label>
                        <input type="text" name="slug" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="icon">Lucide Icon Name</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g. cpu, Activity" required>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Short Description copy</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="editor-add">Detailed Content (HTML)</label>
                        <textarea name="content" id="editor-add" class="form-control" rows="10"></textarea>
                    </div>

                    <div class="full-width" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Create Solution</button>
                    </div>
                </form>
            </div>
        <?php elseif ($current_tab === 'reviews'): ?>
            <!-- TAB CONTENT: REVIEWS CMS -->
            <?php
            $edit_review = null;
            if (isset($_GET['edit_review_id'])) {
                $e_stmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_review_id']]);
                $edit_review = $e_stmt->fetch();
            }
            $reviews = $db->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
            ?>

            <?php if ($edit_review): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="edit"></i> Modify Customer Review</h3>
                    <form method="POST" action="?tab=reviews" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_review">
                        <input type="hidden" name="review_id" value="<?php echo $edit_review['id']; ?>">
                        
                        <div class="form-group">
                            <label for="author_name">Author Name</label>
                            <input type="text" name="author_name" class="form-control" value="<?php echo htmlspecialchars($edit_review['author_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="rating">Rating Stars</label>
                            <select name="rating" class="form-control">
                                <option value="5" <?php echo $edit_review['rating'] == 5 ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?php echo $edit_review['rating'] == 4 ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="3" <?php echo $edit_review['rating'] == 3 ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="2" <?php echo $edit_review['rating'] == 2 ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="1" <?php echo $edit_review['rating'] == 1 ? 'selected' : ''; ?>>1 Star</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="platform">Platform (e.g. Google, Trustpilot)</label>
                            <input type="text" name="platform" class="form-control" value="<?php echo htmlspecialchars($edit_review['platform']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="project_title">Project/Course Title (Optional)</label>
                            <input type="text" name="project_title" class="form-control" value="<?php echo htmlspecialchars($edit_review['project_title'] ?? ''); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="review_text">Review Testimonial Text</label>
                            <textarea name="review_text" class="form-control" rows="4" required><?php echo htmlspecialchars($edit_review['review_text']); ?></textarea>
                        </div>

                        <div class="full-width" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">Save Review</button>
                            <a href="?tab=reviews" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Customer Testimonials</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Author</th>
                                <th>Rating</th>
                                <th>Platform</th>
                                <th>Project / Context</th>
                                <th>Snippet</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $rev): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($rev['author_name']); ?></td>
                                    <td><span style="color: hsl(var(--primary));"><?php echo str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']); ?></span></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($rev['platform']); ?></span></td>
                                    <td><?php echo htmlspecialchars($rev['project_title'] ?? 'General Client'); ?></td>
                                    <td style="font-size: 0.85rem; color: var(--muted-foreground); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($rev['review_text']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?tab=reviews&edit_review_id=<?php echo $rev['id']; ?>" class="action-icon-btn"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete review?')">
                                                <input type="hidden" name="action" value="delete_review">
                                                <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card admin-card" style="margin-top: 2rem;">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="plus-circle"></i> Add New Customer Testimonial</h3>
                <form method="POST" action="?tab=reviews" class="edit-form-grid">
                    <input type="hidden" name="action" value="add_review">
                    
                    <div class="form-group">
                        <label for="author_name">Author Name</label>
                        <input type="text" name="author_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating Stars</label>
                        <select name="rating" class="form-control">
                            <option value="5" selected>5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="platform">Platform (e.g. Google, Trustpilot, LinkedIn)</label>
                        <input type="text" name="platform" class="form-control" placeholder="e.g. Google" required>
                    </div>

                    <div class="form-group">
                        <label for="project_title">Project/Course Title (Optional)</label>
                        <input type="text" name="project_title" class="form-control" placeholder="e.g. Custom Web Development">
                    </div>

                    <div class="form-group full-width">
                        <label for="review_text">Review Testimonial Text</label>
                        <textarea name="review_text" class="form-control" rows="4" placeholder="Enter client's feedback here..." required></textarea>
                    </div>

                    <div class="full-width" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Create Review</button>
                    </div>
                </form>
            </div>
        <?php elseif ($current_tab === 'users'): ?>
            <!-- TAB CONTENT: USER MANAGER -->
            <?php
            $edit_user = null;
            if (isset($_GET['edit_user_id'])) {
                $e_stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = ?");
                $e_stmt->execute([(int)$_GET['edit_user_id']]);
                $edit_user = $e_stmt->fetch();
            }
            $users = $db->query("SELECT id, username, role, created_at FROM users ORDER BY username ASC")->fetchAll();
            ?>

            <?php if ($edit_user): ?>
                <div class="glass-card edit-form-panel">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="user-cog"></i> Modify User Account: <?php echo htmlspecialchars($edit_user['username']); ?></h3>
                    <form method="POST" action="?tab=users" class="edit-form-grid">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="role">Assign Security Role</label>
                            <select name="role" class="form-control">
                                <option value="administrator" <?php echo $edit_user['role'] === 'administrator' ? 'selected' : ''; ?>>Administrator (Full Access)</option>
                                <option value="site_manager" <?php echo $edit_user['role'] === 'site_manager' ? 'selected' : ''; ?>>Site Manager (CMS & Leads)</option>
                                <option value="service_manager" <?php echo $edit_user['role'] === 'service_manager' ? 'selected' : ''; ?>>Service Manager (Service Portfolio)</option>
                                <option value="blog_editor" <?php echo $edit_user['role'] === 'blog_editor' ? 'selected' : ''; ?>>Blog Editor (Only Blog Posts)</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="password">Change Password (Leave blank to keep current password)</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password (optional)">
                        </div>

                        <div class="full-width" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="?tab=users" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="glass-card admin-card">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Administrative Accounts</h3>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $usr): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($usr['username']); ?></td>
                                    <td><span class="badge badge-primary"><?php echo htmlspecialchars($usr['role']); ?></span></td>
                                    <td><code><?php echo htmlspecialchars($usr['created_at']); ?></code></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?tab=users&edit_user_id=<?php echo $usr['id']; ?>" class="action-icon-btn"><i data-lucide="edit" style="width: 16px; height: 16px;"></i></a>
                                            <?php if ($usr['id'] !== $user_id): ?>
                                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete user account?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                                    <button type="submit" class="action-icon-btn delete"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card admin-card" style="margin-top: 2rem;">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="user-plus"></i> Create New Administrative User</h3>
                <form method="POST" action="?tab=users" class="edit-form-grid">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Assign Security Role</label>
                        <select name="role" class="form-control">
                            <option value="administrator">Administrator (Full Access)</option>
                            <option value="site_manager">Site Manager (CMS & Leads)</option>
                            <option value="service_manager">Service Manager (Service Portfolio)</option>
                            <option value="blog_editor">Blog Editor (Only Blog Posts)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="password">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="full-width" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Create Account</button>
                    </div>
                </form>
            </div>
        <?php elseif ($current_tab === 'account'): ?>
            <!-- TAB CONTENT: ACCOUNT VAULT -->
            <div class="glass-card edit-form-panel">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i data-lucide="key"></i> Update Security Credentials</h3>
                <form method="POST" action="?tab=account" class="edit-form-grid" style="max-width: 600px;">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group full-width">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <div class="full-width" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Update Password</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (document.querySelector('#editor-edit')) {
                ClassicEditor
                    .create(document.querySelector('#editor-edit'))
                    .catch(error => { console.error(error); });
            }
            if (document.querySelector('#editor-add')) {
                ClassicEditor
                    .create(document.querySelector('#editor-add'))
                    .catch(error => { console.error(error); });
            }
        });
    </script>
    <script src="app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();

        // AJAX Logo Auto-Uploader & Drag-and-Drop Menu Builder JS Module
        document.addEventListener('DOMContentLoaded', () => {
            // A. Settings Logo AJAX Auto-Uploader
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                if (input.name === 'blog_image') return;
                
                input.addEventListener('change', async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    const fieldName = e.target.name;
                    const previewId = fieldName + '_preview';
                    const previewImg = document.getElementById(previewId);
                    const noFileLabel = document.getElementById(fieldName + '_no_file');

                    const formData = new FormData();
                    formData.append('action', 'ajax_upload_logo');
                    formData.append('field', fieldName);
                    formData.append(fieldName, file);

                    if (previewImg) {
                        previewImg.style.opacity = '0.5';
                    }

                    try {
                        const response = await fetch('?tab=settings', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        if (result.success) {
                            if (previewImg) {
                                previewImg.src = result.url;
                                previewImg.style.display = 'block';
                                previewImg.style.opacity = '1';
                                if (noFileLabel) noFileLabel.style.display = 'none';
                                showToast('Logo uploaded and updated successfully!', 'success');
                            }
                        } else {
                            alert('Upload failed: ' + result.message);
                            if (previewImg) previewImg.style.opacity = '1';
                        }
                    } catch (error) {
                        console.error('Error uploading logo:', error);
                        alert('An error occurred while uploading the logo.');
                        if (previewImg) previewImg.style.opacity = '1';
                    }
                });
            });

            // B. Drag and Drop Menu Builder
            initDragAndDrop();
        });

        // Drag and Drop Logic
        let draggedEl = null;

        function initDragAndDrop() {
            const list = document.querySelector('.nested-sortable-list');
            if (!list) return;

            list.addEventListener('dragstart', (e) => {
                const item = e.target.closest('.menu-item-node');
                if (!item) return;
                draggedEl = item;
                item.classList.add('dragging');
                e.stopPropagation();
            });

            list.addEventListener('dragend', (e) => {
                const item = e.target.closest('.menu-item-node');
                if (item) {
                    item.classList.remove('dragging');
                }
                draggedEl = null;
                saveMenuStructure();
            });

            list.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!draggedEl) return;

                const targetNode = e.target.closest('.menu-item-node');
                if (!targetNode || targetNode === draggedEl) return;

                const card = targetNode.querySelector('.menu-item-card');
                if (!card) return;

                const rect = card.getBoundingClientRect();
                const mouseY = e.clientY - rect.top;
                const mouseX = e.clientX - rect.left;

                const isBefore = mouseY < rect.height / 2;

                if (mouseX > 40 && !isBefore) {
                    let submenu = targetNode.querySelector('.submenu-list');
                    if (!submenu) {
                        submenu = document.createElement('ul');
                        submenu.className = 'submenu-list';
                        targetNode.appendChild(submenu);
                    }
                    const parentList = targetNode.parentElement;
                    if (parentList.classList.contains('nested-sortable-list')) {
                        submenu.appendChild(draggedEl);
                        return;
                    }
                }

                const parentList = targetNode.parentElement;
                if (mouseX < -10 && parentList.classList.contains('submenu-list')) {
                    const grandParentLi = parentList.closest('.menu-item-node');
                    if (grandParentLi) {
                        grandParentLi.parentNode.insertBefore(draggedEl, grandParentLi.nextSibling);
                        return;
                    }
                }

                if (isBefore) {
                    targetNode.parentNode.insertBefore(draggedEl, targetNode);
                } else {
                    targetNode.parentNode.insertBefore(draggedEl, targetNode.nextSibling);
                }
            });
        }

        async function saveMenuStructure() {
            const serialized = [];
            const rootUl = document.querySelector('.nested-sortable-list');
            if (!rootUl) return;

            const walk = (ul, parentId) => {
                const items = ul.querySelectorAll(':scope > li.menu-item-node');
                items.forEach((li, order) => {
                    const id = li.dataset.id;
                    serialized.push({
                        id: id,
                        parent_id: parentId,
                        display_order: order
                    });
                    const subUl = li.querySelector(':scope > ul.submenu-list');
                    if (subUl) {
                        walk(subUl, id);
                    }
                });
            };

            walk(rootUl, null);

            const formData = new FormData();
            formData.append('action', 'update_menu_order');
            formData.append('order_data', JSON.stringify(serialized));

            try {
                const response = await fetch('?tab=menus', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Menu order updated successfully!', 'success');
                } else {
                    alert('Failed to update menu order: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating menu order:', error);
            }
        }

        function moveMenuUp(btn) {
            const li = btn.closest('.menu-item-node');
            const prev = li.previousElementSibling;
            if (prev) {
                li.parentNode.insertBefore(li, prev);
                saveMenuStructure();
            }
        }

        function moveMenuDown(btn) {
            const li = btn.closest('.menu-item-node');
            const next = li.nextElementSibling;
            if (next) {
                li.parentNode.insertBefore(next, li);
                saveMenuStructure();
            }
        }

        function changeMenuIndent(btn, direction) {
            const li = btn.closest('.menu-item-node');
            if (direction === 'indent') {
                const prev = li.previousElementSibling;
                if (prev) {
                    let submenu = prev.querySelector('.submenu-list');
                    if (!submenu) {
                        submenu = document.createElement('ul');
                        submenu.className = 'submenu-list';
                        prev.appendChild(submenu);
                    }
                    const parentList = prev.parentElement;
                    if (parentList.classList.contains('nested-sortable-list')) {
                        submenu.appendChild(li);
                        saveMenuStructure();
                    } else {
                        alert('Only 2 levels of navigation are supported.');
                    }
                } else {
                    alert('There is no preceding item to nest under.');
                }
            } else if (direction === 'outdent') {
                const parentList = li.parentElement;
                if (parentList.classList.contains('submenu-list')) {
                    const parentLi = parentList.closest('.menu-item-node');
                    if (parentLi) {
                        parentLi.parentNode.insertBefore(li, parentLi.nextSibling);
                        if (parentList.children.length === 0) {
                            parentList.remove();
                        }
                        saveMenuStructure();
                    }
                } else {
                    alert('Item is already at the top level.');
                }
            }
        }

        function showToast(message, type) {
            let toast = document.getElementById('admin-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'admin-toast';
                toast.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: hsl(var(--card));
                    border: 1px solid hsl(var(--border));
                    padding: 1rem 1.5rem;
                    border-radius: var(--radius-md);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    transition: all 0.3s ease;
                    transform: translateY(100px);
                    opacity: 0;
                    color: hsl(var(--foreground));
                `;
                document.body.appendChild(toast);
            }
            toast.style.borderColor = type === 'success' ? 'hsl(var(--success))' : 'hsl(var(--destructive))';
            toast.innerHTML = `<i data-lucide="${type === 'success' ? 'check-circle' : 'alert-triangle'}" style="color: ${type === 'success' ? 'hsl(var(--success))' : 'hsl(var(--destructive))'}; width: 18px; height: 18px; display: inline-block; vertical-align: middle;"></i> <span>${message}</span>`;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons({
                    attrs: {
                        style: 'width: 18px; height: 18px; display: inline-block; vertical-align: middle;'
                    }
                });
            }
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
            setTimeout(() => {
                toast.style.transform = 'translateY(100px)';
                toast.style.opacity = '0';
            }, 3000);
        }
    
        }
    </script>
</body>
</html>
