<?php
// lspl.xyz/blog.php - Dynamic Blog Index Page for LSPL Academy
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

// Fetch all blogs
$blogs = $db->query("SELECT * FROM blogs WHERE status = 'Published' ORDER BY created_at DESC")->fetchAll();

// Fetch dynamic nav pages
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
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academy Blog & Insights | <?php echo htmlspecialchars($site['site_title'] ?? 'LSPL Academy'); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <canvas id="particle-canvas"></canvas>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Academy Logo">
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
                            $href = 'page.php?slug=' . htmlspecialchars($top['page_slug']);
                        } else if ($top['link_type'] === 'custom') {
                            $href = htmlspecialchars($top['custom_url']);
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
                                                                $sub_href = $item['link_type'] === 'page' ? 'page.php?slug=' . htmlspecialchars($item['page_slug']) : htmlspecialchars($item['custom_url']);
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
                                            $sub_href = $item['link_type'] === 'page' ? 'page.php?slug=' . htmlspecialchars($item['page_slug']) : htmlspecialchars($item['custom_url']);
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
                <a href="index.php#estimator" class="btn btn-primary btn-sm btn-nav-cta" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Enroll Now</a>
                <button class="menu-toggle" id="menu-toggle-btn" aria-label="Toggle Menu">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="blogs-section" style="margin-top: 10rem;">
        <div class="section-title-wrapper">
            <span class="badge badge-accent">LSPL Insights</span>
            <h2>Academy Publications & Coding News</h2>
            <p>Stay up to date with full-stack programming methods, cybersecurity trends, exam certifications, and live campus bootcamps.</p>
        </div>

        <?php if (empty($blogs)): ?>
            <p style="text-align: center; color: var(--muted-foreground); padding: 4rem 0;">No articles published yet.</p>
        <?php else: ?>
            <div class="blogs-grid">
                <?php foreach ($blogs as $post): ?>
                    <div class="glass-card blog-card">
                        <?php if (!empty($post['image_url'])): ?>
                            <div class="blog-card-image-wrapper">
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-card-image">
                            </div>
                        <?php endif; ?>
                        <div class="blog-meta">
                            <span><i data-lucide="calendar" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> <?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
                            <span><i data-lucide="user" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> By <?php echo htmlspecialchars($post['author']); ?></span>
                        </div>
                        <h3 style="font-family: var(--font-heading); font-weight: 700;"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p style="font-size: 0.88rem; color: hsl(var(--foreground) / 0.75);">
                            <?php echo htmlspecialchars($post['summary']); ?>
                        </p>
                        <a href="blog_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="blog-card-btn">Read Article <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="logo">
                        <img src="logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Academy Logo">
                    </a>
                    <p>LSPL Academy offers professional offline & online certification bootcamps, including weBOShop 2.0 full-stack coding, hackIon 2.0 cybersecurity, and seasonal industrial trainings.</p>
                </div>
                <?php foreach ($footer_grouped as $col_name => $items): ?>
                    <div class="footer-col">
                        <h4><?php echo htmlspecialchars($col_name); ?></h4>
                        <ul class="footer-links">
                            <?php foreach ($items as $item): 
                                $href = $item['link_type'] === 'page' ? 'page.php?slug=' . htmlspecialchars($item['page_slug']) : htmlspecialchars($item['custom_url']);
                            ?>
                                <li><a href="<?php echo $href; ?>"><?php echo htmlspecialchars($item['title']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2014 - <?php echo date('Y'); ?> Longway Softronix Pvt. Ltd. All rights reserved.</p>
                <p>Incorporated in 2014 | Address: <?php echo htmlspecialchars($site['contact_address'] ?? 'Kanpur, India'); ?></p>
            </div>
        </div>
    </footer>

    <script src="app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
