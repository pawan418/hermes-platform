<?php
// lspl.xyz/db.php - Database connection and auto-initialization for LSPL Academy
$db_file = __DIR__ . '/lspl_academy.sqlite';
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
            author TEXT DEFAULT 'Academic Board',
            image_url TEXT DEFAULT NULL,
            status TEXT DEFAULT 'Published',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            type TEXT NOT NULL, -- 'contact', 'registration'
            service_selected TEXT DEFAULT NULL, -- selected course
            duration_selected TEXT DEFAULT NULL, -- batch type/timing
            message TEXT DEFAULT NULL,
            budget TEXT DEFAULT NULL, -- fee estimator result or general details
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
        'site_title' => 'LSPL Academy | Professional IT & Hacking Certifications',
        'site_tagline' => 'Bridging the Gap Between Academics and Industry...',
        'contact_email' => 'academy@longwaysoftronix.com',
        'contact_phone' => '+91-8840010951',
        'contact_address' => '25/6 Shastri Nagar, Kanpur, UP 208005, India',
        'meta_description' => 'LSPL Academy offers professional offline & online certification bootcamps, including weBOShop 2.0 full-stack coding, hackIon 2.0 cybersecurity, Summer/Winter industrial trainings, and campus collaborations.',
        'hero_title' => 'Learn, Build, and Get Certified',
        'hero_subtitle' => 'LSPL Academy was established under parent brand Longway Softronix (founded 2014) to provide project-driven industrial bootcamps, professional certificates, and coding workshops.',
        'stats_courses' => '6 Core Programs',
        'stats_students' => '12,000+ Certified',
        'stats_experience' => 'Founded 2014',
        'stats_placement' => '94% Placement'
    ];

    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    // Seed default courses (the 6 requested academy courses/workshops)
    $courses = [
        [
            'title' => 'weBOShop 2.0 (Full-Stack Coding)',
            'description' => 'Our flagship web engineering bootcamp. Learn interface layouts, database integration, secure user sessions, REST routing, and server management using PHP, SQL, and Node.js.',
            'icon' => 'code',
            'category' => 'Flagship Bootcamps',
            'tech_stack' => 'HTML5, CSS3, JavaScript, PHP MVC, Node.js, SQLite, Git, deployment workflows',
            'display_order' => 1
        ],
        [
            'title' => 'hackIon 2.0 (Cybersecurity & Ethical Hacking)',
            'description' => 'Master defensive design, network packet capture analysis, penetration testing methodologies, database security diagnostics, and system hardening protocols.',
            'icon' => 'shield',
            'category' => 'Flagship Bootcamps',
            'tech_stack' => 'Kali Linux, Wireshark, Metasploit, Nmap, OWASP Top 10, Network Defense Systems',
            'display_order' => 2
        ],
        [
            'title' => 'Summer Industrial Training',
            'description' => 'Rigorous 6-weeks / 6-months certification blueprint engineered for students during summer vacations. Build enterprise web software and native mobile applications from scratch.',
            'icon' => 'sun',
            'category' => 'Seasonal Certifications',
            'tech_stack' => 'Python Django, Laravel MVC, React Frontend, Android Native, REST APIs',
            'display_order' => 3
        ],
        [
            'title' => 'Winter Industrial Training',
            'description' => 'Fast-track technical training program conducted during winter breaks. Ideal for refreshing core software architecture design, data structures, and database pipelines.',
            'icon' => 'snowflake',
            'category' => 'Seasonal Certifications',
            'tech_stack' => 'Data Structures, Web APIs, Relational SQL Databases, Hosting environments',
            'display_order' => 4
        ],
        [
            'title' => 'On-Campus Institutional Training',
            'description' => 'Tailored workshops delivered on-campus in collaboration with engineering colleges. Empowers students with custom curriculums to match direct industry standards.',
            'icon' => 'users',
            'category' => 'Academic Partnering',
            'tech_stack' => 'Custom Syllabi, Live Project Mentoring, Pre-Placement Coding Drills',
            'display_order' => 5
        ],
        [
            'title' => 'School Tech Camps',
            'description' => 'Foundational coding camps designed specifically for school kids. Nurtures algorithmic logic, basic web structures, and introductory database concepts.',
            'icon' => 'graduation-cap',
            'category' => 'Academic Partnering',
            'tech_stack' => 'Scratch Programming, Intro to Web Design (HTML/CSS), Algorithmic Thinking',
            'display_order' => 6
        ]
    ];

    $stmt = $db->prepare("INSERT INTO services (title, description, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($courses as $c) {
        $stmt->execute([$c['title'], $c['description'], $c['icon'], $c['category'], $c['tech_stack'], $c['display_order']]);
    }

    // Seed default pages
    $pages = [
        [
            'title' => 'About Academy',
            'slug' => 'about-academy',
            'content' => '<h3>About LSPL Academy</h3><p>LSPL Academy is the dedicated educational division of Longway Softronix Pvt. Ltd., which was incorporated in 2014. Our mission is to bridge the gap between academic theory and actual industry requirements.</p><p>We have successfully certified over 12,000 students through rigorous hands-on training, providing them with certified validation of their software development and ethical hacking skills.</p>',
            'display_in_nav' => 1,
            'display_order' => 1
        ],
        [
            'title' => 'Student Privacy Policy',
            'slug' => 'student-privacy',
            'content' => '<h3>Student Privacy Policy</h3><p>We take the privacy of our students very seriously. Any personal information, project work, and payment data submitted to LSPL Academy is fully encrypted and stored securely.</p><p>We do not share your contact details with external third-party marketing channels without your explicit consent.</p>',
            'display_in_nav' => 1,
            'display_order' => 2
        ],
        [
            'title' => 'Terms of Certification',
            'slug' => 'certification-terms',
            'content' => '<h3>Terms of Certification</h3><p>In order to earn a certificate from LSPL Academy (whether for weBOShop 2.0, hackIon 2.0, or seasonal training), students must complete all coding assignments and score at least 60% in the final project evaluation.</p><p>All credentials can be verified online by prospective employers using our global register.</p>',
            'display_in_nav' => 1,
            'display_order' => 3
        ]
    ];

    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, display_in_nav, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute([$p['title'], $p['slug'], $p['content'], $p['display_in_nav'], $p['display_order']]);
    }

    // Seed default blogs
    $blogs = [
        [
            'title' => 'Preparing for Your First Software Engineering Interview',
            'slug' => 'engineering-interview-prep',
            'summary' => 'A comprehensive guide to coding challenges, systems design, and behavioral questions for freshers.',
            'content' => '<p>Entering the tech industry can feel intimidating, but structured preparation can make all the difference. In this guide, our senior instructors break down what tech recruiters are looking for, how to explain your database architecture during a whiteboarding session, and how to showcase your project portfolio effectively.</p><p>We recommend starting with core data structures (like arrays, hash maps, and queues), practicing clean SQL queries, and building two robust full-stack projects to display on GitHub. Under our weBOShop 2.0 bootcamp, students build complete e-commerce and CMS systems to ensure they can talk through design tradeoffs in actual job interviews.</p>',
            'author' => 'Academy Board',
            'status' => 'Published'
        ],
        [
            'title' => 'The Rise of Cybersecurity: hackIon 2.0 Training Benefits',
            'slug' => 'cybersecurity-hackion-benefits',
            'summary' => 'Why understanding defensive architecture is as crucial as writing features in modern IT systems.',
            'content' => '<p>In today\'s interconnected world, security is no longer an afterthought. A single cross-site scripting (XSS) vulnerability or SQL injection entry point can expose millions of customer records. That is why we upgraded our hackIon 2.0 curriculum to emphasize secure coding guidelines alongside penetration testing.</p><p>Students in this camp learn how to inspect network packets, run diagnostic security audits, and identify server configuration loopholes. Understanding how attackers think helps you build highly secure products, giving you a major advantage in any development team.</p>',
            'author' => 'Academy Board',
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
        ['Academy Courses', 'weBOShop 2.0 Bootcamp', 'custom', null, 'service.php?id=1', 1],
        ['Academy Courses', 'hackIon 2.0 Security', 'custom', null, 'service.php?id=2', 2],
        ['Academy Courses', 'Summer Industrial Training', 'custom', null, 'service.php?id=3', 3],
        ['Academy Courses', 'Winter Industrial Training', 'custom', null, 'service.php?id=4', 4],
        
        ['Academic Partnering', 'On-Campus Training', 'custom', null, 'service.php?id=5', 5],
        ['Academic Partnering', 'School Tech Camps', 'custom', null, 'service.php?id=6', 6],
        ['Academic Partnering', 'About Academy', 'page', 'about-academy', null, 7],
        
        ['Student Resources', 'Academy Publications', 'custom', null, 'blog.php', 8],
        ['Student Resources', 'Tuition Estimator', 'custom', null, 'index.php#estimator', 9],
        ['Student Resources', 'Student Privacy Policy', 'page', 'student-privacy', null, 10],
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
        ['Courses', 'none', null, null, 'megamenu', 2],
        ['Blog', 'custom', null, 'blog.php', 'single_page', 3],
        ['Estimator', 'custom', null, 'index.php#estimator', 'single_page', 4],
        ['Contact', 'custom', null, 'index.php#contact', 'single_page', 5]
    ];
    
    $stmt = $db->prepare("INSERT INTO header_menu_items (title, link_type, page_slug, custom_url, menu_type, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($top_levels as $item) {
        $stmt->execute($item);
    }
    
    // Get ID of the Courses top-level menu
    $courses_id = $db->query("SELECT id FROM header_menu_items WHERE title = 'Courses' LIMIT 1")->fetchColumn();
    
    if ($courses_id) {
        // 2. Insert sub-menu items nested under 'Courses'
        $sub_items = [
            [$courses_id, 'weBOShop 2.0 Bootcamp', 'custom', null, 'service.php?id=1', 'single_page', 'Academy Courses', 1],
            [$courses_id, 'hackIon 2.0 Security', 'custom', null, 'service.php?id=2', 'single_page', 'Academy Courses', 2],
            [$courses_id, 'Summer Industrial Training', 'custom', null, 'service.php?id=3', 'single_page', 'Academy Courses', 3],
            [$courses_id, 'Winter Industrial Training', 'custom', null, 'service.php?id=4', 'single_page', 'Academy Courses', 4],
            
            [$courses_id, 'On-Campus Training', 'custom', null, 'service.php?id=5', 'single_page', 'Academic Partnering', 5],
            [$courses_id, 'School Tech Camps', 'custom', null, 'service.php?id=6', 'single_page', 'Academic Partnering', 6],
            [$courses_id, 'About Academy', 'page', 'about-academy', null, 'single_page', 'Academic Partnering', 7],
            
            [$courses_id, 'Academy Publications', 'custom', null, 'blog.php', 'single_page', 'Student Resources', 8],
            [$courses_id, 'Tuition Estimator', 'custom', null, 'index.php#estimator', 'single_page', 'Student Resources', 9],
            [$courses_id, 'Student Privacy Policy', 'page', 'student-privacy', null, 'single_page', 'Student Resources', 10]
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
        ['Training Programs', 'Bootcamps Catalog', 'custom', null, 'index.php#courses', 1],
        ['Training Programs', 'Fee Estimator', 'custom', null, 'index.php#estimator', 2],
        ['Training Programs', 'Contact Advisor', 'custom', null, 'index.php#contact', 3],
        
        ['Administration', 'Admin Dashboard Login', 'custom', null, 'admin.php', 4],
        ['Administration', 'Terms of Certification', 'page', 'certification-terms', null, 5],
        ['Administration', 'Main Company (LSPL)', 'custom', null, '../longwaysoftronix/', 6],
    ];
    $stmt = $db->prepare("INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($default_footer as $item) {
        $stmt->execute($item);
    }
}
?>
