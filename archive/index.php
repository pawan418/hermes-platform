<?php
// index.php - Main frontend landing page for LSPL
require_once __DIR__ . '/db.php';

// Fetch all settings
$settings_query = $db->query("SELECT key, value FROM settings");
$site = [];
while ($row = $settings_query->fetch()) {
    $site[$row['key']] = $row['value'];
}

// Fetch all services
$services_stmt = $db->query("SELECT * FROM services ORDER BY display_order ASC");
$services = $services_stmt->fetchAll();

// Fetch all academy courses
$academy_stmt = $db->query("SELECT * FROM academy ORDER BY display_order ASC");
$academy = $academy_stmt->fetchAll();

// Group academy courses by type
$workshops = [];
$bootcamps = [];
foreach ($academy as $item) {
    if ($item['type'] === 'workshop') {
        $workshops[] = $item;
    } else {
        $bootcamps[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site['site_title'] ?? 'LSPL'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site['meta_description'] ?? ''); ?>">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Canvas Particle Backdrop -->
    <canvas id="particle-canvas"></canvas>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="#" class="logo">
                <div class="logo-icon">L</div>
                <span>LSPL<span class="logo-dot">.</span></span>
            </a>
            
            <nav>
                <ul>
                    <li><a href="#" class="active">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#academy">Academy</a></li>
                    <li><a href="#estimator">Estimator</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            
            <div class="nav-actions">
                <button class="theme-toggle" id="theme-toggle-btn" aria-label="Toggle Theme">
                    <i data-lucide="sun"></i>
                </button>
                <a href="#estimator" class="btn btn-primary btn-sm btn-nav-cta" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Get Estimate</a>
                <button class="menu-toggle" id="menu-toggle-btn" aria-label="Toggle Menu">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-content">
            <span class="badge badge-primary hero-subtitle-badge">Redefining Tech Paradigms</span>
            <h1>
                <?php echo htmlspecialchars($site['hero_title'] ?? 'We Implement Your Thoughts Into Code'); ?>
            </h1>
            <p>
                <?php echo htmlspecialchars($site['hero_subtitle'] ?? ''); ?>
            </p>
            <div style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 600; min-height: 35px;">
                Specializing in <span id="typing-text" class="text-gradient-primary"></span>
            </div>
            <div class="hero-actions">
                <a href="#estimator" class="btn btn-primary">Start Project Estimator <i data-lucide="arrow-right"></i></a>
                <a href="#services" class="btn btn-outline">Explore Offerings</a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="hero-glow"></div>
            <!-- Interactive Device Mockup -->
            <div class="mockup-card glass-panel">
                <div class="mockup-header">
                    <span class="mockup-dot"></span>
                    <span class="mockup-dot"></span>
                    <span class="mockup-dot"></span>
                    <span class="mockup-title">LSPL Intelligent Agent</span>
                </div>
                <div class="mockup-chat">
                    <div class="chat-bubble bot">
                        Hello! I am your automated assistant. How can we transform your business operations today?
                    </div>
                    <div class="chat-bubble user">
                        We want to automate outbound scheduling and scale our web applications.
                    </div>
                    <div class="chat-bubble bot">
                        Perfect! We can deploy a custom low-latency AI Voice calling agent and scale your stack using React, Node.js, and AWS.
                    </div>
                </div>
                <div class="mockup-stats-bar">
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-primary">99.9%</span>
                        <span class="mockup-stat-lbl">Uptime SLA</span>
                    </div>
                    <div class="mockup-stat">
                        <span class="mockup-stat-val text-gradient-accent"><200ms</span>
                        <span class="mockup-stat-lbl">Voice Latency</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Panel -->
    <section class="stats-section">
        <div class="stats-grid glass-panel" style="border-radius: var(--radius-xl); padding: 2.5rem;">
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_projects'] ?? '500+'); ?></h3>
                <p>Projects Completed</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_students'] ?? '10,000+'); ?></h3>
                <p>Students Trained</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-primary"><?php echo htmlspecialchars($site['stats_technologies'] ?? '25+'); ?></h3>
                <p>Modern Stacks</p>
            </div>
            <div class="stat-item">
                <h3 class="text-gradient-accent"><?php echo htmlspecialchars($site['stats_experience'] ?? '8+ Years'); ?></h3>
                <p>Industry Presence</p>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="section-header">
            <span class="badge badge-accent">Our Capabilities</span>
            <h2>Integrated IT, AI & Marketing Solutions</h2>
            <p>We blend legacy engineering reliability with next-generation artificial intelligence and performance-driven marketing systems to unlock exponential business growth.</p>
        </div>

        <div class="services-filter">
            <button class="filter-btn active" data-filter="all">All Services</button>
            <button class="filter-btn" data-filter="AI & Automation">AI & Automation</button>
            <button class="filter-btn" data-filter="SaaS Development">SaaS Development</button>
            <button class="filter-btn" data-filter="Web & Software">Web & Software</button>
            <button class="filter-btn" data-filter="Marketing & Search">Marketing & Search</button>
            <button class="filter-btn" data-filter="Infrastructure">Infrastructure</button>
        </div>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="glass-card service-card" data-category="<?php echo htmlspecialchars($service['category']); ?>">
                    <div class="service-icon">
                        <i data-lucide="<?php echo htmlspecialchars($service['icon']); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: var(--muted-foreground); flex-grow: 1;">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                    
                    <div class="service-tech-tags">
                        <?php 
                        $tags = explode(',', $service['tech_stack']);
                        foreach ($tags as $tag) {
                            echo '<span class="tech-tag">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                        ?>
                    </div>
                    
                    <div class="service-card-footer">
                        <span class="badge badge-primary" style="font-size: 0.65rem;"><?php echo htmlspecialchars($service['category']); ?></span>
                        <a href="#" class="service-card-btn open-service-modal">Learn More <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Academy Section -->
    <section class="academy-section" id="academy">
        <div class="section-header">
            <span class="badge badge-primary">LSPL Academy</span>
            <h2>Professional Tech & Security Education</h2>
            <p>Empowering developers, students, and engineers with specialized hands-on bootcamps and intensive technical workshops led by seasoned software engineers.</p>
        </div>

        <div class="academy-tabs">
            <button class="academy-tab-btn active" data-tab="workshops-pane">Specialized Workshops</button>
            <button class="academy-tab-btn" data-tab="bootcamps-pane">Professional Bootcamps</button>
        </div>

        <!-- Workshops Pane -->
        <div class="academy-pane active" id="workshops-pane">
            <?php foreach ($workshops as $item): ?>
                <div class="glass-card course-card">
                    <div class="course-header">
                        <div class="course-meta">
                            <span class="course-duration"><i data-lucide="clock" style="width: 14px; height: 14px;"></i> <?php echo htmlspecialchars($item['duration']); ?></span>
                            <span class="course-price"><?php echo htmlspecialchars($item['price']); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p style="font-size: 0.82rem; color: var(--muted-foreground); margin-top: 0.5rem;"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                    </div>
                    
                    <p style="font-size: 0.9rem; color: hsl(var(--foreground) / 0.8); margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>
                    
                    <ul class="course-features">
                        <?php 
                        $feats = explode(',', $item['features']);
                        foreach ($feats as $feat) {
                            echo '<li><i data-lucide="check" style="width: 16px; height: 16px;"></i><span>' . htmlspecialchars(trim($feat)) . '</span></li>';
                        }
                        ?>
                    </ul>
                    
                    <button class="btn btn-primary open-register-modal" data-course="<?php echo htmlspecialchars($item['title']); ?>" style="width: 100%;">Enroll / Register Now</button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bootcamps Pane -->
        <div class="academy-pane" id="bootcamps-pane">
            <?php foreach ($bootcamps as $item): ?>
                <div class="glass-card course-card">
                    <div class="course-header">
                        <div class="course-meta">
                            <span class="course-duration"><i data-lucide="calendar" style="width: 14px; height: 14px;"></i> <?php echo htmlspecialchars($item['duration']); ?></span>
                            <span class="course-price" style="font-size: 1rem;"><?php echo htmlspecialchars($item['price']); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p style="font-size: 0.82rem; color: var(--muted-foreground); margin-top: 0.5rem;"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                    </div>
                    
                    <p style="font-size: 0.9rem; color: hsl(var(--foreground) / 0.8); margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>
                    
                    <ul class="course-features">
                        <?php 
                        $feats = explode(',', $item['features']);
                        foreach ($feats as $feat) {
                            echo '<li><i data-lucide="check" style="width: 16px; height: 16px;"></i><span>' . htmlspecialchars(trim($feat)) . '</span></li>';
                        }
                        ?>
                    </ul>
                    
                    <button class="btn btn-secondary open-register-modal" data-course="<?php echo htmlspecialchars($item['title']); ?>" style="width: 100%;">Apply to Bootcamp</button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Interactive Project Cost Estimator -->
    <section class="estimator-section" id="estimator">
        <div class="section-header">
            <span class="badge badge-accent">Interactive Tool</span>
            <h2>Estimate Your Project Budget</h2>
            <p>Select your requirements, business scale, and operational timeline. Get an instant budget calculation and request a technical proposal within 24 hours.</p>
        </div>

        <div class="glass-panel estimator-container">
            <!-- Currency Selector -->
            <div class="currency-selector">
                <button type="button" class="currency-btn active" data-currency="INR">₹ INR</button>
                <button type="button" class="currency-btn" data-currency="USD">$ USD</button>
                <button type="button" class="currency-btn" data-currency="GBP">£ GBP</button>
                <button type="button" class="currency-btn" data-currency="EUR">€ EUR</button>
            </div>
            <!-- Progress Bar -->
            <div class="estimator-progress">
                <div class="estimator-progress-bar" id="estimator-progress-bar"></div>
                <div class="progress-step active" data-step="1">1</div>
                <div class="progress-step" data-step="2">2</div>
                <div class="progress-step" data-step="3">3</div>
                <div class="progress-step" data-step="4">4</div>
            </div>

            <form id="estimator-form">
                <!-- Step 1: Select Service -->
                <div class="estimator-step active" data-step="1">
                    <h3 style="text-align: center;">What service matches your project goal?</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="web_dev">
                            <i data-lucide="globe"></i>
                            <strong>Web Application</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Custom interactive portals & servers</p>
                        </div>
                        <div class="option-card" data-value="saas_dev">
                            <i data-lucide="layers"></i>
                            <strong>SaaS Product</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Multi-tenant platforms with billings</p>
                        </div>
                        <div class="option-card" data-value="ai_chatbots">
                            <i data-lucide="message-square"></i>
                            <strong>AI Chatbots</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">NLP assistants & RAG vector data</p>
                        </div>
                        <div class="option-card" data-value="ai_calling">
                            <i data-lucide="phone-call"></i>
                            <strong>AI Voice Agents</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Low-latency call automation systems</p>
                        </div>
                        <div class="option-card" data-value="ai_seo">
                            <i data-lucide="search"></i>
                            <strong>AI & Hybrid SEO</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Semantic search ranking campaigns</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Scale -->
                <div class="estimator-step" data-step="2">
                    <h3 style="text-align: center;">Choose the scope and scale of operation</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="startup">
                            <i data-lucide="rocket"></i>
                            <strong>Startup MVP</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Focus on rapid speed and core functions</p>
                        </div>
                        <div class="option-card" data-value="business">
                            <i data-lucide="briefcase"></i>
                            <strong>Core Business</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Complete professional features & APIs</p>
                        </div>
                        <div class="option-card" data-value="enterprise">
                            <i data-lucide="shield-check"></i>
                            <strong>Enterprise Grade</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">Strict compliance, high load, audits</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Select Timeline -->
                <div class="estimator-step" data-step="3">
                    <h3 style="text-align: center;">What is your planned launch timeline?</h3>
                    <div class="options-grid">
                        <div class="option-card" data-value="fast">
                            <i data-lucide="zap"></i>
                            <strong>Fast-Track</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">< 1 Month (Requires priority coding)</p>
                        </div>
                        <div class="option-card" data-value="standard">
                            <i data-lucide="calendar-days"></i>
                            <strong>Standard</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">1 - 3 Months (Default duration)</p>
                        </div>
                        <div class="option-card" data-value="extended">
                            <i data-lucide="hourglass"></i>
                            <strong>Extended</strong>
                            <p style="font-size: 0.75rem; color: var(--muted-foreground);">3 - 6 Months (Complex scaling phases)</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Contact Details & Estimate -->
                <div class="estimator-step" data-step="4">
                    <h3 style="text-align: center;">Your Custom Estimate Proposal</h3>
                    <div class="estimator-summary">
                        <div>
                            <strong>Estimated Project Budget Range:</strong>
                            <p style="font-size: 0.8rem; color: var(--muted-foreground); margin-top: 0.25rem;">(Refines dynamically based on selected complexity)</p>
                        </div>
                        <div class="estimator-val" id="est-budget-val">₹0</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="est-name">Full Name *</label>
                            <input type="text" id="est-name" name="name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                        </div>
                        <div class="form-group">
                            <label for="est-email">Email Address *</label>
                            <input type="email" id="est-email" name="email" class="form-control" placeholder=" rahul@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="est-phone">Phone Number *</label>
                        <input type="tel" id="est-phone" name="phone" class="form-control" placeholder="e.g. +91 98765 43210" required>
                    </div>
                    <div class="form-group">
                        <label for="est-message">Brief Project Details / Notes</label>
                        <textarea id="est-message" name="message" class="form-control" rows="3" placeholder="Tell us briefly about the system goals or custom requirements..."></textarea>
                    </div>
                </div>

                <!-- Step Buttons -->
                <div class="estimator-footer">
                    <button type="button" class="btn btn-outline" id="est-prev-btn">Back</button>
                    <button type="button" class="btn btn-primary" id="est-next-btn">Continue</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Technologies Grid -->
    <section class="tech-section">
        <div class="section-header">
            <span class="badge badge-primary">Tech Stack</span>
            <h2>Powered by State-of-the-Art Technologies</h2>
            <p>Our engineers utilize highly performant, secure, and modern programming architectures to build resilient applications.</p>
        </div>

        <div class="tech-grid">
            <div class="glass-card tech-item">
                <i data-lucide="layers" style="color: hsl(var(--primary));"></i>
                <span>Next.js / React</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="server" style="color: hsl(var(--secondary));"></i>
                <span>Node.js / Go</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="brain" style="color: hsl(var(--accent));"></i>
                <span>OpenAI / RAG</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="database" style="color: hsl(var(--success));"></i>
                <span>PostgreSQL / SQLite</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="terminal" style="color: hsl(var(--warning));"></i>
                <span>Python / FastAPI</span>
            </div>
            <div class="glass-card tech-item">
                <i data-lucide="shield" style="color: hsl(var(--destructive));"></i>
                <span>DevSecOps / Auth</span>
            </div>
        </div>
    </section>

    <!-- About Section & Timeline -->
    <section class="about-section" id="about">
        <div class="section-header">
            <span class="badge badge-accent">Our Journey</span>
            <h2>Redesigning the Future of LSPL</h2>
            <p>Founded on the principle of bringing "life to your thoughts", Longway Softronix Pvt. Ltd. (LSPL) has evolved from local web work into a global technological agency.</p>
        </div>

        <div class="about-content">
            <div class="about-text">
                <h3>Our Code of Ethics & Values</h3>
                <p>We believe in high-integrity engineering. Whether deploying secure database-driven enterprise systems or training students in our Academy, we ensure compliance, performance, and transparency.</p>
                <p>Now, blending advanced AI voice trunking, custom NLP chatbots, and automated marketing flows, we empower corporate enterprises to scale their business operations efficiently.</p>
                <a href="#estimator" class="btn btn-outline" style="margin-top: 1rem;">Work With Us <i data-lucide="arrow-up-right"></i></a>
            </div>

            <!-- Visual Timeline -->
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2018 - The Foundation</div>
                    <div class="timeline-desc">LSPL was incorporated, focusing on local web layout designing, local network cabling installations, and software setups.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2020 - Academy Expansion</div>
                    <div class="timeline-desc">Launched the popular <strong>weBOShop</strong> and <strong>hackIon</strong> cybersecurity training workshops, training over 5,000 students locally.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2023 - Cloud & SaaS Solutions</div>
                    <div class="timeline-desc">Scaled our portfolio to build cloud-native SaaS applications, Stripe recurring checkouts, and APIs for global startups.</div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-year">2026 - AI-Driven Agency</div>
                    <div class="timeline-desc">Redesigned into a premium, next-generation agency offering custom AI voice calling agents, conversational bots, and semantic SEO frameworks.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="section-header">
            <span class="badge badge-primary">Get in Touch</span>
            <h2>Let's Discuss Your Thought</h2>
            <p>Connect with our engineering office or academy center. Send us a message and we will respond back within 12 hours.</p>
        </div>

        <div class="contact-layout">
            <div class="contact-info">
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="mail"></i></div>
                    <div class="contact-text">
                        <h4>Email Us</h4>
                        <p><?php echo htmlspecialchars($site['contact_email'] ?? 'info@longwaysoftronix.com'); ?></p>
                    </div>
                </div>
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="phone"></i></div>
                    <div class="contact-text">
                        <h4>Call Support</h4>
                        <p><?php echo htmlspecialchars($site['contact_phone'] ?? '+91-8840010951'); ?></p>
                    </div>
                </div>
                <div class="glass-card contact-card">
                    <div class="contact-icon"><i data-lucide="map-pin"></i></div>
                    <div class="contact-text">
                        <h4>Visit Office</h4>
                        <p><?php echo htmlspecialchars($site['contact_address'] ?? 'Lucknow, India'); ?></p>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <form id="contact-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="con-name">Your Name *</label>
                            <input type="text" id="con-name" name="name" class="form-control" placeholder="Rahul" required>
                        </div>
                        <div class="form-group">
                            <label for="con-email">Email Address *</label>
                            <input type="email" id="con-email" name="email" class="form-control" placeholder="rahul@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="con-phone">Phone Number *</label>
                        <input type="tel" id="con-phone" name="phone" class="form-control" placeholder="+91 9876543210" required>
                    </div>
                    <div class="form-group">
                        <label for="con-message">Message *</label>
                        <textarea id="con-message" name="message" class="form-control" rows="4" placeholder="How can we help you? Write your requirements here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Send Direct Message <i data-lucide="send" style="width: 14px; height: 14px;"></i></button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="logo">
                    <div class="logo-icon">L</div>
                    <span>LSPL<span class="logo-dot">.</span></span>
                </a>
                <p>Longway Softronix Pvt. Ltd. (LSPL) provides premium custom software design, multi-tenant SaaS engineering, low-latency AI calling channels, and expert bootcamps.</p>
            </div>
            <div class="footer-col">
                <h4>Core Offerings</h4>
                <ul class="footer-links">
                    <li><a href="#services">AI Chatbots & Voice Agents</a></li>
                    <li><a href="#services">Custom SaaS Engineering</a></li>
                    <li><a href="#services">AI-Based Search Optimization</a></li>
                    <li><a href="#services">Software Solutions</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Academy & Training</h4>
                <ul class="footer-links">
                    <li><a href="#academy">weBOShop 2.0 Workshop</a></li>
                    <li><a href="#academy">hackIon 2.0 Cybersecurity</a></li>
                    <li><a href="#academy">MERN Stack Bootcamps</a></li>
                    <li><a href="#academy">Python AI/ML Training</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Administration</h4>
                <ul class="footer-links">
                    <li><a href="login.php" class="text-gradient-primary" style="font-weight: 600;">Admin Dashboard Login</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Disclaimer</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Longway Softronix Pvt. Ltd. All rights reserved.</p>
            <div class="social-links">
                <a href="#" class="social-link"><i data-lucide="twitter" style="width: 16px; height: 16px;"></i></a>
                <a href="#" class="social-link"><i data-lucide="linkedin" style="width: 16px; height: 16px;"></i></a>
                <a href="#" class="social-link"><i data-lucide="github" style="width: 16px; height: 16px;"></i></a>
            </div>
        </div>
    </footer>

    <!-- Service Details Modal -->
    <div class="modal-overlay" id="service-modal">
        <div class="modal-content glass-panel">
            <button class="modal-close"><i data-lucide="x"></i></button>
            <div class="modal-header">
                <div class="service-icon" style="margin-bottom: 0;">
                    <i data-lucide="info"></i>
                </div>
                <div>
                    <h3 id="modal-service-title">Service Details</h3>
                    <span class="badge badge-accent" id="modal-service-tech-title">Tech Blueprint</span>
                </div>
            </div>
            <div class="modal-body">
                <p id="modal-service-description" style="color: var(--muted-foreground); font-size: 0.95rem;"></p>
                
                <div>
                    <h4 style="margin-bottom: 0.75rem; font-size: 1rem;">Core Technology Stack:</h4>
                    <div class="service-tech-tags" id="modal-service-tech" style="border-top: none; padding-top: 0; margin-top: 0;">
                        <!-- Tags inserted dynamically -->
                    </div>
                </div>

                <div>
                    <h4 style="margin-bottom: 0.75rem; font-size: 1rem;">Our Operational Execution Steps:</h4>
                    <ul class="modal-workflow-list" id="modal-service-workflow">
                        <!-- Steps inserted dynamically -->
                    </ul>
                </div>
                
                <a href="#estimator" class="btn btn-primary" style="margin-top: 1rem; width: 100%;" onclick="document.getElementById('service-modal').classList.remove('active');">Launch Estimator For This Service</a>
            </div>
        </div>
    </div>

    <!-- Academy Registration Modal -->
    <div class="modal-overlay" id="register-modal">
        <div class="modal-content glass-panel" style="max-width: 500px;">
            <button class="modal-close"><i data-lucide="x"></i></button>
            <div class="modal-header">
                <div class="service-icon" style="margin-bottom: 0; background: hsla(var(--secondary) / 0.15); color: hsl(var(--secondary));">
                    <i data-lucide="graduation-cap"></i>
                </div>
                <div>
                    <h3>Academy Registration</h3>
                    <span class="badge badge-secondary">LSPL Educational Track</span>
                </div>
            </div>
            
            <form id="academy-register-form" style="margin-top: 1.5rem;">
                <input type="hidden" name="course_name" id="reg-course-name">
                
                <div class="form-group">
                    <label for="reg-name">Full Name *</label>
                    <input type="text" id="reg-name" name="name" class="form-control" placeholder=" राहुल कुमार" required>
                </div>
                
                <div class="form-group">
                    <label for="reg-email">Email Address *</label>
                    <input type="email" id="reg-email" name="email" class="form-control" placeholder="rahul@example.com" required>
                </div>

                <div class="form-group">
                    <label for="reg-phone">Phone Number *</label>
                    <input type="tel" id="reg-phone" name="phone" class="form-control" placeholder="+91 9876543210" required>
                </div>

                <div class="form-group">
                    <label for="reg-message">Study Goals / Previous Experience</label>
                    <textarea id="reg-message" name="message" class="form-control" rows="3" placeholder="Tell us briefly about your expectations or goals for this course..."></textarea>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 1rem;">Complete Registration</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="app.js"></script>
    <script>
        // Trigger Lucide icons mapping
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
