<?php
$doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$dir = rtrim(str_replace('\\', '/', __DIR__), '/');

$doc_root_lower = strtolower($doc_root);
$dir_lower = strtolower($dir);

$base_path = '/';
if (!empty($doc_root) && strpos($dir_lower, $doc_root_lower) === 0) {
    $rel_path = substr($dir, strlen($doc_root));
    $base_path = '/' . ltrim($rel_path, '/') . '/';
    if ($base_path === '//') {
        $base_path = '/';
    }
}
if (!function_exists('resolve_url')) {
    function resolve_url($url, $base_path) {
        if (empty($url)) return '#';
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0 || strpos($url, 'javascript:') === 0) {
            return $url;
        }
        if ($url === 'index.php') {
            return $base_path;
        }
        if ($url === 'estimator.php') {
            return $base_path . 'estimator';
        }
        if ($url === 'services.php') {
            return $base_path . 'services';
        }
        if ($url === 'solutions.php') {
            return $base_path . 'solutions';
        }
        if (strpos($url, 'index.php#') === 0) {
            return $base_path . substr($url, 9);
        }
        if ($url === 'blog.php') {
            return $base_path . 'blog';
        }
        if (strpos($url, '#') === 0) {
            return $base_path . $url;
        }
        if (strpos($url, '/') === 0 && (strpos($url, '/lspl.xyz_v2') === 0 || strpos($url, '/lsxpl_v2') === 0 || strpos($url, '/longwaysoftronix_v2') === 0)) {
            return $url;
        }
        return $base_path . ltrim($url, '/');
    }
}

