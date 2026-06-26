<?php
// longwaysoftronix/service.php - Dynamic Service Subpage
require_once __DIR__ . '/db.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (strpos($uri, '/service/') !== false) {
        $parts = explode('/', rtrim($uri, '/'));
        $slug = end($parts);
    }
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!empty($slug)) {
    $stmt = $db->prepare("SELECT * FROM services WHERE slug = ?");
    $stmt->execute([$slug]);
    $service = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
} else {
    header('Location: index.php');
    exit;
}

if (!$service) {
    die("Service blueprint not found.");
}

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

// Fetch custom CMS pages for nav
$nav_pages = $db->query("SELECT title, slug FROM pages WHERE display_in_nav = 1 ORDER BY display_order ASC")->fetchAll();

// Fetch dynamic header menu
$header_items = $db->query("SELECT * FROM header_menu_items ORDER BY display_order ASC, id ASC")->fetchAll();
$top_menu = [];
$sub_menu = [];
foreach ($header_items as $item) {
    if ($item['parent_id'] === null || $item['parent_id'] == '') {
        $top_menu[] = $item;
    } else {
        $sub_menu[$item['parent_id']][] = $item;
    }
}

$footer_items = $db->query("SELECT * FROM footer_items ORDER BY column_name ASC, display_order ASC")->fetchAll();
$footer_grouped = [];
foreach ($footer_items as $item) {
    $footer_grouped[$item['column_name']][] = $item;
}

// Map categories to execution workflows
$workflows = [
    'Web & Software' => [
        'Requirement analysis & mapping key technical parameters.',
        'Interface wireframing & prototyping visual HSL styling mockups.',
        'Writing scalable database schemas & custom PHP/Node.js backend functions.',
        'Deploying responsive grid components & layout test audits.',
        'Launching system checks, configuring SSL, and delivering source files.'
    ],
    'E-Commerce Solution' => [
        'Catalog scoping & product database entity setup.',
        'Configuring shop layouts (Magento/PrestaShop) & plugins.',
        'Integrating secure transactional payment gateways & shipping modules.',
        'Performing checkout load tests & transaction testing.',
        'Going live & initiating backend dashboard catalog instructions.'
    ],
    'Marketing & Search' => [
        'Performing detailed diagnostic crawlers to find search indexing bottlenecks.',
        'Mapping semantic user intent & generating metadata blueprints.',
        'Conducting local search map optimization & backlink optimization.',
        'Launching performance ad campaigns & automated email flows.',
        'Generating detailed GA4 conversion logs & keywords track metrics.'
    ],
    'Infrastructure' => [
        'Conducting hardware physical audits & path routing design.',
        'Installing server rack configurations, numbering, & patching panels.',
        'Splicing fiber optics (OFC) & deploying secure access point setups.',
        'Testing connectivity channels, speeds, & routing trunks.',
        'Documenting hardware schematics & network security maps.'
    ]
];

