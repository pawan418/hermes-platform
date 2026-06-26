<?php
// pms_v2/login.php - Login form for the Project Management System
if (!defined('LSPL_SECURE_ROUTE')) {
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    header("Location: " . $base_path);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['pms_logged_in']) && $_SESSION['pms_logged_in'] === true) {
    header('Location: ' . $base_path);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['pms_logged_in'] = true;
                $_SESSION['pms_user_id'] = $user['id'];
                $_SESSION['pms_username'] = $user['username'];
                $_SESSION['pms_role'] = $user['role'];
                
                header('Location: ' . $base_path);
                exit;
            } else {
                $error = 'Invalid credentials. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site['site_title'] ?? 'LSXPL Project Hub'); ?> | Login</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>style.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 3rem 2.5rem;
            border-radius: var(--radius-xl);
            z-index: 10;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: hsl(var(--primary));
        }
        .login-header p {
            color: hsl(var(--muted-foreground));
            font-size: 0.85rem;
        }
        .error-box {
            background: hsla(var(--destructive) / 0.15);
            color: hsl(var(--destructive));
            border: 1px solid hsla(var(--destructive) / 0.25);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="login-container">
    <div class="glass-panel login-card">
        <div class="login-header">
            <div style="display: inline-flex; padding: 10px; border-radius: var(--radius-lg); background: hsla(var(--primary)/0.15); color: hsl(var(--primary)); margin-bottom: 1rem;">
                <i data-lucide="shield-check" style="width: 36px; height: 36px;"></i>
            </div>
            <h2><?php echo htmlspecialchars($site['site_title'] ?? 'LSXPL Project Hub'); ?></h2>
            <p>Access client workspace and milestones portal</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-box">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="form-group">
                <label for="username">Username</label>
                <div style="position: relative;">
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. admin" required autofocus style="padding-left: 2.75rem;">
                    <i data-lucide="user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: hsl(var(--muted-foreground));"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required style="padding-left: 2.75rem;">
                    <i data-lucide="lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: hsl(var(--muted-foreground));"></i>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; justify-content: center; height: 2.75rem;">
                Log In <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
            </button>
        </form>
    </div>
    
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
