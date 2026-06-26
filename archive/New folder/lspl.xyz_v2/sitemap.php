<?php
// Dynamic XML Sitemap Generator V3
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

$sitemap_enabled = $site['sitemap_enabled'] ?? '1';
if ($sitemap_enabled !== '1') {
    header("HTTP/1.0 404 Not Found");
    echo "Sitemap is disabled.";
    exit;
}

$base_url = rtrim($site['canonical_url'] ?? 'http://localhost', '/');

header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Main Pages -->
    <url>
        <loc><?php echo htmlspecialchars($base_url); ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo htmlspecialchars($base_url); ?>/blog</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo htmlspecialchars($base_url); ?>/estimator</loc>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo htmlspecialchars($base_url); ?>/services</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- CMS Pages -->
    <?php
    $pages = $db->query("SELECT slug FROM pages ORDER BY display_order ASC")->fetchAll();
    foreach ($pages as $p) {
        $loc = $base_url . '/page/' . urlencode($p['slug']);
        echo "    <url>\n";
        echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo "        <changefreq>monthly</changefreq>\n";
        echo "        <priority>0.7</priority>\n";
        echo "    </url>\n";
    }
    ?>

    <!-- Services Pages (Slug-based) -->
    <?php
    $services = $db->query("SELECT slug FROM services ORDER BY display_order ASC")->fetchAll();
    foreach ($services as $s) {
        $loc = $base_url . '/service/' . urlencode($s['slug']);
        echo "    <url>\n";
        echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo "        <changefreq>monthly</changefreq>\n";
        echo "        <priority>0.8</priority>\n";
        echo "    </url>\n";
    }
    ?>

    <!-- Industry Pages (Slug-based) -->
    <?php
    // Check if industries table exists
    $table_exists = false;
    try {
        $check = $db->query("SELECT 1 FROM industries LIMIT 1");
        if ($check) { $table_exists = true; }
    } catch (Exception $e) {}
    
    if ($table_exists) {
        $industries = $db->query("SELECT slug FROM industries ORDER BY display_order ASC")->fetchAll();
        foreach ($industries as $ind) {
            $loc = $base_url . '/industry/' . urlencode($ind['slug']);
            echo "    <url>\n";
            echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "        <changefreq>monthly</changefreq>\n";
            echo "        <priority>0.8</priority>\n";
            echo "    </url>\n";
        }
    }
    ?>

    <!-- Blog Articles -->
    <?php
    $blogs = $db->query("SELECT slug FROM blogs WHERE status = 'Published' ORDER BY created_at DESC")->fetchAll();
    foreach ($blogs as $b) {
        $loc = $base_url . '/blog/' . urlencode($b['slug']);
        echo "    <url>\n";
        echo "        <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.8</priority>\n";
        echo "    </url>\n";
    }
    ?>
</urlset>