$category_wf = isset($workflows[$service['category']]) ? $workflows[$service['category']] : $workflows['Web & Software'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?> | LSPL Service</title>
    <meta name="description" content="<?php echo htmlspecialchars($service['description'] ?? ''); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .service-detail-card {
            margin-top: 8rem;
            margin-bottom: 6rem;
        }
        .service-meta-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 1rem 0 2rem;
        }
        .workflow-step-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .workflow-step-list li {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            border-radius: var(--radius-md);
            background: hsl(var(--muted) / 0.4);
            border-left: 4px solid hsl(var(--primary));
        }
        .workflow-step-list li i {
            color: hsl(var(--primary));
            margin-top: 0.15rem;
        }
    </style>
    <?php 
    // Dynamic SEO Metadata Injection
    $canonical_href = '';
    if (!empty($site['canonical_url']) && !empty($service['slug'])) {
        $base = rtrim($site['canonical_url'], '/');
        $canonical_href = $base . '/service/' . $service['slug'];
    }
    ?>
    <?php if (!empty($canonical_href)): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_href); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_href); ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?php echo basename($_SERVER['PHP_SELF']) === 'blog_post.php' ? 'article' : 'website'; ?>">
    <meta property="og:title" content="<?php 
        if (isset($service['title'])) {
            echo htmlspecialchars($service['title'] . ' | ' . ($site['site_title'] ?? ''));
        } elseif (isset($post['title'])) {
            echo htmlspecialchars($post['title'] . ' | ' . ($site['site_title'] ?? ''));
        } elseif (isset($page['title'])) {
            echo htmlspecialchars($page['title'] . ' | ' . ($site['site_title'] ?? ''));
        } else {
            echo htmlspecialchars($site['site_title'] ?? '');
        }
    ?>">
    <meta property="og:description" content="<?php 
        if (isset($service['description'])) {
            echo htmlspecialchars($service['description']);
        } elseif (isset($post['summary'])) {
            echo htmlspecialchars($post['summary']);
        } elseif (isset($page['content'])) {
            echo htmlspecialchars(substr(strip_tags($page['content']), 0, 160));
        } else {
            echo htmlspecialchars($site['meta_description'] ?? '');
        }
    ?>">
    <meta property="og:image" content="<?php 
        $og_img = '';
        if (isset($post['image_url']) && !empty($post['image_url'])) {
            $og_img = $post['image_url'];
        } else {
            $og_img = $site['og_image_url'] ?? 'logo.png';
        }
        if (!empty($og_img) && strpos($og_img, 'http') !== 0 && !empty($site['canonical_url'])) {
            $og_img = rtrim($site['canonical_url'], '/') . '/' . ltrim($og_img, '/');
        }
        echo htmlspecialchars($og_img);
    ?>">
    <?php if (!empty($site['schema_markup'])): ?>
    <script type="application/ld+json">
        <?php echo $site['schema_markup']; ?>
    </script>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo htmlspecialchars($site['site_title'] ?? 'LSPL'); ?>",
        "url": "<?php echo htmlspecialchars($site['canonical_url'] ?? ''); ?>",
        "logo": "<?php echo htmlspecialchars($site['canonical_url'] ?? '') . '/logo.png'; ?>",
        "description": "<?php echo htmlspecialchars($site['meta_description'] ?? ''); ?>"
    }
    </script>
    <?php elseif (isset($service)): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "<?php echo htmlspecialchars($service['title']); ?>",
        "description": "<?php echo htmlspecialchars($service['description']); ?>",
        "provider": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($site['site_title'] ?? 'LSPL'); ?>",
            "url": "<?php echo htmlspecialchars($site['canonical_url'] ?? ''); ?>"
        }
    }
    </script>
    <?php elseif (isset($post)): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php echo htmlspecialchars($post['title']); ?>",
        "description": "<?php echo htmlspecialchars($post['summary']); ?>",
        "datePublished": "<?php echo $post['created_at'] ?? ''; ?>",
        "author": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($site['site_title'] ?? 'LSPL'); ?>",
            "url": "<?php echo htmlspecialchars($site['canonical_url'] ?? ''); ?>"
        }
    }
    </script>
    <?php endif; ?>

