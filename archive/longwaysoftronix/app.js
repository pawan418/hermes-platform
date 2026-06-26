// longwaysoftronix/app.js - Frontend script for LSPL Main Brand

document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Switcher
    initTheme();

    // 2. Particle Canvas Background
    initParticles();

    // 3. Typing Animation in Hero
    initTypingAnimation();

    // 4. Scrolled Navbar
    initNavbarScroll();

    // 5. Services Filter & Modals
    initServices();

    // 6. Responsive Drawer Menu
    initResponsiveMenu();

    // 7. Interactive Project Cost Estimator
    initEstimator();

    // 8. Contact Form AJAX
    initContactForm();
});

function initTheme() {
    const toggleBtn = document.getElementById('theme-toggle-btn');
    if (!toggleBtn) return;

    const currentTheme = localStorage.getItem('lspl_theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(toggleBtn, currentTheme);

    toggleBtn.addEventListener('click', () => {
        const theme = document.documentElement.getAttribute('data-theme');
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nextTheme);
        localStorage.setItem('lspl_theme', nextTheme);
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

function initParticles() {
    const canvas = document.getElementById('particle-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let particles = [];
    const maxParticles = 60;
    
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
            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            ctx.fillStyle = isLight ? 'rgba(0, 153, 255, 0.15)' : 'rgba(0, 153, 255, 0.2)';
            ctx.fill();
        }
    }

    for (let i = 0; i < maxParticles; i++) {
        particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        ctx.strokeStyle = isLight ? 'rgba(0, 153, 255, 0.04)' : 'rgba(0, 153, 255, 0.05)';
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

function initTypingAnimation() {
    const typingSpan = document.getElementById('typing-text');
    if (!typingSpan) return;

    const words = [
        'Web Designing & CMS',
        'Laravel & PHP Web Apps',
        'Magento & PrestaShop Shops',
        'Moodle LMS Virtual Classrooms',
        'iOS & Android Mobile Apps',
        'Professional Industrial Training'
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
            typeSpeed = 2000;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            wordIndex = (wordIndex + 1) % words.length;
            typeSpeed = 500;
        }

        setTimeout(type, typeSpeed);
    }

    setTimeout(type, 1000);
}

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

function initServices() {
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

    const modal = document.getElementById('service-modal');
    if (!modal) return;

    const modalClose = modal.querySelector('.modal-close');
    const modalTitle = document.getElementById('modal-service-title');
    const modalDesc = document.getElementById('modal-service-description');
    const modalTech = document.getElementById('modal-service-tech');
    const modalWorkflow = document.getElementById('modal-service-workflow');

    const defaultWorkflows = {
        'Web & Software': [
            'Requirement analysis & mapping key technical parameters.',
            'Interface wireframing & prototyping visual HSL styling mockups.',
            'Writing scalable database schemas & custom PHP/Node.js backend functions.',
            'Deploying responsive grid components & layout test audits.',
            'Launching system checks, configuring SSL, and delivering source files.'
        ],
        'E-Commerce Solution': [
            'Catalog scoping & product database entity setup.',
            'Configuring shop layouts (Magento/PrestaShop/Shopify) & plugins.',
            'Integrating secure transactional payment gateways & shipping modules.',
            'Performing checkout load tests & transaction testing.',
            'Going live & initiating backend dashboard catalog instructions.'
        ],
        'Marketing & Search': [
            'Performing detailed diagnostic crawlers to find search indexing bottlenecks.',
            'Mapping semantic user intent & generating metadata blueprints.',
            'Conducting local search map optimization & backlink optimization.',
            'Launching performance ad campaigns & automated email flows.',
            'Generating detailed GA4 conversion logs & keywords track metrics.'
        ],
        'Infrastructure': [
            'Conducting hardware physical audits & path routing design.',
            'Installing server rack configurations, numbering, & patching panels.',
            'Splicing fiber optics (OFC) & deploying secure access point setups.',
            'Testing connectivity channels, speeds, & routing trunks.',
            'Documenting hardware schematics & network security maps.'
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

            const workflow = defaultWorkflows[category] || defaultWorkflows['Web & Software'];
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

function initResponsiveMenu() {
    const menuToggleBtn = document.getElementById('menu-toggle-btn');
    const navMenu = document.querySelector('nav');
    if (menuToggleBtn && navMenu) {
        menuToggleBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = menuToggleBtn.querySelector('i');
            if (icon) {
                if (navMenu.classList.contains('active')) {
                    icon.setAttribute('data-lucide', 'x');
                    icon.className = 'lucide-x';
                } else {
                    icon.setAttribute('data-lucide', 'menu');
                    icon.className = 'lucide-menu';
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });

        // Close menu when a link (that is not the megamenu trigger or dropdown trigger) is clicked
        navMenu.querySelectorAll('a:not(.megamenu-trigger):not(.dropdown-trigger)').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                const icon = menuToggleBtn.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', 'menu');
                    icon.className = 'lucide-menu';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        });

        // Toggle mobile megamenu / dropdown accordion
        navMenu.querySelectorAll('.megamenu-trigger, .dropdown-trigger').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    const submenu = trigger.nextElementSibling;
                    if (submenu) {
                        submenu.classList.toggle('active');
                        trigger.closest('li').classList.toggle('open');
                    }
                }
            });
        });
    }
}

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
    let selectedService = '';
    let selectedScale = '';
    let selectedTimeline = '';

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
            if (idx === currentStep) step.classList.add('active');
            else step.classList.remove('active');
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

        const progressPercent = (currentStep / (steps.length - 1)) * 100;
        progressBar.style.width = `${progressPercent}%`;

        if (currentStep === 0) prevBtn.style.visibility = 'hidden';
        else prevBtn.style.visibility = 'visible';

        if (currentStep === steps.length - 1) nextBtn.textContent = 'Submit Details';
        else nextBtn.textContent = 'Continue';

        validateCurrentStep();
    }

    function validateCurrentStep() {
        if (currentStep === 0 && !selectedService) nextBtn.disabled = true;
        else if (currentStep === 1 && !selectedScale) nextBtn.disabled = true;
        else if (currentStep === 2 && !selectedTimeline) nextBtn.disabled = true;
        else nextBtn.disabled = false;
    }

    function updateBudgetEstimate() {
        if (!selectedService || !selectedScale || !selectedTimeline) return;

        const baseCosts = {
            'web_design': 20000,
            'wordpress': 25000,
            'laravel': 50000,
            'web_dev': 60000,
            'mobile_apps': 80000,
            'magento': 120000,
            'prestashop': 45000,
            'moodle': 55000,
            'custom_software': 70000,
            'seo': 15000,
            'marketing': 20000,
            'networking': 30000
        };

        const scaleMultipliers = {
            'startup': 1.0,
            'business': 1.5,
            'enterprise': 2.8
        };

        const timelineMultipliers = {
            'fast': 1.3,
            'standard': 1.0,
            'extended': 0.85
        };

        const base = baseCosts[selectedService] || 30000;
        const scaleMult = scaleMultipliers[selectedScale] || 1.0;
        const timeMult = timelineMultipliers[selectedTimeline] || 1.0;

        const calculatedBudget = base * scaleMult * timeMult;
        
        const lowRange = Math.round(calculatedBudget * 0.9 / 5000) * 5000;
        const highRange = Math.round(calculatedBudget * 1.1 / 5000) * 5000;

        const lowStr = lowRange.toLocaleString('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });
        const highStr = highRange.toLocaleString('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });

        budgetVal.textContent = `${lowStr} - ${highStr}`;
    }

    nextBtn.addEventListener('click', () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            updateStepUI();
        } else {
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
        submitBtn.textContent = 'Submitting...';

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
                showToast('Estimate proposal requested! We will call you.', 'success');
                estimatorForm.reset();
                selectedService = '';
                selectedScale = '';
                selectedTimeline = '';
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                budgetVal.textContent = '₹0';
                currentStep = 0;
                updateStepUI();
            } else {
                showToast(data.message || 'Error processing request.', 'error');
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

    updateStepUI();
}

function initContactForm() {
    const contactForm = document.getElementById('contact-form');
    if (!contactForm) return;

    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        const formData = new FormData(contactForm);
        formData.append('action', 'submit_contact');

        fetch('submit_lead.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Thank you! Your query has been submitted.', 'success');
                contactForm.reset();
            } else {
                showToast(data.message || 'Failed to submit query.', 'error');
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
    
    setTimeout(() => toast.classList.add('show'), 50);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}
