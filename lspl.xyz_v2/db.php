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
        [
            'weBOShop 2.0 (Full-Stack Coding)',
            'weboshop-fullstack-coding',
            'Our flagship full-stack web coding bootcamp. Master responsive design, SQL databases, PHP, and modern API building.',
            '<h3>weBOShop 2.0 Full-Stack Software Engineering Course</h3><p>Become a production-ready developer. weBOShop is our flagship coding bootcamp covering front-end and back-end web development. You will learn HTML5, CSS3, JavaScript, PHP MVC backend frameworks, SQLite/MySQL query writing, and RESTful API structures.</p><h4>Bootcamp Curriculum Details</h4><ul><li><strong>Front-End Core:</strong> Master layout grids, CSS flexbox, variables, and responsive design.</li><li><strong>Backend Engineering:</strong> Code custom PHP MVC applications, configure databases, and build REST APIs.</li><li><strong>DevOps & Git:</strong> Learn deployment pipelines, SSL setup, and Git version control.</li></ul><p>Students build 3 complete database projects and deploy them to real cloud servers, gaining authentic Git collaboration experience.</p><h3>Hands-on Career Track</h3><p>Our weBOShop program is built to mirror real-world developer shifts. Students participate in agile sprint standups, write clean documentation, and practice peer code reviews, ensuring they transition smoothly into professional software roles.</p>',
            'code',
            'Flagship Bootcamps',
            'HTML5, CSS3, JavaScript, PHP MVC, SQLite, Node.js',
            1
        ],
        [
            'hackIon 2.0 (Cybersecurity & Ethical Hacking)',
            'hackion-cybersecurity',
            'Learn defensive system design, network packet capture auditing, ethical hacking, and OWASP security vulnerability assessment.',
            '<h3>hackIon 2.0 Ethical Hacking & Defensive Security Course</h3><p>Secure digital assets. hackIon teaches students to think like hackers to build secure systems. Learn network packet capture analysis, firewall configuration, penetration testing, and how to defend against OWASP Top 10 vulnerabilities.</p><h4>Cybersecurity Course Outline</h4><ul><li><strong>Network Analysis:</strong> Capture packets and run traffic audits in Wireshark.</li><li><strong>Penetration Testing:</strong> Auditing server configurations using Nmap and Metasploit.</li><li><strong>OWASP Top 10:</strong> Securing SQL queries and defending against prompt injections.</li></ul><p>We use tools like Kali Linux, Wireshark, Nmap, and Metasploit in practical, hands-on lab environments, preparing students for security certifications.</p><h3>Vulnerability Shielding</h3><p>Beyond finding security gaps, this training teaches how to patch systems, write secure queries, audit authentication schemes, and configure active firewalls to block intrusion vectors.</p>',
            'shield',
            'Flagship Bootcamps',
            'Kali Linux, Wireshark, Metasploit, Nmap, OWASP',
            2
        ],
        [
            'Mobile App Development Certification',
            'mobile-app-development',
            'Learn native mobile engineering. Develop Swift iOS applications and Kotlin Jetpack Compose Android applications.',
            '<h3>Native Mobile App Development Certification (iOS & Android)</h3><p>Learn native mobile engineering. This course teaches Apple Swift and SwiftUI for iOS apps, alongside Google Kotlin and Jetpack Compose for Android apps. You will build offline-first mobile databases, implement location maps, and study App Store publishing requirements.</p><h4>Mobile Syllabus Overview</h4><ul><li><strong>iOS Engineering:</strong> Coding Swift, SwiftUI, and managing Keychain storage in Xcode.</li><li><strong>Android Engineering:</strong> Declarative UI in Jetpack Compose, Kotlin Coroutines, and Room databases.</li><li><strong>App Store Guidelines:</strong> Publishing checkouts, review compliance, and beta distributions.</li></ul><p>Build fluid mobile apps and launch them on test networks.</p><h3>Performance Optimization</h3><p>Mobile devices require strict memory management. Learn to optimize layout trees, prevent memory leaks in threads, and implement lazy loading lists to guarantee fluid 60FPS user experience.</p>',
            'smartphone',
            'Seasonal Certifications',
            'Swift, Xcode, Kotlin, Android Studio, Jetpack Compose',
            3
        ],
        [
            'Headless CMS & Jamstack Coding',
            'headless-cms-jamstack-coding',
            'Learn modern front-end web development with Next.js, React, headless Strapi APIs, and serverless hosting deployment.',
            '<h3>Headless CMS & Next.js JAMstack Developer Bootcamp</h3><p>Master the modern web stack. Learn Next.js, React, headless CMS systems (Strapi, Contentful), GraphQL queries, and serverless deployment on platforms like Vercel and Netlify. Perfect for preparing for global remote developer roles.</p><h4>JAMstack Learning Path</h4><ul><li><strong>React & Next.js:</strong> Static Site Generation (SSG) and Server-Side Rendering (SSR).</li><li><strong>Headless APIs:</strong> Querying headless CMS nodes via GraphQL or REST.</li><li><strong>Edge CDNs:</strong> Deploying fast code configurations with static asset assets.</li></ul><p>Learn to build instant-load websites that search engines rank higher.</p><h3>Serverless Functions</h3><p>Learn to write serverless backend handlers in Next.js to process user signups, invoice collections, and CRM syncs securely without deploying heavy servers.</p>',
            'globe',
            'Seasonal Certifications',
            'Next.js, React, Node.js, GraphQL, Strapi, Vercel',
            4
        ],
        [
            'SEO & Digital Analytics Course',
            'seo-digital-analytics-course',
            'Master search engine optimization, keyword targeting, Google Search Console auditing, and dynamic GA4 telemetry metrics.',
            '<h3>SEO Optimization & Analytics Specialist Training</h3><p>Master search visibility. Learn to configure Google Search Console, diagnose speed bottlenecks, write structured JSON-LD schema tags, and read Google Analytics 4 tracking. Gain the skills needed for marketing agencies in the UK and globally.</p><h4>Analytics Syllabus</h4><ul><li><strong>Technical SEO:</strong> Canonical tags, robots.txt, sitemaps, and Core Web Vitals.</li><li><strong>Schema Markup:</strong> Building Organization, Service, and Article JSON-LD blocks.</li><li><strong>GA4 Funnels:</strong> Setting up customer tracking, conversion metrics, and dashboards.</li></ul><p>Understand search ranking guidelines and lead optimization strategies.</p><h3>Client Reporting Skills</h3><p>Learn to compile conversion reports and present SEO traffic audits, preparing you for digital marketing roles at global corporate agencies.</p>',
            'search',
            'Seasonal Certifications',
            'Google Search Console, Google Analytics 4, Semrush',
            5
        ],
        [
            'Python & AI Engineering Course',
            'python-ai-engineering',
            'Master Python scripting, database pipelines, and training/calling conversational AI voice agents and LLM APIs.',
            '<h3>Python Scripting & LLM AI Application Course</h3><p>Learn Python programming, SQLite database integration, pandas data cleaning, and how to build applications using conversational LLM APIs. Learn LangChain, prompt engineering, and voice calling agent orchestration.</p><h4>Python AI Modules</h4><ul><li><strong>Python Core:</strong> Scripts, data structures, and SQLite connection queries.</li><li><strong>LLM APIs:</strong> Building applications using OpenAI and Retell/Vapi templates.</li><li><strong>AI Agents:</strong> Setting up LangChain agents with tool tools.</li></ul><p>Learn to design AI models, prompt filters, and autonomous agents.</p><h3>RAG Implementation</h3><p>Master Retrieval-Augmented Generation (RAG) by integrating vector databases like Pinecone to enable AI agents to answer questions from corporate manuals securely.</p>',
            'cpu',
            'On-Campus Partnering',
            'Python, LangChain, OpenAI APIs, SQLite, Pandas',
            6
        ],
        [
            'Seasonal Coding Internships',
            'seasonal-coding-internships',
            'Join our summer and winter industrial coding training program in Kanpur. Gain hands-on project work experience.',
            '<h3>Summer & Winter Industrial Coding Training Internships</h3><p>Get actual work experience. Our Kanpur-based internship programs allow students to join collaborative team sprints, work under senior software engineers, and build portfolio projects that impress recruiters.</p><h4>Internship Key Deliverables</h4><ul><li><strong>Git Team Sprints:</strong> Manage branches, resolve merge conflicts, and review code.</li><li><strong>Senior Mentors:</strong> Work directly with developers from LSPL Kanpur and UK.</li><li><strong>Portfolio Review:</strong> Final project feedback and interview preparation support.</li></ul><p>Get certified by LSPL and boost your tech employment opportunities.</p><h3>Deployment Operations</h3><p>Learn how to package software containers with Docker and deploy them to cloud networks, gaining practical DevOps capabilities valued by IT firms.</p>',
            'graduation-cap',
            'On-Campus Partnering',
            'Full-Stack Projects, Git workflows, Team sprints',
            7
        ],
        [
            'Generative AI & LLM Prompt Engineering Bootcamp',
            'generative-ai-prompt-engineering',
            'Master prompt optimization, Retrieval-Augmented Generation (RAG), vector databases, and constructing autonomous AI agent workflows.',
            '<h3>Generative AI & LLM Prompt Engineering Bootcamp</h3><p>Join the next generation of developers. Learn to integrate Large Language Models (LLMs) and build complex AI workflows using prompt engineering, vector search, and model tuning.</p><h4>Course Curriculum</h4><ul><li><strong>Prompting Patterns:</strong> Learn few-shot, chain-of-thought, and system instructions.</li><li><strong>Vector DB & RAG:</strong> Retrieve private documents with Pinecone, ChromaDB, and LangChain.</li><li><strong>AI Agents:</strong> Develop stateful agents that execute multi-step tools autonomously.</li></ul><p>This bootcamp provides students with direct experience in building autonomous workflows, future-proofing their coding skills.</p><h3>Agent Evaluation</h3><p>Study techniques to evaluate agent accuracy, prevent hallucinations, and audit prompt security to construct commercial-grade LLM applications.</p>',
            'brain',
            'Flagship Bootcamps',
            'Python, LangChain, OpenAI API, HuggingFace',
            8
        ],
        [
            'DevOps, CI/CD & Cloud Orchestration',
            'devops-cloud-orchestration',
            'Learn how to build automated pipelines, package code into containers, and deploy scalable cloud architecture with high availability.',
            '<h3>DevOps, CI/CD & Cloud Orchestration</h3><p>This hands-on program covers modern deployment pipelines, cloud provisioning, and container monitoring for high-availability systems.</p><h4>Key Learning Outcomes</h4><ul><li><strong>Containerization:</strong> Writing optimized Dockerfiles and managing Docker Compose.</li><li><strong>CI/CD Pipelines:</strong> Building automated test, build, and deploy flows on GitHub Actions.</li><li><strong>Kubernetes Orchestration:</strong> Deploying, scaling, and managing container clusters on cloud infrastructure.</li></ul><p>Our curriculum is structured to prepare students for Cloud Certifications and system administration roles.</p><h3>Infrastructure as Code</h3><p>Learn how to manage cloud resources using Terraform scripts, enabling rapid and auditable system provisioning for software releases.</p>',
            'cloud',
            'Seasonal Certifications',
            'Docker, Kubernetes, GitHub Actions, AWS',
            9
        ]
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
        [
            'Mastering Full-Stack Coding: Roadmap for Aspiring Developers',
            'fullstack-developer-roadmap',
            'The ultimate curriculum and learning path from vanilla HTML/CSS to advanced React, Node.js, and SQL databases.',
            '<p>Aspiring developers often face a confusing path with numerous coding languages and libraries to choose from. A structured fullstack engineering curriculum helps students transition from basic styling to complex database designs. This roadmap outlines the key technologies and skills needed to succeed as a full-stack developer.</p><h3>1. The Frontend Foundation: HTML, CSS, and JavaScript</h3><p>Every web developer must master HTML for document structure and CSS for page layout and design. Once styling concepts are solid, JavaScript adds interactive functionality.</p><ul><li><strong>Responsive Design:</strong> Learn grid layouts and CSS media queries to build interfaces that work on all screen sizes.</li><li><strong>Vanilla JavaScript:</strong> Master DOM manipulation, event handlers, and asynchronous API queries before moving to framework libraries.</li></ul><h3>2. Backend Frameworks and Database Management</h3><p>Backend developers write the APIs and logic that run on servers and interact with databases. Node.js with Express or PHP with Laravel are excellent starting points.</p><ul><li><strong>Database Design:</strong> Master SQL databases (like MySQL or PostgreSQL) and understand table normalization, primary/foreign keys, and indexes.</li><li><strong>API Security:</strong> Learn to build secure endpoints using authentication methods like JSON Web Tokens (JWT) or session cookies.</li></ul><h3>Full-Stack Training FAQs</h3><p><strong>Q: How long does it take to become a full-stack developer?</strong><br>A: With structured training, practical coding bootcamps, and daily practice, most students can build intermediate full-stack projects within 6 to 9 months.</p><p><strong>Q: Which database system should beginners learn first?</strong><br>A: We recommend learning SQL databases (like SQLite or MySQL) first, as relational database concepts form the foundation of most enterprise software.</p><p>Ready to start your coding journey? Join the full-stack bootcamp at LSPL Academy.</p>',
            'Academy Board',
            'uploads/blog_fullstack-developer-roadmap.png',
            'Published'
        ],
        [
            'Demystifying hackIon 2.0: The Importance of Defensive Security',
            'cybersecurity-defensive-architecture',
            'Security lessons from hackIon 2.0. Understanding XSS, SQL Injection, CSRF, and building secure PHP/Laravel applications.',
            '<p>Cybersecurity is a vital aspect of modern software engineering. Our recent hackIon 2.0 hackathon highlighted critical security lessons, demonstrating that defensive coding practices are essential to protect enterprise systems from malicious attacks.</p><h3>1. Preventing SQL Injection and XSS Attacks</h3><p>SQL Injection and Cross-Site Scripting (XSS) remain common web application vulnerabilities. Developers must treat all user inputs as untrusted and sanitize them before database entry.</p><ul><li><strong>Prepared Statements:</strong> Use parameterized database queries to prevent SQL injections. Never concatenate raw user input into SQL query strings.</li><li><strong>Input Sanitization:</strong> Escape HTML tags in user inputs to prevent XSS attacks where malicious scripts are executed in customer browsers.</li></ul><h3>2. Implementing Secure Session Configurations</h3><p>Secure session management protects user accounts from hijacking. Developers should use secure cookies, HTTPS encryption, and token validation for all stateful user interactions.</p><h3>Cybersecurity FAQs</h3><p><strong>Q: What is defensive security?</strong><br>A: Defensive security involves designing and coding applications with proactive security controls (input validation, encryption, access limits) to prevent system compromises.</p><p><strong>Q: How do we prevent CSRF attacks in PHP?</strong><br>A: By generating unique, cryptographically secure anti-CSRF tokens for every user session and validating them with each state-changing POST request.</p><p>Interested in security training? Enroll in the defensive cybersecurity course at LSPL Academy.</p>',
            'Academy Board',
            'uploads/blog_cybersecurity-defensive-architecture.png',
            'Published'
        ],
        [
            'Rising Demand for Headless CMS and Jamstack Developers',
            'demand-headless-cms-jamstack-developers',
            'Why companies are moving from monolithic CMS to headless systems like Strapi, Contentful, Next.js, and Netlify.',
            '<p>Modern web development is moving away from traditional content management systems (CMS) toward decoupled headless architectures. The Jamstack framework offers improved performance, security, and scalability for business websites.</p><h3>1. Understanding Headless CMS</h3><p>A headless CMS (like Strapi, Sanity, or Contentful) acts as a content database, delivering articles via APIs. This separates the backend content editor from the frontend styling templates.</p><ul><li><strong>Frontend Flexibility:</strong> Developers can build frontends using React, Vue, or Next.js, pulling content from the headless API.</li><li><strong>Security Benefits:</strong> Since the database is decoupled from the public frontend, there are fewer entry points for attackers.</li></ul><h3>2. The Jamstack Deployment Pipeline</h3><p>Jamstack stands for JavaScript, APIs, and Markup. Websites are pre-rendered into static HTML files and served via global Content Delivery Networks (CDNs), resulting in fast page loads and reduced hosting costs.</p><h3>Headless Development FAQs</h3><p><strong>Q: What is the main difference between WordPress and a Headless CMS?</strong><br>A: WordPress is a monolithic system where backend content and frontend styling are tied together. A headless CMS serves content solely via APIs, letting developers design the frontend independently.</p><p><strong>Q: Is Jamstack suitable for e-commerce sites?</strong><br>A: Yes. By combining static frontends with payment APIs (like Stripe or Shopify), Jamstack provides fast, secure e-commerce storefronts.</p><p>Master modern web stacks. Sign up for our Jamstack developer course at LSPL Academy.</p>',
            'Academy Board',
            'uploads/blog_demand-headless-cms-jamstack-developers.png',
            'Published'
        ],
        [
            'Why Mobile App Engineering in Kotlin and Swift is Future-Proof',
            'mobile-app-engineering-kotlin-swift',
            'Analyzing the job market demand for native mobile developers. Why learning Swift and Kotlin secures high-paying tech roles.',
            '<p>The mobile application market continues to grow, creating high demand for skilled native developers. Learning Swift (iOS) and Kotlin (Android) provides programmers with a strong career path and high-paying job opportunities.</p><h3>1. The Value of Native Coding Skills</h3><p>While hybrid frameworks allow rapid prototyping, native app development remains essential for complex applications. Native coding offers better hardware integration, faster rendering, and fewer compatibility issues.</p><ul><li><strong>Swift for Apple Ecosystems:</strong> Apple’s Swift language is used to build apps for iOS, iPadOS, watchOS, and macOS, giving developers access to Apple’s premium customer base.</li><li><strong>Kotlin for Android Devices:</strong> Google’s Kotlin language is the modern standard for Android development, offering clean syntax and compatibility with Java libraries.</li></ul><h3>2. Career Opportunities and Job Market Demand</h3><p>Global corporations and tech startups require native mobile developers to optimize their platforms. Master native programming to access roles in app design, mobile security, and system integration.</p><h3>Mobile Development FAQs</h3><p><strong>Q: Should I learn iOS or Android development first?</strong><br>A: Both are excellent paths. If you have a Mac, Swift and iOS are great options. If you prefer open-source ecosystems, Kotlin and Android are widely adopted.</p><p><strong>Q: Are native mobile developers in high demand?</strong><br>A: Yes. Businesses require native engineers to build high-performance mobile apps with fluid animations, biometrics, and secure offline operations.</p><p>Start your mobile developer career. Join the native mobile bootcamp at LSPL Academy.</p>',
            'Academy Board',
            'uploads/blog_mobile-app-engineering-kotlin-swift.png',
            'Published'
        ],
        [
            'Understanding GA4 Telemetry and Technical SEO Audits',
            'understanding-ga4-telemetry-seo',
            'Guide to setting up Google Analytics 4, tracking user conversions, custom events, and executing basic technical SEO audits.',
            '<p>Data analytics and search engine optimization are crucial for understanding website performance and user behaviors. Setting up Google Analytics 4 (GA4) and running technical SEO audits helps businesses measure conversions and improve visibility.</p><h3>1. Setting Up GA4 Telemetry Pipelines</h3><p>Google Analytics 4 uses an event-based tracking model. Instead of tracking simple pageviews, GA4 tracks user interactions like button clicks, form submissions, and video plays.</p><ul><li><strong>Custom Event Tracking:</strong> Set up tags in Google Tag Manager to track actions like the "Get Estimate" button clicks.</li><li><strong>Conversion Funnels:</strong> Analyze where users drop off in your inquiry flow to optimize checkout or sign-up forms.</li></ul><h3>2. Running Basic Technical SEO Audits</h3><p>A technical SEO audit ensures search engines can index your website. Use tools like Google Search Console to check for crawl errors, missing meta tags, and slow mobile loading speeds.</p><h3>Analytics & SEO FAQs</h3><p><strong>Q: What is a custom event in GA4?</strong><br>A: A custom event tracks specific user interactions that are not captured by default, such as file downloads, video plays, or specific CTA clicks.</p><p><strong>Q: Why is technical SEO important for analytics?</strong><br>A: Technical SEO ensures your site gets crawled and indexed. Analytics then tracks the organic traffic generated by those indexed pages.</p><p>Learn to analyze web traffic. Enroll in our SEO and Analytics course at LSPL Academy.</p>',
            'Academy Board',
            'uploads/blog_understanding-ga4-telemetry-seo.png',
            'Published'
        ],
        [
            'How to Prepare for Your First Software Engineering Interview',
            'software-engineering-interview-prep',
            'Technical interviews can be stressful. We break down the resume tips, code challenges, system design preparation, and behavioral questions.',
            '<p>Technical interviews can be stressful. We break down the resume tips, code challenges, system design preparation, and behavioral questions to build student confidence.</p><h3>Cracking Code Interviews</h3><p>We run mock interviews and review coding challenges to prepare students for technical interviews at IT corporations.</p><ul><li>Resume Reviews: Creating clear, impact-focused portfolios.</li><li>LeetCode Drills: Solving array and string queries.</li><li>System Design: Structuring scale databases and APIs.</li></ul>',
            'Academy Board',
            'uploads/blog_software-engineering-interview-prep.png',
            'Published'
        ],
        [
            'The Best Programming Languages to Learn First for Beginners',
            'best-languages-for-beginners',
            'Choosing a language is the first step. We compare Python, JavaScript, and PHP, helping beginners choose based on career goals.',
            '<p>Choosing a language is the first step. We compare Python, JavaScript, and PHP, helping beginners choose based on career goals and learning curves.</p><h3>Choosing Your Learning Track</h3><p>We guide students to choose languages that match their career interests, whether in web apps, data analysis, or scripting.</p><ul><li>JavaScript: The standard language for interactive frontends.</li><li>Python: Best for data science and AI algorithms.</li><li>PHP: Widely used for database-driven CMS platforms.</li></ul>',
            'Academy Board',
            'uploads/blog_best-languages-for-beginners.png',
            'Published'
        ],
        [
            'Why Git and Version Control are Vital for Modern Teams',
            'git-version-control-vital',
            'Version control is a core team skill. Learn branching strategies, conflict resolution, pull requests, and Git commit guidelines.',
            '<p>Version control is a core team skill. Learn branching strategies, conflict resolution, pull requests, and Git commit guidelines in our teamwork blocks.</p><h3>Working in Code Teams</h3><p>Students learn to resolve merge conflicts, coordinate pull requests, and manage project branches using Git and GitHub.</p><ul><li>Branching Strategies: Managing development and master branches.</li><li>Conflict Resolution: Merging code changes safely.</li><li>Commit Guidelines: Writing descriptive commit descriptions.</li></ul>',
            'Academy Board',
            'uploads/blog_git-version-control-vital.png',
            'Published'
        ],
        [
            'Introduction to REST APIs: Building and Querying Endpoints',
            'introduction-to-rest-apis',
            'APIs connect modern web services. Learn how HTTP request methods (GET, POST, PUT, DELETE) and JSON data formats enable system communication.',
            '<p>APIs connect modern web services. Learn how HTTP request methods (GET, POST, PUT, DELETE) and JSON data formats enable system communication.</p><h3>Building Secure Endpoints</h3><p>We teach students to build RESTful routers, pass authorization headers, and validate JSON data structures in Express and Laravel.</p><ul><li>HTTP Operations: Mapping requests to database actions.</li><li>JSON Payload formats: Structuring request data packets.</li><li>Status Codes: Returning clear response headers (200, 404, 500).</li></ul>',
            'Academy Board',
            'uploads/blog_introduction-to-rest-apis.png',
            'Published'
        ],
        [
            'Why Cybersecurity Certifications Alone Are Not Enough',
            'cybersecurity-certifications-vs-skills',
            'Certifications look great on paper, but practical skills in network packets auditing, bug bounties, and safe coding are what secure top security jobs.',
            '<p>Certifications look great on paper, but practical skills in network packets auditing, bug bounties, and safe coding are what secure top security jobs.</p><h3>Building Practical Hacking Skills</h3><p>We emphasize hands-on packet capturing, API vulnerability assessments, and defensive configurations over raw exam memorization.</p><ul><li>Packet Analysis: Capturing network headers in Wireshark.</li><li>Vulnerability Scans: Testing systems for OWASP exploits.</li><li>Safe Code Audits: Reviewing seeder and query scripts for holes.</li></ul>',
            'Academy Board',
            'uploads/blog_cybersecurity-certifications-vs-skills.png',
            'Published'
        ],
        [
            'MERN Stack vs. Laravel MVC: Which Should You Learn in 2026?',
            'mern-stack-vs-laravel',
            'Node.js/React and PHP/Laravel are popular stacks. We compare their learning curves, scaling limits, and job demand to help you choose.',
            '<p>Node.js/React and PHP/Laravel are popular stacks. We compare their learning curves, scaling limits, and job demand to help you choose your study track.</p><h3>Comparing Modern Code Stacks</h3><p>We analyze MERN stack (MongoDB, Express, React, Node) and Laravel MVC to help students choose based on project scaling goals.</p><ul><li>MERN Stack: Ideal for single-page apps and WebSockets.</li><li>Laravel MVC: Best for database-driven enterprise platforms.</li><li>Job Market: Comparing regional openings and remote opportunities.</li></ul>',
            'Academy Board',
            'uploads/blog_mern-stack-vs-laravel.png',
            'Published'
        ],
        [
            'The Role of Data Structures and Algorithms in FAANG Interviews',
            'data-structures-algorithms-faang',
            'Big tech interviews evaluate algorithmic problem-solving. We explain arrays, lists, hash maps, binary trees, and dynamic programming.',
            '<p>Big tech interviews evaluate algorithmic problem-solving. We explain arrays, lists, hash maps, binary trees, and dynamic programming algorithms.</p><h3>Algorithmic Problem Solving</h3><p>Our training includes solving algorithmic challenges, analyzing time complexity (Big O), and structuring data hierarchies.</p><ul><li>Data Structures: Managing arrays, lists, and hash tables.</li><li>Search Algorithms: Implementing binary search and depth crawls.</li><li>Big O Notation: Analyzing memory and processing speeds.</li></ul>',
            'Academy Board',
            'uploads/blog_data-structures-algorithms-faang.png',
            'Published'
        ],
        [
            'How Coding Bootcamps Compare to Traditional Computer Science Degrees',
            'coding-bootcamps-vs-cs-degrees',
            'Bootcamps focus on practical coding projects while CS degrees cover theory. We analyze cost, duration, and job placement to compare them.',
            '<p>Bootcamps focus on practical coding projects while CS degrees cover theory. We analyze cost, duration, and job placement to compare them.</p><h3>Choosing Your Education Path</h3><p>We compare theoretical computer science curriculum with intensive coding bootcamps to help students select their career prep.</p><ul><li>Bootcamp Focus: Direct hands-on coding and Git portfolio building.</li><li>Degree Coverage: Math logic, operating systems, and compiler design.</li><li>Career Placement: Analyzing landing speeds for junior developer roles.</li></ul>',
            'Academy Board',
            'uploads/blog_coding-bootcamps-vs-cs-degrees.png',
            'Published'
        ],
        [
            'Mastering CSS Grid and Flexbox for Responsive Web Layouts',
            'mastering-css-grid-flexbox',
            'Responsive styling is a core frontend skill. Learn when to use CSS Grid for page layouts and Flexbox for single-direction element alignments.',
            '<p>Responsive styling is a core frontend skill. Learn when to use CSS Grid for page layouts and Flexbox for single-direction element alignments.</p><h3>Responsive Layout Construction</h3><p>We teach students to build styling structures that adjust dynamically to mobile and desktop browser coordinates.</p><ul><li>CSS Grid: Aligning elements in two-dimensional layouts.</li><li>Flexbox: Aligning lists and nav items in single directions.</li><li>Media Queries: Adjusting typography and padding by screen size.</li></ul>',
            'Academy Board',
            'uploads/blog_mastering-css-grid-flexbox.png',
            'Published'
        ],
        [
            'A Student’s Guide to Launching Your First Open-Source Project',
            'student-guide-open-source',
            'Open-source contributions build developer portfolios. Learn to search github repositories, create issues, submit pull requests, and collaborate.',
            '<p>Open-source contributions build developer portfolios. Learn to search github repositories, create issues, submit pull requests, and collaborate.</p><h3>Contributing to Public Code</h3><p>We guide students to fork repositories, address minor bugs, write documentation markdown, and submit pull requests on GitHub.</p><ul><li>Forking Repositories: Setting up local development branches.</li><li>Issue Tracking: Selecting beginner-friendly bug challenges.</li><li>Pull Requests: Requesting code reviews from project maintainers.</li></ul>',
            'Academy Board',
            'uploads/blog_student-guide-open-source.png',
            'Published'
        ]
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
