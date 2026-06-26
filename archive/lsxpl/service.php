<?php
// lsxpl/service.php - Dynamic AI Service Subpage
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

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
    'AI & Automation' => [
        'Analyze client datasets, operational rules, and RAG semantic targets.',
        'Wireframe conversational logic, chatbot prompts, or outbound Voice call trees.',
        'Configure vector databases (Pinecone/pgvector) & construct LangGraph agent workflows.',
        'Conduct testing for hallucination thresholds & voice response low-latency checks.',
        'Connect endpoints, verify webhooks, and launch live monitoring panels.'
    ],
    'SaaS Development' => [
        'Design multi-tenant database partitioning schemas & data separation gates.',
        'Wireframe responsive glassmorphic interfaces & custom user portfolios.',
        'Integrate secure JWT authorization credentials & Stripe checkout links.',
        'Execute endpoint latency audits & multi-user concurrent tests.',
        'Deploy cloud hosting systems, configure CDNs, and hand over codebase.'
    ],
    'Marketing & Search' => [
        'Crawl dynamic URLs & perform semantic entity optimization audits.',
        'Formulate target keyword blueprints and write LLM content generation pipelines.',
        'Optimize local business maps & execute backlink outreach campaigns.',
        'Manage Google Console indexing triggers and verify sitemaps.',
        'Publish GA4 conversion trackers and report keyword ranking logs.'
    ],
    'Infrastructure' => [
        'Assess network protocols, port allocations, and API security parameters.',
        'Conduct diagnostic scanning for prompt injections and JWT token safety.',
        'Run penetration auditing for OWASP SaaS vulnerabilities.',
        'Test server load tolerances and database backup protocols.',
        'Draft server configuration maps & release compliance reports.'
    ]
];

$category_wf = isset($workflows[$service['category']]) ? $workflows[$service['category']] : $workflows['AI & Automation'];

// Check current theme to set initial logo image
session_start();
$initial_logo = 'logo-dark.png'; // default dark theme logo
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?> | LSXPL AI Lab</title>
    <link rel="stylesheet" href="style.css">
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
</head>
<body>
    <canvas id="particle-canvas"></canvas>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="<?php echo $initial_logo; ?>" alt="LSXPL Logo">
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

    <!-- Main Content -->
    <main class="page-container" style="margin-top: 9rem;">
        <div class="glass-card">
            <div class="service-icon" style="width: 56px; height: 56px; font-size: 1.5rem;">
                <i data-lucide="<?php echo htmlspecialchars($service['icon']); ?>"></i>
            </div>
            
            <h1 style="font-size: 2.25rem; margin-top: 1.5rem; font-family: var(--font-heading);"><?php echo htmlspecialchars($service['title']); ?></h1>
            
            <div class="service-meta-row">
                <span class="badge badge-primary"><?php echo htmlspecialchars($service['category']); ?></span>
                <span style="font-size: 0.85rem; color: hsl(var(--muted-foreground));">Research Node #<?php echo $service['id']; ?></span>
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

            <h3 style="font-size: 1.25rem; border-bottom: 1px dashed hsl(var(--border)); padding-bottom: 0.75rem; margin-bottom: 1rem;">Operational Execution Workflow</h3>
            <ul class="workflow-step-list">
                <?php foreach ($category_wf as $idx => $step): ?>
                    <li>
                        <i data-lucide="check-circle-2"></i>
                        <div>
                            <strong>Step <?php echo $idx + 1; ?>:</strong> <?php echo htmlspecialchars($step); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div style="margin-top: 4rem; text-align: center; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="index.php#estimator" class="btn btn-primary" style="padding: 0.85rem 2rem;">Launch SaaS Estimator</a>
                <a href="index.php#contact" class="btn btn-outline" style="padding: 0.85rem 2rem;">Consult AI Team</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="index.php" class="logo">
                    <img src="<?php echo $initial_logo; ?>" alt="LSXPL Logo">
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
            <p>&copy; 2026 - <?php echo date('Y'); ?> LSXPL AI Lab. All rights reserved.</p>
        </div>
    </footer>

    <script src="app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Match initial logo to localStorage theme state before load
        const storedTheme = localStorage.getItem('lsxpl_theme') || 'dark';
        const logoImg = document.querySelector('.logo img');
        if (logoImg) {
            logoImg.src = storedTheme === 'light' ? 'logo-light.png' : 'logo-dark.png';
        }
    </script>
</body>
</html>
