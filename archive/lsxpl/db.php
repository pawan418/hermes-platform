<?php
// lsxpl/db.php - Database connection and auto-initialization for LSXPL AI Lab Site
$db_file = __DIR__ . '/lsxpl_ai.sqlite';
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
        'site_title' => 'LSXPL | AI Research Lab & Intelligent SaaS Platforms',
        'site_tagline' => 'e X PLore. e X PErience. e X PAnd.',
        'contact_email' => 'info@lsxpl.co',
        'contact_phone' => '+91-8840010951',
        'contact_address' => '25/6 Shastri Nagar, Kanpur, UP 208005, India (AI Lab Branch of LSPL)',
        'meta_description' => 'LSXPL is the specialized AI Research Lab division of Longway Softronix Pvt. Ltd., developing and offering advanced AI chatbots, voice calling agents, AI SEO pipelines, and multi-tenant SaaS products.',
        'hero_title' => 'Pioneering Intelligent SaaS & AI Automation',
        'hero_subtitle' => 'LSXPL is the AI engineering branch of LSPL, building low-latency conversational nodes, automated outbound voice agents, semantic keyword analyzers, and custom LLM solutions.',
        'stats_projects' => '50+',
        'stats_students' => '1,500+',
        'stats_technologies' => '15+',
        'stats_experience' => 'Established 2026'
    ];

    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    // Seed default services (6 AI & SaaS services)
    $services = [
        [
            'title' => 'AI Conversational Chatbots',
            'description' => 'Smart NLP-driven customer assistance agents. Configured with RAG (Retrieval-Augmented Generation) pipelines to search and summarize enterprise data.',
            'icon' => 'message-square',
            'category' => 'AI & Automation',
            'tech_stack' => 'OpenAI API, LangChain, Node.js, Python, VectorDB',
            'display_order' => 1
        ],
        [
            'title' => 'AI Voice & Calling Agents',
            'description' => 'Automate inbound call support or outbound lead qualifications with low-latency, hyper-realistic voice agents that connect seamlessly with webhooks.',
            'icon' => 'phone-call',
            'category' => 'AI & Automation',
            'tech_stack' => 'Twilio Voice, Vapi, Retell AI, ElevenLabs, Webhooks',
            'display_order' => 2
        ],
        [
            'title' => 'AI-Powered & Hybrid SEO',
            'description' => 'Drive technical organic growth using semantic search integration, automated keyword indexing dashboards, and technical page audits.',
            'icon' => 'search',
            'category' => 'Marketing & Search',
            'tech_stack' => 'Google Console API, GPT-4, Semrush, Custom Scraping',
            'display_order' => 3
        ],
        [
            'title' => 'Custom SaaS Platform Design',
            'description' => 'High-scale multi-tenant software architecture. Configured with secure JWT credentials, interactive user panels, Stripe billings, and database partitioning.',
            'icon' => 'layers',
            'category' => 'SaaS Development',
            'tech_stack' => 'React, Next.js, Node.js, Stripe, PostgreSQL, Docker',
            'display_order' => 4
        ],
        [
            'title' => 'Custom LLM & Agent Tuning',
            'description' => 'Integrate domain-specific models, write secure prompt templates, and construct multi-agent chains that automate business tasks.',
            'icon' => 'brain',
            'category' => 'AI & Automation',
            'tech_stack' => 'LlamaIndex, OpenAI, Hugging Face, PyTorch, LangGraph',
            'display_order' => 5
        ],
        [
            'title' => 'Cybersecurity & AI Security Audits',
            'description' => 'Penetration testing audits for web software and SaaS APIs. Protect pipelines against prompt injections, data leaks, and secure Auth gateways.',
            'icon' => 'shield',
            'category' => 'Infrastructure',
            'tech_stack' => 'Kali Linux, OWASP Top 10, DevSecOps pipelines, JWT Check',
            'display_order' => 6
        ]
    ];

    $stmt = $db->prepare("INSERT INTO services (title, description, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute([$s['title'], $s['description'], $s['icon'], $s['category'], $s['tech_stack'], $s['display_order']]);
    }

    // Seed default pages
    $pages = [
        [
            'title' => 'AI Research Agenda',
            'slug' => 'research-agenda',
            'content' => '<h3>Our Research Mandate</h3><p>At LSXPL AI Lab, we study how low-latency calling nodes and multi-agent loops can be deployed in production environment with safety checks.</p><p>Our primary research tracks include multi-agent workflows (using LangGraph), local model quantized execution, and protecting web services from semantic prompt injection attacks.</p>',
            'display_in_nav' => 1,
            'display_order' => 1
        ],
        [
            'title' => 'SaaS Security & Trust',
            'slug' => 'saas-trust',
            'content' => '<h3>Trust & Security Compliance</h3><p>We implement OAuth 2.0, JSON Web Token cryptography, and request caching throttles. Our multi-tenant databases are strictly partitioned to prevent data leakage.</p><p>We conduct regular penetration scans and automated OWASP audits for all SaaS systems we design.</p>',
            'display_in_nav' => 1,
            'display_order' => 2
        ]
    ];

    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, display_in_nav, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute([$p['title'], $p['slug'], $p['content'], $p['display_in_nav'], $p['display_order']]);
    }

    // Seed default blog posts
    $blogs = [
        [
            'title' => 'The Future of Low-Latency Outbound Voice Agents',
            'slug' => 'future-voice-agents',
            'summary' => 'How we combine ElevenLabs and Twilio webhooks to build hyper-realistic phone agents.',
            'content' => '<p>Low-latency voice agents require sub-200ms processing thresholds to maintain natural conversation. In this post, we detail our RAG caching layer that speeds up response queries.</p><p>By combining ElevenLabs Text-to-Speech nodes with Twilio trunking, we build outbound assistants that feel human. We sanitize input and buffer streaming data, preventing conversation lags or overlaps.</p>',
            'author' => 'Admin',
            'status' => 'Published'
        ],
        [
            'title' => 'Prompt Injection and API Safety in SaaS Applications',
            'slug' => 'saas-safety-audits',
            'summary' => 'Essential strategies to shield multi-agent LLM systems against prompt leaks and data vulnerability.',
            'content' => '<p>With the rise of generative AI dashboards, prompt injection vulnerability is a primary concern. We explain how our middleware handles data filtering, token audits, and prompt sanitation.</p><p>By validating all prompt templates against vector-based guardrails, we block rogue commands while ensuring correct tool call triggers for customer queries.</p>',
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
        ['AI Solutions', 'AI Chatbots & Agents', 'custom', null, 'service.php?id=1', 1],
        ['AI Solutions', 'Outbound Voice Agents', 'custom', null, 'service.php?id=2', 2],
        ['AI Solutions', 'Semantic AI SEO', 'custom', null, 'service.php?id=3', 3],
        ['AI Solutions', 'Multi-Tenant SaaS Apps', 'custom', null, 'service.php?id=4', 4],
        
        ['Advanced Research', 'LLM Tuning & RAG', 'custom', null, 'service.php?id=5', 5],
        ['Advanced Research', 'SaaS Security Audits', 'custom', null, 'service.php?id=6', 6],
        ['Advanced Research', 'Research Agenda', 'page', 'research-agenda', null, 7],
        
        ['Resources', 'Research Publications', 'custom', null, 'blog.php', 8],
        ['Resources', 'SaaS Estimator', 'custom', null, 'index.php#estimator', 9],
        ['Resources', 'SaaS Trust Page', 'page', 'saas-trust', null, 10],
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
        ['Lab Home', 'custom', null, 'index.php', 'single_page', 1],
        ['AI Solutions', 'none', null, null, 'megamenu', 2],
        ['Insights', 'custom', null, 'blog.php', 'single_page', 3],
        ['Estimator', 'custom', null, 'index.php#estimator', 'single_page', 4],
        ['Contact', 'custom', null, 'index.php#contact', 'single_page', 5]
    ];
    
    $stmt = $db->prepare("INSERT INTO header_menu_items (title, link_type, page_slug, custom_url, menu_type, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($top_levels as $item) {
        $stmt->execute($item);
    }
    
    // Get ID of the AI Solutions top-level menu
    $solutions_id = $db->query("SELECT id FROM header_menu_items WHERE title = 'AI Solutions' LIMIT 1")->fetchColumn();
    
    if ($solutions_id) {
        // 2. Insert sub-menu items nested under 'AI Solutions'
        $sub_items = [
            [$solutions_id, 'AI Chatbots & Agents', 'custom', null, 'service.php?id=1', 'single_page', 'AI Solutions', 1],
            [$solutions_id, 'Outbound Voice Agents', 'custom', null, 'service.php?id=2', 'single_page', 'AI Solutions', 2],
            [$solutions_id, 'Semantic AI SEO', 'custom', null, 'service.php?id=3', 'single_page', 'AI Solutions', 3],
            [$solutions_id, 'Multi-Tenant SaaS Apps', 'custom', null, 'service.php?id=4', 'single_page', 'AI Solutions', 4],
            
            [$solutions_id, 'LLM Tuning & RAG', 'custom', null, 'service.php?id=5', 'single_page', 'Advanced Research', 5],
            [$solutions_id, 'SaaS Security Audits', 'custom', null, 'service.php?id=6', 'single_page', 'Advanced Research', 6],
            [$solutions_id, 'Research Agenda', 'page', 'research-agenda', null, 'single_page', 'Advanced Research', 7],
            
            [$solutions_id, 'Research Publications', 'custom', null, 'blog.php', 'single_page', 'Resources', 8],
            [$solutions_id, 'SaaS Estimator', 'custom', null, 'index.php#estimator', 'single_page', 'Resources', 9],
            [$solutions_id, 'SaaS Trust Page', 'page', 'saas-trust', null, 'single_page', 'Resources', 10]
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
        ['AI Lab Research', 'Research Agenda', 'page', 'research-agenda', null, 1],
        ['AI Lab Research', 'Publications Log', 'custom', null, 'blog.php', 2],
        ['AI Lab Research', 'Request Consultation', 'custom', null, 'index.php#contact', 3],
        
        ['Administration', 'Admin Dashboard Login', 'custom', null, 'admin.php', 4],
        ['Administration', 'SaaS Security & Trust', 'page', 'saas-trust', null, 5],
        ['Administration', 'Main Company (LSPL)', 'custom', null, '../longwaysoftronix/', 6],
    ];
    $stmt = $db->prepare("INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($default_footer as $item) {
        $stmt->execute($item);
    }
}
?>
