// app.js - Frontend JavaScript for LSPL redesign

document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Switcher Logic
    initTheme();

    // 2. Particle Canvas Background Engine
    initParticles();

    // 3. Typing Animation in Hero
    initTypingAnimation();

    // 4. Scrolled Navbar Effect
    initNavbarScroll();

    // 5. Services Filter & Modals
    initServices();

    // 6. Academy Tabs & Registration Modals
    initAcademy();

    // 7. Interactive Project Cost Estimator
    initEstimator();

    // 8. Contact Form AJAX Handling
    initContactForm();
});

/* ==========================================
   1. Theme Switcher
   ========================================== */
function initTheme() {
    const toggleBtn = document.getElementById('theme-toggle-btn');
    if (!toggleBtn) return;

    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(toggleBtn, currentTheme);

    toggleBtn.addEventListener('click', () => {
        const theme = document.documentElement.getAttribute('data-theme');
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nextTheme);
        localStorage.setItem('theme', nextTheme);
        updateThemeIcon(toggleBtn, nextTheme);
    });
}

function updateThemeIcon(btn, theme) {
    const icon = btn.querySelector('i');
    if (!icon) return;
    if (theme === 'light') {
        icon.className = 'lucide-moon';
        btn.setAttribute('aria-label', 'Switch to Dark Mode');
    } else {
        icon.className = 'lucide-sun';
        btn.setAttribute('aria-label', 'Switch to Light Mode');
    }
}

/* ==========================================
   2. Particle Canvas Engine
   ========================================== */
function initParticles() {
    const canvas = document.getElementById('particle-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let particles = [];
    const maxParticles = 60;
    
    // Set canvas dimensions
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;
            this.radius = Math.random() * 2 + 1;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;

            // Bounce off edges
            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            // Get active color configuration
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            ctx.fillStyle = isLight ? 'rgba(79, 70, 229, 0.15)' : 'rgba(129, 140, 248, 0.2)';
            ctx.fill();
        }
    }

    // Initialize particles
    for (let i = 0; i < maxParticles; i++) {
        particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw connections
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        const lineColor = isLight ? 'rgba(79, 70, 229, 0.04)' : 'rgba(129, 140, 248, 0.05)';
        
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 1;

        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();

            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < 120) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
        requestAnimationFrame(animate);
    }
    animate();
}

/* ==========================================
   3. Typing Animation
   ========================================== */
function initTypingAnimation() {
    const typingSpan = document.getElementById('typing-text');
    if (!typingSpan) return;

    const words = [
        'AI Intelligent Chatbots',
        'Custom SaaS Development',
        'AI Voice Calling Agents',
        'High-Performance Web Apps',
        'AI-Powered SEO Audits',
        'Advanced IT Certifications'
    ];
    let wordIndex = 0;
    let charIndex = 0;
    let isDeleting = false;

    function type() {
        const currentWord = words[wordIndex];
        
        if (isDeleting) {
            typingSpan.textContent = currentWord.substring(0, charIndex - 1);
            charIndex--;
        } else {
            typingSpan.textContent = currentWord.substring(0, charIndex + 1);
            charIndex++;
        }

        let typeSpeed = isDeleting ? 40 : 80;

        if (!isDeleting && charIndex === currentWord.length) {
            typeSpeed = 2000; // Pause at end of word
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            wordIndex = (wordIndex + 1) % words.length;
            typeSpeed = 500; // Pause before typing next word
        }

        setTimeout(type, typeSpeed);
    }

    // Start typing
    setTimeout(type, 1000);
}

/* ==========================================
   4. Navbar Scroll Effect
   ========================================== */
