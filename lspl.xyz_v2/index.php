<?php
// lspl.xyz/index.php - Landing page for LSPL Training Academy (lspl.xyz)
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

// Custom dynamic admin path routing
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$route = '';
if (strpos($request_path, $base_path) === 0) {
    $route = substr($request_path, strlen($base_path));
} else {
    $route = ltrim($request_path, '/');
}
$route = rtrim(explode('?', $route)[0], '/');

$admin_slug = $site['admin_slug'] ?? 'admin';

if ($route !== '' && $route === $admin_slug) {
    define('LSPL_SECURE_ROUTE', true);
    include __DIR__ . '/admin.php';
    exit;
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

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-content">
            <span class="badge badge-primary hero-subtitle-badge">LSPL Academy &bull; Certifications</span>
            <h1>
                <?php echo htmlspecialchars($site['hero_title'] ?? 'Learn, Build, and Get Certified'); ?>
            </h1>
            <p>
                <?php echo htmlspecialchars($site['hero_subtitle'] ?? ''); ?>
            </p>
            <div style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 600; min-height: 35px;">
                Accelerating <span id="typing-text" class="text-gradient-primary"></span>
            </div>
            <div class="hero-actions">
                <a href="<?php echo htmlspecialchars(resolve_url('estimator.php', $base_path)); ?>" class="btn btn-primary">Tuition Calculator <i data-lucide="arrow-right"></i></a>
                <a href="#courses" class="btn btn-outline">Browse Programs</a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="hero-glow"></div>
            <!-- Interactive Academy Portal Card -->
            <div class="mockup-card glass-panel">
                <div class="mockup-header">
                    <span class="mockup-dot"></span>
                    <span class="mockup-dot"></span>
                    <span class="mockup-dot"></span>
                    <span class="mockup-title">LSPL Academy LMS</span>
                </div>
                <div class="mockup-chat">
                    <div class="chat-bubble bot">
                        Enrollment Status: Active for <strong>weBOShop 2.0 (Full-Stack Coding)</strong>. Let's start!
                    </div>
                    <div class="chat-bubble user">
                        Submitting Module 4 project files (Dynamic SQL Database layout).
                    </div>
                    <div class="chat-bubble bot">
                        Project evaluated: <strong>Passed (94%)</strong>. Sandboxed cloud server is now active!
                    </div>
                </div>
                <div class="mockup-stats-bar">
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-primary">12,000+</span>
                        <span class="mockup-stat-lbl">Students Certified</span>
                    </div>
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-accent">94%</span>
                        <span class="mockup-stat-lbl">Job Placement</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Panel -->
    <section class="stats-section">
        <div class="stats-grid glass-panel" style="border-radius: var(--radius-xl); padding: 2.5rem;">
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_courses'] ?? '6 Core Programs'); ?></h3>
                <p>Curated Courses</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_students'] ?? '12,000+ Certified'); ?></h3>
                <p>Successful Alumni</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_experience'] ?? 'Founded 2014'); ?></h3>
                <p>Established Year</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_placement'] ?? '94% Placement'); ?></h3>
                <p>Employment Ratio</p>
            </div>
        </div>
    </section>

    <!-- Courses Grid Section -->
    <section class="courses-section" id="courses">
        <div class="section-title-wrapper">
            <span class="badge badge-accent">Our Curriculum</span>
            <h2>Professional Engineering & Hacking Certifications</h2>
            <p>Acquire direct hands-on coding validation through our curated flagship bootcamps, seasonal certifications, and institutional partnering programs.</p>
        </div>

        <div class="courses-filter">
            <button class="filter-btn active" data-filter="all">All Programs</button>
            <button class="filter-btn" data-filter="Flagship Bootcamps">Flagship Bootcamps</button>
            <button class="filter-btn" data-filter="Seasonal Certifications">Seasonal Certifications</button>
            <button class="filter-btn" data-filter="Academic Partnering">Academic Partnering</button>
        </div>

        <div class="courses-grid">
            <?php $featured_courses = array_slice($courses, 0, 6);
            foreach ($featured_courses as $c): ?>
                <div class="glass-card course-card" data-category="<?php echo htmlspecialchars($c['category']); ?>">
                    <div class="course-icon-box">
                        <i data-lucide="<?php echo htmlspecialchars($c['icon']); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                    <p>
                        <?php echo htmlspecialchars($c['description']); ?>
                    </p>
                    
                    <div class="course-tech-badges">
                        <?php 
                        $tags = explode(',', $c['tech_stack']);
                        foreach ($tags as $tag) {
                            echo '<span class="course-tech-tag">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                        ?>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
                        <span class="badge badge-secondary" style="font-size:0.65rem;"><?php echo htmlspecialchars($c['category']); ?></span>
                        <a href="<?php echo htmlspecialchars($base_path); ?>service.php?id=<?php echo $c['id']; ?>" class="course-action-link">Explore Path <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                </div>
            
            <?php endforeach; ?>
        </div>
    
            <div style="text-align: center; margin-top: 3.5rem;">
                <a href="<?php echo htmlspecialchars(resolve_url('services.php', $base_path)); ?>" class="btn btn-outline" style="padding: 0.85rem 2.5rem; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <span>View All Academy Courses & Certifications</span>
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                </a>
            </div>
        </section>

    <!-- Academic Methodology Timeline -->
    <section class="timeline-section" id="about">
        <div class="section-title-wrapper">
            <span class="badge badge-primary">Methodology</span>
            <h2>Our 4-Stage Learning Pipeline</h2>
            <p>LSPL Academy utilizes an industrial project-centric paradigm that guides students from absolute syntax variables to live server configurations.</p>
        </div>

        <div class="timeline">
            <div class="timeline-container left">
                <div class="glass-card timeline-content">
                    <h3 class="text-gradient-primary">Stage 1: Technical Scoping</h3>
                    <p style="font-size: 0.9rem; color: hsl(var(--muted-foreground)); margin-top: 0.5rem;">Students get introduced to clean environment setups, repository configurations, syntax logic, and basic layout parameters.</p>
                </div>
            </div>
            <div class="timeline-container right">
                <div class="glass-card timeline-content">
                    <h3 class="text-gradient-accent">Stage 2: Active Implementation</h3>
                    <p style="font-size: 0.9rem; color: hsl(var(--muted-foreground)); margin-top: 0.5rem;">Writing dynamic database connections, MVC loops, server routing engines, or ethical vulnerability scripts in sandboxed labs.</p>
                </div>
            </div>
            <div class="timeline-container left">
                <div class="glass-card timeline-content">
                    <h3 class="text-gradient-primary">Stage 3: Sandbox Deployments</h3>
                    <p style="font-size: 0.9rem; color: hsl(var(--muted-foreground)); margin-top: 0.5rem;">Deploying projects live. Students learn to handle domain DNS mapping, secure SSL certificates, configure Nginx, and run load testing.</p>
                </div>
            </div>
            <div class="timeline-container right">
                <div class="glass-card timeline-content">
                    <h3 class="text-gradient-accent">Stage 4: Evaluation & Credentials</h3>
                    <p style="font-size: 0.9rem; color: hsl(var(--muted-foreground)); margin-top: 0.5rem;">Defending the final capstone project before our academic board. Post-evaluation, dynamic credential certificates are issued.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tuition Fee Estimator -->
    <!-- Cost Estimator CTA Banner -->
    <section class="estimator-promo-section glass-panel" style="margin: 5rem auto; padding: 4.5rem 2rem; max-width: 1200px; text-align: center; border-radius: var(--radius-lg); position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2; max-width: 800px; margin: 0 auto;">
            <span class="badge badge-accent" style="margin-bottom: 1.25rem;">Tuition Calculator</span>
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem; font-family: var(--font-heading); background: linear-gradient(135deg, hsl(var(--foreground)) 30%, hsl(var(--primary))); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Calculate Your Course Fee</h2>
            <p style="font-size: 1.1rem; color: var(--muted-foreground); margin-bottom: 2.5rem; line-height: 1.6;">Select your desired training path, learning delivery mode, and batch options to receive a dynamic localized fee breakdown.</p>
            <a href="<?= htmlspecialchars(resolve_url('estimator.php', $base_path)) ?>" class="btn btn-primary btn-lg open-estimator-btn" style="padding: 1rem 2.5rem; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 0.75rem; box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.35);">
                <span>Launch Fee Calculator</span>
                <i data-lucide="arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Latest Insights Blog Section -->
    <section class="blogs-section" id="blog">
        <div class="section-title-wrapper">
            <span class="badge badge-primary">Publications</span>
            <h2>Latest Academy Publications</h2>
            <p>Discover tutorials and career guides written by our academic instructors to help launch your engineering pathways.</p>
        </div>
        
        <?php if (empty($latest_blogs)): ?>
            <p style="text-align: center; color: var(--muted-foreground);">No blog articles published yet.</p>
        <?php else: ?>
            <div class="blogs-grid">
                <?php foreach ($latest_blogs as $post): ?>
                    <div class="glass-card blog-card" data-tilt data-tilt-max="10" data-tilt-speed="400" data-tilt-glare data-tilt-max-glare="0.12">
                        <?php if (!empty($post['image_url'])): ?>
                            <div class="blog-card-image-wrapper">
                                <img src="<?php echo htmlspecialchars($base_path . $post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-card-image">
                            </div>
                        <?php endif; ?>
                        <div class="blog-meta">
                            <span><i data-lucide="calendar" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                            <span><i data-lucide="user" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> By <?php echo htmlspecialchars($post['author']); ?></span>
                        </div>
                        <h3 style="font-family: var(--font-heading); font-weight: 700;"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p style="font-size: 0.88rem; color: hsl(var(--foreground) / 0.75);">
                            <?php echo htmlspecialchars($post['summary']); ?>
                        </p>
                        <a href="<?php echo htmlspecialchars(resolve_url('blog/' . $post['slug'], $base_path)); ?>" class="blog-card-btn">Read Article <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="<?php echo htmlspecialchars($base_path); ?>blog" class="btn btn-outline">Browse All Publications</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Contact & Consultation -->
<!-- Reviews & Testimonials Section -->
<section class="reviews-section" id="reviews" style="border-top: 1px solid hsla(var(--border) / 0.3); padding-top: 6rem; margin-top: 3rem;">
    <div class="section-header">
        <span class="badge badge-accent">Testimonials</span>
        <h2>Student Stories & Academy Reviews</h2>
        <p>What our graduates say about their training experience and placement programs at LSPL Academy.</p>
    </div>
    
    <?php
    $reviews = [];
    try {
        $reviews = $db->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
    } catch (Exception $e) {
        // Fallback
    }
    ?>

    <div class="reviews-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin-top: 3rem;">
        <?php foreach ($reviews as $rev): ?>
            <div class="glass-card review-card" style="padding: 2rem; border-radius: var(--radius-lg); display: flex; flex-direction: column; justify-content: space-between; position: relative;" data-tilt>
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <div>
                            <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem; color: hsl(var(--foreground));"><?php echo htmlspecialchars($rev['author_name']); ?></h4>
                            <?php if (!empty($rev['project_title'])): ?>
                                <span style="font-size: 0.8rem; color: hsl(var(--muted-foreground)); font-weight: 500;"><?php echo htmlspecialchars($rev['project_title']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Platform Badge -->
                        <?php if ($rev['platform'] === 'google'): ?>
                            <div class="platform-badge platform-badge-google" style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; font-weight: 700; color: #4285F4; background: hsla(217, 89%, 61%, 0.1); padding: 0.35rem 0.75rem; border-radius: 20px;">
                                <i data-lucide="chrome" style="width: 14px; height: 14px;"></i> Google
                            </div>
                        <?php elseif ($rev['platform'] === 'peopleperhour'): ?>
                            <div class="platform-badge platform-badge-pph" style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; font-weight: 700; color: #ff5a00; background: hsla(26, 90%, 55%, 0.1); padding: 0.35rem 0.75rem; border-radius: 20px;">
                                <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i> PPH
                            </div>
                        <?php else: ?>
                            <div class="platform-badge platform-badge-trustpilot" style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; font-weight: 700; color: #00b67a; background: hsla(142, 70%, 45%, 0.1); padding: 0.35rem 0.75rem; border-radius: 20px;">
                                <i data-lucide="star" style="width: 14px; height: 14px;"></i> Trustpilot
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Rating Stars -->
                    <div style="display: flex; gap: 0.25rem; margin-bottom: 1.25rem; color: hsl(45, 100%, 50%);">
                        <?php for ($i = 0; $i < $rev['rating']; $i++): ?>
                            <i data-lucide="star" style="width: 16px; height: 16px; fill: hsl(45, 100%, 50%);"></i>
                        <?php endfor; ?>
                        <?php for ($i = $rev['rating']; $i < 5; $i++): ?>
                            <i data-lucide="star" style="width: 16px; height: 16px;"></i>
                        <?php endfor; ?>
                    </div>
                    
                    <p style="font-size: 0.95rem; line-height: 1.6; color: hsl(var(--foreground)); font-style: italic; opacity: 0.9;">
                        "<?php echo htmlspecialchars($rev['review_text']); ?>"
                    </p>
                </div>
                
                <div style="margin-top: 2rem; border-top: 1px solid hsla(var(--border) / 0.2); padding-top: 1rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: hsl(var(--muted-foreground));">
                    <span>Verified Review</span>
                    <span><?php echo date('M Y', strtotime($rev['created_at'])); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

    <section class="estimator-section" id="contact" style="padding-top: 0;">
        <div class="section-title-wrapper">
            <span class="badge badge-accent">Support Desk</span>
            <h2>Speak with Our Academic Counselors</h2>
            <p>Have questions about batch timings, group college discounts, or course syllabus configurations? Message our team directly.</p>
        </div>

        <div class="glass-panel estimator-card">
            <form id="contact-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label for="c-name">Full Name *</label>
                        <input type="text" id="c-name" name="name" class="form-control" placeholder="e.g. Rahul Mishra" required>
                    </div>
                    <div class="form-group">
                        <label for="c-email">Email Address *</label>
                        <input type="email" id="c-email" name="email" class="form-control" placeholder="e.g. rahul@example.com" required>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label for="c-phone">Phone Number *</label>
                    <input type="tel" id="c-phone" name="phone" class="form-control" placeholder="e.g. +91 98765 43210" required>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label for="c-message">How can we help you? *</label>
                    <textarea id="c-message" name="message" class="form-control" rows="4" placeholder="Mention your qualification and the course you are interested in..." required></textarea>
                </div>

                <div style="margin-top: 2rem; text-align: center;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2.5rem;">
                        Send Message <i data-lucide="send" style="width:16px; height:16px;"></i>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
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
