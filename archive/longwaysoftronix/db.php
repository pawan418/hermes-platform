<?php
// longwaysoftronix/db.php - Database connection and auto-initialization for LSPL Main Site
$db_file = __DIR__ . '/lspl_main.sqlite';
$db = null;

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if database needs initialization
$table_exists = false;
try {
    $result = $db->query("SELECT 1 FROM users LIMIT 1");
    if ($result) {
        $table_exists = true;
    }
} catch (Exception $e) {
    // Table does not exist
}

if (!$table_exists) {
    // Create Tables
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            icon TEXT NOT NULL,
            category TEXT NOT NULL,
            tech_stack TEXT NOT NULL,
            display_order INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            content TEXT NOT NULL,
            display_in_nav INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS blogs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            summary TEXT NOT NULL,
            content TEXT NOT NULL,
            author TEXT DEFAULT 'Admin',
            image_url TEXT DEFAULT NULL,
            status TEXT DEFAULT 'Published',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            type TEXT NOT NULL, -- 'contact', 'estimator'
            service_selected TEXT DEFAULT NULL,
            duration_selected TEXT DEFAULT NULL,
            message TEXT DEFAULT NULL,
            budget TEXT DEFAULT NULL,
            status TEXT DEFAULT 'New',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
    ");

    // Seed default administrator (admin / admin123)
    $admin_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $admin_password_hash, 'administrator']);

    // Seed default settings
    $settings = [
        'site_title' => 'LSPL | Longway Softronix Pvt. Ltd. | Custom Software & Web Systems',
        'site_tagline' => 'Implementation Of Your THOUGHTS...',
        'contact_email' => 'info@longwaysoftronix.com',
        'contact_phone' => '+91-8840010951',
        'contact_address' => '25/6 Shastri Nagar, Kanpur, UP 208005, India',
        'meta_description' => 'Longway Softronix Pvt. Ltd. (LSPL) is a registered Private Limited Company founded in 2014, offering custom web design, Laravel backend development, e-commerce stores, native iOS and Android apps, and SEO audits.',
        'hero_title' => 'We Implement Your Thoughts Into Code',
        'hero_subtitle' => 'LSPL was founded in 2014 in Kanpur to provide class-leading custom websites, native mobile applications, secure software systems, and search marketing for corporate clients.',
        'stats_projects' => '750+',
        'stats_students' => '12,000+',
        'stats_technologies' => '30+',
        'stats_experience' => '12+ Years'
    ];

    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    // Seed default services (13 detailed services, including iOS and Android separated)
    $services = [
        [
            'title' => 'Web Designing & UI/UX',
            'description' => 'Vibrant, responsive interactive media layout solutions. Wireframe prototyping, CSS layout designs, and custom dynamic page mockups.',
            'icon' => 'layout',
            'category' => 'Web & Software',
            'tech_stack' => 'HTML5, CSS3, JavaScript, Figma, Bootstrap 5',
            'display_order' => 1
        ],
        [
            'title' => 'WordPress & CMS Development',
            'description' => 'Custom content management setups. Theme integration, plugin extensions, and modular content pages built on WordPress, Joomla, and Drupal.',
            'icon' => 'file-text',
            'category' => 'Web & Software',
            'tech_stack' => 'WordPress, PHP, MySQL, Joomla CMS, Drupal Core',
            'display_order' => 2
        ],
        [
            'title' => 'Laravel & CodeIgniter Web Apps',
            'description' => 'Robust, database-driven backend web portals using standard PHP MVC frameworks. Secure API hubs and enterprise systems.',
            'icon' => 'cpu',
            'category' => 'Web & Software',
            'tech_stack' => 'PHP, Laravel, CodeIgniter, REST APIs, MySQL',
            'display_order' => 3
        ],
        [
            'title' => 'Web Application Development',
            'description' => 'Next-gen high-performance database-driven platforms, full-stack systems, real-time channels, and custom management dashboards.',
            'icon' => 'globe',
            'category' => 'Web & Software',
            'tech_stack' => 'Node.js, React, MongoDB, Express, Django, PostgreSQL',
            'display_order' => 4
        ],
        [
            'title' => 'iOS Application Development',
            'description' => 'Custom native iOS applications engineered for iPhones and iPads. Swift programming, SwiftUI layout templates, and Apple Store deployment.',
            'icon' => 'smartphone',
            'category' => 'Web & Software',
            'tech_stack' => 'iOS Swift, SwiftUI, Xcode IDE, Apple Core Frameworks',
            'display_order' => 5
        ],
        [
            'title' => 'Android Application Development',
            'description' => 'Native Android apps built with standard Google stacks. Material Design, database synchronization, and Google Play Store support.',
            'icon' => 'smartphone',
            'category' => 'Web & Software',
            'tech_stack' => 'Android SDK, Kotlin, Java, Jetpack Compose, SQLite',
            'display_order' => 6
        ],
        [
            'title' => 'Magento Enterprise E-Commerce',
            'description' => 'Robust multi-store e-commerce portals. Catalog systems, secure shopping cart pipelines, invoice tracking, and custom payment processors.',
            'icon' => 'shopping-bag',
            'category' => 'E-Commerce Solution',
            'tech_stack' => 'Magento Core, PHP, MySQL, Redis Cache, Stripe API',
            'display_order' => 7
        ],
        [
            'title' => 'PrestaShop E-Commerce Solutions',
            'description' => 'Lightweight, rapid e-commerce shop coding. Interactive product catalogs, stock management modules, and shipping calculator integrations.',
            'icon' => 'shopping-cart',
            'category' => 'E-Commerce Solution',
            'tech_stack' => 'PrestaShop, Smarty, PHP, MySQL, PayPal Gateway',
            'display_order' => 8
        ],
        [
            'title' => 'Moodle LMS Customization',
            'description' => 'Custom virtual classrooms and learning directories. Progress tracking, online quiz modules, and document storage databases.',
            'icon' => 'graduation-cap',
            'category' => 'Web & Software',
            'tech_stack' => 'Moodle CMS, PHP, MySQL, SCORM Integrations',
            'display_order' => 9
        ],
        [
            'title' => 'Custom Software Solutions',
            'description' => 'High-integrity custom desktop or hybrid business tools engineered to align with local constraints, workflows, and specifications.',
            'icon' => 'code',
            'category' => 'Web & Software',
            'tech_stack' => 'C#, .NET Core, SQL Server, Python, C++',
            'display_order' => 10
        ],
        [
            'title' => 'Search Engine Optimization (SEO)',
            'description' => 'Technical diagnostic audits, meta indexing, local search campaigns, and keyword optimization to elevate search rankings.',
            'icon' => 'search',
            'category' => 'Marketing & Search',
            'tech_stack' => 'Google Search Console, Semrush, Keyword Planner',
            'display_order' => 11
        ],
        [
            'title' => 'Digital & Social Media Marketing',
            'description' => 'Strategic online advertising campaigns, PPC dashboards, newsletter workflows, and social media brand growth automation.',
            'icon' => 'megaphone',
            'category' => 'Marketing & Search',
            'tech_stack' => 'Meta Ads, Google Ads, Mailchimp, Analytics 4',
            'display_order' => 12
        ],
        [
            'title' => 'Network & Infrastructure Solutions',
            'description' => 'Network cabling layouts, server rack configuration, fiber optic deployment (OFC), and secure LAN/WAN system planning.',
            'icon' => 'server',
            'category' => 'Infrastructure',
            'tech_stack' => 'Cisco Systems, Ubiquiti, WAN, LAN, Fiber, Rackmount',
            'display_order' => 13
        ]
    ];

    $stmt = $db->prepare("INSERT INTO services (title, description, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute([$s['title'], $s['description'], $s['icon'], $s['category'], $s['tech_stack'], $s['display_order']]);
    }

    // Seed default pages
    $pages = [
        [
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => '<h3>Who We Are</h3><p>Longway Softronix Pvt. Ltd. (LSPL) is a premier IT consulting and development firm based in Kanpur, India. Founded in 2014, we are committed to turning thoughts and ideas into robust, secure, and clean code.</p><p>We specialize in custom web applications, e-commerce engines, native mobile apps, and technical search engine optimization.</p>',
            'display_in_nav' => 1,
            'display_order' => 1
        ],
        [
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => '<h3>Privacy Policy</h3><p>At LSPL, accessible from longwaysoftronix.com, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by LSPL and how we use it.</p><p>If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us at info@longwaysoftronix.com.</p>',
            'display_in_nav' => 1,
            'display_order' => 2
        ],
        [
            'title' => 'Terms of Service',
            'slug' => 'terms-conditions',
            'content' => '<h3>Terms of Service</h3><p>Welcome to Longway Softronix! These terms and conditions outline the rules and regulations for the use of Longway Softronix Pvt. Ltd.\'s Website, located at longwaysoftronix.com.</p><p>By accessing this website, we assume you accept these terms and conditions. Do not continue to use Longway Softronix if you do not agree to take all of the terms and conditions stated on this page.</p>',
            'display_in_nav' => 1,
            'display_order' => 3
        ]
    ];

    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, display_in_nav, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute([$p['title'], $p['slug'], $p['content'], $p['display_in_nav'], $p['display_order']]);
    }

    // Seed default blog posts
    $blogs = [
        [
            'title' => 'Transforming Web Workflows with Laravel MVC',
            'slug' => 'laravel-workflows',
            'summary' => 'Learn how we leverage Laravel\'s robust ecosystem to deploy secure, caching-enabled client dashboards.',
            'content' => '<p>Laravel has redefined modern backend web development. Its MVC architecture, combined with tools like Eloquent ORM, allows us to build fast, secure database layers. In this article, we explain how we structured our database routing pipelines to scale our custom web portal systems.</p><p>By utilizing Laravel\'s built-in query caching and database migration triggers, we reduce deployment overhead by up to 40% while ensuring zero downtime for business operations. Reach out to our team to consult on scaling your existing PHP databases.</p>',
            'author' => 'Admin',
            'status' => 'Published'
        ],
        [
            'title' => 'Why Custom E-Commerce Beats Generic Template Stores',
            'slug' => 'custom-ecommerce-advantages',
            'summary' => 'Exploring PrestaShop and Magento custom code structures for multi-store scaling and inventory speeds.',
            'content' => '<p>Many online businesses start with simple page builders, but as catalogs grow, loading speeds drop. Custom Magento and PrestaShop code pipelines enable secure multi-store inventory configurations and faster checkouts.</p><p>With custom integrations, you have complete control over payment gateways, API webhooks, automated invoicing, and database scaling. We design systems to load under 1.5 seconds, even with tens of thousands of catalog products.</p>',
            'author' => 'Admin',
            'status' => 'Published'
        ]
    ];

    $stmt = $db->prepare("INSERT INTO blogs (title, slug, summary, content, author, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($blogs as $b) {
        $stmt->execute([$b['title'], $b['slug'], $b['summary'], $b['content'], $b['author'], $b['status']]);
    }
}

