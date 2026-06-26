<?php
// lspl.xyz/index.php - Landing page for LSPL Training Academy (lspl.xyz)
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

// Fetch all courses (stored in services table)
$courses_stmt = $db->query("SELECT * FROM services ORDER BY display_order ASC");
$courses = $courses_stmt->fetchAll();

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
    <title><?php echo htmlspecialchars($site['site_title'] ?? 'LSPL Academy | Professional IT & Coding Certifications'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site['meta_description'] ?? ''); ?>">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* Interactive mockups inside Hero section */
        .mockup-card {
            width: 100%;
            max-width: 380px;
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .mockup-header {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 0.75rem;
        }
        .mockup-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: hsl(var(--muted-foreground) / 0.4);
        }
        .mockup-dot:nth-child(1) { background: #ff5f56; }
        .mockup-dot:nth-child(2) { background: #ffbd2e; }
        .mockup-dot:nth-child(3) { background: #27c93f; }
        .mockup-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: hsl(var(--muted-foreground));
            margin-left: 0.5rem;
            flex-grow: 1;
        }
        .mockup-chat {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            font-size: 0.825rem;
        }
        .chat-bubble {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            max-width: 85%;
        }
        .chat-bubble.bot {
            background: hsl(var(--muted) / 0.6);
            align-self: flex-start;
            border-bottom-left-radius: 2px;
        }
        .chat-bubble.user {
            background: hsl(var(--primary) / 0.15);
            color: hsl(var(--primary));
            align-self: flex-end;
            border-bottom-right-radius: 2px;
            font-weight: 500;
        }
        .mockup-stats-bar {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--glass-border);
            padding-top: 0.75rem;
        }
        .mockup-stat {
            display: flex;
            flex-direction: column;
        }
        .mockup-stat-val {
            font-size: 1.15rem;
            font-weight: 800;
            font-family: var(--font-heading);
        }
        .mockup-stat-lbl {
            font-size: 0.65rem;
            color: hsl(var(--muted-foreground));
            font-weight: 500;
        }

        /* Fee Estimator styling alignment */
        .estimator-progress {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 3rem;
            z-index: 1;
        }
        .estimator-progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            background: hsl(var(--primary));
            z-index: -1;
            transform: translateY(-50%);
            transition: width var(--transition-normal);
            width: 0%;
        }
        .estimator-progress::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 4px;
            background: hsl(var(--muted));
            z-index: -2;
            transform: translateY(-50%);
        }
        .progress-step {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: hsl(var(--card));
            border: 2px solid hsl(var(--border));
            color: hsl(var(--muted-foreground));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all var(--transition-normal);
        }
        .progress-step.active {
            border-color: hsl(var(--primary));
            color: hsl(var(--primary));
            box-shadow: 0 0 0 4px hsla(var(--primary) / 0.15);
        }
        .progress-step.completed {
            background: hsl(var(--primary));
            border-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
        }

        .estimator-step {
            display: none;
        }
        .estimator-step.active {
            display: block;
            animation: fade-in 0.3s ease;
        }
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.25rem;
            margin-top: 1.5rem;
        }
        .option-card {
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            transition: all var(--transition-normal);
        }
        .option-card:hover {
            border-color: hsla(var(--primary) / 0.4);
            transform: translateY(-2px);
        }
        .option-card.selected {
            background: hsla(var(--primary) / 0.1);
            border-color: hsl(var(--primary));
            box-shadow: 0 0 0 2px hsla(var(--primary) / 0.1);
        }
        .option-card i {
            font-size: 1.5rem;
            color: hsl(var(--primary));
        }
        .option-card strong {
            font-size: 0.95rem;
            font-family: var(--font-heading);
        }
        .option-card p {
            font-size: 0.75rem;
            color: hsl(var(--muted-foreground));
        }
        .estimator-summary {
            background: hsla(var(--primary) / 0.08);
            border: 1px dashed hsla(var(--primary) / 0.2);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .estimator-val {
            font-size: 2.25rem;
            font-family: var(--font-heading);
            font-weight: 800;
            color: hsl(var(--primary));
        }
        .estimator-footer {
            margin-top: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            z-index: 2000;
        }
        .toast {
            min-width: 300px;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Course filters */
        .courses-filter {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        .filter-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 0.85rem;
            color: hsl(var(--muted-foreground));
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .filter-btn:hover, .filter-btn.active {
            background: hsl(var(--primary));
            border-color: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
            box-shadow: 0 4px 12px hsla(var(--primary) / 0.2);
        }
    </style>
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
                <img src="<?php echo htmlspecialchars($base_path); ?>logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Academy Logo">
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
                <a href="<?php echo htmlspecialchars(resolve_url('estimator.php', $base_path)); ?>" class="btn btn-primary btn-sm btn-nav-cta" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Enroll Now</a>
                <button class="menu-toggle" id="menu-toggle-btn" aria-label="Toggle Menu">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
    </header>
    <!-- Dedicated Estimator Main Wrapper -->
    <main style="padding-top: 120px; padding-bottom: 80px; min-height: 85vh; position: relative; z-index: 10;">
        <section class="estimator-section" id="estimator">
        <div class="section-title-wrapper">
            <span class="badge badge-accent">Registration Portal</span>
            <h2>Interactive Course Fee Calculator</h2>
            <p>Select your desired training path, learning delivery mode, and batch scheduling parameters to calculate dynamic tuition costs instantly.</p>
        </div>

        <div class="glass-panel estimator-card">
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
                <!-- Step 1: Select Course -->
                <div class="estimator-step active" data-step="1">
                    <h3 style="text-align: center; margin-bottom: 1.5rem; font-family: var(--font-heading);">Choose Training Program</h3>
                    <div class="options-grid">
                        <?php
                        $est_courses = $db->query("SELECT * FROM services ORDER BY display_order ASC")->fetchAll();
                        foreach ($est_courses as $c):
                        ?>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="<?= htmlspecialchars($c['slug']) ?>" data-title="<?= htmlspecialchars($c['title']) ?>">
                            <i data-lucide="<?= htmlspecialchars($c['icon']) ?>"></i>
                            <strong><?= htmlspecialchars($c['title']) ?></strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);"><?= htmlspecialchars($c['description']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Select Delivery Mode -->
                <div class="estimator-step" data-step="2">
                    <h3 style="text-align: center; margin-bottom: 1.5rem; font-family: var(--font-heading);">Choose Delivery Mode</h3>
                    <div class="options-grid">
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="online">
                            <i data-lucide="laptop"></i>
                            <strong>Online Live Mentoring</strong>
                            <p>Interactive streaming & cloud labs</p>
                        </div>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="offline">
                            <i data-lucide="building"></i>
                            <strong>Physical Classroom Lab</strong>
                            <p>Kanpur headquarters physical labs</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Select Schedule -->
                <div class="estimator-step" data-step="3">
                    <h3 style="text-align: center; margin-bottom: 1.5rem; font-family: var(--font-heading);">Choose Batch Format</h3>
                    <div class="options-grid">
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="regular">
                            <i data-lucide="calendar"></i>
                            <strong>Regular Batch</strong>
                            <p>Weekday classes + Saturday contests</p>
                        </div>
                        <div class="option-card" data-tilt data-tilt-max="15" data-tilt-speed="500" data-value="fast_track">
                            <i data-lucide="zap"></i>
                            <strong>Fast-Track Mentorship</strong>
                            <p>Accelerated syllabus & personal slots</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Submit Registration -->
                <div class="estimator-step" data-step="4">
                    <h3 style="text-align: center; margin-bottom: 1.5rem; font-family: var(--font-heading);">Your Estimated Tuition Fee</h3>
                    <div class="estimator-summary">
                        <div>
                            <strong>Estimated Program Cost:</strong>
                            <p style="font-size: 0.8rem; color: var(--muted-foreground); margin-top: 0.25rem;">(Calculated based on variables, subject to scholarship deductions)</p>
                        </div>
                        <div class="estimator-val" id="est-budget-val">₹0</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                        <div class="form-group">
                            <label for="est-name">Full Name *</label>
                            <input type="text" id="est-name" name="name" class="form-control" placeholder="e.g. Rahul Mishra" required>
                        </div>
                        <div class="form-group">
                            <label for="est-email">Email Address *</label>
                            <input type="email" id="est-email" name="email" class="form-control" placeholder="e.g. rahul@example.com" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="est-phone">Phone Number *</label>
                        <input type="tel" id="est-phone" name="phone" class="form-control" placeholder="e.g. +91 98765 43210" required>
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
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="<?php echo htmlspecialchars($base_path); ?>" class="logo">
                        <img src="<?php echo htmlspecialchars($base_path); ?>logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Academy Logo">
                    </a>
                    <p>LSPL Academy offers professional offline & online certification bootcamps, including weBOShop 2.0 full-stack coding, hackIon 2.0 cybersecurity, and seasonal industrial trainings.</p>
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
                <p>Incorporated in 2014 | Address: <?php echo htmlspecialchars($site['contact_address'] ?? 'Kanpur, India'); ?></p>
            </div>
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
