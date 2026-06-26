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
            return $base_path . 'ai-capabilities';
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

// lsxpl_v2/db.php - Database connection and auto-initialization
$db_file = __DIR__ . '/lsxpl_ai_v2.sqlite';
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
        'site_title' => 'LSXPL | AI Research Lab & Intelligent SaaS Platforms',
        'site_tagline' => 'eXPlore, eXPerience, eXPand',
        'contact_email' => 'lab@lsxpl.com',
        'contact_phone' => '+91-8840010951',
        'contact_address' => '25/6 Shastri Nagar, Kanpur, UP 208005, India',
        'meta_description' => 'LSXPL is an advanced AI research lab and intelligent software agency. We specialize in custom outbound AI voice calling agents, conversational support chatbots, multi-tenant Next.js AI SaaS platforms, and secure LLM prompt injection audits for clients in the UK and globally.',
        'hero_title' => 'Autonomous AI Agent Orchestration',
        'hero_subtitle' => 'LSXPL develops state-of-the-art NLP models, outbound calling voice bots, predictive booking algorithms, and security audits for large language models to future-proof your business operations.',
        'stats_projects' => '120+ AI Deployments',
        'stats_students' => '3,500+ AI Members',
        'stats_technologies' => '10+ LLM Models',
        'stats_experience' => '5+ Years',
        'canonical_url' => 'https://www.lsxpl.com',
        'og_image_url' => 'logo.png',
        'schema_markup' => '{"@context":"https://schema.org","@type":"ResearchOrganization","name":"LSXPL AI Research Lab","url":"https://www.lsxpl.com","logo":"https://www.lsxpl.com/logo.png","address":{"@type":"PostalAddress","streetAddress":"25/6 Shastri Nagar","addressLocality":"Kanpur","addressRegion":"UP","postalCode":"208005","addressCountry":"IN"}}',
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
        ['AI Conversational Chatbots', 'ai-conversational-chatbots', 'Context-aware NLP-driven customer assistance agents. Custom support workflows built on robust vector databases.', '<h3>Conversational NLP Chatbots & Vector Databases</h3><p>We build intelligent, context-aware conversational chatbots that integrate with your corporate databases. Using advanced semantic search and vector stores, our chatbots retrieve accurate answers from your documents, reducing support loads by up to 60%.</p><h4>Conversational Chatbot Specs</h4><ul><li><strong>Vector Embeddings:</strong> Mapping document metrics into vector databases (Pinecone/Chroma).</li><li><strong>Context Retrieval:</strong> Retrieval-Augmented Generation (RAG) to prevent model hallucinations.</li><li><strong>Multi-Channel deployment:</strong> Launching chatbots on web portfolios, Slack, and WhatsApp.</li></ul><p>Our systems integrate seamlessly with web portals, Slack, and corporate CRM software, providing instant customer assistance.</p>', 'message-square', 'AI & Automation', 'OpenAI API, LangChain, Python, VectorDB, FastAPI', 1],
        ['Outbound AI Voice Agents', 'outbound-ai-voice-agents', 'Automated outbound conversational calling voice agents. General voice calling API integrations for sales and bookings.', '<h3>Conversational Outbound AI Voice Agents</h3><p>We design custom outbound AI voice calling systems that conduct natural human-like phone conversations. Using low-latency voice engines, these agents schedule appointments, qualify leads, and handle inbound customer calls.</p><h4>AI Voice Calling Specs</h4><ul><li><strong>Low-latency Audio:</strong> Under 600ms speech response times for natural flow.</li><li><strong>Speech-to-Text:</strong> Real-time transcription of customer voice notes.</li><li><strong>Retell & Vapi APIs:</strong> Connecting speech nodes to custom phone routes.</li></ul><p>We build connections to Retell AI, ElevenLabs, and Vapi using secure backend API webhooks, automating calendar shift bookings.</p>', 'phone-call', 'AI & Automation', 'Vapi, Retell AI, ElevenLabs, Custom Voice APIs', 2],
        ['Custom AI SaaS Platforms', 'custom-saas-ai-platforms', 'Multi-tenant Next.js AI software architectures, integrating speech recognition, text-to-speech, and database orchestration.', '<h3>Multi-Tenant AI SaaS Engineering & Next.js</h3><p>Launch your AI startup. We engineer custom multi-tenant SaaS platforms featuring user authentication, payment subscriptions (Stripe), dynamic user credits, and speech-to-text / text-to-speech API pipelines.</p><h4>AI SaaS Core Stack</h4><ul><li><strong>Next.js App Router:</strong> Building responsive, fast SaaS client dashboards.</li><li><strong>Stripe Webhooks:</strong> Automating user subscription renewals.</li><li><strong>Credit Systems:</strong> Allocating LLM token credits per customer account.</li></ul><p>Our Next.js front-ends guarantee sub-second load times and elegant cyberpunk glassmorphic interfaces, optimized for SaaS startups.</p>', 'cpu', 'AI & Automation', 'Next.js, React, Node.js, Python, SQLite', 3],
        ['AI Model Security & Prompt Injection Audits', 'ai-model-security-audits', 'OWASP prompt injection vulnerability checks, model guardrails auditing, and API penetration testing.', '<h3>AI Prompt Injection Audits & LLM Security</h3><p>Audit and secure your LLM integrations. We run security audits against model prompt injections, data leaks, and unauthorized tool calls. We set up defensive guardrails (LlamaGuard, NeMo Guardrails) to keep your AI agents compliant with data safety standards.</p><h4>AI Security Checklist</h4><ul><li><strong>Prompt Shielding:</strong> Defending database connections from malicious user inputs.</li><li><strong>Tool Sandboxing:</strong> Restricting LLM execution parameters.</li><li><strong>Vulnerability Auditing:</strong> Reviewing AI code blocks against OWASP LLM guidelines.</li></ul><p>Keep your AI platforms secure and secure customer trust.</p>', 'shield', 'Security & Audits', 'Guardrails, Prompt Defense, OWASP LLM Audits', 4],
        ['Headless CMS & Jamstack AI Integrations', 'headless-cms-jamstack-ai', 'Next.js headless Shopify stores with AI product recommendation models and personalized content search engines.', '<h3>JAMstack Headless Stores & AI Recommendations</h3><p>Combine sub-second JAMstack shop loads with AI recommendation algorithms. We build Next.js headless Shopify storefronts that suggest personalized products to shoppers based on real-time behavior tracking, increasing sales conversions.</p><h4>E-Commerce AI Specs</h4><ul><li><strong>Next.js Static Generation:</strong> Pre-rendering e-commerce storefronts for instant loading.</li><li><strong>Personalization models:</strong> Analyzing shopper navigation to recommend products.</li><li><strong>Shopify GraphQL API:</strong> Fetching e-commerce catalogs via secure, fast API routes.</li></ul><p>Increase your online sales conversions with AI personalization and Next.js.</p>', 'globe', 'AI Solutions', 'Next.js, Jamstack, Shopify GraphQL, AI Search', 5],
        ['WhatsApp AI Conversational Commerce', 'whatsapp-ai-commerce', 'WhatsApp customer support AI chatbots and conversational commerce agents for retail brands in the UK and globally.', '<h3>AI WhatsApp Chatbots & Conversational Commerce</h3><p>Meet customers where they are. We build intelligent WhatsApp chatbots that browse product catalogs, take table reservations, track packages, and answer support queries. Integrated with LangGraph and WhatsApp Business APIs.</p><h4>WhatsApp AI Highlights</h4><ul><li><strong>LangGraph Workflows:</strong> Structuring multi-step reservation and checkout paths.</li><li><strong>Dynamic Catalogs:</strong> Displaying product images directly inside WhatsApp chats.</li><li><strong>Real-time Sync:</strong> Updating your CRM database instantly on customer checkout.</li></ul><p>Automate retail sales and customer support directly inside WhatsApp.</p>', 'message-circle', 'AI & Automation', 'WhatsApp Business API, LangGraph, Node.js', 6],
        ['Computer Vision & Visual OCR Engines', 'computer-vision-ocr', 'Implement real-time object detection, facial recognition, document intelligence OCR, and spatial intelligence solutions.', '<p>Our visual AI systems extract actionable data from images, video streams, and scanned files. We build custom classifiers, OCR parsers, and tracking pipelines.</p><h4>Capabilities</h4><ul><li><strong>Visual OCR Parsers:</strong> Extract unstructured text from invoices, medical receipts, and ID documents with 99%+ accuracy.</li><li><strong>Object Detection & YOLO:</strong> Real-time tracking of assets, quality inspections on assembly lines.</li><li><strong>Facial & Biometric Recognition:</strong> Secure, privacy-preserving validation engines for access control.</li></ul>', 'eye', 'AI & Automation', 'Python, PyTorch, OpenCV, YOLOv8', 7],
        ['Predictive AI & Time-Series Forecasting', 'predictive-ai-forecasting', 'Train custom models to forecast customer churn, inventory demand, financial markets, and equipment failures before they happen.', '<p>Leverage your historical data. We deploy predictive algorithms that optimize your logistics pipelines, forecasting sales, market trends, and risk metrics.</p><h4>Use Cases</h4><ul><li><strong>Demand Forecasting:</strong> Minimize inventory overheads by anticipating purchase peaks.</li><li><strong>Churn Prediction:</strong> Retain high-value customers by spotting early warning signs.</li><li><strong>Anomaly Detection:</strong> Monitor server grids or manufacturing telemetry to predict hardware faults.</li></ul>', 'trending-up', 'AI & Automation', 'Python, TensorFlow, Scikit-Learn, Pandas', 8],
    ];
    $stmt = $db->prepare("INSERT INTO services (title, slug, description, content, icon, category, tech_stack, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($services as $s) {
        $stmt->execute($s);
    }

    // Seed industries
    $industries = [
        ['AI-Driven Educational ERP', 'ai-education-erp', 'Educational ERP platforms with AI student performance analytics and grading predictor models.', '<h3>AI-Driven Educational Performance Analytics</h3><p>Future-proof schools. Our AI-driven ERP analyzes historical student grades, attendance records, and logs to predict final performance outcomes. It alerts teachers about struggling students, helping automate curriculum pacing.</p><h4>AI Educational Features</h4><ul><li><strong>Predictive Grading:</strong> Machine learning models predict student exam outcomes.</li><li><strong>Pacing Algorithms:</strong> Recommending custom study schedules based on performance logs.</li><li><strong>Automated Reports:</strong> AI-generated student feedback cards for parents.</li></ul><p>Help teachers optimize classrooms with predictive AI student analytics.</p>', 'graduation-cap', 1],
        ['AI Healthcare & EHR Analytics', 'ai-healthcare-ehr', 'AI EHR clinical summarizers and diagnostic voice notes transcription engines.', '<h3>AI Medical EHR Summarizers & Diagnostics</h3><p>Reduce doctor paperwork. Our healthcare systems use speech recognition to transcribe diagnostic voice notes. It extracts medical terms and compiles them into structured EHR reports, improving clinical operations.</p><h4>AI Clinical Features</h4><ul><li><strong>Voice EHR Transcriber:</strong> Transcribing doctor clinical summaries with medical term mapping.</li><li><strong>EHR Synthesizer:</strong> Summarizing complex patient histories in seconds.</li><li><strong>Secure Databases:</strong> Encryption compliant with global medical data privacy rules.</li></ul><p>Allow doctors to focus on patients while AI handles EHR transcription.</p>', 'activity', 2],
        ['AI Pharmacy Billing OCR', 'ai-pharmacy-ocr', 'Pharmacy POS systems with AI invoice OCR scanning and predictive inventory forecasting.', '<h3>AI Pharmacy POS & Invoice OCR Scanners</h3><p>Avoid stock depletion. Our pharmacy system uses OCR to scan supplier invoices and parse batch numbers, expiry dates, and costs. It forecasts pharmacy stock demand using predictive analytics.</p><h4>AI Pharmacy POS Highlights</h4><ul><li><strong>OCR Invoice Parser:</strong> Parsing supplier invoice PDFs automatically.</li><li><strong>Predictive Stock Forecast:</strong> Forecasting pharmacy inventory requirements based on seasonality.</li><li><strong>Billing Integration:</strong> Quick checkout terminal interfaces with drug databases.</li></ul><p>Reduce human error and optimize medicine inventory with OCR and AI forecasts.</p>', 'pill', 3],
        ['AI Table Booking & Food Recommendation Bots', 'ai-restaurant-pos', 'AI restaurant table booking voice agents and personalized food recommender systems.', '<h3>AI Restaurant Table Booking & Food Bots</h3><p>Automate dining reservations. Our AI reservation bots take bookings via phone calls, sync calendars, and recommend personalized food items to customers based on past orders.</p><h4>AI Restaurant Features</h4><ul><li><strong>Voice Booking Assistant:</strong> Handling phone reservations via natural voice bots.</li><li><strong>Menu Recommender:</strong> Suggesting dishes based on regional trends and history.</li><li><strong>Calendar Sync:</strong> Updating restaurant reservation spreadsheets in real time.</li></ul><p>Optimize restaurant seat occupancy and automate table bookings.</p>', 'utensils', 4],
        ['AI Hotel Reservation & Room Pricing', 'ai-hotel-booking', 'Predictive hotel room pricing algorithms and reservation calendars.', '<h3>AI Predictive Hotel Pricing & Room Bookings</h3><p>Optimize room rates. Our hotel AI algorithms analyze regional occupancy trends to adjust room prices dynamically, while voice agents handle guest check-ins.</p><h4>AI Hotel Features</h4><ul><li><strong>Predictive Pricing Model:</strong> Dynamic calculations adjusting room rates.</li><li><strong>Voice Reservation Helper:</strong> AI phone agents take guest booking confirmations.</li><li><strong>Availability Sync:</strong> Real-time room booking calendars.</li></ul><p>Optimize hotel revenue and automate guest check-in bookings.</p>', 'hotel', 5],
        ['AI Voice Booking & Scheduling Assistants', 'ai-salon-scheduling', 'Salon scheduling voice assistants and Spa appointment scheduling bots.', '<h3>AI Salon Voice Schedulers & Booking Bots</h3><p>Fill appointment books automatically. Our salon voice assistants handle phone reservations, update scheduler calendars, and send automatic slots confirmations.</p><h4>AI Salon Scheduling Highlights</h4><ul><li><strong>Phone Scheduling Agent:</strong> Voice agent schedules spa appointments.</li><li><strong>Calendar Auto-Updates:</strong> Real-time slots matching to therapist calendars.</li><li><strong>No-Show Predictor:</strong> Automated SMS reminders sent to high-risk bookings.</li></ul><p>Fill scheduling slots automatically with AI phone booking bots.</p>', 'clock', 6],
        ['AI Property Valuation & Real Estate', 'ai-real-estate', 'AI real estate pricing estimators, property valuation models, and automated property search bots.', '<h3>AI Property Valuation & Real Estate Assistants</h3><p>Automate appraisals. Our algorithms estimate real estate values by analyzing comparable market listings and neighborhood trends.</p><h4>AI Real Estate Features</h4><ul><li><strong>Dynamic Price Estimator:</strong> Predicting property values.</li><li><strong>Search Match Bots:</strong> Recommending property options to buyer profiles.</li><li><strong>Agent Lead Match:</strong> Smart routing of property leads to real estate agents.</li></ul><p>Provide instant appraisals and match buyers with real estate listings.</p>', 'home', 7],
        ['Fintech Risk Assessment & AI Models', 'ai-fintech', 'Credit risk assessment AI models, loan approval predictors, and fraud detection analytics.', '<h3>AI Fintech Risk Assessment & Loan Predictors</h3><p>Secure micro-finance loans. We build risk assessment models that verify applicant records and predict credit scores, protecting lending firms from fraud.</p><h4>AI Fintech Capabilities</h4><ul><li><strong>Credit Score Predictor:</strong> Assessing loan approval risk using ML.</li><li><strong>Fraud Check Pipeline:</strong> Scanning applicant uploads for forged documents.</li><li><strong>Secure Transactions:</strong> Encrypted databases logging lending ledger records.</li></ul><p>Prevent credit defaults and automate fintech loan approvals.</p>', 'credit-card', 8],
        ['Logistics Delivery Route Optimization AI', 'ai-logistics', 'AI package delivery route planners, delivery dispatcher systems, and dispatch logs.', '<h3>AI Logistics Delivery Route Planners</h3><p>Reduce logistics fuel costs. Our routing models analyze traffic congestion, drop locations, and package weights to design optimized courier routes.</p><h4>AI Logistics Features</h4><ul><li><strong>Route Optimizer:</strong> Live calculation of optimal delivery paths.</li><li><strong>Load Allocation Model:</strong> Optimizing truck loading capacities.</li><li><strong>ETA Predictor:</strong> Real-time delivery status updates for customer sitemaps.</li></ul><p>Optimize cargo logistics dispatch shifts and save fuel costs.</p>', 'truck', 9],
        ['Fitness AI Workout & Workout Generators', 'ai-fitness', 'AI gym workout generators, member subscription trackers, and trainer scheduling helpers.', '<h3>AI Gym Workout Generators & Member Logs</h3><p>Personalize gym programs. Our AI fitness software generates custom workout plans based on customer health parameters, while tracking gym billing.</p><h4>AI Gym Capabilities</h4><ul><li><strong>Workout Generator Model:</strong> Automatic training plans based on goals.</li><li><strong>Member Billing Sync:</strong> Auto-invoicing monthly subscription card charges.</li><li><strong>Trainer Schedule Allocator:</strong> Smart slot booking templates.</li></ul><p>Provide customized gym programs and automate gym administration.</p>', 'heart', 10],
        ['Travel Itinerary AI Custom Generators', 'ai-travel', 'AI tourist itinerary planning builders and flight hotel reservation search assistants.', '<h3>AI Travel Itinerary Planners & Booking Helpers</h3><p>Design trips in seconds. Our AI travel agents compile hotels, flight schedules, and restaurant recommendations into customized itineraries.</p><h4>AI Travel Highlights</h4><ul><li><strong>Itinerary Generator:</strong> Instantly compile tourist plans based on budget.</li><li><strong>Hotel Price Search:</strong> Scraping optimal room reservation rates.</li><li><strong>Activity Recommender:</strong> Personalized tourist spot recommendations.</li></ul><p>Help travel agencies build custom trips in seconds.</p>', 'compass', 11],
        ['Legal Contract Summaries & Case Analytics', 'ai-legal', 'AI legal document summarizers, contract analysis checkers, and case calendar organizers.', '<h3>AI Legal Contract Summarizers & Case Checkers</h3><p>Audit legal agreements. Our NLP models summarize case files, flag risky clauses in contracts, and manage calendar court deadlines.</p><h4>AI Legal Features</h4><ul><li><strong>Contract Summarizer:</strong> Compiling 50-page agreements into key highlights.</li><li><strong>Risk Clause Spotter:</strong> Highlighting non-compliant legal terms.</li><li><strong>Hearing Deadline Sync:</strong> Automated law firm calendar alerts.</li></ul><p>Audit contracts and manage law firm cases with secure AI text tools.</p>', 'briefcase', 12],
        ['ATS Resume Match & HR Screening AI', 'ai-hr', 'AI applicant ATS resume parsers and recruitment voice interviewer helpers.', '<h3>AI ATS Resume Parsers & Interview Assistants</h3><p>Find the best talent. Our HR recruitment algorithms parse resume PDFs, score candidates against job requirements, and run voice pre-screening calls.</p><h4>AI HR Capabilities</h4><ul><li><strong>Resume Scanner:</strong> Score applicants based on skill keywords.</li><li><strong>Voice Screener Bot:</strong> Automated phone calls for initial HR screenings.</li><li><strong>Interview Schedulers:</strong> Live booking calendars for recruiter interviews.</li></ul><p>Automate HR screening and find the best candidates.</p>', 'users', 13],
        ['Car Rental AI Updates', 'ai-car-rental', 'Intelligent AI-driven car rental portals featuring demand forecasting, dynamic pricing, automated voice scheduling, and driver KYC validation.', '<h3>AI-Driven Car Rental &amp; Fleet Optimization</h3><p>Leverage autonomous artificial intelligence to revolutionize car hire booking, driver shift allocation, and real-time vehicle telemetrics. LSXPL AI Research Lab builds custom intelligent car rental portals featuring demand forecasting, automated voice dispatches, and predictive fleet maintenance algorithms.</p><h4>Predictive Demand Forecasting &amp; Pricing Models</h4><p>Maximize fleet utility and rental yields. Our machine learning models analyze historical hire trends, regional tourism patterns, and weather metrics to predict fleet demand and dynamically adjust rental pricing tiers. Our predictive engines ensure your vehicles are priced optimally to match seasonal customer activity.</p><h4>Autonomous Voice Booking &amp; Fleet Dispatches</h4><p>Streamline booking customer support. We build conversational voice calling bots that handle customer reservations over phone calls, verify driver license credentials via OCR scanners, and trigger automatic booking dispatches without human intervention.</p><h4>AI-Enhanced Fleet Telematics</h4><ul><li><strong>Predictive Maintenance Alerts:</strong> ML algorithms analyze engine telemetry logs to warn fleet managers before critical breakdowns occur.</li><li><strong>AI Route Optimization:</strong> Dynamic route planning for delivery fleets and shuttle drivers to minimize fuel consumption.</li><li><strong>Smart Driver Scoring:</strong> Tracking driving behaviors (acceleration, speed limits) to optimize fleet insurance rates.</li><li><strong>Instant Document OCR:</strong> Automated parsing and validation of passport and license uploads for swift KYC checks.</li></ul><p>Optimize your fleet assets, automate customer inquiries, and secure high-value vehicles with state-of-the-art AI-driven fleet platforms.</p>', 'car', 14],
        ['Smart Ticketing dynamic pricing AI', 'ai-events', 'AI ticket pricing models, seating booking systems, and QR scanner interfaces.', '<h3>AI Smart Event Ticketing & Dynamic Pricing</h3><p>Maximize event revenue. Our pricing models adjust ticket costs dynamically based on seat booking speed and event date proximity.</p><h4>AI Event Capabilities</h4><ul><li><strong>Dynamic Price Model:</strong> Automatically adjust ticket rates.</li><li><strong>Seat Map Sync:</strong> Interactive reservation maps.</li><li><strong>QR Entry API:</strong> Scanner endpoints for doors validation.</li></ul><p>Sell dynamic event tickets and automate door validation check-ins.</p>', 'ticket', 15],
        ['AI Logistics Delivery Route Optimizer', 'ai-logistics-optimizer', 'Smart routing algorithm integrating historical traffic, weather forecasts, and package density to minimize fleet overheads.', '<p>Traditional dispatching is slow and inefficient. Our AI-driven route optimization engine automates delivery sequencing to slash transit times and fuel consumption.</p><h4>Core Tech Highlights</h4><ul><li><strong>Real-Time Dynamic Routing:</strong> Adjusts route plans mid-journey based on accidents or weather shifts.</li><li><strong>Load Density Matching:</strong> Matches vehicle capacity to package weights for fuel savings.</li><li><strong>Carbon Emission Reports:</strong> Visualizes green metrics and logistics savings on a dashboard.</li></ul>', 'truck', 16],
    ];
    $stmt = $db->prepare("INSERT INTO industries (title, slug, description, content, icon, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($industries as $ind) {
        $stmt->execute($ind);
    }

    // Seed pages
    $pages = [
        ['AI Research Agenda', 'research-agenda', '<h3>Our AI Research Mandate</h3><p>At LSXPL AI Lab, we study autonomous multi-agent orchestration, LLM safety guardrails, and real-time outbound voice calling architectures. Our research aims to make natural language processing systems secure, low-latency, and highly contextual for commercial integrations.</p>', 1, 1],
        ['SaaS Security & Trust', 'saas-trust', '<h3>Trust & Security Compliance</h3><p>Security is paramount in AI SaaS integrations. We enforce OWASP LLM security practices, run penetration tests against prompt injection vulnerabilities, and encrypt all database data blocks. Our software platforms conform to SOC2 and GDPR requirements.</p>', 1, 2],
        ['About Lab', 'about-lab', '<h3>LSXPL AI Research Lab</h3><p>LSXPL AI Research Lab is the dedicated artificial intelligence and agentic computing division of Longway Softronix Pvt. Ltd. Founded in Kanpur, India, our research lab focuses on developing low-latency conversational AI agents, autonomous workflow orchestration platforms, and multi-agent SaaS architectures.</p><p>Led by Director Pawan K Singh, our mission is to implement cutting-edge LLM security audits, prompt injection shielding, and voice telemetry systems for global enterprises.</p><h3 style="margin-top: 3rem; margin-bottom: 2rem;">Research Milestones</h3><div class="about-timeline"><div class="timeline-item"><div class="timeline-year">2014</div><div class="timeline-content"><h4>LSPL Incorporation</h4><p>Parent company Longway Softronix was founded, establishing core database and software competencies.</p></div></div><div class="timeline-item"><div class="timeline-year">2020</div><div class="timeline-content"><h4>NLP & Search Frameworks</h4><p>Pioneered advanced technical SEO semantic analysis and natural language parsing layouts.</p></div></div><div class="timeline-item"><div class="timeline-year">2023</div><div class="timeline-content"><h4>Lab Inauguration</h4><p>Officially founded LSXPL AI Lab, dedicating engineering resources to study generative models and LLM APIs.</p></div></div><div class="timeline-item"><div class="timeline-year">2025</div><div class="timeline-content"><h4>Multi-Tenant SaaS Shielding</h4><p>Developed secure multi-tenant credit managers and released frameworks to prevent prompt injections in business workflows.</p></div></div><div class="timeline-item"><div class="timeline-year">2026</div><div class="timeline-content"><h4>Low-Latency Voice Telemetry</h4><p>Launched voice routing algorithms and real-time speech telemetry models for enterprise automation.</p></div></div></div>', 1, 3],
    ];
    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, display_in_nav, display_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute($p);
    }

    // Seed blogs
    $blogs = [
        ['Architecting Low-Latency Outbound AI Voice Agents', 'low-latency-ai-voice-agents', 'How we connect ElevenLabs, Retell AI, and custom voice calling APIs to build secure speech assistants.', '<p>Building conversational AI voice agents for phone calls requires minimizing latency. Every millisecond counts. By combining low-latency text-to-speech models (like ElevenLabs) with robust voice calling APIs (Vapi/Retell), we achieve natural, real-time responses.</p><p>This article details our backend webhook architecture and how we synchronize user intent with database state during call routing.</p>', 'Admin', 'uploads/ai_voice_agents.png', 'Published'],
        ['Prompt Injection Vulnerabilities in Multi-Agent SaaS Systems', 'prompt-injection-saas-security', 'Essential safety strategies to audit and shield LLM system prompts and database actions.', '<p>Multi-agent SaaS systems expose database connections and external API triggers. If an attacker injects malicious instructions into a conversational prompt, they can execute unauthorized actions.</p><p>We conduct prompt injection audits, implement OWASP LLM security controls, and deploy guardrail layers to protect LLM backends.</p>', 'Admin', 'uploads/cybersecurity_hacking.png', 'Published'],
        ['OCR Invoice Parsing & Predictive Stocks in Pharmacy POS', 'ocr-invoice-parsing-predictive-pharmacy-pos', 'How AI is transforming retail pharmacy billing and supply inventory forecast models.', '<p>Manually entering billing invoices is error-prone. By deploying custom OCR models, pharmacy software can extract product names, expiry dates, and batches automatically. Additionally, predictive models analyze historical sales data to forecast future pharmacy stock requirements.</p>', 'Admin', 'uploads/school_hospital_erp.png', 'Published'],
        ['Predictive Hotel Pricing Algorithms and Restaurant Reservation Bots', 'predictive-hotel-pricing-restaurant-bots', 'How AI agents schedule salon calendars, table bookings, and room rates dynamically.', '<p>Hospitality operations are highly seasonal. By integrating AI booking bots, hotels can set predictive room pricing based on booking history, and restaurants can automate table reservations via voice bots, updating calendar systems in real time.</p>', 'Admin', 'uploads/industry_erp_solutions.png', 'Published'],
        ['Next.js Headless E-Commerce with AI Personalization', 'nextjs-headless-ecommerce-ai-personalization', 'Combining Jamstack Shopify stores with AI recommendation models to boost online retail conversions.', '<p>E-commerce is more competitive than ever. A Next.js headless Shopify store offers instant page loads. By integrating AI recommendation agents, we display personalized product suggestions based on real-time browser behaviors, improving sales conversions for UK retailers.</p>', 'Admin', 'uploads/ecommerce_shopping.png', 'Published'],
    ];
    $stmt = $db->prepare("INSERT INTO blogs (title, slug, summary, content, author, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($blogs as $b) {
        $stmt->execute($b);
    }

    // Seed header menu
    $header_menu = [
        [1, null, 'Lab Home', 'custom', null, 'index.php', 'single_page', null, 1],
        [41, null, 'About Lab', 'page', 'about-lab', null, 'single_page', null, 2],
        [2, null, 'AI Capabilities', 'none', null, null, 'megamenu', null, 3],
        [3, null, 'AI Sector Solutions', 'none', null, null, 'megamenu', null, 4],
        [4, null, 'Publications', 'custom', null, 'blog.php', 'single_page', null, 5],
        [5, null, 'Estimator', 'custom', null, 'estimator.php', 'single_page', null, 6],
        [16, 2, 'Conversational NLP Chatbots', 'custom', null, 'service/ai-conversational-chatbots', 'single_page', 'Conversational AI', 1],
        [17, 2, 'Outbound Calling Voice Bots', 'custom', null, 'service/outbound-ai-voice-agents', 'single_page', 'Conversational AI', 2],
        [18, 2, 'Custom AI SaaS Builder', 'custom', null, 'service/custom-saas-ai-platforms', 'single_page', 'AI SaaS & Platforms', 4],
        [19, 2, 'LLM Security & Penetration audits', 'custom', null, 'service/ai-model-security-audits', 'single_page', 'Vision & AI Audits', 6],
        [20, 2, 'JAMstack Headless AI Engine', 'custom', null, 'service/headless-cms-jamstack-ai', 'single_page', 'AI SaaS & Platforms', 5],
        [21, 2, 'WhatsApp AI Commerce', 'custom', null, 'service/whatsapp-ai-commerce', 'single_page', 'Conversational AI', 3],
        
        # Solutions (parent_id = 3) split in columns
        [22, 3, 'AI Student ERP Analytics', 'custom', null, 'industry/ai-education-erp', 'single_page', 'Operational AI', 1],
        [23, 3, 'AI Clinical EHR Summaries', 'custom', null, 'industry/ai-healthcare-ehr', 'single_page', 'Operational AI', 2],
        [24, 3, 'AI Invoice OCR Pharmacy', 'custom', null, 'industry/ai-pharmacy-ocr', 'single_page', 'Operational AI', 3],
        [25, 3, 'AI Legal Case Summaries', 'custom', null, 'industry/ai-legal', 'single_page', 'Operational AI', 4],
        [26, 3, 'AI ATS Resume Screening', 'custom', null, 'industry/ai-hr', 'single_page', 'Operational AI', 5],
        
        [27, 3, 'AI Restaurant Table Reservation', 'custom', null, 'industry/ai-restaurant-pos', 'single_page', 'Booking & Schedulers', 6],
        [28, 3, 'AI Hotel Dynamic Pricing', 'custom', null, 'industry/ai-hotel-booking', 'single_page', 'Booking & Schedulers', 7],
        [29, 3, 'AI Voice Salon Scheduling', 'custom', null, 'industry/ai-salon-scheduling', 'single_page', 'Booking & Schedulers', 8],
        [30, 3, 'Fitness AI Workout Coach', 'custom', null, 'industry/ai-fitness', 'single_page', 'Booking & Schedulers', 9],
        [31, 3, 'AI Dynamic Event Ticketing', 'custom', null, 'industry/ai-events', 'single_page', 'Booking & Schedulers', 10],
        
        [32, 3, 'AI Property Valuation', 'custom', null, 'industry/ai-real-estate', 'single_page', 'Valuation & Fleet AI', 11],
        [33, 3, 'AI Fintech Risk Assessment', 'custom', null, 'industry/ai-fintech', 'single_page', 'Valuation & Fleet AI', 12],
        [34, 3, 'AI Logistics Route Planner', 'custom', null, 'industry/ai-logistics', 'single_page', 'Valuation & Fleet AI', 13],
        [35, 3, 'AI Travel Itinerary Planner', 'custom', null, 'industry/ai-travel', 'single_page', 'Valuation & Fleet AI', 14],
        [36, 3, 'AI Car Rental Fleet Dispatch', 'custom', null, 'industry/ai-car-rental', 'single_page', 'Valuation & Fleet AI', 15],
        [42, 2, 'Computer Vision & Visual OCR', 'custom', null, 'service/computer-vision-ocr', 'single_page', 'Vision & AI Audits', 7],
        [43, 2, 'Predictive AI & Time-Series', 'custom', null, 'service/predictive-ai-forecasting', 'single_page', 'Vision & AI Audits', 8],
        [44, 3, 'AI Logistics Delivery Optimizer', 'custom', null, 'industry/ai-logistics-optimizer', 'single_page', 'Valuation & Fleet AI', 16],
    ];
    // We insert items preserving IDs to maintain parent-child relationships correctly
    $stmt = $db->prepare("INSERT INTO header_menu_items (id, parent_id, title, link_type, page_slug, custom_url, menu_type, column_name, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($header_menu as $h) {
        $stmt->execute($h);
    }

    // Seed footer items
    $footer_items = [
        ['AI Lab Research', 'Research Agenda', 'page', 'research-agenda', null, 1],
        ['AI Lab Research', 'Publications Log', 'custom', null, 'blog.php', 2],
        ['Lab Info', 'About Lab', 'page', 'about-lab', null, 3],
        ['Lab Info', 'Estimator Blueprint', 'custom', null, 'estimator.php', 4],
        ['Security & Compliance', 'SaaS Security & Trust', 'page', 'saas-trust', null, 5],
        ['Security & Compliance', 'Privacy policy statement', 'custom', null, 'page/saas-trust', 6],
    ];
    $stmt = $db->prepare("INSERT INTO footer_items (column_name, title, link_type, page_slug, custom_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($footer_items as $f) {
        $stmt->execute($f);
    }

    // Seed reviews
    $reviews = [
        ['Dr. Aris Vance', 'LSXPL delivered a highly responsive conversational AI voice agent for our client support hotline. The natural language processing latency is incredibly low. A stellar engineering team.', 5, 'google', 'AI Voice Agent System'],
        ['Elena Rostova', 'Fantastic custom NLP solution built by LSXPL. Their document classifier API saved our operations team hours of manual categorization. Deep learning model accuracy is outstanding.', 5, 'peopleperhour', 'Automated Document Classifier'],
        ['Liam Fitzpatrick', 'We integrated LSXPL\'s machine learning model into our supply chain ERP. The system\'s stock predictive analytics reduced our holding costs by 22%. Outstanding AI expertise!', 5, 'trustpilot', 'Predictive Inventory Modeler']
    ];
    $stmt = $db->prepare("INSERT INTO reviews (author_name, review_text, rating, platform, project_title) VALUES (?, ?, ?, ?, ?)");
    foreach ($reviews as $r) {
        $stmt->execute($r);
    }
}
?>