</head>
<body>
    <canvas id="particle-canvas"></canvas>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="<?php echo htmlspecialchars($base_path); ?>" class="logo">
                <img src="<?php echo htmlspecialchars($base_path); ?>logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Logo">
            </a>
            
            <nav>
                <ul>
                    <?php 
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $current_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
                    
                    foreach ($top_menu as $top): 
                        $has_subs = isset($sub_menu[$top['id']]) && count($sub_menu[$top['id']]) > 0;
                        $li_class = '';
                        $trigger_class = '';
                        if ($has_subs) {
                            if ($top['menu_type'] === 'megamenu') {
                                $li_class = 'class="has-megamenu"';
                                $trigger_class = 'class="megamenu-trigger"';
                            } else if ($top['menu_type'] === 'dropdown') {
                                $li_class = 'class="has-dropdown"';
                                $trigger_class = 'class="dropdown-trigger"';
                            }
                        }
                        
                        $is_active = false;
                        if ($top['link_type'] === 'page' && $current_page === 'page.php' && $current_slug === $top['page_slug']) {
                            $is_active = true;
                        } else if ($top['link_type'] === 'custom') {
                            if ($current_page === $top['custom_url'] || ($current_page === 'index.php' && $top['custom_url'] === 'index.php')) {
                                $is_active = true;
                            }
                        }
                        
                        $active_class = $is_active ? 'class="active"' : '';
                        $href = '#';
                        if ($top['link_type'] === 'page') {
                            $href = htmlspecialchars(resolve_url('page/' . $top['page_slug'], $base_path));
                        } else if ($top['link_type'] === 'custom') {
                            $href = htmlspecialchars(resolve_url($top['custom_url'], $base_path));
                        }
                    ?>
                        <li <?php echo $li_class; ?>>
                            <a href="<?php echo $href; ?>" <?php echo $trigger_class; ?> <?php echo $active_class; ?>>
                                <?php echo htmlspecialchars($top['title']); ?>
                                <?php if ($has_subs): ?>
                                    <i data-lucide="chevron-down" class="dropdown-chevron"></i>
                                <?php endif; ?>
                            </a>
                            <?php if ($has_subs): ?>
                                <?php if ($top['menu_type'] === 'megamenu'): 
                                    $grouped_cols = [];
                                    foreach ($sub_menu[$top['id']] as $sub) {
                                        $col_name = $sub['column_name'] ?? 'General';
                                        $grouped_cols[$col_name][] = $sub;
                                    }
                                ?>
                                    <div class="megamenu-dropdown">
                                        <div class="megamenu-container">
                                            <div class="megamenu-grid">
                                                <?php foreach ($grouped_cols as $col_name => $items): ?>
                                                    <div class="megamenu-col">
                                                        <h4 class="megamenu-col-title"><?php echo htmlspecialchars($col_name); ?></h4>
                                                        <ul class="megamenu-links">
                                                            <?php foreach ($items as $item): 
                                                                $sub_href = $item['link_type'] === 'page' ? htmlspecialchars(resolve_url('page/' . $item['page_slug'], $base_path)) : htmlspecialchars(resolve_url($item['custom_url'], $base_path));
                                                            ?>
                                                                <li><a href="<?php echo $sub_href; ?>"><?php echo htmlspecialchars($item['title']); ?></a></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($top['menu_type'] === 'dropdown'): ?>
                                    <ul class="standard-dropdown">
                                        <?php foreach ($sub_menu[$top['id']] as $item): 
                                            $sub_href = $item['link_type'] === 'page' ? htmlspecialchars(resolve_url('page/' . $item['page_slug'], $base_path)) : htmlspecialchars(resolve_url($item['custom_url'], $base_path));
                                        ?>
                                            <li><a href="<?php echo $sub_href; ?>"><?php echo htmlspecialchars($item['title']); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <div class="nav-actions">
                <button class="theme-toggle" id="theme-toggle-btn" aria-label="Toggle Theme">
                    <i data-lucide="sun"></i>
                </button>
                <a href="<?php echo htmlspecialchars(resolve_url('estimator.php', $base_path)); ?>" class="btn btn-primary btn-sm btn-nav-cta" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Get Estimate</a>
                <button class="menu-toggle" id="menu-toggle-btn" aria-label="Toggle Menu">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="page-container" style="margin-top: 9rem;">
        <div class="glass-card">
            <div class="service-icon" style="width: 56px; height: 56px; font-size: 1.5rem;">
                <i data-lucide="<?php echo htmlspecialchars($service['icon']); ?>"></i>
            </div>
            
            <h1 style="font-size: 2.25rem; margin-top: 1.5rem; font-family: var(--font-heading);"><?php echo htmlspecialchars($service['title']); ?></h1>
            
            <div class="service-meta-row">
                <span class="badge badge-primary"><?php echo htmlspecialchars($service['category']); ?></span>
                
            </div>

            <p style="font-size: 1.15rem; line-height: 1.7; color: hsl(var(--foreground) / 0.95); margin-bottom: 2.5rem;">
                <?php echo htmlspecialchars($service['description']); ?>
            </p>

            <h3 style="font-size: 1.25rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.75rem; margin-bottom: 1rem;">Core Technology Stack</h3>
            <div class="service-tech-tags" style="border: none; padding: 0; margin-bottom: 3rem;">
                <?php 
                $tags = explode(',', $service['tech_stack']);
                foreach ($tags as $tag) {
                    echo '<span class="tech-tag" style="font-size: 0.85rem; padding: 0.3rem 0.75rem;">' . htmlspecialchars(trim($tag)) . '</span>';
                }
                ?>
            </div>

            <div class="service-html-content" style="margin-top: 3rem; line-height: 1.8;">
                <?php echo $service['content']; ?>
            </div>

            <div style="margin-top: 4rem; text-align: center; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars(resolve_url('estimator.php', $base_path)); ?>" class="btn btn-primary" style="padding: 0.85rem 2rem;">Launch Budget Estimator</a>
                <a href="<?php echo htmlspecialchars($base_path); ?>#contact" class="btn btn-outline" style="padding: 0.85rem 2rem;">Consult Our Team</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="<?php echo htmlspecialchars($base_path); ?>" class="logo">
                    <img src="<?php echo htmlspecialchars($base_path); ?>logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Logo">
                </a>
                <p>Longway Softronix Pvt. Ltd. (LSPL) provides premium custom software design, database integration, e-commerce stores, and secure cloud server installation & configuration.</p>
            </div>
            <?php foreach ($footer_grouped as $col_name => $items): ?>
                <div class="footer-col">
                    <h4><?php echo htmlspecialchars($col_name); ?></h4>
                    <ul class="footer-links">
                        <?php foreach ($items as $item): 
                            $href = $item['link_type'] === 'page' ? htmlspecialchars(resolve_url('page/' . $item['page_slug'], $base_path)) : htmlspecialchars(resolve_url($item['custom_url'], $base_path));
                        ?>
                            <li><a href="<?php echo $href; ?>"><?php echo htmlspecialchars($item['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2014 - <?php echo date('Y'); ?> Longway Softronix Pvt. Ltd. All rights reserved.</p>
        </div>
    </footer>

    <script>window.basePath = '<?php echo htmlspecialchars($base_path); ?>';</script>
    <script src="<?php echo htmlspecialchars($base_path); ?>app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
