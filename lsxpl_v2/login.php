<?php
// lsxpl/login.php - Secure admin login form for LSXPL AI Lab
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
if (!isset($admin_slug)) {
    try {
        $slug_q = $db->query("SELECT value FROM settings WHERE key = 'admin_slug' LIMIT 1");
        $slug_r = $slug_q->fetch();
        $admin_slug = $slug_r ? $slug_r['value'] : 'admin';
    } catch (Exception $e) {
        $admin_slug = 'admin';
    }
}
$admin_url = $base_path . $admin_slug;

// If already logged in, redirect to admin panel
if (isset($_SESSION['lsxpl_admin_logged_in']) && $_SESSION['lsxpl_admin_logged_in'] === true) {
    header('Location: ' . $admin_url);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill out all fields.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['lsxpl_admin_logged_in'] = true;
                $_SESSION['lsxpl_admin_username'] = $user['username'];
                $_SESSION['lsxpl_admin_role'] = $user['role'];
                $_SESSION['lsxpl_admin_user_id'] = $user['id'];
                
                header('Location: ' . $admin_url);
                exit;
            } else {
                $error = 'Invalid username or password.';
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
    <title>LSXPL AI Lab Admin | Secure Login</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .login-body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: hsl(var(--background));
            padding: 2rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 3rem 2.5rem;
            border-radius: var(--radius-xl);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .login-header {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        .login-header h2 {
            font-size: 1.75rem;
        }
        .login-header p {
            font-size: 0.85rem;
            color: hsl(var(--muted-foreground));
        }
        .error-message {
            background: hsla(var(--destructive) / 0.15);
            color: hsl(var(--destructive));
            border: 1px solid hsla(var(--destructive) / 0.25);
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="login-body">
    <!-- Canvas Particle Backdrop -->
    <canvas id="particle-canvas"></canvas>

    <div class="glass-panel login-card">
        <div class="login-header">
            <img src="logo.png" alt="LSXPL Logo" style="max-height: 48px; width: auto; object-fit: contain;">
            <h2>LSXPL AI Lab Admin</h2>
            <p>Enter credentials to access research workspace</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
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

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                Secure Log In <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
            </button>
        </form>

        <a href="<?php echo htmlspecialchars($base_path); ?>" style="text-align: center; font-size: 0.85rem; color: hsl(var(--muted-foreground)); text-decoration: underline;">
            Back to Research Portal
        </a>
    </div>

    <!-- Scripts -->
    <script src="app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
