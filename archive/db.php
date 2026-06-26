<?php
// db.php - Database connection and auto-initialization

$db_file = __DIR__ . '/lspl_database.sqlite';
$db = null;

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if database needs initialization (we check if 'users' table exists)
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

        CREATE TABLE IF NOT EXISTS academy (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            subtitle TEXT NOT NULL,
            duration TEXT NOT NULL,
            description TEXT NOT NULL,
            features TEXT NOT NULL, -- Stored as comma-separated or JSON list
            price TEXT NOT NULL,
            type TEXT NOT NULL, -- 'bootcamp' or 'workshop'
            display_order INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            type TEXT NOT NULL, -- 'contact', 'registration', 'estimator'
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
        'site_title' => 'LSPL | Next-Gen Software, AI Solutions & Tech Academy',
        'site_tagline' => 'Implementation of Your Thoughts into Premium Code',
        'contact_email' => 'info@longwaysoftronix.com',
        'contact_phone' => '+91-8840010951',
        'contact_address' => 'Lucknow, Uttar Pradesh, India',
        'meta_description' => 'LSPL offers custom software, SaaS products, AI chatbots, automated voice agents, and AI-powered SEO, alongside top-tier IT training workshops like weBOShop and hackIon.',
        'hero_title' => 'We Implement Your Thoughts Into Code',
        'hero_subtitle' => 'Pioneering intelligent software, custom SaaS products, automated AI agents, and high-performance engineering for businesses globally, while training the next generation of tech leaders.',
        'stats_projects' => '500+',
        'stats_students' => '10,000+',
        'stats_technologies' => '25+',
        'stats_experience' => '8+ Years'
    ];

    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    // Seed default services
    $services = [
        [
            'title' => 'AI Chatbots & Conversational Systems',
            'description' => 'Smart NLP-driven customer assistance, intelligent content recommendation, and knowledge base integration using advanced LLM pipelines (RAG).',
            'icon' => 'message-square',
            'category' => 'AI & Automation',
            'tech_stack' => 'OpenAI API, LangChain, Node.js, Python, VectorDB',
            'display_order' => 1
        ],
        [
            'title' => 'AI Calling & Voice Agents',
            'description' => 'Automate outbound and inbound communication with lifelike, low-latency AI voice agents for customer support, lead qualification, and scheduling.',
            'icon' => 'phone-call',
            'category' => 'AI & Automation',
            'tech_stack' => 'Twilio, Vapi, Retell AI, ElevenLabs, Webhooks',
            'display_order' => 2
        ],
        [
            'title' => 'AI-Powered & Hybrid SEO',
            'description' => 'Unlocking organic growth using semantic search integration, automated keyword discovery dashboards, AI-assisted content auditing, and technical audits.',
            'icon' => 'search',
            'category' => 'Marketing & Search',
            'tech_stack' => 'Google Console, GPT-4, Semrush API, Custom Scraping',
            'display_order' => 3
        ],
        [
            'title' => 'Custom SaaS Development',
            'description' => 'End-to-end design and coding of multi-tenant cloud software. Robust authentication, secure subscription billings, API hubs, and dashboard consoles.',
            'icon' => 'layers',
            'category' => 'SaaS Development',
            'tech_stack' => 'React, Next.js, Node.js, Stripe, PostgreSQL, Docker',
            'display_order' => 4
        ],
        [
            'title' => 'Web Application Development',
            'description' => 'High-performance interactive web portals, responsive user experiences, remote server integrations, and enterprise database-driven platforms.',
            'icon' => 'globe',
            'category' => 'Web & Software',
            'tech_stack' => 'React, Vue, PHP, Node.js, MySQL, REST APIs',
            'display_order' => 5
        ],
        [
            'title' => 'E-Commerce Solutions',
            'description' => 'Building secure and highly optimized online shopping experiences, including headless commerce integrations, inventory sync pipelines, and payment setups.',
            'icon' => 'shopping-cart',
            'category' => 'Web & Software',
            'tech_stack' => 'Shopify, WooCommerce, Next.js, Stripe, headless commerce',
            'display_order' => 6
        ],
        [
            'title' => 'Custom Software Solutions',
            'description' => 'Custom-built desktop and desktop-hybrid enterprise systems tailored specifically to operational parameters, workflows, and local constraints.',
            'icon' => 'code',
            'category' => 'Web & Software',
            'tech_stack' => 'C#, .NET Core, Python, SQL Server, C++',
            'display_order' => 7
        ],
        [
            'title' => 'Performance & Digital Marketing',
            'description' => 'Data-driven marketing campaigns, social media growth hacking, and automated marketing flows that place your products directly where users are searching.',
            'icon' => 'megaphone',
            'category' => 'Marketing & Search',
            'tech_stack' => 'Meta Ads, Google Ads Manager, HubSpot, Analytics 4',
            'display_order' => 8
        ],
        [
            'title' => 'Network & Infrastructure Solutions',
            'description' => 'LAN/WAN network design, secure wireless routing configurations, server rack deployment, fiber optic planning, and cloud-hybrid setups.',
            'icon' => 'server',
            'category' => 'Infrastructure',
            'tech_stack' => 'Cisco, Ubiquiti, Cloud-VPC, OFC, Fiber, Rackmounts',
            'display_order' => 9
        ]
    ];

    $stmt = $db->prepare("INSERT INTO services (title, description, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute([$s['title'], $s['description'], $s['icon'], $s['category'], $s['tech_stack'], $s['display_order']]);
    }

    // Seed default academy programs (workshops & bootcamps)
    $academy = [
        [
            'title' => 'weBOShop 2.0',
            'subtitle' => 'Interactive Web Designing & Frontend Prototyping Workshop',
            'duration' => '3 Days (Weekend Intensive)',
            'description' => 'Master the basics of modern web design, UI layout foundations, and interactive components. Perfect for students and designers eager to learn modern development paradigms.',
            'features' => 'Modern CSS Flexbox & Grid, Figma to Code Translation, Intro to Javascript Interactivity, Training Kit & Study Material, Certificate of Participation',
            'price' => 'Free',
            'type' => 'workshop',
            'display_order' => 1
        ],
        [
            'title' => 'hackIon 2.0',
            'subtitle' => 'Advanced Ethical Hacking & Cybersecurity Workshop',
            'duration' => '5 Days',
            'description' => 'A hands-on workshop focused on web security, networking penetration testing, and white-hat hacking. Learn to identify and patch critical digital vulnerabilities.',
            'features' => 'Kali Linux & Backtrack Tools, OWASP Top 10 Web Scanning, Wireless Security Audit, Authorized hackIon Certification, Cybersecurity Toolkit Included',
            'price' => '₹1,499',
            'type' => 'workshop',
            'display_order' => 2
        ],
        [
            'title' => 'Full-Stack MERN Development',
            'subtitle' => 'Next-Gen Professional Bootcamp',
            'duration' => '6 / 12 Weeks (Options Available)',
            'description' => 'An intensive career-focused engineering track starting from core JavaScript to building full-scale, secure database-driven React applications deployed on cloud environments.',
            'features' => 'Advanced React & TypeScript, Node.js + Express Server API, MongoDB Schema Modeling, JWT Auth & Stripe billing Integration, Live Project with Company Experience Letter, Placement Support & Interview Prep',
            'price' => 'Contact for Pricing',
            'type' => 'bootcamp',
            'display_order' => 3
        ],
        [
            'title' => 'Python, AI & Machine Learning Engineering',
            'subtitle' => 'Advanced Intelligence Engineering Bootcamp',
            'duration' => '8 / 12 Weeks',
            'description' => 'Dive deep into data science pipelines, model training, and building generative AI applications using custom LLMs and RAG techniques.',
            'features' => 'Python Data Science (NumPy/Pandas), Deep Learning with PyTorch, OpenAI API & LangChain Orchestration, Building Custom RAG & Chatbots, Industry Project Experience Certificate, Mentoring by AI Engineers',
            'price' => 'Contact for Pricing',
            'type' => 'bootcamp',
            'display_order' => 4
        ]
    ];

    $stmt = $db->prepare("INSERT INTO academy (title, subtitle, duration, description, features, price, type, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($academy as $a) {
        $stmt->execute([$a['title'], $a['subtitle'], $a['duration'], $a['description'], $a['features'], $a['price'], $a['type'], $a['display_order']]);
    }
}
?>
