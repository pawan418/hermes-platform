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
    <link rel="stylesheet" href="style.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Canvas Particle Backdrop -->
    <canvas id="particle-canvas"></canvas>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Logo">
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
                <a href="index.php#estimator" class="btn btn-primary btn-sm btn-nav-cta" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Get Estimate</a>
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
                <a href="#estimator" class="btn btn-primary">Launch Project Estimator <i data-lucide="arrow-right"></i></a>
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
            <button class="filter-btn active" data-filter="all">All Services</button>
            <button class="filter-btn" data-filter="Web & Software">Web & Software</button>
            <button class="filter-btn" data-filter="E-Commerce Solution">E-Commerce</button>
            <button class="filter-btn" data-filter="Marketing & Search">SEO & Marketing</button>
            <button class="filter-btn" data-filter="Infrastructure">Infrastructure</button>
        </div>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="glass-card service-card" data-category="<?php echo htmlspecialchars($service['category']); ?>">
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
                        <a href="service.php?id=<?php echo $service['id']; ?>" class="service-card-btn">View Blueprint <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
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
                    <div class="glass-card blog-card">
                        <?php if (!empty($post['image_url'])): ?>
                            <div class="blog-card-image-wrapper">
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-card-image">
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
                        <a href="blog_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="blog-card-btn">Read Article <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="blog.php" class="btn btn-outline">View All Articles</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Interactive Project Cost Estimator -->
    <section class="estimator-section" id="estimator">
        <div class="section-header">
            <span class="badge badge-accent">Interactive Cost Calculator</span>
            <h2>Estimate Your Software Project</h2>
            <p>Configure your development scope, database needs, and timelines to calculate an instant budget estimation blueprint.</p>
        </div>

        <div class="glass-panel estimator-container">
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
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Select Core Technology Domain</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="web_design">
                            <i data-lucide="layout"></i>
                            <strong>UI/UX Layout Designing</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Custom graphics & layouts</p>
                        </div>
                        <div class="option-card" data-value="wordpress">
                            <i data-lucide="file-text"></i>
                            <strong>WordPress & CMS Setup</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Theme & plugins integration</p>
                        </div>
                        <div class="option-card" data-value="laravel">
                            <i data-lucide="cpu"></i>
                            <strong>Laravel / PHP MVC</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Robust databases & secure API portals</p>
                        </div>
                        <div class="option-card" data-value="web_dev">
                            <i data-lucide="globe"></i>
                            <strong>Web Application Node.js</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Full-stack high performance platforms</p>
                        </div>
                        <div class="option-card" data-value="mobile_apps">
                            <i data-lucide="smartphone"></i>
                            <strong>iOS App Development</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Native Apple Swift applications</p>
                        </div>
                        <div class="option-card" data-value="magento">
                            <i data-lucide="shopping-bag"></i>
                            <strong>Magento Enterprise Shop</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Multi-store e-commerce portals</p>
                        </div>
                        <div class="option-card" data-value="prestashop">
                            <i data-lucide="shopping-cart"></i>
                            <strong>PrestaShop E-Commerce</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Lightweight rapid e-shops</p>
                        </div>
                        <div class="option-card" data-value="moodle">
                            <i data-lucide="graduation-cap"></i>
                            <strong>Moodle LMS Customization</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">SCORM virtual classrooms</p>
                        </div>
                        <div class="option-card" data-value="custom_software">
                            <i data-lucide="code"></i>
                            <strong>Custom Software (.NET/Python)</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Desktop tools & integrations</p>
                        </div>
                        <div class="option-card" data-value="seo">
                            <i data-lucide="search"></i>
                            <strong>Search Engine SEO</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Audits & rankings growth</p>
                        </div>
                        <div class="option-card" data-value="marketing">
                            <i data-lucide="megaphone"></i>
                            <strong>Digital Marketing PPC</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Lead campaigns & newsletters</p>
                        </div>
                        <div class="option-card" data-value="networking">
                            <i data-lucide="server"></i>
                            <strong>Network OFC Cabling</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">LAN/WAN rack planning</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Scale -->
                <div class="estimator-step" data-step="2">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Select Scope & Project Scale</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="startup">
                            <i data-lucide="rocket"></i>
                            <strong>Startup MVP / Small scale</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Focus on rapid core layout designs</p>
                        </div>
                        <div class="option-card" data-value="business">
                            <i data-lucide="briefcase"></i>
                            <strong>Core Business Scope</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Full-scale dashboard integrations</p>
                        </div>
                        <div class="option-card" data-value="enterprise">
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
                        <div class="option-card" data-value="fast">
                            <i data-lucide="zap"></i>
                            <strong>Fast-Track</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">< 1 Month (Requires priority coding)</p>
                        </div>
                        <div class="option-card" data-value="standard">
                            <i data-lucide="calendar"></i>
                            <strong>Standard</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">1 - 3 Months (Standard schedule)</p>
                        </div>
                        <div class="option-card" data-value="extended">
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
                <a href="#estimator" class="btn btn-outline" style="margin-top: 1rem;">Start Estimator <i data-lucide="arrow-up-right"></i></a>
            </div>

            <!-- Visual Timeline -->
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2014 - Establishment</div>
                    <div class="timeline-desc">LSPL was incorporated in Kanpur, setting up local web designing, cabling, and software consulting operations.</div>
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
                <a href="index.php" class="logo">
                    <img src="logo.png?v=<?php echo filemtime(__DIR__ . '/logo.png'); ?>" alt="LSPL Logo">
                </a>
                <p>Longway Softronix Pvt. Ltd. (LSPL) provides premium custom software design, database integrations, e-commerce stores, and high-performance network cabling configurations.</p>
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
        </div>
    </footer>

    <!-- Scripts -->
    <script src="app.js"></script>
    <script>
        // Trigger Lucide icons mapping
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
