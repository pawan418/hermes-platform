<?php
// lsxpl/index.php - Landing page for LSXPL AI Lab (Subdivision of LSPL)
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
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site['site_title'] ?? 'LSXPL | AI Research Lab'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site['meta_description'] ?? ''); ?>">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* Estimator layout classes */
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
    </style>
</head>
<body>
    <!-- Canvas Particle Backdrop -->
    <canvas id="particle-canvas"></canvas>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="logo-dark.png?v=<?php echo filemtime(__DIR__ . '/logo-dark.png'); ?>" alt="LSXPL Logo">
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
                <a href="#estimator" class="btn btn-primary btn-sm btn-nav-cta" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Get SLA Estimate</a>
                <button class="menu-toggle" id="menu-toggle-btn" aria-label="Toggle Menu">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-content">
            <span class="badge badge-primary hero-subtitle-badge">AI Research Lab subdivision of LSPL</span>
            <h1>
                <?php echo htmlspecialchars($site['hero_title'] ?? 'Pioneering Intelligent SaaS & AI Automation'); ?>
            </h1>
            <p>
                <?php echo htmlspecialchars($site['hero_subtitle'] ?? ''); ?>
            </p>
            <div style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 600; min-height: 35px;">
                Specializing in <span id="typing-text" class="text-gradient-primary"></span>
            </div>
            <div class="hero-actions">
                <a href="#estimator" class="btn btn-primary">Estimate AI SaaS Model <i data-lucide="arrow-right"></i></a>
                <a href="#services" class="btn btn-outline">Explore AI Solutions</a>
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
                    <span class="mockup-title">LSXPL Neural Node</span>
                </div>
                <div class="mockup-chat">
                    <div class="chat-bubble bot">
                        Node online. Fine-tuned models verified. Neural network pipelines ready for webhook ingestion. How can we serve?
                    </div>
                    <div class="chat-bubble user">
                        Deploy Twilio calling trunks with low latency.
                    </div>
                    <div class="chat-bubble bot">
                        Twilio trunk verified. Quantized streaming audio buffering enabled. SLA latency targeted under 180ms.
                    </div>
                </div>
                <div class="mockup-stats-bar">
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-primary">50+</span>
                        <span class="mockup-stat-lbl">AI Models deployed</span>
                    </div>
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-accent">< 180ms</span>
                        <span class="mockup-stat-lbl">Voice Latency</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Panel -->
    <section class="stats-section">
        <div class="stats-grid glass-panel" style="border-radius: var(--radius-xl); padding: 2.5rem;">
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_projects'] ?? '50+'); ?></h3>
                <p>Models Deployed</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_students'] ?? '1,500+'); ?></h3>
                <p>Enterprise Users</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_technologies'] ?? '15+'); ?></h3>
                <p>AI Stack Integrations</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_experience'] ?? 'Established 2026'); ?></h3>
                <p>Lab Branch History</p>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="section-header">
            <span class="badge badge-accent">Our Capabilities</span>
            <h2>Integrated Neural & Automation Services</h2>
            <p>From smart RAG conversational chatbots and automated calling agents to custom multi-tenant SaaS structures and technical search SEO engines.</p>
        </div>

        <div class="services-filter">
            <button class="filter-btn active" data-filter="all">All Solutions</button>
            <button class="filter-btn" data-filter="AI & Automation">AI & Automation</button>
            <button class="filter-btn" data-filter="SaaS Development">SaaS Development</button>
            <button class="filter-btn" data-filter="Marketing & Search">AI SEO</button>
            <button class="filter-btn" data-filter="Infrastructure">AI Security</button>
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

    <!-- Latest Insights Section -->
    <section class="blogs-section" id="blog">
        <div class="section-header">
            <span class="badge badge-primary">AI Lab Insights</span>
            <h2>Latest Research Publications</h2>
            <p>Read our articles on vector indexing speedups, conversational speech latency, and neural networks security compliance.</p>
        </div>
        
        <?php if (empty($latest_blogs)): ?>
            <p style="text-align: center; color: var(--muted-foreground);">No research papers published yet.</p>
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
                <a href="blog.php" class="btn btn-outline">View All Research</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Interactive Project Cost Estimator -->
    <section class="estimator-section" id="estimator">
        <div class="section-header">
            <span class="badge badge-accent">SaaS Cost Configurator</span>
            <h2>Estimate Your AI SaaS Integration</h2>
            <p>Select your neural model scope, pipeline complexity, and delivery targets. Receive an immediate SLA cost estimate proposal.</p>
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
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Select Neural Domain</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="ai_chatbots">
                            <i data-lucide="message-square"></i>
                            <strong>AI Conversational Chatbots</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Custom RAG agent systems</p>
                        </div>
                        <div class="option-card" data-value="ai_calling">
                            <i data-lucide="phone-call"></i>
                            <strong>Outbound AI Voice Agents</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Twilio trunking call automation</p>
                        </div>
                        <div class="option-card" data-value="ai_seo">
                            <i data-lucide="search"></i>
                            <strong>AI-Powered Semantic SEO</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Organic search index campaigns</p>
                        </div>
                        <div class="option-card" data-value="saas_platform">
                            <i data-lucide="layers"></i>
                            <strong>Custom Multi-Tenant SaaS</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Next.js + Stripe scale portals</p>
                        </div>
                        <div class="option-card" data-value="llm_rag">
                            <i data-lucide="brain"></i>
                            <strong>Custom LLM & Agent Tuning</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">LangGraph multi-agent systems</p>
                        </div>
                        <div class="option-card" data-value="cyber_audit">
                            <i data-lucide="shield"></i>
                            <strong>AI & API Security Audits</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Vulnerability scan & injection defense</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Scale -->
                <div class="estimator-step" data-step="2">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Choose Scale & Model Intensity</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="startup">
                            <i data-lucide="rocket"></i>
                            <strong>SaaS MVP / Local Model</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Rapid setup utilizing public APIs</p>
                        </div>
                        <div class="option-card" data-value="business">
                            <i data-lucide="briefcase"></i>
                            <strong>Production / Tuned LLM</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Vector stores, semantic filters</p>
                        </div>
                        <div class="option-card" data-value="enterprise">
                            <i data-lucide="shield-check"></i>
                            <strong>Enterprise Grade / Multi-Agent</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Strict compliance, high SLA caching</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Select Timeline -->
                <div class="estimator-step" data-step="3">
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Choose Launch Timeline</h3>
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
                    <h3 style="text-align: center; margin-bottom: 1.5rem;">Your Customized SLA Estimate</h3>
                    <div class="estimator-summary">
                        <div>
                            <strong>Estimated AI Project Budget:</strong>
                            <p style="font-size: 0.8rem; color: var(--muted-foreground); margin-top: 0.25rem;">(Calculated based on model complexity variables)</p>
                        </div>
                        <div class="estimator-val" id="est-budget-val">₹0</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="est-name">Full Name *</label>
                            <input type="text" id="est-name" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                        </div>
                        <div class="form-group">
                            <label for="est-email">Email Address *</label>
                            <input type="email" id="est-email" name="email" class="form-control" placeholder="e.g. rahul@example.com" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="est-phone">Phone Number *</label>
                        <input type="tel" id="est-phone" name="phone" class="form-control" placeholder="e.g. +91 99887 76655" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="est-message">Model details / API webhook goals</label>
                        <textarea id="est-message" name="message" class="form-control" rows="3" placeholder="Tell us about your target model, volume, or SaaS workflow..."></textarea>
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
            <span class="badge badge-primary">Active AI Stacks</span>
            <h2>Engineering Resilient Neural Architectures</h2>
            <p>Our research lab constructs high-speed, secure pipelines using modern frameworks and vectors.</p>
        </div>

        <div class="tech-grid">
            <div class="glass-card tech-item">
                <i data-lucide="brain" style="color: hsl(var(--primary));"></i>
                <span>LangChain & LangGraph</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="message-square" style="color: hsl(var(--secondary));"></i>
                <span>OpenAI / Anthropic APIs</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="database" style="color: hsl(var(--accent));"></i>
                <span>Pinecone / pgvector</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="cpu" style="color: hsl(var(--success));"></i>
                <span>FastAPI / PyTorch</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="smartphone" style="color: hsl(var(--warning));"></i>
                <span>Twilio / Retell Voice</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="shield" style="color: hsl(var(--destructive));"></i>
                <span>OWASP LLM Security</span>
            </div>
        </div>
    </section>

    <!-- About Section & Timeline -->
    <section class="about-section" id="about">
        <div class="section-header">
            <span class="badge badge-accent">Our Journey</span>
            <h2>LSXPL: The AI Subdivision of LSPL</h2>
            <p>Established to consolidate AI research, multi-agent voice calling systems, and custom SaaS platforms under one specialized banner.</p>
        </div>

        <div class="about-content">
            <div class="about-text">
                <h3>Our Research Mandate</h3>
                <p>We are a dedicated division of Longway Softronix Pvt. Ltd. (LSPL). While LSPL serves standard custom software projects, e-commerce, and core technology certificates since 2014, LSXPL is a specialized AI Lab.</p>
                <p>We build low-latency outbound trunks, fine-tune models, audit JWT tokens, and engineer modern multi-tenant SaaS interfaces.</p>
                <p>As a branch of LSPL, we share the Kanpur physically registered address at Shastri Nagar (Company incorporated in 2014).</p>
                <a href="#estimator" class="btn btn-outline" style="margin-top: 1rem;">Work With Us <i data-lucide="arrow-up-right"></i></a>
            </div>

            <!-- Visual Timeline -->
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2014 - LSPL Incorporated</div>
                    <div class="timeline-desc">Our parent company, Longway Softronix, was founded in Kanpur, focusing on local web development.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2020 - DevSecOps Audits</div>
                    <div class="timeline-desc">Launched specialized hackIon cybersecurity tracks, training students in API security & pentesting.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2026 - LSXPL Established</div>
                    <div class="timeline-desc">LSXPL was launched as the dedicated AI subdivision branch of LSPL, building conversational agents and SaaS.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2026 (Later) - Voice & RAG</div>
                    <div class="timeline-desc">Deployed low-latency calling nodes and customized LLM knowledgebases for global corporate operations.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="section-header">
            <span class="badge badge-primary">Get in Touch</span>
            <h2>Connect With Our AI Lab</h2>
            <p>Connect with our engineering office or project managers. Send us a message and we will respond back within 12 hours.</p>
        </div>

        <div class="contact-layout">
            <div class="contact-info">
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="mail"></i></div>
                    <div class="contact-text">
                        <h4>Email Us</h4>
                        <p><?php echo htmlspecialchars($site['contact_email'] ?? 'info@lsxpl.co'); ?></p>
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
                        <h4>Lab Location</h4>
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
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="con-phone">Phone Number *</label>
                        <input type="tel" id="con-phone" name="phone" class="form-control" placeholder="+91 9876543210" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="con-message">Message *</label>
                        <textarea id="con-message" name="message" class="form-control" rows="4" placeholder="Briefly specify your target AI/SaaS query..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Send Direct Message <i data-lucide="send" style="width: 14px; height: 14px;"></i></button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="index.php" class="logo">
                    <img src="logo-dark.png?v=<?php echo filemtime(__DIR__ . '/logo-dark.png'); ?>" alt="LSXPL Logo">
                </a>
                <p>LSXPL is the specialized AI Research Lab division of Longway Softronix Pvt. Ltd., developing and offering advanced AI chatbots, voice calling agents, AI SEO pipelines, and multi-tenant SaaS products.</p>
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
            <p>&copy; 2026 - <?php echo date('Y'); ?> LSXPL AI Lab. All rights reserved. (Branch of Longway Softronix Pvt. Ltd.)</p>
            <p>Incorporated in 2014 | Head Office: Shastri Nagar, Kanpur, India</p>
        </div>
    </footer>

    <!-- Service Details Modal -->
    <div class="modal-overlay" id="service-modal">
        <div class="modal-content glass-panel">
            <button class="modal-close"><i data-lucide="x"></i></button>
            <div class="modal-header">
                <div class="service-icon" style="margin-bottom: 0;">
                    <i data-lucide="info"></i>
                </div>
                <div>
                    <h3 id="modal-service-title">Service Details</h3>
                    <span class="badge badge-accent" id="modal-service-tech-title">Tech Blueprint</span>
                </div>
            </div>
            <div class="modal-body">
                <p id="modal-service-description" style="color: var(--muted-foreground); font-size: 0.95rem;"></p>
                
                <div>
                    <h4 style="margin-bottom: 0.75rem; font-size: 1rem;">Core Technology Stack:</h4>
                    <div class="service-tech-tags" id="modal-service-tech" style="border-top: none; padding-top: 0; margin-top: 0;">
                        <!-- Tags inserted dynamically -->
                    </div>
                </div>

                <div>
                    <h4 style="margin-bottom: 0.75rem; font-size: 1rem;">Our Operational Execution Steps:</h4>
                    <ul class="modal-workflow-list" id="modal-service-workflow">
                        <!-- Steps inserted dynamically -->
                    </ul>
                </div>
                
                <a href="#estimator" class="btn btn-primary" style="margin-top: 1rem; width: 100%;" onclick="document.getElementById('service-modal').classList.remove('active');">Launch Estimator For This Service</a>
            </div>
        </div>
    </div>

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
