<?php
// debug_route.php - Route diagnosis script
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Route Diagnoser</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #121214; color: #e1e1e6; }
        h1 { color: #00b37e; border-bottom: 1px solid #29292e; padding-bottom: 0.5rem; }
        h2 { color: #ffb86c; margin-top: 2rem; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #29292e; padding: 0.75rem; text-align: left; }
        th { background: #202024; color: #8257e5; }
        pre { background: #202024; padding: 1rem; border-radius: 4px; border: 1px solid #29292e; }
        .success { color: #00b37e; font-weight: bold; }
        .error { color: #f75a68; font-weight: bold; }
    </style>
</head>
<body>
    <h1>LSPL Route Diagnoser</h1>

    <h2>Environment Diagnostics</h2>
    <table>
        <tr><th>Variable</th><th>Value</th></tr>
        <tr><td>PHP Version</td><td><?php echo phpversion(); ?></td></tr>
        <tr><td>SAPI Name</td><td><?php echo php_sapi_name(); ?></td></tr>
        <tr><td>HTTP_HOST</td><td><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'NOT SET'); ?></td></tr>
        <tr><td>REQUEST_URI</td><td><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'NOT SET'); ?></td></tr>
        <tr><td>PHP_SELF</td><td><?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? 'NOT SET'); ?></td></tr>
        <tr><td>SCRIPT_NAME</td><td><?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'NOT SET'); ?></td></tr>
        <tr><td>QUERY_STRING</td><td><?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? 'NOT SET'); ?></td></tr>
    </table>

    <h2>Dynamic Base Path Calculation</h2>
    <pre>
$base_path = '<?php echo htmlspecialchars($base_path); ?>';
    </pre>

    <h2>URL Slug Parsing Test</h2>
    <?php
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $method = 'QUERY_STRING';
    if (empty($slug)) {
        $method = 'FALLBACK_URL_PATH';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (strpos($uri, '/service/') !== false) {
            $parts = explode('/', rtrim($uri, '/'));
            $slug = end($parts);
        }
    }
    ?>
    <table>
        <tr><th>Parameter</th><th>Value</th></tr>
        <tr><td>Resolved Slug</td><td>"<?php echo htmlspecialchars($slug); ?>"</td></tr>
        <tr><td>Resolved ID</td><td><?php echo $id; ?></td></tr>
        <tr><td>Resolution Method</td><td><?php echo $method; ?></td></tr>
    </table>

    <h2>Database Connection Check</h2>
    <?php
    if (isset($db)) {
        echo '<p class="success">[OK] Database is connected successfully.</p>';
        try {
            $check = $db->query("SELECT COUNT(*) FROM services")->fetchColumn();
            echo '<p class="success">[OK] Services table contains ' . $check . ' records.</p>';
            if (!empty($slug)) {
                $stmt = $db->prepare("SELECT id, title FROM services WHERE slug = ?");
                $stmt->execute([$slug]);
                $svc = $stmt->fetch();
                if ($svc) {
                    echo '<p class="success">[OK] Successfully matched slug "' . htmlspecialchars($slug) . '" to Service ID: ' . $svc['id'] . ' ("' . htmlspecialchars($svc['title']) . '")</p>';
                } else {
                    echo '<p class="error">[FAIL] No service record found with slug "' . htmlspecialchars($slug) . '".</p>';
                }
            }
        } catch (Exception $e) {
            echo '<p class="error">[FAIL] SQL Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    } else {
        echo '<p class="error">[FAIL] Database variable $db is not set.</p>';
    }
    ?>

    <h2>File Check</h2>
    <table>
        <tr><th>File</th><th>Exists?</th></tr>
        <tr><td>style.css</td><td><?php echo file_exists(__DIR__ . '/style.css') ? '<span class="success">YES</span>' : '<span class="error">NO</span>'; ?></td></tr>
        <tr><td>logo.png</td><td><?php echo file_exists(__DIR__ . '/logo.png') ? '<span class="success">YES</span>' : '<span class="error">NO</span>'; ?></td></tr>
        <tr><td>app.js</td><td><?php echo file_exists(__DIR__ . '/app.js') ? '<span class="success">YES</span>' : '<span class="error">NO</span>'; ?></td></tr>
    </table>
</body>
</html>