function initNavbarScroll() {
    const header = document.querySelector('header');
    if (!header) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

/* ==========================================
   5. Services Filtering & Modals
   ========================================== */
function initServices() {
    // A. Filter buttons
    const filterBtns = document.querySelectorAll('.filter-btn');
    const serviceCards = document.querySelectorAll('.service-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const category = btn.getAttribute('data-filter');
            
            serviceCards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'all' || cardCat === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // B. Service details modal
    const modal = document.getElementById('service-modal');
    if (!modal) return;

    const modalClose = modal.querySelector('.modal-close');
    const modalTitle = document.getElementById('modal-service-title');
    const modalDesc = document.getElementById('modal-service-description');
    const modalTech = document.getElementById('modal-service-tech');
    const modalWorkflow = document.getElementById('modal-service-workflow');

    // Default workflows based on service type
    const defaultWorkflows = {
        'AI & Automation': [
            'Analyze business data & specify custom agent requirements.',
            'Design NLP conversational trees or configure low-latency Twilio voice trunks.',
            'Connect LLM pipelines (RAG vector models) to local knowledge directories.',
            'Perform rigorous regression tests on bot prompts and call workflows.',
            'Deploy cloud instances and monitor feedback logs via dashboard logs.'
        ],
        'SaaS Development': [
            'Architect secure multi-tenant structures, data entities, and database bounds.',
            'Establish API controllers (Node.js/Go) and dashboard structures (Next.js).',
            'Integrate Stripe recurring checkouts and secure customer billing consoles.',
            'Conduct load test benchmarks to guarantee seamless scale.',
            'Launch environment pipelines via containerized Docker environments.'
        ],
        'Marketing & Search': [
            'Run deep indexing crawls to identify search errors and structural bottlenecks.',
            'Generate semantic intent mapping profiles using GPT keyword dashboards.',
            'Perform metadata enhancements, optimize loading speed, and link networks.',
            'Optimize local maps, review profile setups, and run paid ad campaigns.',
            'Deliver monthly diagnostic reports mapping traffic and keyword indexing.'
        ],
        'Web & Software': [
            'Collaborate on client specifications, wireframes, and CSS design tokens.',
            'Construct robust PHP/Node servers and responsive frontend views.',
            'Establish database tables, write SQL queries, and construct CMS portals.',
            'Validate responsive mobile views and cross-browser formatting.',
            'Deploy code to secure hosts, configure SSL, and deliver operations manuals.'
        ],
        'default': [
            'Scope exact project requirements, goals, and technical dependencies.',
            'Draft system diagrams, UI prototypes, and wireframe outlines.',
            'Write secure, structured codebase utilizing modern coding frameworks.',
            'Conduct thorough verification, unit testing, and layout audits.',
            'Deploy production release and support operational launch.'
        ]
    };

    document.querySelectorAll('.open-service-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const card = btn.closest('.service-card');
            const title = card.querySelector('h3').textContent;
            const desc = card.querySelector('p').textContent;
            const tech = card.querySelector('.service-tech-tags').innerHTML;
            const category = card.getAttribute('data-category');

            modalTitle.textContent = title;
            modalDesc.textContent = desc;
            modalTech.innerHTML = tech;

            // Generate workflow list
            const workflow = defaultWorkflows[category] || defaultWorkflows['default'];
            modalWorkflow.innerHTML = '';
            workflow.forEach((step, index) => {
                const li = document.createElement('li');
                li.innerHTML = `<i>✔</i> <div><strong>Step ${index + 1}:</strong> ${step}</div>`;
                modalWorkflow.appendChild(li);
            });

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
}

/* ==========================================
   6. Academy Tabs & Registration Modals
   ========================================== */
function initAcademy() {
    const tabBtns = document.querySelectorAll('.academy-tab-btn');
    const panes = document.querySelectorAll('.academy-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-tab');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Registration Modal
    const regModal = document.getElementById('register-modal');
    if (!regModal) return;

    const modalClose = regModal.querySelector('.modal-close');
    const regCourseInput = document.getElementById('reg-course-name');
    const regForm = document.getElementById('academy-register-form');

    document.querySelectorAll('.open-register-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const courseTitle = btn.getAttribute('data-course');
            regCourseInput.value = courseTitle;
            regModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    const closeRegModal = () => {
        regModal.classList.remove('active');
        document.body.style.overflow = '';
    };

    modalClose.addEventListener('click', closeRegModal);
    regModal.addEventListener('click', (e) => {
        if (e.target === regModal) closeRegModal();
    });

    // Handle AJAX Form Submission
    if (regForm) {
        regForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const submitBtn = regForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(regForm);
            formData.append('action', 'register_academy');

            fetch('submit_lead.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Registration Successful! We will contact you soon.', 'success');
                    regForm.reset();
                    closeRegModal();
                } else {
                    showToast(data.message || 'An error occurred. Please try again.', 'error');
                }
            })
            .catch(err => {
                showToast('Network error. Please try again.', 'error');
                console.error(err);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
}

/* ==========================================
   7. Interactive Cost Estimator
   ========================================== */
function initEstimator() {
    const steps = document.querySelectorAll('.estimator-step');
    const progressSteps = document.querySelectorAll('.progress-step');
    const progressBar = document.getElementById('estimator-progress-bar');
    const nextBtn = document.getElementById('est-next-btn');
    const prevBtn = document.getElementById('est-prev-btn');
    const budgetVal = document.getElementById('est-budget-val');
    const estimatorForm = document.getElementById('estimator-form');

    if (steps.length === 0) return;

    let currentStep = 0;
    
    // Estimate parameters
    let selectedService = '';
    let selectedScale = '';
    let selectedTimeline = '';
    let currentCurrency = 'INR';

    // Auto-detect based on timezone
    try {
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone.toLowerCase();
        if (tz.includes('london') || tz.includes('gb') || tz.includes('uk')) {
            currentCurrency = 'GBP';
        } else if (tz.includes('europe') || tz.includes('berlin') || tz.includes('paris') || tz.includes('rome') || tz.includes('madrid') || tz.includes('amsterdam') || tz.includes('brussels') || tz.includes('dublin') || tz.includes('vienna') || tz.includes('lisbon')) {
            currentCurrency = 'EUR';
        } else if (tz.includes('america') || tz.includes('us')) {
            currentCurrency = 'USD';
        }
    } catch (e) {}

    const currencyButtons = document.querySelectorAll('.currency-selector .currency-btn');

    // IP-based currency detection
    fetch('https://ipapi.co/json/')
        .then(res => res.json())
        .then(data => {
            if (data && data.country_code) {
                const country = data.country_code.toUpperCase();
                if (country === 'GB') {
                    currentCurrency = 'GBP';
                } else if (['AT', 'BE', 'CY', 'EE', 'FI', 'FR', 'DE', 'GR', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PT', 'SK', 'SI', 'ES'].includes(country)) {
                    currentCurrency = 'EUR';
                } else if (country === 'US') {
                    currentCurrency = 'USD';
                } else if (country === 'IN') {
                    currentCurrency = 'INR';
                }
                
                // Update UI state
                currencyButtons.forEach(btn => {
                    if (btn.getAttribute('data-currency') === currentCurrency) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
                updateBudgetEstimate();
            }
        })
        .catch(err => console.log('Geo-IP lookup failed, using timezone/default fallback:', err));

    // Set active tab based on detected currency and add click listener
    currencyButtons.forEach(btn => {
        if (btn.getAttribute('data-currency') === currentCurrency) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
        
        btn.addEventListener('click', () => {
            currencyButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCurrency = btn.getAttribute('data-currency');
            updateBudgetEstimate();
        });
    });

    // Card Selections
    document.querySelectorAll('.estimator-step[data-step="1"] .option-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.estimator-step[data-step="1"] .option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedService = card.getAttribute('data-value');
            updateBudgetEstimate();
            nextBtn.disabled = false;
        });
    });

    document.querySelectorAll('.estimator-step[data-step="2"] .option-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.estimator-step[data-step="2"] .option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedScale = card.getAttribute('data-value');
            updateBudgetEstimate();
            nextBtn.disabled = false;
        });
    });

    document.querySelectorAll('.estimator-step[data-step="3"] .option-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.estimator-step[data-step="3"] .option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedTimeline = card.getAttribute('data-value');
            updateBudgetEstimate();
            nextBtn.disabled = false;
        });
    });

    function updateStepUI() {
        steps.forEach((step, idx) => {
            if (idx === currentStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        progressSteps.forEach((step, idx) => {
            if (idx <= currentStep) {
                step.classList.add('active');
                if (idx < currentStep) step.classList.add('completed');
                else step.classList.remove('completed');
            } else {
                step.classList.remove('active');
                step.classList.remove('completed');
            }
        });

        // Update progress line width
        const progressPercent = (currentStep / (steps.length - 1)) * 100;
        progressBar.style.width = `${progressPercent}%`;

        // Buttons display
        if (currentStep === 0) {
            prevBtn.style.visibility = 'hidden';
        } else {
            prevBtn.style.visibility = 'visible';
        }

        if (currentStep === steps.length - 1) {
            nextBtn.textContent = 'Submit Proposal';
        } else {
            nextBtn.textContent = 'Continue';
        }

        // Validate current step to enable/disable Next
        validateCurrentStep();
    }

    function validateCurrentStep() {
        if (currentStep === 0 && !selectedService) {
            nextBtn.disabled = true;
        } else if (currentStep === 1 && !selectedScale) {
            nextBtn.disabled = true;
        } else if (currentStep === 2 && !selectedTimeline) {
            nextBtn.disabled = true;
        } else {
            nextBtn.disabled = false;
        }
    }

    function updateBudgetEstimate() {
        if (!selectedService || !selectedScale || !selectedTimeline) return;

        // Base Costs (INR)
        const baseCosts = {
            'web_dev': 45000,
            'saas_dev': 150000,
            'ai_chatbots': 90000,
            'ai_calling': 120000,
            'ai_seo': 35000
        };

        const scaleMultipliers = {
            'startup': 1.0,
            'business': 1.6,
            'enterprise': 3.2
        };

        const timelineMultipliers = {
            'fast': 1.3,
            'standard': 1.0,
            'extended': 0.85
        };

        const base = baseCosts[selectedService] || 40000;
        const scaleMult = scaleMultipliers[selectedScale] || 1.0;
        const timeMult = timelineMultipliers[selectedTimeline] || 1.0;

        const calculatedBudget = base * scaleMult * timeMult;
        
        let lowRange = calculatedBudget * 0.9;
        let highRange = calculatedBudget * 1.1;
        let locale = 'en-IN';
        let currency = 'INR';

        if (currentCurrency === 'USD') {
            lowRange = Math.round((lowRange / 80) / 100) * 100;
            highRange = Math.round((highRange / 80) / 100) * 100;
            locale = 'en-US';
            currency = 'USD';
        } else if (currentCurrency === 'GBP') {
            lowRange = Math.round((lowRange / 100) / 100) * 100;
            highRange = Math.round((highRange / 100) / 100) * 100;
            locale = 'en-GB';
            currency = 'GBP';
        } else if (currentCurrency === 'EUR') {
            lowRange = Math.round((lowRange / 90) / 100) * 100;
            highRange = Math.round((highRange / 90) / 100) * 100;
            locale = 'en-IE';
            currency = 'EUR';
        } else {
            lowRange = Math.round(lowRange / 5000) * 5000;
            highRange = Math.round(highRange / 5000) * 5000;
        }

        const lowStr = lowRange.toLocaleString(locale, { style: 'currency', currency: currency, maximumFractionDigits: 0 });
        const highStr = highRange.toLocaleString(locale, { style: 'currency', currency: currency, maximumFractionDigits: 0 });

        budgetVal.textContent = `${lowStr} - ${highStr}`;
    }

    nextBtn.addEventListener('click', () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            updateStepUI();
        } else {
            // Submit form
            submitEstimatorData();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateStepUI();
        }
    });

    function submitEstimatorData() {
        const nameInput = document.getElementById('est-name');
        const emailInput = document.getElementById('est-email');
        const phoneInput = document.getElementById('est-phone');
        
        if (!nameInput.value || !emailInput.value || !phoneInput.value) {
            showToast('Please fill out all contact fields.', 'error');
            return;
        }

        const submitBtn = nextBtn;
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting Request...';

        const formData = new FormData(estimatorForm);
        formData.append('action', 'submit_estimator');
        formData.append('service', selectedService);
        formData.append('scale', selectedScale);
        formData.append('timeline', selectedTimeline);
        formData.append('estimated_budget', budgetVal.textContent);

        fetch('submit_lead.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Estimate request received! Our team will contact you.', 'success');
                // Reset form
                estimatorForm.reset();
                selectedService = '';
                selectedScale = '';
                selectedTimeline = '';
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                budgetVal.textContent = '₹0';
                currentStep = 0;
                updateStepUI();
            } else {
                showToast(data.message || 'Error sending request.', 'error');
            }
        })
        .catch(err => {
            showToast('Network error occurred.', 'error');
            console.error(err);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    // Init UI
    updateStepUI();
}

/* ==========================================
   8. Contact Form AJAX
   ========================================== */
function initContactForm() {
    const contactForm = document.getElementById('contact-form');
    if (!contactForm) return;

    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending Message...';

        const formData = new FormData(contactForm);
        formData.append('action', 'submit_contact');

        fetch('submit_lead.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Thank you! Your message has been sent successfully.', 'success');
                contactForm.reset();
            } else {
                showToast(data.message || 'Failed to send message. Please try again.', 'error');
            }
        })
        .catch(err => {
            showToast('Network connection error.', 'error');
            console.error(err);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}

/* ==========================================
   Utility: Toast Notifications
   ========================================== */
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast glass-panel';
    
    const iconName = type === 'success' ? 'check-circle' : 'alert-circle';
    const iconColor = type === 'success' ? 'var(--success)' : 'var(--destructive)';

    toast.innerHTML = `
        <i data-lucide="${iconName}" style="color: ${iconColor}; font-size: 1.25rem;"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 50);

    // Auto remove
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}