// lspl.xyz_v2/db.php - Database connection and auto-initialization
$db_file = __DIR__ . '/lspl_academy_v2.sqlite';
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
            slug TEXT UNIQUE NOT NULL,
            description TEXT NOT NULL,
            content TEXT NOT NULL,
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
            type TEXT NOT NULL,
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

        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            author_name TEXT NOT NULL,
            review_text TEXT NOT NULL,
            rating INTEGER DEFAULT 5,
            platform TEXT NOT NULL,
            project_title TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed settings
    $settings = [
        'stats_courses' => '6 Core Programs',
        'stats_placement' => '94% Placement',
        'sitemap_enabled' => '1',
        'site_title' => 'LSPL Academy | Professional IT & Hacking Certifications',
        'site_tagline' => 'Bridging the Gap Between Academics and Industry...',
        'contact_email' => 'academy@longwaysoftronix.com',
        'contact_phone' => '+91-8840010951',
        'contact_address' => '25/6 Shastri Nagar, Kanpur, UP 208005, India',
        'meta_description' => 'LSPL Academy offers professional web development bootcamps and cybersecurity certifications online and in Kanpur, India. Learn HTML, CSS, JavaScript, PHP MVC, ethical hacking, and secure network infrastructure from expert developers.',
        'hero_title' => 'Shape Your Tech Future with LSPL Academy',
        'hero_subtitle' => 'Our structured bootcamps (weBOShop and hackIon) bridge the gap between classroom theory and real-world software engineering to help students rank among the top global developer talent.',
        'stats_projects' => '50+ Batches',
        'stats_students' => '12,000+ Alumni',
        'stats_technologies' => '15+ Courses',
        'stats_experience' => '8+ Years',
        'canonical_url' => 'https://academy.longwaysoftronix.com',
        'og_image_url' => 'logo.png',
        'schema_markup' => '{"@context":"https://schema.org","@type":"EducationalOrganization","name":"LSPL Academy","url":"https://academy.longwaysoftronix.com","logo":"https://academy.longwaysoftronix.com/logo.png","address":{"@type":"PostalAddress","streetAddress":"25/6 Shastri Nagar","addressLocality":"Kanpur","addressRegion":"UP","postalCode":"208005","addressCountry":"IN"}}',
    ];
    $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    // Seed users
    $users = [
        ['admin', '$2y$10$5UzS5XEk8MaCKAEOdCRYsOBuc0iJ/ezMFGEpfHLVtgCKdL0aupOJO', 'administrator'],
        ['site_mgr', '$2y$10$G5mmlvtlPFIZGZ5OzQYPXuO8gJvUASwE1C.vOIziYOn2FYnattaqq', 'site_manager'],
        ['service_mgr', '$2y$10$cruThNMPH92isFkcyqVy9eWhYK73bJY6mk5olBDeU1zM5g5o4eoqa', 'service_manager'],
        ['blog_edit', '$2y$10$2byJdI.8UCtPdXIAB6BSTeFy267keLmCCzVsykobeEXRRs28CIR1q', 'blog_editor'],
    ];
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    foreach ($users as $u) {
        $stmt->execute($u);
    }

    // Seed services
    $services = [
        ['weBOShop 2.0 (Full-Stack Coding)', 'weboshop-fullstack-coding', 'Our flagship full-stack web coding bootcamp. Master responsive design, SQL databases, PHP, and modern API building.', '<h3>weBOShop 2.0 Full-Stack Software Engineering Course</h3><p>Become a production-ready developer. weBOShop is our flagship coding bootcamp covering front-end and back-end web development. You will learn HTML5, CSS3, JavaScript, PHP MVC backend frameworks, SQLite/MySQL query writing, and RESTful API structures.</p><h4>Bootcamp Curriculum Details</h4><ul><li><strong>Front-End Core:</strong> Master layout grids, CSS flexbox, variables, and responsive design.</li><li><strong>Backend Engineering:</strong> Code custom PHP MVC applications, configure databases, and build REST APIs.</li><li><strong>DevOps & Git:</strong> Learn deployment pipelines, SSL setup, and Git version control.</li></ul><p>Students build 3 complete database projects and deploy them to real cloud servers, gaining authentic Git collaboration experience.</p>', 'code', 'Flagship Bootcamps', 'HTML5, CSS3, JavaScript, PHP MVC, SQLite, Node.js', 1],
        ['hackIon 2.0 (Cybersecurity & Ethical Hacking)', 'hackion-cybersecurity', 'Learn defensive system design, network packet capture auditing, ethical hacking, and OWASP security vulnerability assessment.', '<h3>hackIon 2.0 Ethical Hacking & Defensive Security Course</h3><p>Secure digital assets. hackIon teaches students to think like hackers to build secure systems. Learn network packet capture analysis, firewall configuration, penetration testing, and how to defend against OWASP Top 10 vulnerabilities.</p><h4>Cybersecurity Course Outline</h4><ul><li><strong>Network Analysis:</strong> Capture packets and run traffic audits in Wireshark.</li><li><strong>Penetration Testing:</strong> Auditing server configurations using Nmap and Metasploit.</li><li><strong>OWASP Top 10:</strong> Securing SQL queries and defending against prompt injections.</li></ul><p>We use tools like Kali Linux, Wireshark, Nmap, and Metasploit in practical, hands-on lab environments, preparing students for security certifications.</p>', 'shield', 'Flagship Bootcamps', 'Kali Linux, Wireshark, Metasploit, Nmap, OWASP', 2],
        ['Mobile App Development Certification', 'mobile-app-development', 'Learn native mobile engineering. Develop Swift iOS applications and Kotlin Jetpack Compose Android applications.', '<h3>Native Mobile App Development Certification (iOS & Android)</h3><p>Learn native mobile engineering. This course teaches Apple Swift and SwiftUI for iOS apps, alongside Google Kotlin and Jetpack Compose for Android apps. You will build offline-first mobile databases, implement location maps, and study App Store publishing requirements.</p><h4>Mobile Syllabus Overview</h4><ul><li><strong>iOS Engineering:</strong> Coding Swift, SwiftUI, and managing Keychain storage in Xcode.</li><li><strong>Android Engineering:</strong> Declarative UI in Jetpack Compose, Kotlin Coroutines, and Room databases.</li><li><strong>App Store Guidelines:</strong> Publishing checkouts, review compliance, and beta distributions.</li></ul><p>Build fluid mobile apps and launch them on test networks.</p>', 'smartphone', 'Seasonal Certifications', 'Swift, Xcode, Kotlin, Android Studio, Jetpack Compose', 3],
        ['Headless CMS & Jamstack Coding', 'headless-cms-jamstack-coding', 'Learn modern front-end web development with Next.js, React, headless Strapi APIs, and serverless hosting deployment.', '<h3>Headless CMS & Next.js JAMstack Developer Bootcamp</h3><p>Master the modern web stack. Learn Next.js, React, headless CMS systems (Strapi, Contentful), GraphQL queries, and serverless deployment on platforms like Vercel and Netlify. Perfect for preparing for global remote developer roles.</p><h4>JAMstack Learning Path</h4><ul><li><strong>React & Next.js:</strong> Static Site Generation (SSG) and Server-Side Rendering (SSR).</li><li><strong>Headless APIs:</strong> Querying headless CMS nodes via GraphQL or REST.</li><li><strong>Edge CDNs:</strong> Deploying fast code configurations with static asset assets.</li></ul><p>Learn to build instant-load websites that search engines rank higher.</p>', 'globe', 'Seasonal Certifications', 'Next.js, React, Node.js, GraphQL, Strapi, Vercel', 4],
        ['SEO & Digital Analytics Course', 'seo-digital-analytics-course', 'Master search engine optimization, keyword targeting, Google Search Console auditing, and dynamic GA4 telemetry metrics.', '<h3>SEO Optimization & Analytics Specialist Training</h3><p>Master search visibility. Learn to configure Google Search Console, diagnose speed bottlenecks, write structured JSON-LD schema tags, and read Google Analytics 4 tracking. Gain the skills needed for marketing agencies in the UK and globally.</p><h4>Analytics syllabus</h4><ul><li><strong>Technical SEO:</strong> Canonical tags, robots.txt, sitemaps, and Core Web Vitals.</li><li><strong>Schema Markup:</strong> Building Organization, Service, and Article JSON-LD blocks.</li><li><strong>GA4 Funnels:</strong> Setting up customer tracking, conversion metrics, and dashboards.</li></ul><p>Understand search ranking guidelines and lead optimization strategies.</p>', 'search', 'Seasonal Certifications', 'Google Search Console, Google Analytics 4, Semrush', 5],
        ['Python & AI Engineering Course', 'python-ai-engineering', 'Master Python scripting, database pipelines, and training/calling conversational AI voice agents and LLM APIs.', '<h3>Python Scripting & LLM AI Application Course</h3><p>Learn Python programming, SQLite database integration, pandas data cleaning, and how to build applications using conversational LLM APIs. Learn LangChain, prompt engineering, and voice calling agent orchestration.</p><h4>Python AI Modules</h4><ul><li><strong>Python Core:</strong> Scripts, data structures, and SQLite connection queries.</li><li><strong>LLM APIs:</strong> Building applications using OpenAI and Retell/Vapi templates.</li><li><strong>AI Agents:</strong> Setting up LangChain agents with tool tools.</li></ul><p>Learn to design AI models, prompt filters, and autonomous agents.</p>', 'cpu', 'On-Campus Partnering', 'Python, LangChain, OpenAI APIs, SQLite, Pandas', 6],
        ['Seasonal Coding Internships', 'seasonal-coding-internships', 'Join our summer and winter industrial coding training program in Kanpur. Gain hands-on project work experience.', '<h3>Summer & Winter Industrial Coding Training Internships</h3><p>Get actual work experience. Our Kanpur-based internship programs allow students to join collaborative team sprints, work under senior software engineers, and build portfolio projects that impress recruiters.</p><h4>Internship Key Deliverables</h4><ul><li><strong>Git Team Sprints:</strong> Manage branches, resolve merge conflicts, and review code.</li><li><strong>Senior Mentors:</strong> Work directly with developers from LSPL Kanpur and UK.</li><li><strong>Portfolio Review:</strong> Final project feedback and interview preparation support.</li></ul><p>Get certified by LSPL and boost your tech employment opportunities.</p>', 'graduation-cap', 'On-Campus Partnering', 'Full-Stack Projects, Git workflows, Team sprints', 7],
        ['Generative AI & LLM Prompt Engineering Bootcamp', 'generative-ai-prompt-engineering', 'Master prompt optimization, Retrieval-Augmented Generation (RAG), vector databases, and constructing autonomous AI agent workflows.', '<p>Join the next generation of developers. Learn to integrate Large Language Models (LLMs) and build complex AI workflows using prompt engineering, vector search, and model tuning.</p><h4>Course Curriculum</h4><ul><li><strong>Prompting Patterns:</strong> Learn few-shot, chain-of-thought, and system instructions.</li><li><strong>Vector DB & RAG:</strong> Retrieve private documents with Pinecone, ChromaDB, and LangChain.</li><li><strong>AI Agents:</strong> Develop stateful agents that execute multi-step tools autonomously.</li></ul>', 'brain', 'Flagship Bootcamps', 'Python, LangChain, OpenAI API, HuggingFace', 8],
        ['DevOps, CI/CD & Cloud Orchestration', 'devops-cloud-orchestration', 'Learn how to build automated pipelines, package code into containers, and deploy scalable cloud architecture with high availability.', '<p>This hands-on program covers modern deployment pipelines, cloud provisioning, and container monitoring for high-availability systems.</p><h4>Key Learning Outcomes</h4><ul><li><strong>Containerization:</strong> Writing optimized Dockerfiles and managing Docker Compose.</li><li><strong>CI/CD Pipelines:</strong> Building automated test, build, and deploy flows on GitHub Actions.</li><li><strong>Kubernetes Orchestration:</strong> Deploying, scaling, and managing container clusters on cloud infrastructure.</li></ul>', 'cloud', 'Seasonal Certifications', 'Docker, Kubernetes, GitHub Actions, AWS', 9],
    ];
    $stmt = $db->prepare("INSERT INTO services (title, slug, description, content, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute($s);
    }

    // Seed pages
    $pages = [
        ['About Academy', 'about-academy', '<h3>LSPL Academy</h3><p>LSPL Academy is the dedicated educational wing of Longway Softronix Pvt. Ltd. Founded in Kanpur, our academy specializes in training the next generation of full-stack developers, cybersecurity specialists, and mobile app engineers.</p><p>Our industry-vetted curriculums like weBOShop and hackIon ensure that our graduates possess actual project-building experience and are ready for global remote and on-site roles.</p><h3 style="margin-top: 3rem; margin-bottom: 2rem;">Academy Milestones</h3><div class="about-timeline"><div class="timeline-item"><div class="timeline-year">2014</div><div class="timeline-content"><h4>LSPL Incorporation</h4><p>Parent organization Longway Softronix was founded, establishing high standards in custom software engineering.</p></div></div><div class="timeline-item"><div class="timeline-year">2018</div><div class="timeline-content"><h4>Internal Training Cohorts</h4><p>Launched corporate mentoring structures and practical developer internships inside local workspace projects.</p></div></div><div class="timeline-item"><div class="timeline-year">2021</div><div class="timeline-content"><h4>Virtual Program Beta Tests</h4><p>Initiated interactive online labs, sandbox workspaces, and remote developer training frameworks.</p></div></div><div class="timeline-item"><div class="timeline-year">2023</div><div class="timeline-content"><h4>Academy Launch (weBOShop & hackIon)</h4><p>Officially opened LSPL Academy with advanced bootcamps for full-stack MVC coding and defensive system hacking.</p></div></div><div class="timeline-item"><div class="timeline-year">2026</div><div class="timeline-content"><h4>Global Graduate Placement</h4><p>Successfully placed over 1,000 graduates in premium IT developer and system analyst positions globally.</p></div></div></div>', 1, 1],
        ['Student Privacy Policy', 'student-privacy', '<h3>Student Privacy Policy</h3><p>We take student data privacy seriously. This policy details how we handle academic logs, assignment uploads, and portal credentials. Your progress metrics are shared only with registered partner companies for placement assistance.</p>', 1, 2],
        ['Terms of Certification', 'certification-terms', '<h3>Terms of Certification</h3><p>In order to earn a certificate from LSPL Academy (whether for weBOShop 2.0, hackIon 2.0, or seasonal training), students must complete all coding assignments and score at least 60% in the final project evaluation.</p><p>All credentials can be verified online by prospective employers using our global register.</p>', 1, 3],
    ];
    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, display_in_nav, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute($p);
    }

    // Seed blogs
    $blogs = [
        ['Mastering Full-Stack Coding: Roadmap for Aspiring Developers', 'fullstack-developer-roadmap', 'A step-by-step guide to transitions from basic HTML to robust backend API development.', '<p>Becoming a full-stack engineer requires structured learning. Starting with semantic HTML5, CSS3, and JavaScript, students build visual layouts. Moving to backend systems involves understanding databases (SQLite/MySQL) and writing server-side logic in PHP MVC or Node.js.</p><p>At LSPL Academy, our weBOShop bootcamp guides students through actual production deployments, giving them hands-on Git and REST API experience.</p>', 'Academy Board', 'uploads/laravel_backend.png', 'Published'],
        ['Demystifying hackIon 2.0: The Importance of Defensive Security', 'cybersecurity-defensive-architecture', 'Why learning defensive architecture, packet capture audits, and penetration testing is critical for tech careers.', '<p>Cybersecurity is no longer just for specialized security engineers. Every developer must understand defensive design. Our hackIon security training program teaches Kali Linux, Wireshark, Metasploit, and how to defend against OWASP Top 10 vulnerabilities.</p><p>By learning how hackers exploit system loopholes, students learn how to build secure, robust software networks.</p>', 'Academy Board', 'uploads/cybersecurity_hacking.png', 'Published'],
        ['Rising Demand for Headless CMS and Jamstack Developers', 'demand-headless-cms-jamstack-developers', 'How learning Next.js, React, and headless Strapi CMS prepares students for premium remote global developer jobs.', '<p>The web is moving towards headless setups. Learning Next.js and JAMstack development allows students to build sites that load instantly and offer superior security. Global remote employers in the UK and US actively recruit developers who can integrate React front-ends with headless APIs.</p>', 'Academy Board', 'uploads/ecommerce_shopping.png', 'Published'],
        ['Why Mobile App Engineering in Kotlin and Swift is Future-Proof', 'mobile-app-engineering-kotlin-swift', 'Exploring careers in native mobile development for iOS and Android platforms.', '<p>Mobile applications dominate digital transactions. Our mobile app certification program teaches native development using Swift/SwiftUI for Apple devices and Kotlin/Jetpack Compose for Android. Students build fully functional offline-first apps and learn App Store publishing guidelines.</p>', 'Academy Board', 'uploads/mobile_app_dev.png', 'Published'],
        ['Understanding GA4 Telemetry and Technical SEO Audits', 'understanding-ga4-telemetry-seo', 'How learning analytics setup and search ranking audits boosts student employability.', '<p>Every company needs organic search traffic. Our SEO & Digital Analytics course teaches students to configure Google Analytics 4, read Search Console reports, and write structured JSON-LD schemas. These analytical skills are highly valued by marketing agencies worldwide.</p>', 'Academy Board', 'uploads/seo_search_marketing.png', 'Published'],
    ];
    $stmt = $db->prepare("INSERT INTO blogs (title, slug, summary, content, author, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($blogs as $b) {
        $stmt->execute($b);
    }

    // Seed header menu
    $header_menu = [
        [1, null, 'Home', 'custom', null, 'index.php', 'single_page', null, 1],
        [26, null, 'About Academy', 'page', 'about-academy', null, 'single_page', null, 2],
        [2, null, 'Courses', 'none', null, null, 'megamenu', null, 3],
        [3, null, 'Blog', 'custom', null, 'blog.php', 'single_page', null, 4],
        [4, null, 'Estimator', 'custom', null, 'estimator.php', 'single_page', null, 5],
        [5, null, 'Contact', 'custom', null, 'index.php#contact', 'single_page', null, 6],
        [16, 2, 'weBOShop 2.0 Bootcamp', 'custom', null, 'service/weboshop-fullstack-coding', 'single_page', 'Bootcamps & Hacking', 1],
        [17, 2, 'hackIon 2.0 Security', 'custom', null, 'service/hackion-cybersecurity', 'single_page', 'Bootcamps & Hacking', 2],
        [18, 2, 'Mobile App Certification', 'custom', null, 'service/mobile-app-development', 'single_page', 'Bootcamps & Hacking', 3],
        [19, 2, 'Headless JAMstack Course', 'custom', null, 'service/headless-cms-jamstack-coding', 'single_page', 'Web & AI Certs', 4],
        [20, 2, 'SEO & Analytics Specialist', 'custom', null, 'service/seo-digital-analytics-course', 'single_page', 'Web & AI Certs', 5],
        [21, 2, 'Python & AI Engineering', 'custom', null, 'service/python-ai-engineering', 'single_page', 'Web & AI Certs', 6],
        [22, 2, 'Coding Internships', 'custom', null, 'service/seasonal-coding-internships', 'single_page', 'Internships & Info', 7],
        [23, 2, 'Academy Publications', 'custom', null, 'blog.php', 'single_page', 'Internships & Info', 8],
        [24, 2, 'About Academy Us', 'page', 'about-academy', null, 'single_page', 'Internships & Info', 9],
        [25, 2, 'Student Privacy Policy', 'page', 'student-privacy', null, 'single_page', 'Internships & Info', 10],
        [27, 2, 'Generative AI & LLM Prompting', 'custom', null, 'service/generative-ai-prompt-engineering', 'single_page', 'Bootcamps & Hacking', 8],
        [28, 2, 'DevOps & Cloud Orchestration', 'custom', null, 'service/devops-cloud-orchestration', 'single_page', 'Web & AI Certs', 9],
    ];
    // We insert items preserving IDs to maintain parent-child relationships correctly
    $stmt = $db->prepare("INSERT INTO header_menu_items (id, parent_id, title, link_type, page_slug, custom_url, menu_type, column_name, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($header_menu as $h) {
        $stmt->execute($h);
    }

    // Seed footer items
    $footer_items = [
        ['Training Programs', 'Bootcamps Catalog', 'custom', null, 'index.php#courses', 1],
        ['Training Programs', 'Fee Estimator', 'custom', null, 'estimator.php', 2],
        ['Training Programs', 'Contact Academy', 'custom', null, 'index.php#contact', 3],
        ['Company Info', 'About Academy', 'page', 'about-academy', null, 4],
        ['Academy Policies', 'Student Privacy Policy', 'page', 'student-privacy', null, 5],
        ['Academy Policies', 'Terms of Certification', 'page', 'certification-terms', null, 6],
    ];
    $stmt = $db->prepare("INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($footer_items as $f) {
        $stmt->execute($f);
    }

    // Seed reviews
    $reviews = [
        ['Amit Sharma', 'Excellent learning experience at LSPL Academy. The React and PHP course content is extremely detailed. Pawan Sir\'s hands-on guidance on real-world projects made all the difference in my job placements.', 5, 'google', 'Full Stack Development Training'],
        ['Chloe Adams', 'Took their custom WordPress development training. Very practical exercises. I can now design custom Gutenberg blocks and plugins with confidence. Thanks to the LSPL training team!', 5, 'peopleperhour', 'WordPress Theme Development Course'],
        ['Rohan Gupta', 'The data analytics course using BigQuery and Python was very well structured. Clear explanations, weekly assignments, and brilliant mentors. Best place in Kanpur for professional IT training.', 5, 'trustpilot', 'Python Data Analytics Bootcamp']
    ];
    $stmt = $db->prepare("INSERT INTO reviews (author_name, review_text, rating, platform, project_title) VALUES (?, ?, ?, ?, ?)");
    foreach ($reviews as $r) {
        $stmt->execute($r);
    }
}
?>
