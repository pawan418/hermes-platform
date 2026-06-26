<?php
// longwaysoftronix/index.php - Landing page for Longway Softronix Pvt. Ltd. (LSPL)
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

// Fetch all services
$services_stmt = $db->query("SELECT * FROM services ORDER BY display_order ASC");
$services = $services_stmt->fetchAll();

// Fetch all industries
$industries_stmt = $db->query("SELECT * FROM industries ORDER BY display_order ASC");
$industries = $industries_stmt->fetchAll();

// Fetch custom CMS pages for nav
$pages_stmt = $db->query("SELECT * FROM pages WHERE display_in_nav = 1 ORDER BY display_order ASC");
$nav_pages = $pages_stmt->fetchAll();

// Fetch latest blogs
$blogs_stmt = $db->query("SELECT * FROM blogs WHERE status = 'Published' ORDER BY created_at DESC LIMIT 3");
$latest_blogs = $blogs_stmt->fetchAll();

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
    <title><?php echo htmlspecialchars($site['site_title'] ?? 'LSPL | Longway Softronix Pvt. Ltd.'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site['meta_description'] ?? ''); ?>">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php 
    // Dynamic SEO Metadata Injection
    $canonical_href = '';
    if (!empty($site['canonical_url'])) {
        $base = rtrim($site['canonical_url'], '/');
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page === 'index.php') {
            $canonical_href = $base . '/';
        } else {
            $query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
            $canonical_href = $base . '/' . $current_page . $query;
        }
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
    <!-- Canvas Particle Backdrop -->
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
    <!-- Dedicated Estimator Main Wrapper -->
    <main style="padding-top: 120px; padding-bottom: 80px; min-height: 85vh; position: relative; z-index: 10;">
        <section class="estimator-section" id="estimator">
        <div class="section-header">
            <span class="badge badge-accent">Interactive Cost Calculator</span>
            <h2>Estimate Your Software Project</h2>
            <p>Configure your development scope, database needs, and timelines to calculate an instant budget estimation blueprint.</p>
        </div>

        <div class="glass-panel estimator-container">
            <!-- Currency Selector -->
            <div class="currency-selector">
                <button type="button" class="currency-btn" data-currency="INR">₹ INR</button>
                <button type="button" class="currency-btn" data-currency="USD">$ USD</button>
                <button type="button" class="currency-btn" data-currency="GBP">£ GBP</button>
                <button type="button" class="currency-btn" data-currency="EUR">€ EUR</button>
            </div>
            <!-- Progress Bar -->
            <div class="estimator-progress">
                <div class="estimator-progress-bar" id="estimator-progress-bar"></div>
                <div class="progress-step active" data-step="1">1</div>
                <div class="progress-step" data-step="2">2</div>
                <div class="progress-step" data-step="3">3</div>
                <div class="progress-step" data-step="4">4</div>
            </div>

            <form id="estimator-form">
                <!-- Step 1: Select Service -->
                <div class="estimator-step active" data-step="1">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Select Service or Solution</h3>
                    
                    <h4 style="margin: 1.5rem 0 1rem 0; color: var(--primary); font-family: var(--font-heading); font-size: 1.1rem; border-left: 3px solid var(--primary); padding-left: 0.5rem; text-align: left;">Core Tech Services</h4>
                    <div class="options-grid">
                        <?php
                        $est_services = $db->query("SELECT * FROM services ORDER BY display_order ASC")->fetchAll();
                        foreach ($est_services as $s):
                        ?>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="<?= htmlspecialchars($s['slug']) ?>" data-title="<?= htmlspecialchars($s['title']) ?>">
                            <i data-lucide="<?= htmlspecialchars($s['icon']) ?>"></i>
                            <strong><?= htmlspecialchars($s['title']) ?></strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);"><?= htmlspecialchars($s['description']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <h4 style="margin: 2.5rem 0 1rem 0; color: var(--accent); font-family: var(--font-heading); font-size: 1.1rem; border-left: 3px solid var(--accent); padding-left: 0.5rem; text-align: left;">Industry ERP Solutions</h4>
                    <div class="options-grid">
                        <?php
                        $est_industries = $db->query("SELECT * FROM industries ORDER BY display_order ASC")->fetchAll();
                        foreach ($est_industries as $i):
                        ?>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="<?= htmlspecialchars($i['slug']) ?>" data-title="<?= htmlspecialchars($i['title']) ?>">
                            <i data-lucide="<?= htmlspecialchars($i['icon']) ?>"></i>
                            <strong><?= htmlspecialchars($i['title']) ?></strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);"><?= htmlspecialchars($i['description']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div><!-- Step 2: Select Scale -->
                <div class="estimator-step" data-step="2">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Select Scope & Project Scale</h3>
                    <div class="options-grid">
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="startup">
                            <i data-lucide="rocket"></i>
                            <strong>Startup MVP / Small scale</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Focus on rapid core layout designs</p>
                        </div>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="business">
                            <i data-lucide="briefcase"></i>
                            <strong>Core Business Scope</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Full-scale dashboard integrations</p>
                        </div>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="enterprise">
                            <i data-lucide="shield-alert"></i>
                            <strong>Enterprise Grade</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Strict compliance, audits & server caching</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Select Timeline -->
                <div class="estimator-step" data-step="3">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Choose Development Timeline</h3>
                    <div class="options-grid">
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="fast">
                            <i data-lucide="zap"></i>
                            <strong>Fast-Track</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">< 1 Month (Requires priority coding)</p>
                        </div>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="standard">
                            <i data-lucide="calendar"></i>
                            <strong>Standard</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">1 - 3 Months (Standard schedule)</p>
                        </div>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="extended">
                            <i data-lucide="clock"></i>
                            <strong>Extended schedule</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">3 - 6 Months (Multi-phase milestones)</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Contact Details & Estimate -->
                <div class="estimator-step" data-step="4">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Your Customized Cost Estimate</h3>
                    <div class="estimator-summary">
                        <div>
                            <strong>Estimated Project Budget Range:</strong>
                            <p style="font-size: 0.8rem; color: var(--muted-foreground); margin-top: 0.25rem;">(Calculated based on technology complex variables)</p>
                        </div>
                        <div class="estimator-val" id="est-budget-val">₹0</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="est-name">Full Name *</label>
                            <input type="text" id="est-name" name="name" class="form-control" placeholder="e.g. Amit Kumar" required>
                        </div>
                        <div class="form-group">
                            <label for="est-email">Email Address *</label>
                            <input type="email" id="est-email" name="email" class="form-control" placeholder="e.g. amit@gmail.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="est-phone">Phone Number *</label>
                        <input type="tel" id="est-phone" name="phone" class="form-control" placeholder="e.g. +91 99887 76655" required>
                    </div>
                    <div class="form-group">
                        <label for="est-message">Brief Description of Project requirements</label>
                        <textarea id="est-message" name="message" class="form-control" rows="3" placeholder="Tell us about your custom software requirements or catalog size..."></textarea>
                    </div>
                </div>

                <!-- Step Buttons -->
                <div class="estimator-footer">
                    <button type="button" class="btn btn-outline" id="est-prev-btn">Back</button>
                    <button type="button" class="btn btn-primary" id="est-next-btn">Continue</button>
                </div>
            </form>
        </div>
    </section>
    </main>
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="<?php echo htmlspecialchars($base_path); ?>" class="logo">
                    <img src="<?php echo htmlspecialchars($base_path); ?>logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Logo">
                </a>
                <p>Longway Softronix Pvt. Ltd. (LSPL) provides premium custom software design, database integrations, e-commerce stores, and secure cloud server installation & configuration.</p>
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

    <!-- Scripts -->
    <script>window.basePath = '<?php echo htmlspecialchars($base_path); ?>';</script>
    <script src="<?php echo htmlspecialchars($base_path); ?>app.js"></script>
    <script>
        // Trigger Lucide icons mapping
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