// Ensure Megamenu, Header Menu, & Footer items tables exist (Auto-migration)
$db->exec("
    CREATE TABLE IF NOT EXISTS megamenu_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        column_name TEXT NOT NULL,
        title TEXT NOT NULL,
        link_type TEXT NOT NULL,
        page_slug TEXT DEFAULT NULL,
        custom_url TEXT DEFAULT NULL,
        display_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS header_menu_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parent_id INTEGER DEFAULT NULL,
        title TEXT NOT NULL,
        link_type TEXT NOT NULL,
        page_slug TEXT DEFAULT NULL,
        custom_url TEXT DEFAULT NULL,
        menu_type TEXT DEFAULT 'single_page',
        column_name TEXT DEFAULT NULL,
        display_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(parent_id) REFERENCES header_menu_items(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS footer_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        column_name TEXT NOT NULL,
        title TEXT NOT NULL,
        link_type TEXT NOT NULL,
        page_slug TEXT DEFAULT NULL,
        custom_url TEXT DEFAULT NULL,
        display_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// Seed default megamenu items if empty (legacy support)
$megamenu_count = $db->query("SELECT COUNT(*) FROM megamenu_items")->fetchColumn();
if ($megamenu_count == 0) {
    $default_megamenu = [
        ['Web Services', 'Web Designing & UI/UX', 'custom', null, 'service.php?id=1', 1],
        ['Web Services', 'WordPress Development', 'custom', null, 'service.php?id=2', 2],
        ['Web Services', 'Laravel Web Apps', 'custom', null, 'service.php?id=3', 3],
        ['Web Services', 'Web Application Dev', 'custom', null, 'service.php?id=4', 4],
        
        ['Mobile & E-Commerce', 'iOS App Development', 'custom', null, 'service.php?id=5', 5],
        ['Mobile & E-Commerce', 'Android App Development', 'custom', null, 'service.php?id=6', 6],
        ['Mobile & E-Commerce', 'Magento E-Commerce', 'custom', null, 'service.php?id=7', 7],
        ['Mobile & E-Commerce', 'PrestaShop Store', 'custom', null, 'service.php?id=8', 8],
        
        ['Quick Resources', 'Insights Blog', 'custom', null, 'blog.php', 9],
        ['Quick Resources', 'About Our Company', 'page', 'about-us', null, 10],
        ['Quick Resources', 'Launch Estimator', 'custom', null, 'index.php#estimator', 11],
    ];
    $stmt = $db->prepare("INSERT INTO megamenu_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($default_megamenu as $item) {
        $stmt->execute($item);
    }
}

// Seed default header menu items if empty
$header_count = $db->query("SELECT COUNT(*) FROM header_menu_items")->fetchColumn();
if ($header_count == 0) {
    // 1. Insert top-level items
    $top_levels = [
        ['Home', 'custom', null, 'index.php', 'single_page', 1],
        ['Services', 'none', null, null, 'megamenu', 2],
        ['Blog', 'custom', null, 'blog.php', 'single_page', 3],
        ['Estimator', 'custom', null, 'index.php#estimator', 'single_page', 4],
        ['Contact', 'custom', null, 'index.php#contact', 'single_page', 5]
    ];
    
    $stmt = $db->prepare("INSERT INTO header_menu_items (title, link_type, page_slug, custom_url, menu_type, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($top_levels as $item) {
        $stmt->execute($item);
    }
    
    // Get ID of the Services top-level menu
    $services_id = $db->query("SELECT id FROM header_menu_items WHERE title = 'Services' LIMIT 1")->fetchColumn();
    
    if ($services_id) {
        // 2. Insert sub-menu items nested under 'Services'
        $sub_items = [
            [$services_id, 'Web Designing & UI/UX', 'custom', null, 'service.php?id=1', 'single_page', 'Web Services', 1],
            [$services_id, 'WordPress Development', 'custom', null, 'service.php?id=2', 'single_page', 'Web Services', 2],
            [$services_id, 'Laravel Web Apps', 'custom', null, 'service.php?id=3', 'single_page', 'Web Services', 3],
            [$services_id, 'Web Application Dev', 'custom', null, 'service.php?id=4', 'single_page', 'Web Services', 4],
            
            [$services_id, 'iOS App Development', 'custom', null, 'service.php?id=5', 'single_page', 'Mobile & E-Commerce', 5],
            [$services_id, 'Android App Development', 'custom', null, 'service.php?id=6', 'single_page', 'Mobile & E-Commerce', 6],
            [$services_id, 'Magento E-Commerce', 'custom', null, 'service.php?id=7', 'single_page', 'Mobile & E-Commerce', 7],
            [$services_id, 'PrestaShop Store', 'custom', null, 'service.php?id=8', 'single_page', 'Mobile & E-Commerce', 8],
            
            [$services_id, 'Insights Blog', 'custom', null, 'blog.php', 'single_page', 'Quick Resources', 9],
            [$services_id, 'About Our Company', 'page', 'about-us', null, 'single_page', 'Quick Resources', 10],
            [$services_id, 'Launch Estimator', 'custom', null, 'index.php#estimator', 'single_page', 'Quick Resources', 11]
        ];
        
        $sub_stmt = $db->prepare("INSERT INTO header_menu_items (parent_id, title, link_type, page_slug, custom_url, menu_type, column_name, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sub_items as $sub) {
            $sub_stmt->execute($sub);
        }
    }
}

// Seed default footer items if empty
$footer_count = $db->query("SELECT COUNT(*) FROM footer_items")->fetchColumn();
if ($footer_count == 0) {
    $default_footer = [
        ['Support & Pages', 'About Our Company', 'page', 'about-us', null, 1],
        ['Support & Pages', 'Insights Blog', 'custom', null, 'blog.php', 2],
        ['Support & Pages', 'Contact Support', 'custom', null, 'index.php#contact', 3],
        
        ['Administration', 'Admin Console Login', 'custom', null, 'admin.php', 4],
        ['Administration', 'Privacy Policy', 'page', 'privacy-policy', null, 5],
        ['Administration', 'Terms & Conditions', 'page', 'terms-conditions', null, 6],
    ];
    $stmt = $db->prepare("INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($default_footer as $item) {
        $stmt->execute($item);
    }
}
?>
