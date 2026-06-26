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

// longwaysoftronix_v2/db.php - Database connection and auto-initialization
$db_file = __DIR__ . '/lspl_main_v2.sqlite';
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

        CREATE TABLE IF NOT EXISTS industries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            description TEXT NOT NULL,
            content TEXT NOT NULL,
            icon TEXT NOT NULL,
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
        'sitemap_enabled' => '1',
        'site_title' => 'LSPL | Longway Softronix Pvt. Ltd. | Custom Software & Web Systems',
        'site_tagline' => 'Implementation of Your THOUGHTS...',
        'contact_email' => 'info@longwaysoftronix.com',
        'contact_phone' => '+91-8840010951',
        'contact_address' => '25/6 Shastri Nagar, Kanpur, UP 208005, India',
        'meta_description' => 'Longway Softronix Pvt. Ltd. (LSPL) is a registered IT consulting and custom software development company based in Kanpur, India, serving clients in the UK and globally. We build custom Laravel web apps, WordPress and Shopify e-commerce platforms, native Swift and Android Kotlin apps, and perform technical search engine optimization (SEO).',
        'hero_title' => 'We Implement Your Thoughts Into Code',
        'hero_subtitle' => 'Founded in 2014 in Kanpur, LSPL delivers class-leading custom web applications, native mobile apps, ERP systems, and search engine optimization campaigns for corporate clients in the UK, India, and globally.',
        'stats_projects' => '750+',
        'stats_students' => '12,000+',
        'stats_technologies' => '30+',
        'stats_experience' => '12+ Years',
        'canonical_url' => 'https://www.longwaysoftronix.com',
        'og_image_url' => 'logo.png',
        'schema_markup' => '{"@context":"https://schema.org","@type":"Organization","name":"Longway Softronix Pvt. Ltd.","alternateName":"LSPL","url":"https://www.longwaysoftronix.com","logo":"https://www.longwaysoftronix.com/logo.png","contactPoint":{"@type":"ContactPoint","telephone":"+91-8840010951","contactType":"customer service","areaServed":["IN","GB","US"],"availableLanguage":["en","hi"]},"sameAs":["https://www.facebook.com/longwaysoftronix","https://twitter.com/longwaysoftronix","https://www.linkedin.com/company/longwaysoftronix"]}',
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
            'Web Designing & UI/UX',
            'web-designing-ui-ux',
            'Figma UI/UX wireframes, user journeys, responsive design, CSS custom properties, and Web Core Vitals mapping.',
            '<h3>Figma Layout Prototyping & Responsive Web Interfaces</h3><p>At LSPL, we believe that design is not just how a website looks, but how it works. We create responsive layout systems and Figma wireframes tailored for global and UK user expectations. Our styling tokens use curated HSL color schemes that adapt to light and dark modes seamlessly.</p><h4>Our Creative Process</h4><ul><li><strong>Wireframing & Prototyping:</strong> We design interactive Figma wireframes to map the user journey before writing code.</li><li><strong>HSL Theme Architectures:</strong> Implementing CSS custom properties to swap themes dynamically.</li><li><strong>Core Web Vitals Optimization:</strong> Ensuring layout shifts (CLS) are minimized for better SEO.</li></ul><p>We build accessible interfaces (conforming to WCAG AA guidelines) that load instantly and provide smooth hover micro-animations. Our front-end team guarantees a consistent mobile, desktop, and tablet design blueprint, optimized for conversions.</p><h3>Why Work With Us?</h3><p>Our dedicated design team focuses on conversion rate optimization (CRO) and user-centric flows. We do not use generic templates. Every layout is crafted from scratch in Figma to match your unique brand identity, ensuring high retention and lower bounce rates.</p>',
            'layout',
            'Web & Software',
            'Figma, HTML5, CSS3, JavaScript, HSL Themes',
            1
        ],
        [
            'WordPress & CMS Development',
            'wordpress-cms-development',
            'Custom WordPress theme development, Gutenberg blocks, custom plugins, Joomla site setups, and Drupal customization.',
            '<h3>Custom WordPress Theme Development & CMS Setup</h3><p>We build high-performance CMS solutions on WordPress, Joomla, and Drupal. Rather than relying on bloated pre-made themes, we code custom layouts from scratch using clean PHP MVC structures. This ensures your admin panel is simple to edit, loads quickly, and is optimized for search rankings.</p><h4>CMS Optimization Blueprint</h4><ul><li><strong>Gutenberg Block Coding:</strong> Designing custom visual blocks for simple page building.</li><li><strong>Custom Plugins:</strong> Writing custom plugins to implement corporate directories, case files, and bookings.</li><li><strong>Security Hardening:</strong> Implementing database prefixes, firewall layers, and secure file permissions.</li></ul><p>We integrate robust caching policies (Redis, WP Super Cache) and database queries to handle heavy traffic spikes. Whether you need a corporate blog, a media portal, or a multisite setup, our team guarantees security, speed, and clean code.</p><h3>Advanced SEO Integration</h3><p>Every CMS site we deploy is pre-configured with SEO best practices, including custom meta tags, canonical URL structures, automatic XML sitemaps, and Schema markup. This ensures search engines index your pages correctly from day one.</p>',
            'file-text',
            'Web & Software',
            'WordPress Core, PHP, MySQL, Joomla, Drupal',
            2
        ],
        [
            'Shopify & E-Commerce Setups',
            'shopify-ecommerce-setups',
            'Shopify store setups, custom liquid themes, WooCommerce cart integration, and PrestaShop catalog pipelines for UK retail brands.',
            '<h3>Shopify Store Setups & Enterprise E-Commerce Portals</h3><p>We build robust e-commerce solutions that convert visitors into buyers. Our expertise includes custom Shopify store setups, coding custom Liquid layouts, integrating WooCommerce shopping carts, and scaling Magento or PrestaShop multi-store catalogs.</p><h4>E-Commerce Key Features</h4><ul><li><strong>Liquid Theme Engineering:</strong> Custom coding Shopify storefront layouts for faster load times.</li><li><strong>Multi-Store Catalog Sync:</strong> Synchronizing inventory data across multiple warehouses and marketplaces.</li><li><strong>Stripe & Wallet Integration:</strong> Building secure checkout pipelines with instant fraud checks.</li></ul><p>We configure secure transactional payment gateways (Stripe, PayPal, Apple Pay) and synchronize order metrics with external ERP and invoicing APIs. Our systems are optimized to load in under 1.5 seconds, even with catalog sizes exceeding 50,000 product nodes.</p><h3>SEO & Performance Optimization</h3><p>E-commerce success relies heavily on site speed. Slow load times kill conversions. We optimize image loading, leverage CDNs, minify CSS/JS bundles, and write efficient database queries to guarantee a sub-second page rendering experience for checkout flows.</p>',
            'shopping-bag',
            'E-Commerce Solution',
            'Shopify, Liquid, WooCommerce, PrestaShop, Stripe',
            3
        ],
        [
            'Laravel & PHP Web Apps',
            'laravel-php-web-apps',
            'Secure, database-driven custom web applications. Robust Eloquent ORM architectures and high-performance Laravel API systems.',
            '<h3>Custom Web Applications with Laravel & PHP MVC</h3><p>For custom web systems that require complex business logic, we use Laravel and CodeIgniter. We design robust database schemas, configure Eloquent ORM relationships, and build high-performance RESTful APIs to connect front-ends and mobile apps.</p><h4>Laravel Technical Capabilities</h4><ul><li><strong>Eloquent ORM Scaling:</strong> Optimizing database query indexes to prevent execution bottlenecks.</li><li><strong>Secure Authentication:</strong> Implementing OAuth2, Laravel Sanctum, and session-based credentials.</li><li><strong>Background Jobs Queuing:</strong> Offloading heavy notifications and emails to Redis queues.</li></ul><p>We implement secure authentication protocols, job queuing handlers for background emails, and Redis key-value caching to scale database read operations. Our software remains clean, structured, and simple to expand.</p><h3>Enterprise-Grade DevSecOps</h3><p>All our custom Laravel deployments follow strict security protocols, including CSRF protection, SQL injection prevention, input sanitization, and automated test coverages. We construct modular architectures that make feature scaling easy and safe.</p>',
            'cpu',
            'Web & Software',
            'PHP 8.2, Laravel MVC, MySQL, CodeIgniter',
            4
        ],
        [
            'Full-Stack & Mobile Development',
            'fullstack-mobile-development',
            'Native Swift iOS application design and Google Kotlin Compose Android apps. End-to-end full stack development.',
            '<h3>Native Mobile App Engineering: Swift & Kotlin Compose</h3><p>We engineer native mobile applications that feel fluid and premium. Our iOS applications are written in Swift using SwiftUI layout frameworks. Our Android applications utilize Kotlin, Coroutines, and Jetpack Compose for declarative UI flows.</p><h4>Mobile Core Features</h4><ul><li><strong>SwiftUI & Compose Layouts:</strong> Building fluid interfaces with high-frame-rate animations.</li><li><strong>Offline-First Sync:</strong> Utilizing SQLite/Room databases with background network sync.</li><li><strong>Keychain Cryptography:</strong> Storing client sessions and API tokens in hardware-level keychains.</li></ul><p>We focus on offline-first databases, secure authentication keys, push notification triggers, and location services. We handle the entire App Store and Google Play deployment checklist, ensuring compliance with Apple and Google design guidelines.</p><h3>Cross-Platform Options</h3><p>In addition to native codebases, we also build React Native applications to target both iOS and Android platforms from a single codebase, reducing initial development time and maintenance overheads by up to 50%.</p>',
            'smartphone',
            'Web & Software',
            'iOS Swift, Android Kotlin, Jetpack Compose, React Native',
            5
        ],
        [
            'Professional SEO & Digital Marketing',
            'seo-digital-marketing',
            'Technical diagnostic SEO audits, keyword rankings, local search optimization in Kanpur and UK, backlink generation, and Google Ads PPC.',
            '<h3>Technical SEO Audits & Local Search Marketing Campaigns</h3><p>Maximize your organic search engine ranking. We conduct technical SEO diagnostic audits, configure XML sitemaps, set up canonical tags, and write custom JSON-LD schemas. We research high-value keywords for the UK, India (Kanpur), and global markets.</p><h4>SEO Implementation Guide</h4><ul><li><strong>Indexing Audits:</strong> Eliminating duplicate paths, configuring robots.txt, and canonical tags.</li><li><strong>Structured Data:</strong> Injecting Organization, Service, and Article schemas for search appearance.</li><li><strong>Google Analytics 4:</strong> Building conversion funnels to track visitors and leads.</li></ul><p>We also manage search engine PPC ads (Google Ads), social advertising (Meta Ads), and email campaigns to drive leads. Our reports provide clear conversions telemetry metrics from Google Analytics 4, helping you track your return on investment.</p><h3>White-Hat Link Building</h3><p>Our digital marketing strategies include securing high-authority editorial links and local directory citations. We optimize your content structure to rank for intent-based searches, ensuring long-term organic visitor growth.</p>',
            'search',
            'Marketing & Search',
            'Google Search Console, Semrush, Google Ads PPC',
            6
        ],
        [
            'Headless CMS & JAMstack Development',
            'headless-cms-jamstack',
            'Decoupled web applications utilizing Next.js, React, and headless CMS frameworks like Strapi or headless WordPress.',
            '<h3>Headless JAMstack Architectures: Next.js & Strapi CMS</h3><p>JAMstack represents the future of web performance. We decouple the front-end (built with Next.js or React) from the content repository. We connect to headless CMS engines (Strapi, Contentful, Sanity) via GraphQL or REST APIs.</p><h4>Headless Core Benefits</h4><ul><li><strong>Sub-Second Loading:</strong> Pre-rendering static HTML pages for instant loading.</li><li><strong>Zero DB Exposure:</strong> Hosting front-ends on edge CDN networks to eliminate SQL injection risks.</li><li><strong>Flexible Editing:</strong> Giving editors a simple admin panel while developers use React code.</li></ul><p>This headless configuration ensures maximum security (no direct database exposure), sub-second page loads, and top-tier Core Web Vitals rankings that search engines favor.</p><h3>Vercel & Netlify Edge Deployments</h3><p>By hosting your Next.js application on global serverless CDNs, your pages load instantly for visitors regardless of their location, reducing Time to First Byte (TTFB) and boosting search engine placement.</p>',
            'globe',
            'Web & Software',
            'Next.js, React, Strapi CMS, Headless WordPress',
            7
        ],
        [
            'Data Analytics & Business Intelligence',
            'data-analytics-bi',
            'Custom corporate database pipelines. Integrating ERP metrics with BigQuery, PowerBI dashboards, and Google Analytics 4 reports.',
            '<h3>Data Pipelines & Custom BI Dashboards</h3><p>Turn raw database logs into actionable insights. We build custom data pipelines using Python to ingest e-commerce and ERP metrics into Cloud Warehouses like Google BigQuery.</p><h4>Analytics Technical Workflow</h4><ul><li><strong>ETL Pipelines:</strong> Automating data extraction, transformation, and load operations.</li><li><strong>BigQuery Warehousing:</strong> Storing and querying terabytes of business records in seconds.</li><li><strong>Visual Dashboards:</strong> Building PowerBI, Tableau, and React charts for real-time tracking.</li></ul><p>We build interactive dashboards (using PowerBI, Tableau, or custom React graphs) that display live revenue, visitor conversions, and inventory tracking. Our systems help corporate stakeholders make data-driven choices.</p><h3>Data Governance & Security</h3><p>We configure role-based access control (RBAC), row-level security, and audit trails to keep your analytics clean and compliant with GDPR. Your raw records remain completely protected.</p>',
            'bar-chart',
            'Marketing & Search',
            'BigQuery, Google Cloud, PowerBI, Tableau, Python',
            8
        ],
        [
            'Cloud Deployments & Server Setup',
            'cloud-server-setup',
            'Installation and configuration of custom servers or cloud deployments across AWS, Azure, Google Cloud, and Oracle Cloud.',
            '<h3>Cloud Infrastructure & Custom Server Setup</h3><p>We configure custom server installations and manage secure cloud deployments across AWS, Microsoft Azure, Google Cloud (GCP), and Oracle Cloud. We ensure high-availability routing, configure secure firewalls, set up SSL, and automate data backups.</p><h4>Cloud & Server Core Deliverables</h4><ul><li><strong>Cloud Architectures:</strong> Custom AWS EC2, Azure VMs, and GCP Compute Engine nodes.</li><li><strong>Custom Servers:</strong> Physical server configuration, virtualization (VMware/KVM), and Linux server hardening.</li><li><strong>CI/CD Pipelines:</strong> Automated code deployments with GitHub Actions and Docker containers.</li></ul><p>We optimize server loading metrics and configure load balancers to scale your web applications and API servers seamlessly, maintaining 99.9% uptime.</p><h3>DevOps Automation</h3><p>We build automated infrastructure-as-code (IaC) deployment pipelines using Terraform and Ansible. This makes spinning up test servers or replicating environments rapid, safe, and fully documented.</p>',
            'server',
            'Infrastructure',
            'AWS, Microsoft Azure, Google Cloud, Oracle Cloud, Linux, Docker, Nginx',
            9
        ],
        [
            'Custom SaaS Platforms & Microservices',
            'custom-saas-platforms',
            'We design, build, and deploy multi-tenant SaaS products and high-performance microservices tailored to your business model.',
            '<h3>Custom SaaS Platforms & Microservices</h3><p>Building a successful SaaS platform requires careful architectural planning, security, multi-tenant databases, and seamless scalability. At LSPL, we construct custom SaaS platforms and microservices that grow with your user base.</p><h4>Our Development Approach</h4><ul><li><strong>Multi-Tenancy Architecture:</strong> Secure database separation or partitioned single-database models.</li><li><strong>Scalable Microservices:</strong> Deployed in containerized Docker environments with automated API gateways.</li><li><strong>Subscription Billing Integration:</strong> Stripe, PayPal, or custom regional gateways with auto-invoicing.</li></ul><p>We focus on clean, decoupled code that makes it simple to extend SaaS features over time, providing high uptime and modular growth path.</p>',
            'cpu',
            'Web & Software',
            'Laravel, Vue.js, Node.js, Docker',
            10
        ],
        [
            'API Integration & Webhook Gateways',
            'api-integrations',
            'Unify your systems by building robust, secure REST/GraphQL APIs and serverless webhook pipelines for seamless data flow.',
            '<h3>API Integration & Webhook Gateways</h3><p>Modern enterprises rely on multiple SaaS tools. We integrate, synchronize, and extend your platforms with custom REST, SOAP, and GraphQL APIs.</p><h4>Integration Specializations</h4><ul><li><strong>Webhook Handlers:</strong> High-throughput serverless endpoints handling real-time data syncs.</li><li><strong>Payment & SMS Gateways:</strong> Integration of secure verification and transaction APIs.</li><li><strong>Enterprise CRM/ERP Sync:</strong> Connecting front-end applications with Salesforce, SAP, or custom systems.</li></ul><p>Our API pipelines are fortified with OAuth authorization, API keys, rate limiters, and complete logs for auditing and debugging.</p>',
            'git-branch',
            'Web & Software',
            'PHP, Python, AWS API Gateway, RabbitMQ',
            11
        ]
    ];

    $stmt = $db->prepare("INSERT INTO services (title, slug, description, content, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute($s);
    }

    // Seed industries
    $industries = [
        [
            'School ERP Systems',
            'school-erp',
            'All-in-one school administration systems. Aggregates student attendance, parent portals, grades, and fee billing database modules.',
            '<h3>School ERP Administration Systems</h3><p>We build custom School ERP platforms that streamline school operations. Features include student enrollment, attendance logs, exam marksheets, calendar scheduling, and parent portal panels. All fee transaction details are logged in secure SQLite/MySQL databases with automatic email invoice receipts.</p><h4>ERP Core Modules</h4><ul><li><strong>Academic Calendar:</strong> Automatic timetable scheduling and class allocations.</li><li><strong>Online Fee Collection:</strong> Payment gateway integrations with automatic receipt generation.</li><li><strong>Parent-Teacher Board:</strong> Dynamic messaging boards and grade reports.</li></ul><p>Our School ERP solutions are built on lightweight database systems, making them fast and highly secure for students, teachers, and administrators.</p><h3>Custom Reporting</h3><p>Generate academic growth charts, attendance trends, and financial reports instantly. Administrators can filter metrics by class, date, or category to make database-backed choices.</p>',
            'graduation-cap',
            1
        ],
        [
            'Hospital Management Systems',
            'hospital-management',
            'Secure EHR record aggregation, doctor scheduling calendars, OPD clinic billing databases, and patient portal setups.',
            '<h3>Hospital EHR & Medical Operations Software</h3><p>Streamline healthcare administration. Our Hospital systems manage doctor scheduling, patient clinical Electronic Health Records (EHR), OPD consultation queues, ward allocations, and billing. The platform conforms to patient data privacy guidelines.</p><h4>Healthcare Key Features</h4><ul><li><strong>EHR Security:</strong> Encrypted storage for patient medical histories.</li><li><strong>OPD Queue Manager:</strong> Real-time patient appointment queuing logs.</li><li><strong>Insurance Billing:</strong> Automated cost calculation modules and invoice tracking.</li></ul><p>We design clinical database systems that prevent data leaks, simplify auditing, and ensure doctors have instant access to patient histories.</p><h3>Patient Portals</h3><p>Secure web interfaces for patients to view test lab reports, book appointment slots, and consult online prescriptions securely, cutting clinic desk overheads.</p>',
            'activity',
            2
        ],
        [
            'Pharmacy & Medical Store POS',
            'pharmacy-billing',
            'Pharmacy point-of-sale systems, inventory batch tracking, medicine expiry notifications, and drug database structures.',
            '<h3>Pharmacy POS & Expiry Tracking Database</h3><p>Avoid stock issues. Our Pharmacy POS software tracks batch numbers, medicine expiries, supplier records, and handles rapid invoice printing. It keeps your medical store inventory synchronized in real time.</p><h4>Pharmacy POS Capabilities</h4><ul><li><strong>Expiry Notifications:</strong> Automated warnings when batches are close to expiration.</li><li><strong>Barcode Scanning:</strong> Rapid drug checkout with inventory database lookup.</li><li><strong>Supplier Billing:</strong> Log supplier purchase orders and tax calculations.</li></ul><p>Keep your pharmacy compliant, organized, and profitable with our custom point-of-sale systems.</p><h3>Analytics Integrations</h3><p>Track sales reports by drug category or time period. Predict future inventory demand trends based on historical billing history, optimizing supply orders.</p>',
            'pill',
            3
        ],
        [
            'Restaurant & Cafe POS',
            'restaurant-pos',
            'Table reservation managers, kitchen display screens, online food ordering systems, and POS terminal interfaces.',
            '<h3>Restaurant Table Reservation & Billing POS</h3><p>Scale food operations. We build restaurant POS software featuring interactive table layout managers, kitchen ticket print channels, menu custom options, and mobile ordering apps.</p><h4>POS Features</h4><ul><li><strong>Kitchen Display System:</strong> Sending order tickets directly to kitchen monitors.</li><li><strong>Table Manager:</strong> Drag-and-drop table reservations and bill splits.</li><li><strong>Inventory Tracking:</strong> Automatic deduction of ingredients based on sold dishes.</li></ul><p>Our restaurant systems increase order speeds, eliminate errors, and provide real-time sales reporting.</p><h3>Multi-Store Management</h3><p>Scale from a single cafe to a national franchise. Control menus, staff permissions, and inventory levels across all branch nodes from a central dashboard.</p>',
            'utensils',
            4
        ],
        [
            'Hotel Room Reservation Engines',
            'hotel-booking',
            'Hotel room availability booking engines, guest reservation calendars, and dynamic pricing calculators.',
            '<h3>Hotel Booking & Channel Management Portals</h3><p>Manage lodging check-ins. Our Hotel booking engine aggregates room types, reservation availability calendars, guest records, and manages dynamic seasonal pricing changes.</p><h4>Hotel Portal Capabilities</h4><ul><li><strong>Availability Calendar:</strong> Live room occupancy calendars with color coding.</li><li><strong>Dynamic Pricing:</strong> Adjust room rates based on seasonality and local holidays.</li><li><strong>Guest Profiling:</strong> Secure guest logs with reservation histories.</li></ul><p>Increase direct bookings and optimize room occupancy with our robust hotel booking portals.</p><h3>API Channel Integrations</h3><p>Sync availability calendars dynamically with external OTAs (Booking.com, Airbnb, Expedia) to eliminate double-booking overlaps.</p>',
            'hotel',
            5
        ],
        [
            'Salon & Spa Appointment Schedulers',
            'salon-scheduling',
            'Salon appointment scheduling calendars, therapist slot booking engines, and SMS reminder notifications.',
            '<h3>Salon Slot Scheduling & Spa Calendars</h3><p>Reduce client no-shows. We build salon reservation calendars that manage stylist slot availabilities, log customer histories, and trigger SMS/Email reminders.</p><h4>Salon Scheduler Modules</h4><ul><li><strong>Stylist Calendars:</strong> Separate slot booking grids for each staff member.</li><li><strong>SMS Reminders:</strong> Automated notifications sent 24 hours before appointment slots.</li><li><strong>Package Billing:</strong> Managing salon membership points and gift vouchers.</li></ul><p>Keep your salon calendar full and organize your staff shifts efficiently.</p><h3>Analytics dashboard</h3><p>Analyze stylist utilization rates, average ticket values, and recurring client metrics to optimize scheduling policies and maximize spa revenue.</p>',
            'clock',
            6
        ],
        [
            'Real Estate Portal Listings',
            'real-estate',
            'Property listing databases, MLS integration, real estate search filters, and agent CRM boards.',
            '<h3>Real Estate Property Search Directories</h3><p>Showcase properties online. We build real estate listings portals with advanced search parameters (location, budget, layout), agent contact forms, and interactive Google Maps.</p><h4>Property Directory Features</h4><ul><li><strong>Property Search Engine:</strong> Multi-variable filters for location and price.</li><li><strong>Agent CRM Boards:</strong> CRM boards for realtors to manage leads.</li><li><strong>Image Slideshows:</strong> High-resolution property image uploads and virtual tours.</li></ul><p>Help buyers find their dream homes with our fast, search-optimized real estate portals.</p><h3>Agent Portals</h3><p>Enable real estate agents to register, upload listings, edit pricing tags, and communicate directly with interested buyer leads from their profile dashboard.</p>',
            'home',
            7
        ],
        [
            'Fintech & Micro-Lending Pipelines',
            'fintech-lending',
            'Lending approval software pipelines, microfinance loan interest calculators, and customer credit check records.',
            '<h3>Fintech Lending Pipelines & Credit Calculators</h3><p>Secure micro-finance workflows. We build custom fintech loan portals with automated interest calculations, document upload modules, and agent approval checklists.</p><h4>Fintech System Highlights</h4><ul><li><strong>Loan Calculators:</strong> Interactive sliders to compute EMI and interest rates.</li><li><strong>Document Uploads:</strong> Secure KYC document verification vaults.</li><li><strong>Approval Checklists:</strong> Step-by-step credit check logs for lending agents.</li></ul><p>Ensure compliance, prevent fraud, and accelerate loan approvals with our secure fintech platforms.</p><h3>Security Vaults</h3><p>Encrypt all sensitive financial details, bank statements, and ID documents using modern database encryption standards, maintaining audit trust.</p>',
            'credit-card',
            8
        ],
        [
            'Logistics & Package Trackers',
            'logistics-tracking',
            'Logistics delivery tracking platforms, shipment dispatch calendars, and warehouse parcel stock databases.',
            '<h3>Logistics Parcel Tracking & Fleet Dashboards</h3><p>Audit packages on the move. Our logistics database systems track parcel status updates, dispatch timelines, and dispatch details for shipping agencies.</p><h4>Logistics Capabilities</h4><ul><li><strong>Barcode Tracking:</strong> Scanners log parcel check-ins at each warehouse node.</li><li><strong>Dispatch Calendars:</strong> Scheduling delivery dispatches and shifts.</li><li><strong>Customer Portal:</strong> Real-time status updates via tracking numbers.</li></ul><p>Increase delivery reliability and optimize warehouse sorting operations.</p><h3>Dynamic Fleet Routes</h3><p>Integrate shipping route maps to guide drivers, reducing average parcel delivery times and fleet fuel costs.</p>',
            'truck',
            9
        ],
        [
            'Gym & Fitness Membership Portals',
            'gym-membership',
            'Gym member registration check-ins, automated subscription fee billing, and trainer booking schedules.',
            '<h3>Gym Membership Systems & Trainer Slots</h3><p>Automate fitness center billing. We build gym software with member card check-ins, monthly subscription auto-billing, and personal trainer reservation lists.</p><h4>Gym Portal Features</h4><ul><li><strong>Check-in Logs:</strong> Logging member entry times via barcode cards.</li><li><strong>Subscription Billing:</strong> Automatic credit card renewals and invoice logs.</li><li><strong>Trainer Schedulers:</strong> Shift calendars for personal training slots.</li></ul><p>Organize your members, reduce subscription payment friction, and manage trainers.</p><h3>Mobile Workout Feeds</h3><p>Give gym members access to workouts, workout logs, and calendar schedules directly from their profile pages, enhancing user retention.</p>',
            'heart',
            10
        ],
        [
            'Travel Agency Itinerary Builders',
            'travel-itineraries',
            'Custom tourist itinerary planner builders, flight hotel booking interfaces, and tour pricing calculators.',
            '<h3>Travel Itinerary Builders & Booking Panels</h3><p>Simplify trip planning. We design custom tour planners that compile flight, hotel, and activity schedules into mobile-friendly PDF itineraries.</p><h4>Itinerary Builder Highlights</h4><ul><li><strong>Drag-and-Drop Builders:</strong> Easily structure multi-day travel itineraries.</li><li><strong>Costing Engine:</strong> Real-time calculations of tour package costs.</li><li><strong>API Flight Feeds:</strong> Integrating live availability feeds.</li></ul><p>Build custom tours, print client travel itineraries, and manage travel agent booking margins.</p><h3>Interactive Maps</h3><p>Embed interactive day-by-day mapping routes in client travel plans to guide tourists on their destination schedules.</p>',
            'compass',
            11
        ],
        [
            'Law Firm Case Management Tools',
            'legal-case-manager',
            'Law firm court hearing calendars, client case file storage databases, and legal time tracking invoices.',
            '<h3>Legal Case Managers & Law Firm Invoicing</h3><p>Secure case records. Our law firm software manages client files, court hearing calendars, legal document templates, and lawyer billable hours tracking.</p><h4>Legal Tool Features</h4><ul><li><strong>Case Timeline:</strong> Chronological logs of court actions and filings.</li><li><strong>Hearing Calendars:</strong> Auto-alerts for court hearing deadlines.</li><li><strong>Time Billing:</strong> Log billable hours and auto-generate client invoices.</li></ul><p>Protect client privilege with secure database encryption, and streamline law firm operations.</p><h3>Client Portals</h3><p>Secure legal portals where clients can review contract drafts, upload case files, and pay invoices, reducing lawyer administration overheads.</p>',
            'briefcase',
            12
        ],
        [
            'HR Recruiters & ATS Software',
            'hr-ats',
            'Applicant Tracking System (ATS), job board posting boards, and recruitment interview scheduling pipelines.',
            '<h3>HR Applicant Tracking System (ATS)</h3><p>Scale hiring operations. We build custom HR candidate databases with resume parsing, job application pipelines, and interviewer feedback scorecards.</p><h4>HR ATS Highlights</h4><ul><li><strong>Candidate Pipelines:</strong> Visual boards showing applicant stages (Applied, Interview, Offer).</li><li><strong>Resume Parsers:</strong> Automatically extract candidate skills from PDF resumes.</li><li><strong>Feedback Scorecards:</strong> Multi-interviewer scoring grids.</li></ul><p>Find the best talent, coordinate interviews, and manage hiring team feedback.</p><h3>Careers Page Integrations</h3><p>Sync job boards dynamically with your company website, enabling candidates to apply directly to candidate tables.</p>',
            'users',
            13
        ],
        [
            'Car Rental Reservation Portals',
            'car-rental',
            'Secure car rental booking software, vehicle fleet availability grids, and Stripe pre-authorized holds.',
            '<h3>Enterprise Car Rental &amp; Fleet Logistics Systems</h3><p>Modern vehicle hire platforms require robust, real-time database management to handle vehicle availabilities, driver shifts, and secure digital checkouts. At LSPL, we build custom car rental reservation portals tailored to simplify fleet management, automate booking contracts, and optimize dispatcher workflows.</p><h4>Dynamic Fleet Availability &amp; Calendar Feeds</h4><p>Our custom software eliminates double-booking errors by keeping a centralized occupancy matrix. Every vehicle is tracked dynamically using status checks (Available, In Service, Rented, Maintenance). Real-time calendar grids display booking blocks, allowing dispatchers to drag-and-drop bookings, assign drivers, and edit rental agreements with ease.</p><h4>Stripe Payment Holds &amp; Security Vaults</h4><p>Security is critical for rental transactions. We integrate robust payment gateways (like Stripe) that support pre-authorized security holds. This holds deposit funds on a customer\\\'s card during the rental period and automatically releases them post-inspection. All customer data and driver licenses are stored in encrypted database tables complying with local privacy frameworks.</p><h4>Key Operational Features</h4><ul><li><strong>Live Fleet Status Matrices:</strong> Color-coded reservation grids detailing vehicle status, fuel levels, and service schedules.</li><li><strong>Automated Driver Scheduling:</strong> Assigning local dispatchers and drivers to airport transfer bookings with SMS notifications.</li><li><strong>Digital Rental Agreements:</strong> Auto-generating PDF invoices and signature-ready contracts on checkout completion.</li><li><strong>GPS Telemetry Integrations:</strong> Integrating real-time coordinate logs and mileage trackers into backend analytics.</li></ul><p>Whether you manage a boutique local car hire in Kanpur or a large-scale corporate fleet in the UK, our custom booking systems ensure speed, security, and absolute database integrity.</p>',
            'car',
            14
        ],
        [
            'Event Ticketing & QR Check-In',
            'event-ticketing',
            'Event ticketing reservation systems, seat map designers, and mobile QR ticket validation scanners.',
            '<h3>Event Reservation Engines & QR Check-In</h3><p>Sell tickets directly. We design event registration platforms with seating map selections, barcode invoice generation, and QR scan apps for door check-ins.</p><h4>Event Ticketing Capabilities</h4><ul><li><strong>Dynamic Seat Maps:</strong> Visual seating charts with variable pricing tiers.</li><li><strong>QR Ticket Invoices:</strong> Dynamic barcode tickets sent via email.</li><li><strong>Door Scan API:</strong> Mobile scan endpoints for rapid door validation.</li></ul><p>Maximize ticket sales, eliminate duplicate passes, and streamline gate management.</p><h3>Sales Analytics</h3><p>Dashboard charts displaying ticket revenue, seat occupancies, and check-in rates in real time, helping event coordinators plan promotion campaigns.</p>',
            'ticket',
            15
        ],
        [
            'AgTech Yield Software',
            'agtech-yield',
            'Agriculture crop monitoring databases, harvest calendar schedules, and soil moisture sensor dashboards.',
            '<h3>AgTech Crop Monitoring & Harvest Databases</h3><p>Track farm outputs. We build agriculture portals that log crop yields, soil moisture logs, weather forecasts, and harvest scheduling charts.</p><h4>AgTech Key Features</h4><ul><li><strong>Yield Logging:</strong> Database tables to track crop outputs per field unit.</li><li><strong>Sensor Feeds:</strong> Integrating telemetry logs from moisture and temperature grids.</li><li><strong>Harvest Calendars:</strong> Dispatch calendars for harvest logistics.</li></ul><p>Audit farm parameters, track crop yields, and plan harvest operations.</p><h3>Agronomy Dashboards</h3><p>Generate crop analytics trends across different soil sectors to optimize fertilizer applications and increase annual farm harvests.</p>',
            'leaf',
            16
        ],
        [
            'Bespoke CRM & Enterprise ERP Systems',
            'bespoke-crm-erp',
            'Integrated dashboard software for automating sales pipelines, customer relations, employee attendance, inventory tracking, and billing.',
            '<h3>Bespoke CRM & Enterprise ERP Solutions</h3><p>Generic CRM and ERP systems often force companies to change their workflows to fit the software. Our custom CRM and ERP developments align perfectly with your existing operations.</p><h4>Key Enterprise Features</h4><ul><li><strong>Automated Workflows:</strong> Streamline sales, HR requests, lead generation, and invoicing.</li><li><strong>Role-Based Analytics:</strong> Dashboards customized for field executives, team managers, and C-level directors.</li><li><strong>Custom POS & Inventory:</strong> Seamless tracking of physical assets and warehouse storage.</li></ul><p>We build secure systems that integrate with your existing databases, keeping your operations streamlined and protected.</p><h3>Continuous Support</h3><p>Our engineering team ensures high system availability, provides custom API modifications, and implements regular security updates for server nodes.</p>',
            'briefcase',
            17
        ]
    ];

    $stmt = $db->prepare("INSERT INTO industries (title, slug, description, content, icon, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($industries as $ind) {
        $stmt->execute($ind);
    }

    // Seed pages
    $pages = [
        ['About Us', 'about-us', '<h3>Who We Are</h3><p>Longway Softronix Pvt. Ltd. (LSPL) is a registered IT consulting and custom software development company founded in 2014 in Kanpur, India. Over the last decade, we have scaled our operations to support corporate clients, e-commerce brands, and startups in the UK, India, and globally.</p><p>Our core philosophy is the <strong>Implementation of your Thoughts</strong>.</p><h3>Leadership & Direction</h3><p>LSPL is led by our Founder and Director, <strong>Pawan K Singh</strong>. Under his engineering leadership, we have successfully shipped custom software platforms, SaaS architectures, mobile applications, and technical SEO campaigns. Pawan is a seasoned full-stack engineer and engineering leader whose vision drives our engineering standards and dedication to client success.</p><p>Learn more about his engineering portfolio and publications at <a href="https://pawanksingh.com" target="_blank" rel="noopener">pawanksingh.com</a>.</p><h3 style="margin-top: 3rem; margin-bottom: 2rem;">Our Journey</h3><div class="about-timeline"><div class="timeline-item"><div class="timeline-year">2014</div><div class="timeline-content"><h4>Company Foundation</h4><p>LSPL was incorporated in Kanpur, India, with a vision to translate client thoughts into high-performance desktop and local web systems.</p></div></div><div class="timeline-item"><div class="timeline-year">2017</div><div class="timeline-content"><h4>Enterprise & Database Scaling</h4><p>Scaled our services to deploy robust Laravel backends, multi-tenant databases, and custom ERP workflows for educational and healthcare institutions.</p></div></div><div class="timeline-item"><div class="timeline-year">2020</div><div class="timeline-content"><h4>Global Expansion</h4><p>Broadened our footprint internationally, establishing long-term delivery partnerships with corporate businesses and e-commerce clients in the UK and USA.</p></div></div><div class="timeline-item"><div class="timeline-year">2023</div><div class="timeline-content"><h4>Educational Wing & AI Lab</h4><p>Launched LSPL Academy to train developers through bootcamps like weBOShop and hackIon, and inaugurated the LSXPL AI Research Lab.</p></div></div><div class="timeline-item"><div class="timeline-year">2026</div><div class="timeline-content"><h4>Agentic Computing & DevSecOps</h4><p>Pioneering low-latency outbound AI calling agents, secure cloud SaaS platforms, and advanced prompt injection defense audits.</p></div></div></div>', 1, 1],
        ['Privacy Policy', 'privacy-policy', '<h3>Privacy Policy</h3><p>Your privacy is important to LSPL. This policy outlines how we collect, store, and process your data across our platforms in compliance with global standards, including the UK GDPR.</p><p>We only request personal information when it is truly needed to provide a custom development quote or to manage your admin portal account.</p>', 1, 2],
        ['Terms of Service', 'terms-conditions', '<h3>Terms of Service</h3><p>By accessing our website and utilizing our custom software development services, you agree to comply with and be bound by these terms. All software delivery packages, server access conditions, and source file ownership are subject to our formal client agreements.</p>', 1, 3],
        ['Partnership Program (Franchise)', 'franchise', '<h3>LSPL Partnership & Franchise Program</h3><p class="lead">Grow your IT business by partnering with Longway Softronix Pvt. Ltd. (LSPL). We offer a comprehensive franchise model designed for entrepreneurs, technology agencies, and educational institutions looking to deliver top-tier software solutions and IT training.</p><div class="row mt-4"><div class="col-md-6 mb-4"><div class="glass-card h-100"><h4 class="text-primary" style="margin-top: 0; display: flex; align-items: center;"><i data-lucide="briefcase" class="me-2 icon-inline"></i>Business Partnership</h4><p class="mt-3">Collaborate with LSPL to deliver enterprise-grade Laravel web apps, custom Shopify e-commerce setups, native iOS/Android mobile apps, and technical SEO campaigns. Tap into our extensive resource pool and technology blueprints.</p><ul><li>Access to proprietary ERP & POS product codebases</li><li>Shared engineering resources and technical support</li><li>Co-branded marketing collaterals and case studies</li><li>Exclusive client referral pipeline in your territory</li></ul></div></div><div class="col-md-6 mb-4"><div class="glass-card h-100"><h4 class="text-secondary" style="margin-top: 0; display: flex; align-items: center;"><i data-lucide="graduation-cap" class="me-2 icon-inline"></i>Academy Franchise</h4><p class="mt-3">Establish a certified LSPL IT Academy branch in your city. Offer our flagship bootcamps like <strong>weBOShop 2.0</strong> and <strong>hackIon 2.0</strong> with fully-structured industry-vetted curriculums and student registers.</p><ul><li>Structured curriculum guides and lab materials</li><li>Instructor training, certification, and audits</li><li>Centralized student registration and certificate registers</li><li>Placement assistance network for graduates</li></ul></div></div></div><h3 class="mt-4">Why Partner with LSPL?</h3><p>Over the past decade, LSPL has built a reputation for excellence, delivering 750+ projects globally and training over 12,000 students. Our franchise model ensures that you get operational support from day one, helping you minimize risks and maximize returns.</p><ul><li><strong>Be Your Own Boss:</strong> Run a successful software or training business with predictable results.</li><li><strong>Verified Success Record:</strong> Benefit from a brand that clients and students trust.</li><li><strong>Continuous Innovation:</strong> Access our latest tech developments (including outbound AI voice agents and prompt injection defense models).</li></ul><div class="glass-card mt-5" id="franchise-form-container"><h3 style="margin-top: 0; display: flex; align-items: center;"><i data-lucide="file-text" class="me-2 icon-inline text-primary"></i>Apply for LSPL Franchise / Partnership</h3><p class="text-muted mb-4">Submit the application form below. Our business development team will review your application and get in touch within 2-3 business days.</p><form id="franchise-application-form"><div class="row"><div class="col-md-6 mb-3"><label for="f_name" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Full Name *</label><input type="text" class="form-control bg-glass" id="f_name" name="name" required placeholder="John Doe" style="width: 100%; box-sizing: border-box;"></div><div class="col-md-6 mb-3"><label for="f_email" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email Address *</label><input type="email" class="form-control bg-glass" id="f_email" name="email" required placeholder="john@example.com" style="width: 100%; box-sizing: border-box;"></div></div><div class="row"><div class="col-md-6 mb-3"><label for="f_phone" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Phone Number *</label><input type="tel" class="form-control bg-glass" id="f_phone" name="phone" required placeholder="+91-8840010951" style="width: 100%; box-sizing: border-box;"></div><div class="col-md-6 mb-3"><label for="f_partnership" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Partnership Type *</label><select class="form-control bg-glass" id="f_partnership" name="partnership_type" required style="width: 100%; box-sizing: border-box;"><option value="" disabled selected style="background: hsl(var(--card));">Select Partnership Type</option><option value="Business Partnership" style="background: hsl(var(--card));">Business Partnership (Custom Apps & Services)</option><option value="Academy Franchise" style="background: hsl(var(--card));">Academy Franchise (IT Training & Certs)</option></select></div></div><div class="mb-3"><label for="f_location" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Proposed Location (City, State/Country) *</label><input type="text" class="form-control bg-glass" id="f_location" name="location" required placeholder="Kanpur, Uttar Pradesh, India" style="width: 100%; box-sizing: border-box;"></div><div class="mb-4"><label for="f_message" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Professional Background & Business Plan Summary</label><textarea class="form-control bg-glass" id="f_message" name="message" rows="4" placeholder="Briefly describe your background, current business setup, and plan for this partnership..." style="width: 100%; box-sizing: border-box;"></textarea></div><button type="submit" class="btn btn-primary w-100" style="width: 100%; padding: 0.8rem; font-weight: 600;">Submit Application</button></form></div>', 1, 4],
    ];
    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, display_in_nav, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute($p);
    }

    // Seed blogs
    $blogs = [
        [
            'Unlocking Enterprise Scale with Laravel MVC Architecture',
            'laravel-enterprise-scale',
            'A deep dive into structuring modular Laravel projects, repository patterns, database sharding, and Redis caching for global enterprises.',
            '<p>As enterprise operations grow, web applications must scale horizontally to handle millions of transactions. Laravel is a popular framework for rapid application building, but its default configurations require adjustments to scale effectively for global enterprises. We explore the architectural models, design patterns, and caching pipelines required to build resilient Laravel applications.</p><h3>1. Adopting the Repository and Service Pattern</h3><p>The standard Laravel MVC model places business logic inside controllers or Eloquent models. However, at enterprise scale, this creates massive, hard-to-test code structures. By decoupling database access and business logic into repositories and service classes, developers can write clean, modular, and maintainable code.</p><ul><li><strong>Service Layer:</strong> Handles the core domain business logic, orchestrating calls between APIs, databases, and third-party services.</li><li><strong>Repository Layer:</strong> Acts as an abstraction layer over Eloquent models. If you need to switch databases (e.g. SQLite to PostgreSQL), you only change the repository implementation.</li></ul><h3>2. Implementing Redis and Advanced Caching Networks</h3><p>Database queries are often the primary performance bottleneck. Implementing high-speed Redis caching reduces database loads by storing frequently accessed tables (such as configuration parameters or site menus) in-memory.</p><h4>Recommended Cache Headers and Expiries:</h4><ul><li><strong>Static Assets:</strong> Cache for 24-48 hours using Redis tag clusters.</li><li><strong>User Session Data:</strong> Store session states directly in Redis to enable fast multi-server stateless operations.</li><li><strong>Query Serialization:</strong> Compress large database payloads before storing them in cache, saving system memory.</li></ul><h3>3. Queue Processing and Background Jobs</h3><p>Never process slow tasks (like invoice generation, sending emails, or processing media uploads) inside the user’s HTTP request pipeline. Offload these operations to Laravel Horizon or background Redis queues, maintaining instant response times for the client browser.</p><h3>Enterprise Architecture FAQs</h3><p><strong>Q: When should we implement database sharding in Laravel?</strong><br>A: When single database servers cannot handle the read/write load, or when database storage size exceeds local server capabilities. Sharding distributes tables across multiple databases based on keys (e.g. country or user ID).</p><p><strong>Q: How does Redis improve Laravel queue performance?</strong><br>A: Redis is an in-memory datastore, which allows background workers to fetch queued jobs with microsecond latency, far faster than querying traditional SQL databases.</p><p>Need to scale your business portal? Contact Longway Softronix Pvt. Ltd. (LSPL) to speak with our enterprise engineers today.</p>',
            'Admin',
            'uploads/blog_laravel-enterprise-scale.png',
            'Published'
        ],
        [
            'Why Custom E-Commerce Architectures Win Over Generic Platforms',
            'custom-ecommerce-architectures',
            'Exploring PrestaShop, Shopify, and Magento custom code structures for multi-store scaling, faster checkouts, and Core Web Vitals optimization.',
            '<p>Generic e-commerce platforms offer fast setup, but as product catalogs expand, they suffer from theme bloat and sluggish checkouts. Custom Liquid code on Shopify, customized PrestaShop templates, or headless Magento architectures allow businesses to build fast, secure, and search-optimized storefronts.</p><h3>1. Optimizing Core Web Vitals for Search Visibility</h3><p>Search engines rank websites based on loading performance and user experience (Core Web Vitals). Generic templates load excessive stylesheets and JavaScript libraries, which delay rendering. Custom e-commerce engineering solves this by loading only the critical CSS and JS required for the page.</p><ul><li><strong>Largest Contentful Paint (LCP):</strong> Keep main product images optimized and preloaded to render under 1.5 seconds.</li><li><strong>First Input Delay (FID):</strong> Remove blocking third-party tracking scripts to keep the page interactive.</li><li><strong>Cumulative Layout Shift (CLS):</strong> Define exact height and width attributes for images and sliders to prevent layout shifts.</li></ul><h3>2. Secure Checkout Pipelines and API Integrations</h3><p>Custom architectures let you separate checkout flows and invoice generation into microservices. If your main storefront experiences high traffic, the checkout API remains responsive on a dedicated server. This prevents cart abandonment and protects customer payment processing.</p><h3>E-Commerce Engineering FAQs</h3><p><strong>Q: Can custom Shopify sites rank better than standard templates?</strong><br>A: Yes. Custom Shopify templates built with Liquid or headless frameworks are significantly faster, reducing layout shifts and boosting search rankings.</p><p><strong>Q: Is PrestaShop suitable for multi-store inventories?</strong><br>A: PrestaShop is excellent for multi-store management. By writing custom SQL database modules, we can synchronize stock changes across global locations in real time.</p><p>Ready to upgrade your online store? Get a custom e-commerce solution blueprint from the LSPL team.</p>',
            'Admin',
            'uploads/blog_custom-ecommerce-architectures.png',
            'Published'
        ],
        [
            'The Complete Blueprint for Native iOS and Android Application Design',
            'native-mobile-design-blueprint',
            'How we utilize Apple\'s native Swift and Google\'s Kotlin Compose APIs to build fluid, high-fidelity mobile experiences.',
            '<p>While cross-platform toolkits offer rapid prototyping, native engineering is essential to build high-performance applications. By developing directly with Swift (iOS) and Kotlin Jetpack Compose (Android), we utilize local hardware features, ensure tight security, and deliver responsive interfaces.</p><h3>1. Swift and SwiftUI for iOS</h3><p>Swift is Apple’s compiled language, optimized for iOS, macOS, and watchOS. By using SwiftUI, we construct reactive layouts that sync with local states, enabling smooth transitions and consistent rendering.</p><ul><li><strong>Apple CoreData & SQLite:</strong> Keep user databases stored securely in local app directories for offline access.</li><li><strong>Keychain Encryption:</strong> Protect API tokens and user passwords using native hardware security.</li></ul><h3>2. Kotlin and Jetpack Compose for Android</h3><p>Google’s Kotlin Compose framework changes how Android interfaces are built. Instead of managing complex XML layouts, developers write declarative code that updates as state changes, reducing bugs and memory usage.</p><ul><li><strong>Kotlin Coroutines:</strong> Run network queries and database operations in background threads, keeping the UI responsive.</li><li><strong>Jetpack Room Database:</strong> Abstract SQLite access for robust offline-first operations.</li></ul><h3>Native App Development FAQs</h3><p><strong>Q: What is the main benefit of native app development?</strong><br>A: Native applications have access to all hardware features (biometrics, camera, GPU) without bridge layers, resulting in faster performance and higher security.</p><p><strong>Q: Can native mobile apps work offline?</strong><br>A: Yes. By implementing local databases like Room or CoreData, apps store user inputs locally and sync with cloud servers when a connection is restored.</p><p>Looking to launch a mobile application? Contact LSPL to build your native iOS or Android app.</p>',
            'Admin',
            'uploads/blog_native-mobile-design-blueprint.png',
            'Published'
        ],
        [
            'Optimizing School ERP and Hospital Management Software',
            'school-erp-hospital-management-software',
            'A guide to streamlining educational records, hospital billing databases, and medical store stock tracking with high security.',
            '<p>Enterprise administrative systems require high accuracy and security. Educational institutions and healthcare clinics need school ERPs, hospital billing engines, and medical store inventory portals to operate efficiently. Custom software engineering provides secure databases and workflows tailored to these operations.</p><h3>1. Designing School ERP Dashboards</h3><p>A school ERP must centralize student admissions, fee structures, class schedules, and grades. Custom dashboards help teachers, students, and parents view relevant metrics securely.</p><ul><li><strong>Automated Fee Billing:</strong> Sends automated SMS alerts and emails with secure checkout links for online payments.</li><li><strong>Gradebook Portals:</strong> Allows teachers to input exam marks directly, dynamically calculating percentages and grade reports.</li></ul><h3>2. Secure Hospital Billing and Medical Inventory</h3><p>Hospital management software processes sensitive patient health records (EHR) and financial data. We design these portals with role-based access control (RBAC), ensuring that only authorized personnel can view patient records or access medical store inventories.</p><ul><li><strong>Electronic Health Records (EHR):</strong> Securely encrypts patient history and doctor consultations.</li><li><strong>Pharmacy Billing & Stock Tracking:</strong> Automatically deducts inventory items as prescriptions are billed, preventing stock shortages.</li></ul><h3>Enterprise Systems FAQs</h3><p><strong>Q: How do custom ERPs protect patient and student privacy?</strong><br>A: By using role-based permissions, data encryption at rest and in transit, and detailed audit logs that record all database modifications.</p><p><strong>Q: Can school ERP systems integrate with online payment portals?</strong><br>A: Yes. Custom ERPs integrate with payment gateways like Razorpay, PayPal, or Stripe, enabling secure and automated fee collection.</p><p>Need custom administrative software? Get an estimate from the LSPL team today.</p>',
            'Admin',
            'uploads/blog_school-erp-hospital-management-software.png',
            'Published'
        ],
        [
            'A Strategic Guide to Global and UK Local SEO Campaigns',
            'strategic-guide-seo-campaigns',
            'Discover how keyword planning, technical audits, schema markup, and content frameworks elevate search engine ranking results.',
            '<p>Search engine optimization (SEO) is essential for generating organic search traffic. However, ranking globally or in local markets requires distinct strategies. We discuss the technical audits, JSON-LD schemas, and content strategies needed to improve search engine rankings.</p><h3>1. The Value of Technical SEO Audits</h3><p>Search engine crawlers analyze technical performance before evaluating content. If your website has slow load times or broken links, search engines will limit its visibility. Key technical steps include:</p><ul><li><strong>Canonical URLs:</strong> Use canonical tags to prevent search engines from indexing duplicate URL paths.</li><li><strong>XML Sitemaps:</strong> Maintain an updated sitemap detailing all active pages to help crawlers index your site.</li><li><strong>Core Web Vitals:</strong> Optimize page speeds and reduce layout shifts to improve search performance.</li></ul><h3>2. Implementing Structured Schema Markup</h3><p>JSON-LD schemas provide structured data directly to search engines. Injecting Organization, Service, and FAQ schemas helps search engines display rich snippets (like star ratings and site links) in search results, improving click-through rates.</p><h3>SEO FAQs</h3><p><strong>Q: What is local SEO?</strong><br>A: Local SEO focuses on optimizing your online presence for local search queries (e.g. "software developer in Kanpur"). This involves managing Google Business Profiles and local citation directories.</p><p><strong>Q: How long does it take to rank on page 1 of Google?</strong><br>A: Depending on competition and keyword difficulty, it typically takes 3 to 6 months of technical optimization and consistent content publishing to see ranking improvements.</p><p>Want to improve your search visibility? Contact LSPL for a comprehensive technical SEO audit.</p>',
            'Admin',
            'uploads/blog_strategic-guide-seo-campaigns.png',
            'Published'
        ],
        [
            'Maximizing Conversion Rates with Customized UI/UX Audits',
            'maximizing-conversion-rates-ui-ux',
            'Design is not just aesthetics; it is about user behavior and conversion optimization. Custom UI/UX audits analyze click Heatmaps and scroll depths to improve client conversions.',
            '<p>Custom UI/UX audits analyze click Heatmaps and scroll depths to improve client conversions. Designing intuitive navigation maps ensures that customers locate checkouts and sign-up prompts within 3 clicks.</p><h3>Conversion-Focused Auditing</h3><p>We analyze user actions on landing pages to simplify layout structures, reducing bounce rates and maximizing CTA interactions.</p><ul><li>Heatmap Tracking: Mapping user clicks and page movements.</li><li>Micro-interactions: Adding subtle button animations to guide users.</li><li>A/B Testing: Comparing visual layouts to maximize conversions.</li></ul>',
            'Admin',
            'uploads/blog_maximizing-conversion-rates-ui-ux.png',
            'Published'
        ],
        [
            'The Ultimate Checklist for Cloud Server Migration and Setup',
            'cloud-server-migration-checklist',
            'Moving web platforms to AWS, GCP, or DigitalOcean requires planning. Secure SSH access, firewalls, database replication, and SSL certifications are vital to avoid downtime.',
            '<p>Moving web platforms to AWS, GCP, or DigitalOcean requires planning. We configure secure SSH protocols, firewalls, database replication streams, and SSL certifications to keep migration operations smooth.</p><h3>Deploying Secure Environments</h3><p>We configure system containers, balance loads across web clusters, and automate backups to prevent data loss during migration.</p><ul><li>SSH Hardening: Restricting root login permissions.</li><li>SSL Integration: Automating certificate renewals.</li><li>Replication Setup: Synchronizing active and standby databases.</li></ul>',
            'Admin',
            'uploads/blog_cloud-server-migration-checklist.png',
            'Published'
        ],
        [
            'Why Custom APIs and Webhooks Beat Pre-Built Integration Plugins',
            'custom-apis-vs-plugins',
            'Pre-built plugins often introduce security risks and lag. Custom REST APIs and webhooks allow secure, fast data synchronization between CRM and ERP databases.',
            '<p>Pre-built plugins often introduce security risks and lag. Custom REST APIs and webhooks allow secure, fast data synchronization between CRM and ERP databases without extra background processes.</p><h3>API-First Integrations</h3><p>We design custom RESTful endpoints with bearer tokens and payload validation, keeping data transfers secure and lightweight.</p><ul><li>Token Authorization: Protecting endpoints from unauthorized requests.</li><li>Payload Minification: Minimizing data packet sizes.</li><li>Custom Webhooks: Triggering instant event-driven sync tasks.</li></ul>',
            'Admin',
            'uploads/blog_custom-apis-vs-plugins.png',
            'Published'
        ],
        [
            'Securing PHP Applications Against OWASP Top 10 Vulnerabilities',
            'securing-php-owasp-top-10',
            'Secure coding is critical. PHP systems should be protected against SQL injection, XSS, CSRF, and broken access controls using modern framework middlewares.',
            '<p>Secure coding is critical. PHP systems should be protected against SQL injection, XSS, CSRF, and broken access controls using modern framework middlewares and inputs validation.</p><h3>Implementing Defense Layers</h3><p>We audit database query structures, escape output rendering tags, and deploy secure CSRF token controls to protect user sessions.</p><ul><li>SQL Parameterization: Protecting databases from injection inputs.</li><li>Output Escaping: Neutralizing cross-site scripting strings.</li><li>Session Encryption: Securing authentication cookies.</li></ul>',
            'Admin',
            'uploads/blog_securing-php-owasp-top-10.png',
            'Published'
        ],
        [
            'The Future of Desktop Software in a Web-First Business World',
            'future-desktop-software',
            'While web apps are popular, desktop systems (Electron, Qt, C#) are still vital for offline operations, high-speed invoicing, and local device control.',
            '<p>While web apps are popular, desktop systems (Electron, Qt, C#) are still vital for offline operations, high-speed invoicing, and local device control in retail POS environments.</p><h3>High-Speed Offline Software</h3><p>Custom desktop frameworks enable direct communication with local hardware devices (receipt printers, scales) while maintaining local database sync.</p><ul><li>Hardware Interfacing: Connecting POS devices via USB/Serial ports.</li><li>Offline Operations: Running software without active internet connections.</li><li>Speed Optimization: Fetching catalog queries in microseconds.</li></ul>',
            'Admin',
            'uploads/blog_future-desktop-software.png',
            'Published'
        ],
        [
            'How to Choose the Right Tech Stack for Your Startup MVP',
            'startup-mvp-tech-stack',
            'Startups need rapid deployment and scalability. We compare React, Node.js, Python, and Laravel to help you choose the right tech stack for your MVP.',
            '<p>Startups need rapid deployment and scalability. We compare React, Node.js, Python, and Laravel to help you choose the right tech stack for your MVP.</p><h3>Balancing Speed and Scaling</h3><p>Choosing a tech stack requires evaluating developer availability, rendering speeds, and database routing capabilities.</p><ul><li>Laravel Stacks: Excellent for rapid MVP database setups.</li><li>Node.js Stacks: Perfect for real-time WebSockets and APIs.</li><li>React Frontends: Creating fluid, high-fidelity customer dashboards.</li></ul>',
            'Admin',
            'uploads/blog_startup-mvp-tech-stack.png',
            'Published'
        ],
        [
            'Scaling Relational Databases: Sharding, Partitioning, and Caching',
            'scaling-relational-databases',
            'Databases are critical bottlenecks. Scaling requires partitioning large tables, sharding databases across servers, and implementing Redis caching.',
            '<p>Databases are critical bottlenecks. Scaling requires partitioning large tables, sharding databases across servers, and implementing Redis caching.</p><h3>Advanced Database Tuning</h3><p>We implement indexing keys, configure read/write replication pools, and deploy Redis structures to handle high transactional traffic.</p><ul><li>Table Partitioning: Splitting large tables by date ranges.</li><li>Database Sharding: Distributing datasets across servers.</li><li>Redis Caching: Offloading database queries for persistent records.</li></ul>',
            'Admin',
            'uploads/blog_scaling-relational-databases.png',
            'Published'
        ],
        [
            'Why Slow Loading Speeds are Killing Your Checkout Conversions',
            'slow-loading-checkout-conversions',
            'Speed is revenue. Every 100ms latency increase in checkout operations reduces sales conversion by 1%. Learn optimization methods for fast checkouts.',
            '<p>Speed is revenue. Every 100ms latency increase in checkout operations reduces sales conversion by 1%. Learn optimization methods for fast checkouts.</p><h3>Optimizing Checkout Performance</h3><p>We optimize product database queries, bundle checkout styles, and decouple invoice generation to keep transaction times under 1 second.</p><ul><li>Image Compression: Minimizing cart thumbnail sizes.</li><li>Deferred Invoicing: Generating receipts in background queues.</li><li>API Optimization: Reducing round-trip payment payloads.</li></ul>',
            'Admin',
            'uploads/blog_slow-loading-checkout-conversions.png',
            'Published'
        ],
        [
            'A Guide to Custom PrestaShop Multi-Store Inventory Scaling',
            'prestashop-multistore-inventory-scaling',
            'Managing inventories across multiple stores is complex. PrestaShop database custom synchronization hooks enable real-time stock updates.',
            '<p>Managing inventories across multiple stores is complex. PrestaShop database custom synchronization hooks enable real-time stock updates.</p><h3>Synchronizing Catalog Inventories</h3><p>We write database triggers that update product stock counts across global storefronts whenever a purchase occurs.</p><ul><li>Database Triggers: Auto-updating inventory levels.</li><li>API Sync: Synchronizing inventories with physical stock.</li><li>Conflict Resolution: Preventing double-purchase oversights.</li></ul>',
            'Admin',
            'uploads/blog_prestashop-multistore-inventory-scaling.png',
            'Published'
        ],
        [
            'The Importance of Continuous Integration and Automated Testing',
            'continuous-integration-automated-testing',
            'Automated pipelines catch bugs before release. Deploying CI/CD with PHPUnit, Cypress, and GitHub Actions ensures stable web operations.',
            '<p>Automated pipelines catch bugs before release. Deploying CI/CD with PHPUnit, Cypress, and GitHub Actions ensures stable web operations.</p><h3>Deploying CI/CD Pipelines</h3><p>We set up automated testing scripts that run during each repository commit, verifying routing and syntax before server deployments.</p><ul><li>PHPUnit Checks: Testing database and logic functions.</li><li>Cypress Audits: Testing frontend checkout and signup flows.</li><li>GitHub Actions: Automating deployments to production servers.</li></ul>',
            'Admin',
            'uploads/blog_continuous-integration-automated-testing.png',
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
        [41, null, 'About Us', 'page', 'about-us', null, 'single_page', null, 2],
        [2, null, 'Services', 'none', null, null, 'megamenu', null, 3],
        [3, null, 'Solutions', 'none', null, null, 'megamenu', null, 4],
        [4, null, 'Blog', 'custom', null, 'blog.php', 'single_page', null, 5],
        [5, null, 'Estimator', 'custom', null, 'estimator.php', 'single_page', null, 6],
        [6, null, 'Contact', 'custom', null, 'index.php#contact', 'single_page', null, 7],
        [17, 2, 'Web Designing & UI/UX', 'custom', null, 'service/web-designing-ui-ux', 'single_page', 'Design & Frontend', 1],
        [18, 2, 'WordPress & CMS Dev', 'custom', null, 'service/wordpress-cms-development', 'single_page', 'Design & Frontend', 2],
        [19, 2, 'Shopify Store Setup', 'custom', null, 'service/shopify-ecommerce-setups', 'single_page', 'Design & Frontend', 3],
        [20, 2, 'Laravel Web Apps', 'custom', null, 'service/laravel-php-web-apps', 'single_page', 'SaaS & Custom Apps', 5],
        [21, 2, 'Native Mobile Apps', 'custom', null, 'service/fullstack-mobile-development', 'single_page', 'SaaS & Custom Apps', 6],
        [22, 2, 'Search SEO Optimization', 'custom', null, 'service/seo-digital-marketing', 'single_page', 'Cloud & Optimization', 9],
        [23, 2, 'Headless JAMstack', 'custom', null, 'service/headless-cms-jamstack', 'single_page', 'Design & Frontend', 4],
        [24, 2, 'Data Analytics BI', 'custom', null, 'service/data-analytics-bi', 'single_page', 'Cloud & Optimization', 10],
        [42, 2, 'Cloud & Server Setup', 'custom', null, 'service/cloud-server-setup', 'single_page', 'Cloud & Optimization', 11],
        
        # Solutions (parent_id = 3) split in columns
        [25, 3, 'School ERP System', 'custom', null, 'industry/school-erp', 'single_page', 'Enterprise Systems', 1],
        [26, 3, 'Hospital EHR Manager', 'custom', null, 'industry/hospital-management', 'single_page', 'Enterprise Systems', 2],
        [27, 3, 'Pharmacy POS & Stock', 'custom', null, 'industry/pharmacy-billing', 'single_page', 'Enterprise Systems', 3],
        [28, 3, 'Law Firm Case Manager', 'custom', null, 'industry/legal-case-manager', 'single_page', 'Enterprise Systems', 4],
        [29, 3, 'HR Recruiters & ATS', 'custom', null, 'industry/hr-ats', 'single_page', 'Enterprise Systems', 5],
        
        [30, 3, 'Restaurant POS Terminal', 'custom', null, 'industry/restaurant-pos', 'single_page', 'Retail & Bookings', 6],
        [31, 3, 'Hotel Room Booking', 'custom', null, 'industry/hotel-booking', 'single_page', 'Retail & Bookings', 7],
        [32, 3, 'Salon Slot Scheduler', 'custom', null, 'industry/salon-scheduling', 'single_page', 'Retail & Bookings', 8],
        [33, 3, 'Gym & Fitness Portal', 'custom', null, 'industry/gym-membership', 'single_page', 'Retail & Bookings', 9],
        [34, 3, 'Event Ticketing & QR', 'custom', null, 'industry/event-ticketing', 'single_page', 'Retail & Bookings', 10],
        
        [35, 3, 'Real Estate Database', 'custom', null, 'industry/real-estate', 'single_page', 'Logistics & Finance', 11],
        [36, 3, 'Fintech Lending Board', 'custom', null, 'industry/fintech-lending', 'single_page', 'Logistics & Finance', 12],
        [37, 3, 'Logistics Parcel Tracker', 'custom', null, 'industry/logistics-tracking', 'single_page', 'Logistics & Finance', 13],
        [38, 3, 'Travel Itinerary Builder', 'custom', null, 'industry/travel-itineraries', 'single_page', 'Logistics & Finance', 14],
        [39, 3, 'Car Rental Reservation', 'custom', null, 'industry/car-rental', 'single_page', 'Logistics & Finance', 15],
        [40, 3, 'AgTech Yield Software', 'custom', null, 'industry/agtech-yield', 'single_page', 'Logistics & Finance', 16],
        [43, 2, 'Custom SaaS & Microservices', 'custom', null, 'service/custom-saas-platforms', 'single_page', 'SaaS & Custom Apps', 7],
        [44, 2, 'API & Webhook Integrations', 'custom', null, 'service/api-integrations', 'single_page', 'SaaS & Custom Apps', 8],
        [45, 3, 'Bespoke CRM & Enterprise ERP', 'custom', null, 'industry/bespoke-crm-erp', 'single_page', 'Enterprise Systems', 17],
    ];
    // We insert items preserving IDs to maintain parent-child relationships correctly
    $stmt = $db->prepare("INSERT INTO header_menu_items (id, parent_id, title, link_type, page_slug, custom_url, menu_type, column_name, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($header_menu as $h) {
        $stmt->execute($h);
    }

    // Seed footer items
    $footer_items = [
        ['Support & Pages', 'About Our Company', 'page', 'about-us', null, 1],
        ['Support & Pages', 'Insights Blog', 'custom', null, 'blog.php', 2],
        ['Support & Pages', 'Contact Support', 'custom', null, 'index.php#contact', 3],
        ['Support & Pages', 'Partnership Program', 'page', 'franchise', null, 4],
        ['Support & Pages', 'LSPL IT Academy', 'custom', null, '/lspl.xyz_v2/', 5],
        ['Industries We Serve', 'School ERP System', 'custom', null, 'industry/school-erp', 6],
        ['Industries We Serve', 'Hospital EHR Manager', 'custom', null, 'industry/hospital-management', 7],
        ['Industries We Serve', 'Restaurant POS Terminal', 'custom', null, 'industry/restaurant-pos', 8],
        ['Legal & Policies', 'Privacy Policy', 'page', 'privacy-policy', null, 9],
        ['Legal & Policies', 'Terms & Conditions', 'page', 'terms-conditions', null, 10],
    ];
    $stmt = $db->prepare("INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($footer_items as $f) {
        $stmt->execute($f);
    }

    // Seed reviews
    $reviews = [
        ['Johnathan Mercer', 'Pawan K Singh and the LSPL team did an exceptional job building our enterprise Laravel backend. Their custom MVC coding speed and attention to query caching optimization were top-notch. Highly recommended for complex SaaS applications!', 5, 'google', 'Enterprise Laravel Integration'],
        ['Sarah Jenkins', 'LSPL designed a high-performance custom Shopify liquid layout that loads in under 1.5 seconds. Pawan is a brilliant full-stack engineer who understands clean database schemas. Communication was seamless throughout the project.', 5, 'peopleperhour', 'Custom E-Commerce Platform'],
        ['Marcus Thorne', 'We deployed LSPL\'s custom School ERP system across our multi-campus academy. The parent-teacher portals, fee collections, and calendar modules operate flawlessly. Outstanding support and professional delivery.', 5, 'trustpilot', 'School ERP Implementation']
    ];
    $stmt = $db->prepare("INSERT INTO reviews (author_name, review_text, rating, platform, project_title) VALUES (?, ?, ?, ?, ?)");
    foreach ($reviews as $r) {
        $stmt->execute($r);
    }
}
?>
