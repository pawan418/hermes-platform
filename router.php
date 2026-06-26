<?php
// router.php - Router for PHP built-in web server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize SCRIPT_NAME for portal subdirectories to resolve correct base path and styles
$uri_parts_temp = explode('/', ltrim($uri, '/'));
$portal_folder_temp = $uri_parts_temp[0] ?? '';
if ($portal_folder_temp === 'longwaysoftronix_v2' || $portal_folder_temp === 'lspl.xyz_v2' || $portal_folder_temp === 'lsxpl_v2' || $portal_folder_temp === 'pms_v2') {
    $sub_part_temp = $uri_parts_temp[1] ?? '';
    if ($sub_part_temp === '' || strpos($sub_part_temp, '.') === false || substr($sub_part_temp, -4) === '.php') {
        $script_temp = $sub_part_temp;
        if ($script_temp === '' || substr($script_temp, -4) !== '.php') {
            $script_temp = 'index.php';
        }
        $_SERVER['SCRIPT_NAME'] = '/' . $portal_folder_temp . '/' . $script_temp;
    }
}

// Normalize legacy redirect key to support both root and subdirectory requests
$redirect_key = $uri;
if (strpos($redirect_key, '/longwaysoftronix_v2/') === 0) {
    $redirect_key = '/' . substr($redirect_key, 21);
}

$legacy_redirects = [
    '/Website-Package.php' => '/longwaysoftronix_v2/#estimator',
    '/E-Commerce-Package.php' => '/longwaysoftronix_v2/#estimator',
    '/SEO-Package.php' => '/longwaysoftronix_v2/#estimator',
    '/aboutus.php' => '/longwaysoftronix_v2/page/about-us',
    '/franchise.php' => '/longwaysoftronix_v2/page/franchise',
    '/contact.php' => '/longwaysoftronix_v2/#contact',
    '/Web-Designing.php' => '/longwaysoftronix_v2/service/web-designing-ui-ux',
    '/Web-Application-Development.php' => '/longwaysoftronix_v2/service/laravel-php-web-apps',
    '/Software-Development.php' => '/longwaysoftronix_v2/service/laravel-php-web-apps',
    '/Digital-Marketing.php' => '/longwaysoftronix_v2/service/seo-digital-marketing',
    '/Search-Engine-Optimization.php' => '/longwaysoftronix_v2/service/seo-digital-marketing',
    '/E-Commerce-Solution.php' => '/longwaysoftronix_v2/service/shopify-ecommerce-setups',
    '/Android-App-Development.php' => '/longwaysoftronix_v2/service/fullstack-mobile-development',
    '/Networking.php' => '/longwaysoftronix_v2/service/cloud-server-setup',
    '/weBOShop.php' => '/lspl.xyz_v2/service/weboshop-fullstack-coding',
    '/hackIon.php' => '/lspl.xyz_v2/service/hackion-cybersecurity',
    '/Summer-Training-by-LSPL.php' => '/lspl.xyz_v2/service/seasonal-coding-internships',
    '/Winter-Training-by-LSPL.php' => '/lspl.xyz_v2/service/seasonal-coding-internships',
    '/Summer-Training-Registration.php' => '/lspl.xyz_v2/#estimator',
];

if (isset($legacy_redirects[$redirect_key])) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $legacy_redirects[$redirect_key]);
    exit;
}

$file = __DIR__ . $uri;

if (file_exists($file) && !is_dir($file)) {
    return false; // Serve the file as-is
}

