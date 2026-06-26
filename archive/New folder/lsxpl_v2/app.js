// lsxpl/app.js - Frontend script for LSXPL AI Lab Site

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

    // 9. Manual 3D Tilt Initialization
    initTilt();
});

function initTheme() {
    const toggleBtn = document.getElementById('theme-toggle-btn');
    if (!toggleBtn) return;

    const currentTheme = localStorage.getItem('lsxpl_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(toggleBtn, currentTheme);

    toggleBtn.addEventListener('click', () => {
        const theme = document.documentElement.getAttribute('data-theme');
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nextTheme);
        localStorage.setItem('lsxpl_theme', nextTheme);
        updateThemeIcon(toggleBtn, nextTheme);
    });
}

function updateThemeIcon(btn, theme) {
    if (theme === 'light') {
        btn.innerHTML = '<i data-lucide="moon"></i>';
        btn.setAttribute('aria-label', 'Switch to Dark Mode');
    } else {
        btn.innerHTML = '<i data-lucide="sun"></i>';
        btn.setAttribute('aria-label', 'Switch to Light Mode');
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Swap logo based on light/dark mode
    const logos = document.querySelectorAll('.logo img');
    logos.forEach(logo => {
        const currentSrc = logo.getAttribute('src');
        if (currentSrc) {
            const basePath = currentSrc.substring(0, Math.max(currentSrc.lastIndexOf('/') + 1, 0));
            if (theme === 'light') {
                logo.src = basePath + 'logo-light.png';
            } else {
                logo.src = basePath + 'logo-dark.png';
            }
        }
    });
}

function initParticles() {
    const canvas = document.getElementById('particle-canvas');
    if (!canvas) return;

    let renderer, scene, camera, particleSystem, lineSystem;
    const numParticles = 80;
    let mouseX = 0, mouseY = 0;

    try {
        renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.setSize(window.innerWidth, window.innerHeight);

        scene = new THREE.Scene();
        camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
        camera.position.z = 400;

        const positions = new Float32Array(numParticles * 3);
        const velocities = [];

        for (let i = 0; i < numParticles; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 500;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 400;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 300;

            velocities.push({
                x: (Math.random() - 0.5) * 0.4,
                y: (Math.random() - 0.5) * 0.4,
                z: (Math.random() - 0.5) * 0.3
            });
        }

        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        const material = new THREE.PointsMaterial({
            color: 0x00d2ff, // Vibrant Cyan
            size: 4,
            transparent: true,
            opacity: 0.8
        });

        particleSystem = new THREE.Points(geometry, material);
        scene.add(particleSystem);

        const lineMaterial = new THREE.LineBasicMaterial({
            color: 0x0055ff, // Cyber Blue
            transparent: true,
            opacity: 0.2,
            blending: THREE.AdditiveBlending
        });

        const maxConnections = 120;
        const linePositions = new Float32Array(maxConnections * 2 * 3);
        const lineGeometry = new THREE.BufferGeometry();
        lineGeometry.setAttribute('position', new THREE.BufferAttribute(linePositions, 3));

        lineSystem = new THREE.LineSegments(lineGeometry, lineMaterial);
        scene.add(lineSystem);

        document.addEventListener('mousemove', (e) => {
            mouseX = (e.clientX - window.innerWidth / 2) * 0.06;
            mouseY = (e.clientY - window.innerHeight / 2) * 0.06;
        });

        function animate() {
            requestAnimationFrame(animate);

            const posAttr = geometry.attributes.position;
            const linePosAttr = lineGeometry.attributes.position;

            for (let i = 0; i < numParticles; i++) {
                let x = posAttr.getX(i) + velocities[i].x;
                let y = posAttr.getY(i) + velocities[i].y;
                let z = posAttr.getZ(i) + velocities[i].z;

                if (x < -250 || x > 250) velocities[i].x *= -1;
                if (y < -200 || y > 200) velocities[i].y *= -1;
                if (z < -150 || z > 150) velocities[i].z *= -1;

                posAttr.setXYZ(i, x, y, z);
            }
            posAttr.needsUpdate = true;

            let connectionCount = 0;
            for (let i = 0; i < numParticles; i++) {
                const xi = posAttr.getX(i);
                const yi = posAttr.getY(i);
                const zi = posAttr.getZ(i);

                for (let j = i + 1; j < numParticles; j++) {
                    const xj = posAttr.getX(j);
                    const yj = posAttr.getY(j);
                    const zj = posAttr.getZ(j);

                    const dist = Math.sqrt(
                        (xi - xj) ** 2 +
                        (yi - yj) ** 2 +
                        (zi - zj) ** 2
                    );

                    if (dist < 85 && connectionCount < maxConnections) {
                        const lineIdx = connectionCount * 2;
                        linePosAttr.setXYZ(lineIdx, xi, yi, zi);
                        linePosAttr.setXYZ(lineIdx + 1, xj, yj, zj);
                        connectionCount++;
                    }
                }
            }
            linePosAttr.needsUpdate = true;
            lineGeometry.setDrawRange(0, connectionCount * 2);

            camera.position.x += (mouseX - camera.position.x) * 0.05;
            camera.position.y += (-mouseY - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    } catch (e) {
        console.error("Three.js initialization failed: ", e);
    }
}

function initTypingAnimation() {
    const typingSpan = document.getElementById('typing-text');
    if (!typingSpan) return;

    const words = [
        'AI Conversational Chatbots',
        'Outbound AI Voice Agents',
        'AI-Powered SEO Indexing',
        'Custom SaaS Development',
        'Multi-Agent LLM Orchestrations',
        'Penetration Testing Audits'
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
        'AI & Automation': [
            'Analyze business data & outline conversational models.',
            'Design NLP mapping matrices or configure voice trunks.',
            'Establish RAG vector databases & index knowledge directories.',
            'Perform prompt testing & conversational flow verification.',
            'Deploy cloud instances & hook up active log monitors.'
        ],
        'SaaS Development': [
            'Architect multi-tenant structures & database boundaries.',
            'Develop secure APIs & backend dashboards (Next.js/React).',
            'Integrate recurring billing checkouts & transaction panels.',
            'Conduct server scale load tests.',
            'Launch environment containers & trigger Git automation pipelines.'
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

            const workflow = defaultWorkflows[category] || defaultWorkflows['AI & Automation'];
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

    // Set active tab based on detected currency
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

        if (currentStep === steps.length - 1) nextBtn.textContent = 'Submit Proposal';
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
            // Core AI Services
            'ai-conversational-chatbots': 90000,
            'outbound-ai-voice-agents': 120000,
            'custom-saas-ai-platforms': 150000,
            'ai-model-security-audits': 80000,
            'headless-cms-jamstack-ai': 70000,
            'whatsapp-ai-commerce': 75000,
            'computer-vision-ocr': 100000,
            'predictive-ai-forecasting': 95000,
            
            // AI Solutions (Industries)
            'ai-education-erp': 110000,
            'ai-healthcare-ehr': 130000,
            'ai-pharmacy-ocr': 85000,
            'ai-restaurant-pos': 80000,
            'ai-hotel-booking': 95000,
            'ai-salon-scheduling': 75000,
            'ai-real-estate': 100000,
            'ai-fintech': 150000,
            'ai-logistics': 120000,
            'ai-fitness': 70000,
            'ai-travel': 90000,
            'ai-legal': 115000,
            'ai-hr': 95000,
            'ai-car-rental': 88000,
            'ai-events': 82000,
            'ai-logistics-optimizer': 125000,

            // Backwards compatibility
            'ai_chatbots': 90000,
            'ai_calling': 120000,
            'ai_seo': 35000,
            'saas_platform': 150000,
            'llm_rag': 100000,
            'cyber_audit': 80000
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

        const base = baseCosts[selectedService] || 80000;
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

        const basePath = window.basePath || '/';
        fetch(basePath + 'submit_lead.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('AI Lab Proposal requested! We will call you.', 'success');
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

        const basePath = window.basePath || '/';
        fetch(basePath + 'submit_lead.php', {
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

function initTilt() {
    if (typeof VanillaTilt !== 'undefined') {
        VanillaTilt.init(document.querySelectorAll("[data-tilt]"), {
            max: 15,
            speed: 400,
            glare: true,
            "max-glare": 0.15
        });
    }
}
