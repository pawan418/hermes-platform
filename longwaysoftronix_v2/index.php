<?php
// longwaysoftronix/index.php - Landing page for Longway Softronix Pvt. Ltd. (LSPL)
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

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-content">
            <span class="badge badge-primary hero-subtitle-badge">Established 2014 &bull; Kanpur</span>
            <h1>
                <?php echo htmlspecialchars($site['hero_title'] ?? 'We Implement Your Thoughts Into Code'); ?>
            </h1>
            <p>
                <?php echo htmlspecialchars($site['hero_subtitle'] ?? ''); ?>
            </p>
            <div style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 600; min-height: 35px;">
                Specializing in <span id="typing-text" class="text-gradient-primary"></span>
            </div>
            <div class="hero-actions">
                <a href="<?php echo htmlspecialchars(resolve_url('estimator.php', $base_path)); ?>" class="btn btn-primary">Launch Project Estimator <i data-lucide="arrow-right"></i></a>
                <a href="#services" class="btn btn-outline">Explore Capabilities</a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="hero-glow"></div>
            <!-- Interactive Device Mockup -->
            <div class="mockup-card glass-panel">
                <div class="mockup-header">
                    <span class="mockup-dot"></span>
                    <span class="mockup-dot"></span>
                    <span class="mockup-dot"></span>
                    <span class="mockup-title">LSPL Client Portal</span>
                </div>
                <div class="mockup-chat">
                    <div class="chat-bubble bot">
                        Welcome to LSPL! We design custom websites, mobile applications, and implement enterprise systems. How can we help?
                    </div>
                    <div class="chat-bubble user">
                        We need native iOS and Android apps connected to a Laravel dashboard.
                    </div>
                    <div class="chat-bubble bot">
                        We can build that. We will design Swift/Kotlin layouts, structure API schemas, and deploy a secure admin portal.
                    </div>
                </div>
                <div class="mockup-stats-bar">
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-primary">100%</span>
                        <span class="mockup-stat-lbl">Custom Code</span>
                    </div>
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-accent">2014</span>
                        <span class="mockup-stat-lbl">Founded Year</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Panel -->
    <section class="stats-section">
        <div class="stats-grid glass-panel" style="border-radius: var(--radius-xl); padding: 2.5rem;">
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_projects'] ?? '750+'); ?></h3>
                <p>Projects Completed</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_students'] ?? '12,000+'); ?></h3>
                <p>Students Impacted</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_technologies'] ?? '30+'); ?></h3>
                <p>Tech Stacks</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_experience'] ?? '12+ Years'); ?></h3>
                <p>Industry Experience</p>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="section-header">
            <span class="badge badge-accent">Our Capabilities</span>
            <h2>Integrated Software & Infrastructure Solutions</h2>
            <p>From modern Laravel APIs and WordPress CMS setups to Magento e-commerce engines, native mobile apps, and fiber optic network infrastructure layouts.</p>
        </div>

        <div class="services-filter">
            <button class="filter-btn active" data-filter="all">All Capabilities</button>
            <button class="filter-btn" data-filter="Web & Software">Web & Software</button>
            <button class="filter-btn" data-filter="E-Commerce Solution">E-Commerce</button>
            <button class="filter-btn" data-filter="Marketing & Search">SEO & Marketing</button>
        </div>

        <div class="services-grid">
            <?php $featured_services = array_slice($services, 0, 6);
                foreach ($featured_services as $service): ?>
                <div class="glass-card service-card" data-tilt data-tilt-max="12" data-tilt-speed="450" data-tilt-glare data-tilt-max-glare="0.15" data-category="<?php echo htmlspecialchars($service['category']); ?>">
                    <div class="service-icon">
                        <i data-lucide="<?php echo htmlspecialchars($service['icon']); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: var(--muted-foreground); flex-grow: 1;">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                    
                    <div class="service-tech-tags">
                        <?php 
                        $tags = explode(',', $service['tech_stack']);
                        foreach ($tags as $tag) {
                            echo '<span class="tech-tag">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                        ?>
                    </div>
                    
                    <div class="service-card-footer">
                        <span class="badge badge-primary" style="font-size: 0.65rem;"><?php echo htmlspecialchars($service['category']); ?></span>
                        <a href="service/<?php echo urlencode($service['slug']); ?>" class="service-card-btn">View Blueprint <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    
            <div style="text-align: center; margin-top: 3.5rem;">
                <a href="<?php echo htmlspecialchars(resolve_url('services.php', $base_path)); ?>" class="btn btn-outline" style="padding: 0.85rem 2.5rem; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <span>View All Capabilities & Services</span>
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                </a>
            </div>
        </section>
    
    <!-- Solutions Section (New) -->
    <section class="services-section" id="solutions" style="border-top: 1px solid hsla(var(--border) / 0.3); padding-top: 6rem; margin-top: 3rem;">
        <div class="section-header">
            <span class="badge badge-accent">Tailored Industries</span>
            <h2>Custom Solutions & Dynamic ERP Frameworks</h2>
            <p>Ready-to-deploy digital models designed for specific sectors, from retail and hospitality to clinic databases and school ERP platforms.</p>
        </div>

        <div class="services-grid">
            <?php $featured_industries = array_slice($industries, 0, 6);
                foreach ($featured_industries as $ind): ?>
                <div class="glass-card service-card" data-tilt data-tilt-max="12" data-tilt-speed="450" data-tilt-glare data-tilt-max-glare="0.15">
                    <div class="service-icon" style="background: hsla(var(--accent) / 0.1); color: hsl(var(--accent));">
                        <i data-lucide="<?php echo htmlspecialchars($ind['icon']); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($ind['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: var(--muted-foreground); flex-grow: 1;">
                        <?php echo htmlspecialchars($ind['description']); ?>
                    </p>
                    
                    <div class="service-card-footer" style="margin-top: 2rem;">
                        <span class="badge badge-accent" style="font-size: 0.65rem;">Sector Model</span>
                        <a href="industry/<?php echo urlencode($ind['slug']); ?>" class="service-card-btn" style="color: hsl(var(--accent));">Explore System <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    
            <div style="text-align: center; margin-top: 3.5rem;">
                <a href="<?php echo htmlspecialchars(resolve_url('solutions.php', $base_path)); ?>" class="btn btn-outline" style="padding: 0.85rem 2.5rem; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; border-color: hsl(var(--accent)); color: hsl(var(--accent));">
                    <span>View All Industry ERP Solutions</span>
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                </a>
            </div>
        </section>

    <!-- Blogs Grid Section -->
    <section class="blogs-section" id="blog">
        <div class="section-header">
            <span class="badge badge-primary">LSPL Insights</span>
            <h2>Latest Engineering Articles</h2>
            <p>Explore articles written by our engineering team on database management, PHP scaling, and digital setups.</p>
        </div>
        
        <?php if (empty($latest_blogs)): ?>
            <p style="text-align: center; color: var(--muted-foreground);">No blog posts published yet.</p>
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
                <a href="<?php echo htmlspecialchars($base_path); ?>blog" class="btn btn-outline">View All Articles</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Interactive Project Cost Estimator -->
    <!-- Cost Estimator CTA Banner -->
    <section class="estimator-promo-section glass-panel" style="margin: 5rem auto; padding: 4.5rem 2rem; max-width: 1200px; text-align: center; border-radius: var(--radius-lg); position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2; max-width: 800px; margin: 0 auto;">
            <span class="badge badge-accent" style="margin-bottom: 1.25rem;">Interactive Cost Calculator</span>
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem; font-family: var(--font-heading); background: linear-gradient(135deg, hsl(var(--foreground)) 30%, hsl(var(--primary))); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Estimate Your Software Project</h2>
            <p style="font-size: 1.1rem; color: var(--muted-foreground); margin-bottom: 2.5rem; line-height: 1.6;">Configure your development scope, database needs, and timelines to calculate an instant budget estimation blueprint.</p>
            <a href="<?= htmlspecialchars(resolve_url('estimator.php', $base_path)) ?>" class="btn btn-primary btn-lg open-estimator-btn" style="padding: 1rem 2.5rem; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 0.75rem; box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.35);">
                <span>Launch Interactive Estimator</span>
                <i data-lucide="arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Technologies Grid -->
    <section class="tech-section">
        <div class="section-header">
            <span class="badge badge-primary">Tech Blueprint</span>
            <h2>Diverse Software & Infrastructure Stack</h2>
            <p>Our developers write scalable code using the best frameworks to align with client goals and compliance standards.</p>
        </div>

        <div class="tech-grid">
            <div class="glass-card tech-item">
                <i data-lucide="code" style="color: hsl(var(--primary));"></i>
                <span>PHP / Laravel / CI</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="server" style="color: hsl(var(--secondary));"></i>
                <span>Node.js / Express</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="layout-template" style="color: hsl(var(--accent));"></i>
                <span>WordPress / Moodle</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="shopping-bag" style="color: hsl(var(--success));"></i>
                <span>Magento / PrestaShop</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="smartphone" style="color: hsl(var(--warning));"></i>
                <span>Flutter / Swift / Android</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="git-branch" style="color: hsl(var(--destructive));"></i>
                <span>Google Console & SEO</span>
            </div>
        </div>
    </section>

    <!-- About Section & Timeline -->
    <section class="about-section" id="about">
        <div class="section-header">
            <span class="badge badge-accent">Company History</span>
            <h2>Serving Enterprises Since 2014</h2>
            <p>Longway Softronix Pvt. Ltd. (LSPL) was founded to deliver custom web engineering, e-commerce, and professional training certifications.</p>
        </div>

        <div class="about-content">
            <div class="about-text">
                <h3>Our Engineering Code</h3>
                <p>We build robust web and mobile applications that don't crash. We structure neat databases, integrate secure payment gateways, and configure local networks.</p>
                <p>We are a registered Private Limited Company. Our registered physical headquarters is situated in Shastri Nagar, Kanpur, from where we serve students, corporate training drives, and global businesses alike.</p>
                <a href="<?php echo htmlspecialchars(resolve_url('estimator.php', $base_path)); ?>" class="btn btn-outline" style="margin-top: 1rem;">Start Estimator <i data-lucide="arrow-up-right"></i></a>
            </div>

            <!-- Visual Timeline -->
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2014 - Establishment</div>
                    <div class="timeline-desc">LSPL was incorporated in Kanpur, setting up local web designing, server installation, and software consulting operations.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2020 - DevSecOps Audits</div>
                    <div class="timeline-desc">Initiated offline/online bootcamps: <strong>weBOShop</strong> and <strong>hackIon</strong> cybersecurity training, certifying thousands.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2023 - Multi-tenant & LMS</div>
                    <div class="timeline-desc">Scaled into custom Laravel MVC portals, Magento enterprise stores, and Moodle LMS systems for academic institutions.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2026 - AI Lab Subdivision</div>
                    <div class="timeline-desc">Established our research branch <strong>LSXPL</strong> (AI Lab) to deploy LLM pipelines, chatbots, calling voice agents, and AI SEO.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
<!-- Founder & Director Section -->
<section class="director-section" id="director" style="border-top: 1px solid hsla(var(--border) / 0.3); padding-top: 6rem; margin-top: 3rem;">
    <div class="section-header">
        <span class="badge badge-primary">Leadership</span>
        <h2>Founder & Director</h2>
        <p>Driving technological innovation and robust custom software architectures at LSPL.</p>
    </div>
    
    <div class="about-content" style="display: flex; gap: 4rem; align-items: center; justify-content: space-between; margin-top: 3rem; flex-wrap: wrap;">
        <div class="director-image-container" style="flex: 1; min-width: 300px; max-width: 400px; position: relative;">
            <div class="glass-card" style="padding: 1rem; border-radius: var(--radius-lg); position: relative; overflow: hidden; transform-style: preserve-3d;" data-tilt>
                <div style="width: 100%; height: 350px; border-radius: var(--radius-md); overflow: hidden; position: relative; background: hsla(var(--border) / 0.2); display: flex; align-items: center; justify-content: center;">
                    <img src="<?php echo htmlspecialchars($base_path); ?>uploads/pawan.jpg" alt="Pawan K Singh" style="width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform 0.5s ease;">
                </div>
                <div style="margin-top: 1.5rem; text-align: center;">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem; color: hsl(var(--foreground));">Pawan K Singh</h3>
                    <span style="font-size: 0.85rem; color: hsl(var(--muted-foreground)); font-weight: 500;">Founder, CEO & Director</span>
                </div>
            </div>
        </div>
        
        <div class="about-text" style="flex: 1.5; min-width: 300px;">
            <h3 style="color: hsl(var(--foreground));">Pawan K Singh</h3>
            <p style="font-size: 1.1rem; line-height: 1.7; color: hsl(var(--foreground)); margin-top: 1rem; opacity: 0.9;">
                Pawan K Singh founded Longway Softronix Pvt. Ltd. (LSPL) in 2014 with a core vision: <strong>Implementing client thoughts into clean, high-performance code</strong>. As a seasoned full-stack engineer and enterprise system engineer, he leads the technical direction, DevSecOps integrations, and custom database designs across all LSPL portals.
            </p>
            <p style="font-size: 1.1rem; line-height: 1.7; color: hsl(var(--foreground)); margin-top: 1rem; opacity: 0.9;">
                Under his leadership, LSPL has delivered custom web applications, native Swift and Android Kotlin apps, and comprehensive technical search engine optimization (SEO) audits for UK, Indian, and global brands. His focus on strict clean-code MVC standards ensures that LSPL's systems are robust, scalable, and built to last.
            </p>
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <a href="https://pawanksingh.com" target="_blank" rel="noopener" class="btn btn-primary">
                    Visit Portfolio Website <i data-lucide="external-link" style="margin-left: 0.5rem; width: 16px; height: 16px; display: inline-block; vertical-align: middle;"></i>
                </a>
                <a href="#contact" class="btn btn-outline">Schedule Consulting</a>
            </div>
        </div>
    </div>
</section>


<!-- Reviews & Testimonials Section -->
<section class="reviews-section" id="reviews" style="border-top: 1px solid hsla(var(--border) / 0.3); padding-top: 6rem; margin-top: 3rem;">
    <div class="section-header">
        <span class="badge badge-accent">Testimonials</span>
        <h2>Client Feedback & Reviews</h2>
        <p>What our clients say about Pawan K Singh and the engineering team at LSPL.</p>
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

    <section class="contact-section" id="contact">
        <div class="section-header">
            <span class="badge badge-primary">Get in Touch</span>
            <h2>Connect With Our Office</h2>
            <p>Connect with our engineering office or project managers. Send us a message and we will respond back within 12 hours.</p>
        </div>

        <div class="contact-layout">
            <div class="contact-info">
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="mail"></i></div>
                    <div class="contact-text">
                        <h4>Email Us</h4>
                        <p><?php echo htmlspecialchars($site['contact_email'] ?? 'info@longwaysoftronix.com'); ?></p>
                    </div>
                </div>
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="phone"></i></div>
                    <div class="contact-text">
                        <h4>Call Support</h4>
                        <p><?php echo htmlspecialchars($site['contact_phone'] ?? '+91-8840010951'); ?></p>
                    </div>
                </div>
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="map-pin"></i></div>
                    <div class="contact-text">
                        <h4>Registered Address</h4>
                        <p><?php echo htmlspecialchars($site['contact_address'] ?? '25/6 Shastri Nagar, Kanpur, UP 208005, India'); ?></p>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <form id="contact-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="con-name">Your Name *</label>
                            <input type="text" id="con-name" name="name" class="form-control" placeholder="Rahul" required>
                        </div>
                        <div class="form-group">
                            <label for="con-email">Email Address *</label>
                            <input type="email" id="con-email" name="email" class="form-control" placeholder="rahul@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="con-phone">Phone Number *</label>
                        <input type="tel" id="con-phone" name="phone" class="form-control" placeholder="+91 9876543210" required>
                    </div>
                    <div class="form-group">
                        <label for="con-message">Message *</label>
                        <textarea id="con-message" name="message" class="form-control" rows="4" placeholder="Briefly specify your requirement..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Direct Message <i data-lucide="send" style="width: 14px; height: 14px;"></i></button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