// Helper to load settings from SQLite dynamically
function get_custom_admin_slug($portal_folder) {
    $db_paths = [
        'longwaysoftronix_v2' => __DIR__ . '/longwaysoftronix_v2/lspl_main_v2.sqlite',
        'lspl.xyz_v2' => __DIR__ . '/lspl.xyz_v2/lspl_academy_v2.sqlite',
        'lsxpl_v2' => __DIR__ . '/lsxpl_v2/lsxpl_ai_v2.sqlite'
    ];
    $db_path = $db_paths[$portal_folder] ?? '';
    if (!$db_path || !file_exists($db_path)) {
        return 'admin';
    }
    try {
        $db = new PDO("sqlite:" . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'admin_slug' LIMIT 1");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val ? trim($val) : 'admin';
    } catch (Exception $e) {
        return 'admin';
    }
}

// Check for custom admin URL routes
$uri_parts = explode('/', ltrim($uri, '/'));
$portal_folder = $uri_parts[0] ?? '';

if ($portal_folder === 'longwaysoftronix_v2' || $portal_folder === 'lspl.xyz_v2' || $portal_folder === 'lsxpl_v2') {
    $custom_admin = get_custom_admin_slug($portal_folder);
    $sub_path = isset($uri_parts[1]) ? $uri_parts[1] : '';
    
    // Strip query parameters for matching
    $sub_path = explode('?', $sub_path)[0];
    
    if ($sub_path !== '' && $sub_path === $custom_admin) {
        include __DIR__ . '/' . $portal_folder . '/index.php';
        exit;
    }
}

// Route PMS requests
if ($portal_folder === 'pms_v2') {
    $file_path_temp = __DIR__ . $uri;
    if (file_exists($file_path_temp) && !is_dir($file_path_temp)) {
        return false; // serve static file as-is
    }
    include __DIR__ . '/pms_v2/index.php';
    exit;
}


// Explicitly serve index.php for directory roots
if ($uri === '/longwaysoftronix_v2' || $uri === '/longwaysoftronix_v2/') {
    include __DIR__ . '/longwaysoftronix_v2/index.php';
    exit;
}
if ($uri === '/longwaysoftronix' || $uri === '/longwaysoftronix/') {
    include __DIR__ . '/longwaysoftronix/index.php';
    exit;
}
if ($uri === '/lspl.xyz_v2' || $uri === '/lspl.xyz_v2/') {
    include __DIR__ . '/lspl.xyz_v2/index.php';
    exit;
}
if ($uri === '/lspl.xyz' || $uri === '/lspl.xyz/') {
    include __DIR__ . '/lspl.xyz/index.php';
    exit;
}
if ($uri === '/lsxpl_v2' || $uri === '/lsxpl_v2/') {
    include __DIR__ . '/lsxpl_v2/index.php';
    exit;
}
if ($uri === '/lsxpl' || $uri === '/lsxpl/') {
    include __DIR__ . '/lsxpl/index.php';
    exit;
}

// Router rules for longwaysoftronix_v2
if (preg_match('#^/longwaysoftronix_v2/blog/?$#', $uri)) {
    include __DIR__ . '/longwaysoftronix_v2/blog.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/estimator/?$#', $uri)) {
    include __DIR__ . '/longwaysoftronix_v2/estimator.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/services/?$#', $uri)) {
    include __DIR__ . '/longwaysoftronix_v2/services.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/solutions/?$#', $uri)) {
    include __DIR__ . '/longwaysoftronix_v2/solutions.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/service/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/longwaysoftronix_v2/service.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/industry/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/longwaysoftronix_v2/industry.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/page/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/longwaysoftronix_v2/page.php';
    exit;
}
if (preg_match('#^/longwaysoftronix_v2/blog/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/longwaysoftronix_v2/blog_post.php';
    exit;
}

// Router rules for lspl.xyz_v2
if (preg_match('#^/lspl.xyz_v2/blog/?$#', $uri)) {
    include __DIR__ . '/lspl.xyz_v2/blog.php';
    exit;
}
if (preg_match('#^/lspl.xyz_v2/estimator/?$#', $uri)) {
    include __DIR__ . '/lspl.xyz_v2/estimator.php';
    exit;
}
if (preg_match('#^/lspl.xyz_v2/services/?$#', $uri)) {
    include __DIR__ . '/lspl.xyz_v2/services.php';
    exit;
}
if (preg_match('#^/lspl.xyz_v2/service/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lspl.xyz_v2/service.php';
    exit;
}
if (preg_match('#^/lspl.xyz_v2/page/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lspl.xyz_v2/page.php';
    exit;
}
if (preg_match('#^/lspl.xyz_v2/blog/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lspl.xyz_v2/blog_post.php';
    exit;
}

// Router rules for lsxpl_v2
if (preg_match('#^/lsxpl_v2/blog/?$#', $uri)) {
    include __DIR__ . '/lsxpl_v2/blog.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/estimator/?$#', $uri)) {
    include __DIR__ . '/lsxpl_v2/estimator.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/ai-capabilities/?$#', $uri)) {
    include __DIR__ . '/lsxpl_v2/services.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/solutions/?$#', $uri)) {
    include __DIR__ . '/lsxpl_v2/solutions.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/service/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lsxpl_v2/service.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/industry/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lsxpl_v2/industry.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/page/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lsxpl_v2/page.php';
    exit;
}
if (preg_match('#^/lsxpl_v2/blog/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/lsxpl_v2/blog_post.php';
    exit;
}

return false; // Fallback
