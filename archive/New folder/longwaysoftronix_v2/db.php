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
        ['Web Designing & UI/UX', 'web-designing-ui-ux', 'Figma wireframes, layout mockups, and responsive web interface styles tailored for UK and global audiences.', '<h3>Figma Layout Prototyping & Responsive Web Interfaces</h3><p>At LSPL, we believe that design is not just how a website looks, but how it works. We create responsive layout systems and Figma wireframes tailored for global and UK user expectations. Our styling tokens use curated HSL color schemes that adapt to light and dark modes seamlessly.</p><h4>Our Creative Process</h4><ul><li><strong>Wireframing & Prototyping:</strong> We design interactive Figma wireframes to map the user journey before writing code.</li><li><strong>HSL Theme Architectures:</strong> Implementing CSS custom properties to swap themes dynamically.</li><li><strong>Core Web Vitals Optimization:</strong> Ensuring layout shifts (CLS) are minimized for better SEO.</li></ul><p>We build accessible interfaces (conforming to WCAG AA guidelines) that load instantly and provide smooth hover micro-animations. Our front-end team guarantees a consistent mobile, tablet, and desktop design blueprint, optimized for conversions.</p>', 'layout', 'Web & Software', 'Figma, HTML5, CSS3, JavaScript, HSL Themes', 1],
        ['WordPress & CMS Development', 'wordpress-cms-development', 'Custom WordPress theme development, Joomla site setups, and Drupal core customization. Clean PHP coding for flexible editor experiences.', '<h3>Custom WordPress Theme Development & CMS Setup</h3><p>We build high-performance CMS solutions on WordPress, Joomla, and Drupal. Rather than relying on bloated pre-made themes, we code custom layouts from scratch using clean PHP MVC structures. This ensures your admin panel is simple to edit, loads quickly, and is optimized for search rankings.</p><h4>CMS Optimization Blueprint</h4><ul><li><strong>Gutenberg Block Coding:</strong> Designing custom visual blocks for simple page building.</li><li><strong>Custom Plugins:</strong> Writing custom plugins to implement corporate directories, case files, and bookings.</li><li><strong>Security Hardening:</strong> Implementing database prefixes, firewall layers, and secure file permissions.</li></ul><p>We integrate robust caching policies (Redis, WP Super Cache) and database queries to handle heavy traffic spikes. Whether you need a corporate blog, a media portal, or a multisite setup, our team guarantees security, speed, and clean code.</p>', 'file-text', 'Web & Software', 'WordPress Core, PHP, MySQL, Joomla, Drupal', 2],
        ['Shopify & E-Commerce Setups', 'shopify-ecommerce-setups', 'Shopify store setups, custom liquid themes, WooCommerce cart integration, and PrestaShop catalog pipelines for UK retail brands.', '<h3>Shopify Store Setups & Enterprise E-Commerce Portals</h3><p>We build robust e-commerce solutions that convert visitors into buyers. Our expertise includes custom Shopify store setups, coding custom Liquid layouts, integrating WooCommerce shopping carts, and scaling Magento or PrestaShop multi-store catalogs.</p><h4>E-Commerce Key Features</h4><ul><li><strong>Liquid Theme Engineering:</strong> Custom coding Shopify storefront layouts for faster load times.</li><li><strong>Multi-Store Catalog Sync:</strong> Synchronizing inventory data across multiple warehouses and marketplaces.</li><li><strong>Stripe & Wallet Integration:</strong> Building secure checkout pipelines with instant fraud checks.</li></ul><p>We configure secure transactional payment gateways (Stripe, PayPal, Apple Pay) and synchronize order metrics with external ERP and invoicing APIs. Our systems are optimized to load in under 1.5 seconds, even with catalog sizes exceeding 50,000 product nodes.</p>', 'shopping-bag', 'E-Commerce Solution', 'Shopify, Liquid, WooCommerce, PrestaShop, Stripe', 3],
        ['Laravel & PHP Web Apps', 'laravel-php-web-apps', 'Secure, database-driven custom web applications. Robust Eloquent ORM architectures and high-performance Laravel API systems.', '<h3>Custom Web Applications with Laravel & PHP MVC</h3><p>For custom web systems that require complex business logic, we use Laravel and CodeIgniter. We design robust database schemas, configure Eloquent ORM relationships, and build high-performance RESTful APIs to connect front-ends and mobile apps.</p><h4>Laravel Technical Capabilities</h4><ul><li><strong>Eloquent ORM Scaling:</strong> Optimizing database query indexes to prevent execution bottlenecks.</li><li><strong>Secure Authentication:</strong> Implementing OAuth2, Laravel Sanctum, and session-based credentials.</li><li><strong>Background Jobs Queuing:</strong> Offloading heavy notifications and emails to Redis queues.</li></ul><p>We implement secure authentication protocols, job queuing handlers for background emails, and Redis key-value caching to scale database read operations. Our software remains clean, structured, and simple to expand.</p>', 'cpu', 'Web & Software', 'PHP 8.2, Laravel MVC, MySQL, CodeIgniter', 4],
        ['Full-Stack & Mobile Development', 'fullstack-mobile-development', 'Native Swift iOS application design and Google Kotlin Compose Android apps. End-to-end full stack development.', '<h3>Native Mobile App Engineering: Swift & Kotlin Compose</h3><p>We engineer native mobile applications that feel fluid and premium. Our iOS applications are written in Swift using SwiftUI layout frameworks. Our Android applications utilize Kotlin, Coroutines, and Jetpack Compose for declarative UI flows.</p><h4>Mobile Core Features</h4><ul><li><strong>SwiftUI & Compose Layouts:</strong> Building fluid interfaces with high-frame-rate animations.</li><li><strong>Offline-First Sync:</strong> Utilizing SQLite/Room databases with background network sync.</li><li><strong>Keychain Cryptography:</strong> Storing client sessions and API tokens in hardware-level keychains.</li></ul><p>We focus on offline-first databases, secure authentication keys, push notification triggers, and location services. We handle the entire App Store and Google Play deployment checklist, ensuring compliance with Apple and Google design guidelines.</p>', 'smartphone', 'Web & Software', 'iOS Swift, Android Kotlin, Jetpack Compose, React Native', 5],
        ['Professional SEO & Digital Marketing', 'seo-digital-marketing', 'Technical diagnostic SEO audits, keyword rankings, local search optimization in Kanpur and UK, backlink generation, and Google Ads PPC.', '<h3>Technical SEO Audits & Local Search Marketing Campaigns</h3><p>Maximize your organic search engine ranking. We conduct technical SEO diagnostic audits, configure XML sitemaps, set up canonical tags, and write custom JSON-LD schemas. We research high-value keywords for the UK, India (Kanpur), and global markets.</p><h4>SEO Implementation Guide</h4><ul><li><strong>Indexing Audits:</strong> Eliminating duplicate paths, configuring robots.txt, and canonical tags.</li><li><strong>Structured Data:</strong> Injecting Organization, Service, and Article schemas for search appearance.</li><li><strong>Google Analytics 4:</strong> Building conversion funnels to track visitors and leads.</li></ul><p>We also manage search engine PPC ads (Google Ads), social advertising (Meta Ads), and email campaigns to drive leads. Our reports provide clear conversions telemetry metrics from Google Analytics 4, helping you track your return on investment.</p>', 'search', 'Marketing & Search', 'Google Search Console, Semrush, Google Ads PPC', 6],
        ['Headless CMS & JAMstack Development', 'headless-cms-jamstack', 'Decoupled web applications utilizing Next.js, React, and headless CMS frameworks like Strapi or headless WordPress.', '<h3>Headless JAMstack Architectures: Next.js & Strapi CMS</h3><p>JAMstack represents the future of web performance. We decouple the front-end (built with Next.js or React) from the content repository. We connect to headless CMS engines (Strapi, Contentful, Sanity) via GraphQL or REST APIs.</p><h4>Headless Core Benefits</h4><ul><li><strong>Sub-Second Loading:</strong> Pre-rendering static HTML pages for instant loading.</li><li><strong>Zero DB Exposure:</strong> Hosting front-ends on edge CDN networks to eliminate SQL injection risks.</li><li><strong>Flexible Editing:</strong> Giving editors a simple admin panel while developers use React code.</li></ul><p>This headless configuration ensures maximum security (no direct database exposure), sub-second page loads, and top-tier Core Web Vitals rankings that search engines favor.</p>', 'globe', 'Web & Software', 'Next.js, React, Strapi CMS, Headless WordPress', 7],
        ['Data Analytics & Business Intelligence', 'data-analytics-bi', 'Custom corporate database pipelines. Integrating ERP metrics with BigQuery, PowerBI dashboards, and Google Analytics 4 reports.', '<h3>Data Pipelines & Custom BI Dashboards</h3><p>Turn raw database logs into actionable insights. We build custom data pipelines using Python to ingest e-commerce and ERP metrics into Cloud Warehouses like Google BigQuery.</p><h4>Analytics Technical Workflow</h4><ul><li><strong>ETL Pipelines:</strong> Automating data extraction, transformation, and load operations.</li><li><strong>BigQuery Warehousing:</strong> Storing and querying terabytes of business records in seconds.</li><li><strong>Visual Dashboards:</strong> Building PowerBI, Tableau, and React charts for real-time tracking.</li></ul><p>We build interactive dashboards (using PowerBI, Tableau, or custom React graphs) that display live revenue, visitor conversions, and inventory tracking. Our systems help corporate stakeholders make data-driven choices.</p>', 'bar-chart', 'Marketing & Search', 'BigQuery, Google Cloud, PowerBI, Tableau, Python', 8],
        ['Cloud Deployments & Server Setup', 'cloud-server-setup', 'Installation and configuration of custom servers or cloud deployments across AWS, Azure, Google Cloud, and Oracle Cloud.', '<h3>Cloud Infrastructure & Custom Server Setup</h3><p>We configure custom server installations and manage secure cloud deployments across AWS, Microsoft Azure, Google Cloud (GCP), and Oracle Cloud. We ensure high-availability routing, configure secure firewalls, set up SSL, and automate data backups.</p><h4>Cloud & Server Core Deliverables</h4><ul><li><strong>Cloud Architectures:</strong> Custom AWS EC2, Azure VMs, and GCP Compute Engine nodes.</li><li><strong>Custom Servers:</strong> Physical server configuration, virtualization (VMware/KVM), and Linux server hardening.</li><li><strong>CI/CD Pipelines:</strong> Automated code deployments with GitHub Actions and Docker containers.</li></ul><p>We optimize server loading metrics and configure load balancers to scale your web applications and API servers seamlessly, maintaining 99.9% uptime.</p>', 'server', 'Infrastructure', 'AWS, Microsoft Azure, Google Cloud, Oracle Cloud, Linux, Docker, Nginx', 9],
        ['Custom SaaS Platforms & Microservices', 'custom-saas-platforms', 'We design, build, and deploy multi-tenant SaaS products and high-performance microservices tailored to your business model.', '<p>Building a successful SaaS platform requires careful architectural planning, security, multi-tenant databases, and seamless scalability. At LSPL, we construct custom SaaS platforms and microservices that grow with your user base.</p><h4>Our Development Approach</h4><ul><li><strong>Multi-Tenancy Architecture:</strong> Secure database separation or partitioned single-database models.</li><li><strong>Scalable Microservices:</strong> Deployed in containerized Docker environments with automated API gateways.</li><li><strong>Subscription Billing Integration:</strong> Stripe, PayPal, or custom regional gateways with auto-invoicing.</li></ul>', 'cpu', 'Web & Software', 'Laravel, Vue.js, Node.js, Docker', 10],
        ['API Integration & Webhook Gateways', 'api-integrations', 'Unify your systems by building robust, secure REST/GraphQL APIs and serverless webhook pipelines for seamless data flow.', '<p>Modern enterprises rely on multiple SaaS tools. We integrate, synchronize, and extend your platforms with custom REST, SOAP, and GraphQL APIs.</p><h4>Integration Specializations</h4><ul><li><strong>Webhook Handlers:</strong> High-throughput serverless endpoints handling real-time data syncs.</li><li><strong>Payment & SMS Gateways:</strong> Integration of secure verification and transaction APIs.</li><li><strong>Enterprise CRM/ERP Sync:</strong> Connecting front-end applications with Salesforce, SAP, or custom systems.</li></ul>', 'git-branch', 'Web & Software', 'PHP, Python, AWS API Gateway, RabbitMQ', 11],
    ];
    $stmt = $db->prepare("INSERT INTO services (title, slug, description, content, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute($s);
    }

    // Seed industries
    $industries = [
        ['School ERP Systems', 'school-erp', 'All-in-one school administration systems. Aggregates student attendance, parent portals, grades, and fee billing database modules.', '<h3>School ERP Administration Systems</h3><p>We build custom School ERP platforms that streamline school operations. Features include student enrollment, attendance logs, exam marksheets, calendar scheduling, and parent portal panels. All fee transaction details are logged in secure SQLite/MySQL databases with automatic email invoice receipts.</p><h4>ERP Core Modules</h4><ul><li><strong>Academic Calendar:</strong> Automatic timetable scheduling and class allocations.</li><li><strong>Online Fee Collection:</strong> Payment gateway integrations with automatic receipt generation.</li><li><strong>Parent-Teacher Board:</strong> Dynamic messaging boards and grade reports.</li></ul><p>Our School ERP solutions are built on lightweight database systems, making them fast and highly secure for students, teachers, and administrators.</p>', 'graduation-cap', 1],
        ['Hospital Management Systems', 'hospital-management', 'Secure EHR record aggregation, doctor scheduling calendars, OPD clinic billing databases, and patient portal setups.', '<h3>Hospital EHR & Medical Operations Software</h3><p>Streamline healthcare administration. Our Hospital systems manage doctor scheduling, patient clinical Electronic Health Records (EHR), OPD consultation queues, ward allocations, and billing. The platform conforms to patient data privacy guidelines.</p><h4>Healthcare Key Features</h4><ul><li><strong>EHR Security:</strong> Encrypted storage for patient medical histories.</li><li><strong>OPD Queue Manager:</strong> Real-time patient appointment queuing logs.</li><li><strong>Insurance Billing:</strong> Automated cost calculation modules and invoice tracking.</li></ul><p>We design clinical database systems that prevent data leaks, simplify auditing, and ensure doctors have instant access to patient histories.</p>', 'activity', 2],
        ['Pharmacy & Medical Store POS', 'pharmacy-billing', 'Pharmacy point-of-sale systems, inventory batch tracking, medicine expiry notifications, and drug database structures.', '<h3>Pharmacy POS & Expiry Tracking Database</h3><p>Avoid stock issues. Our Pharmacy POS software tracks batch numbers, medicine expiries, supplier records, and handles rapid invoice printing. It keeps your medical store inventory synchronized in real time.</p><h4>Pharmacy POS Capabilities</h4><ul><li><strong>Expiry Notifications:</strong> Automated warnings when batches are close to expiration.</li><li><strong>Barcode Scanning:</strong> Rapid drug checkout with inventory database lookup.</li><li><strong>Supplier Billing:</strong> Log supplier purchase orders and tax calculations.</li></ul><p>Keep your pharmacy compliant, organized, and profitable with our custom point-of-sale systems.</p>', 'pill', 3],
        ['Restaurant & Cafe POS', 'restaurant-pos', 'Table reservation managers, kitchen display screens, online food ordering systems, and POS terminal interfaces.', '<h3>Restaurant Table Reservation & Billing POS</h3><p>Scale food operations. We build restaurant POS software featuring interactive table layout managers, kitchen ticket print channels, menu custom options, and mobile ordering apps.</p><h4>POS Features</h4><ul><li><strong>Kitchen Display System:</strong> Sending order tickets directly to kitchen monitors.</li><li><strong>Table Manager:</strong> Drag-and-drop table reservations and bill splits.</li><li><strong>Inventory Tracking:</strong> Automatic deduction of ingredients based on sold dishes.</li></ul><p>Our restaurant systems increase order speeds, eliminate errors, and provide real-time sales reporting.</p>', 'utensils', 4],
        ['Hotel Room Reservation Engines', 'hotel-booking', 'Hotel room availability booking engines, dynamic seasonal pricing calculators, and guest reservation calendars.', '<h3>Hotel Booking & Channel Management Portals</h3><p>Manage lodging check-ins. Our Hotel booking engine aggregates room types, reservation availability calendars, guest records, and manages dynamic seasonal pricing changes.</p><h4>Hotel Portal Capabilities</h4><ul><li><strong>Availability Calendar:</strong> Live room occupancy calendars with color coding.</li><li><strong>Dynamic Pricing:</strong> Adjust room rates based on seasonality and local holidays.</li><li><strong>Guest Profiling:</strong> Secure guest logs with reservation histories.</li></ul><p>Increase direct bookings and optimize room occupancy with our robust hotel booking portals.</p>', 'hotel', 5],
        ['Salon & Spa Appointment Schedulers', 'salon-scheduling', 'Salon appointment scheduling calendars, therapist slot booking engines, and SMS reminder notifications.', '<h3>Salon Slot Scheduling & Spa Calendars</h3><p>Reduce client no-shows. We build salon reservation calendars that manage stylist slot availabilities, log customer histories, and trigger SMS/Email reminders.</p><h4>Salon Scheduler Modules</h4><ul><li><strong>Stylist Calendars:</strong> Separate slot booking grids for each staff member.</li><li><strong>SMS Reminders:</strong> Automated notifications sent 24 hours before appointment slots.</li><li><strong>Package Billing:</strong> Managing salon membership points and gift vouchers.</li></ul><p>Keep your salon calendar full and organize your staff shifts efficiently.</p>', 'clock', 6],
        ['Real Estate Portal Listings', 'real-estate', 'Property listing databases, MLS integration, real estate search filters, and agent CRM boards.', '<h3>Real Estate Property Search Directories</h3><p>Showcase properties online. We build real estate listings portals with advanced search parameters (location, budget, layout), agent contact forms, and interactive Google Maps.</p><h4>Property Directory Features</h4><ul><li><strong>Property Search Engine:</strong> Multi-variable filters for location and price.</li><li><strong>Agent CRM Boards:</strong> CRM boards for realtors to manage leads.</li><li><strong>Image Slideshows:</strong> High-resolution property image uploads and virtual tours.</li></ul><p>Help buyers find their dream homes with our fast, search-optimized real estate portals.</p>', 'home', 7],
        ['Fintech & Micro-Lending Pipelines', 'fintech-lending', 'Lending approval software pipelines, microfinance loan interest calculators, and customer credit check records.', '<h3>Fintech Lending Pipelines & Credit Calculators</h3><p>Secure micro-finance workflows. We build custom fintech loan portals with automated interest calculations, document upload modules, and agent approval checklists.</p><h4>Fintech System Highlights</h4><ul><li><strong>Loan Calculators:</strong> Interactive sliders to compute EMI and interest rates.</li><li><strong>Document Uploads:</strong> Secure KYC document verification vaults.</li><li><strong>Approval Checklists:</strong> Step-by-step credit check logs for lending agents.</li></ul><p>Ensure compliance, prevent fraud, and accelerate loan approvals with our secure fintech platforms.</p>', 'credit-card', 8],
        ['Logistics & Package Trackers', 'logistics-tracking', 'Logistics delivery tracking platforms, shipment dispatch calendars, and warehouse parcel stock databases.', '<h3>Logistics Parcel Tracking & Fleet Dashboards</h3><p>Audit packages on the move. Our logistics database systems track parcel status updates, dispatch timelines, and dispatch details for shipping agencies.</p><h4>Logistics Capabilities</h4><ul><li><strong>Barcode Tracking:</strong> Scanners log parcel check-ins at each warehouse node.</li><li><strong>Dispatch Calendars:</strong> Scheduling delivery dispatches and shifts.</li><li><strong>Customer Portal:</strong> Real-time status updates via tracking numbers.</li></ul><p>Increase delivery reliability and optimize warehouse sorting operations.</p>', 'truck', 9],
        ['Gym & Fitness Membership Portals', 'gym-membership', 'Gym member registration check-ins, automated subscription fee billing, and trainer booking schedules.', '<h3>Gym Membership Systems & Trainer Slots</h3><p>Automate fitness center billing. We build gym software with member card check-ins, monthly subscription auto-billing, and personal trainer reservation lists.</p><h4>Gym Portal Features</h4><ul><li><strong>Check-in Logs:</strong> Logging member entry times via barcode cards.</li><li><strong>Subscription Billing:</strong> Automatic credit card renewals and invoice logs.</li><li><strong>Trainer Schedulers:</strong> Shift calendars for personal training slots.</li></ul><p>Organize your members, reduce subscription payment friction, and manage trainers.</p>', 'heart', 10],
        ['Travel Agency Itinerary Builders', 'travel-itineraries', 'Custom tourist itinerary planner builders, flight hotel booking interfaces, and tour pricing calculators.', '<h3>Travel Itinerary Builders & Booking Panels</h3><p>Simplify trip planning. We design custom tour planners that compile flight, hotel, and activity schedules into mobile-friendly PDF itineraries.</p><h4>Itinerary Builder Highlights</h4><ul><li><strong>Drag-and-Drop Builders:</strong> Easily structure multi-day travel itineraries.</li><li><strong>Costing Engine:</strong> Real-time calculations of tour package costs.</li><li><strong>API Flight Feeds:</strong> Integrating live availability feeds.</li></ul><p>Build custom tours, print client travel itineraries, and manage agent booking margins.</p>', 'compass', 11],
        ['Law Firm Case Management Tools', 'legal-case-manager', 'Law firm court hearing calendars, client case file storage databases, and legal time tracking invoices.', '<h3>Legal Case Managers & Law Firm Invoicing</h3><p>Secure case records. Our law firm software manages client files, court hearing calendars, legal document templates, and lawyer billable hours tracking.</p><h4>Legal Tool Features</h4><ul><li><strong>Case Timeline:</strong> Chronological logs of court actions and filings.</li><li><strong>Hearing Calendars:</strong> Auto-alerts for court hearing deadlines.</li><li><strong>Time Billing:</strong> Log billable hours and auto-generate client invoices.</li></ul><p>Protect client privilege with secure database encryption, and streamline law firm operations.</p>', 'briefcase', 12],
        ['HR Recruiters & ATS Software', 'hr-ats', 'Applicant Tracking System (ATS), job board posting boards, and recruitment interview scheduling pipelines.', '<h3>HR Applicant Tracking System (ATS)</h3><p>Scale hiring operations. We build custom HR candidate databases with resume parsing, job application pipelines, and interviewer feedback scorecards.</p><h4>HR ATS Highlights</h4><ul><li><strong>Candidate Pipelines:</strong> Visual boards showing applicant stages (Applied, Interview, Offer).</li><li><strong>Resume Parsers:</strong> Automatically extract candidate skills from PDF resumes.</li><li><strong>Feedback Scorecards:</strong> Multi-interviewer scoring grids.</li></ul><p>Find the best talent, coordinate interviews, and manage hiring team feedback.</p>', 'users', 13],
        ['Car Rental Reservation Portals', 'car-rental', 'Secure car rental booking software, live vehicle fleet tracking calendars, Stripe security hold integration, and dispatcher scheduling boards.', '<h3>Enterprise Car Rental &amp; Fleet Logistics Systems</h3><p>Modern vehicle hire platforms require robust, real-time database management to handle vehicle availabilities, driver shifts, and secure digital checkouts. At LSPL, we build custom car rental reservation portals tailored to simplify fleet management, automate booking contracts, and optimize dispatcher workflows.</p><h4>Dynamic Fleet Availability &amp; Calendar Feeds</h4><p>Our custom software eliminates double-booking errors by keeping a centralized occupancy matrix. Every vehicle is tracked dynamically using status checks (Available, In Service, Rented, Maintenance). Real-time calendar grids display booking blocks, allowing dispatchers to drag-and-drop bookings, assign drivers, and edit rental agreements with ease.</p><h4>Stripe Payment Holds &amp; Security Vaults</h4><p>Security is critical for rental transactions. We integrate robust payment gateways (like Stripe) that support pre-authorized security holds. This holds deposit funds on a customer\'s card during the rental period and automatically releases them post-inspection. All customer data and driver licenses are stored in encrypted database tables complying with local privacy frameworks.</p><h4>Key Operational Features</h4><ul><li><strong>Live Fleet Status Matrices:</strong> Color-coded reservation grids detailing vehicle status, fuel levels, and service schedules.</li><li><strong>Automated Driver Scheduling:</strong> Assigning local dispatchers and drivers to airport transfer bookings with SMS notifications.</li><li><strong>Digital Rental Agreements:</strong> Auto-generating PDF invoices and signature-ready contracts on checkout completion.</li><li><strong>GPS Telemetry Integrations:</strong> Integrating real-time coordinate logs and mileage trackers into backend analytics.</li></ul><p>Whether you manage a boutique local car hire in Kanpur or a large-scale corporate fleet in the UK, our custom booking systems ensure speed, security, and absolute database integrity.</p>', 'car', 14],
        ['Event Ticketing & QR Check-In', 'event-ticketing', 'Event ticketing reservation systems, seat map designers, and mobile QR ticket validation scanners.', '<h3>Event Reservation Engines & QR Check-In</h3><p>Sell tickets directly. We design event registration platforms with seating map selections, barcode invoice generation, and QR scan apps for door check-ins.</p><h4>Event Ticketing Capabilities</h4><ul><li><strong>Dynamic Seat Maps:</strong> Visual seating charts with variable pricing tiers.</li><li><strong>QR Ticket Invoices:</strong> Dynamic barcode tickets sent via email.</li><li><strong>Door Scan API:</strong> Mobile scan endpoints for rapid door validation.</li></ul><p>Maximize ticket sales, eliminate duplicate passes, and streamline gate management.</p>', 'ticket', 15],
        ['AgTech Yield Software', 'agtech-yield', 'Agriculture crop monitoring databases, harvest calendar schedules, and soil moisture sensor dashboards.', '<h3>AgTech Crop Monitoring & Harvest Databases</h3><p>Track farm outputs. We build agriculture portals that log crop yields, soil moisture logs, weather forecasts, and harvest scheduling charts.</p><h4>AgTech Key Features</h4><ul><li><strong>Yield Logging:</strong> Database tables to track crop outputs per field unit.</li><li><strong>Sensor Feeds:</strong> Integrating telemetry logs from moisture and temperature grids.</li><li><strong>Harvest Calendars:</strong> Dispatch calendars for harvest logistics.</li></ul><p>Audit farm parameters, track crop yields, and plan harvest operations.</p>', 'leaf', 16],
        ['Bespoke CRM & Enterprise ERP Systems', 'bespoke-crm-erp', 'Integrated dashboard software for automating sales pipelines, customer relations, employee attendance, inventory tracking, and billing.', '<p>Generic CRM and ERP systems often force companies to change their workflows to fit the software. Our custom CRM and ERP developments align perfectly with your existing operations.</p><h4>Key Enterprise Features</h4><ul><li><strong>Automated Workflows:</strong> Streamline sales, HR requests, lead generation, and invoicing.</li><li><strong>Role-Based Analytics:</strong> Dashboards customized for field executives, team managers, and C-level directors.</li><li><strong>Custom POS & Inventory:</strong> Seamless tracking of physical assets and warehouse storage.</li></ul>', 'briefcase', 17],
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
        ['Unlocking Enterprise Scale with Laravel MVC Architecture', 'laravel-enterprise-scale', 'How structured routing, cached query patterns, and modular Eloquent ORM enable high-performance client dashboards.', "<p>Laravel has redefined modern backend web development. Its MVC architecture, combined with tools like Eloquent ORM, allows us to build fast, secure database layers. In this article, we explain how we structured our database routing pipelines to scale our custom web portal systems.</p><p>By utilizing Laravel's built-in query caching and database migration triggers, we reduce deployment overhead by up to 40% while ensuring zero downtime for business operations. Reach out to our team to consult on scaling your existing PHP databases.</p>", 'Admin', 'uploads/laravel_backend.png', 'Published'],
        ['Why Custom E-Commerce Architectures Win Over Generic Platforms', 'custom-ecommerce-architectures', 'Exploring PrestaShop, Shopify, and Magento custom code structures for multi-store scaling and inventory speeds.', '<p>Many online businesses start with simple page builders, but as catalogs grow, loading speeds drop. Custom Magento, Shopify, and PrestaShop code pipelines enable secure multi-store inventory configurations and faster checkouts.</p><p>With custom integrations, you have complete control over payment gateways, API webhooks, automated invoicing, and database scaling. We design systems to load under 1.5 seconds, even with tens of thousands of catalog products.</p>', 'Admin', 'uploads/ecommerce_shopping.png', 'Published'],
        ['The Complete Blueprint for Native iOS and Android Application Design', 'native-mobile-design-blueprint', "How we utilize Apple's native Swift and Google's Kotlin Compose APIs to build fluid, high-fidelity mobile experiences.", '<p>Native iOS app development requires deep integration with Apple Core Frameworks. By writing clean Swift code and modular SwiftUI components, we can achieve high-frame-rate rendering, smooth interface transitions, and tight security controls.</p><p>On Android, Kotlin and Jetpack Compose provide a reactive model that accelerates layout construction. Learn how to optimize your app for modern iOS and Android devices.</p>', 'Admin', 'uploads/mobile_app_dev.png', 'Published'],
        ['Optimizing School ERP and Hospital Management Software', 'school-erp-hospital-management-software', 'A guide to streamlining educational records, hospital billing databases, and medical store stock tracking.', '<p>Running an educational or healthcare facility requires high administrative accuracy. A custom School ERP manages student attendance, homework, exams, and fee collections. Similarly, Hospital management software and Medical Store billing systems synchronize patient records, pharmacy stock tracking, and accounting.</p><p>We build custom desktop and hybrid software solutions that securely aggregate these metrics, preventing data leaks and simplifying auditing.</p>', 'Admin', 'uploads/school_hospital_erp.png', 'Published'],
        ['A Strategic Guide to Global and UK Local SEO Campaigns', 'strategic-guide-seo-campaigns', 'Discover how keyword planning, technical audits, and content frameworks elevate search engine ranking results.', '<p>Search Engine Optimization (SEO) is not just about keywords; it is about satisfying user intent and core web vitals. To rank in the UK, US, and local Indian markets like Kanpur, you need distinct content frameworks.</p><p>We perform technical SEO diagnostic audits, set up canonical tags, inject JSON-LD schemas, and construct structured sitemaps. Learn our methods for ranking custom software and web services.</p>', 'Admin', 'uploads/seo_search_marketing.png', 'Published'],
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
