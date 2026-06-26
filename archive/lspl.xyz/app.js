// lspl.xyz/app.js - Frontend scripting for LSPL Academy Portal
document.addEventListener('DOMContentLoaded', () => {
    // 1. Theme Switcher
    initTheme();

    // 2. Particle Canvas Background (Green Theme)
    initParticles();

    // 3. Typing Animation in Hero
    initTypingAnimation();

    // 4. Scrolled Navbar
    initNavbarScroll();

    // 5. Course Filtering
    initCourseFilter();

    // 6. Responsive Drawer Menu
    initResponsiveMenu();

    // 7. Interactive Tuition Fee Estimator
    initFeeEstimator();

    // 8. Contact Form AJAX
    initContactForm();
});

function initTheme() {
    const toggleBtn = document.getElementById('theme-toggle-btn');
    if (!toggleBtn) return;

    // Use isolated key so we don't conflict with root/other domains
    const currentTheme = localStorage.getItem('lspl_xyz_theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(toggleBtn, currentTheme);

    toggleBtn.addEventListener('click', () => {
        const theme = document.documentElement.getAttribute('data-theme');
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nextTheme);
        localStorage.setItem('lspl_xyz_theme', nextTheme);
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
            this.vx = (Math.random() - 0.5) * 0.45;
            this.vy = (Math.random() - 0.5) * 0.45;
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
            // Use HSL 155 (Green) matching the theme primary color
            ctx.fillStyle = isLight ? 'rgba(13, 138, 67, 0.15)' : 'rgba(13, 138, 67, 0.22)';
            ctx.fill();
        }
    }

    for (let i = 0; i < maxParticles; i++) {
        particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        ctx.strokeStyle = isLight ? 'rgba(13, 138, 67, 0.04)' : 'rgba(13, 138, 67, 0.06)';
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
        'weBOShop 2.0 Coding',
        'hackIon 2.0 Cybersecurity',
        'Summer Industrial Training',
        'Winter Industrial Training',
        'On-Campus Collaborations',
        'School IT Tech Camps'
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

function initCourseFilter() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const courseCards = document.querySelectorAll('.course-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const category = btn.getAttribute('data-filter');
            
            courseCards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'all' || cardCat === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
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

function initFeeEstimator() {
    const steps = document.querySelectorAll('.estimator-step');
    const progressSteps = document.querySelectorAll('.progress-step');
    const progressBar = document.getElementById('estimator-progress-bar');
    const nextBtn = document.getElementById('est-next-btn');
    const prevBtn = document.getElementById('est-prev-btn');
    const feeVal = document.getElementById('est-budget-val');
    const estimatorForm = document.getElementById('estimator-form');

    if (steps.length === 0) return;

    let currentStep = 0;
    let selectedCourse = '';
    let selectedMode = ''; // online vs offline
    let selectedBatch = ''; // regular vs fast_track

    document.querySelectorAll('.estimator-step[data-step="1"] .option-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.estimator-step[data-step="1"] .option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedCourse = card.getAttribute('data-value');
            updateFeeEstimate();
            nextBtn.disabled = false;
        });
    });

    document.querySelectorAll('.estimator-step[data-step="2"] .option-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.estimator-step[data-step="2"] .option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedMode = card.getAttribute('data-value');
            updateFeeEstimate();
            nextBtn.disabled = false;
        });
    });

    document.querySelectorAll('.estimator-step[data-step="3"] .option-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.estimator-step[data-step="3"] .option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedBatch = card.getAttribute('data-value');
            updateFeeEstimate();
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

        if (currentStep === steps.length - 1) nextBtn.textContent = 'Register Now';
        else nextBtn.textContent = 'Continue';

        validateCurrentStep();
    }

    function validateCurrentStep() {
        if (currentStep === 0 && !selectedCourse) nextBtn.disabled = true;
        else if (currentStep === 1 && !selectedMode) nextBtn.disabled = true;
        else if (currentStep === 2 && !selectedBatch) nextBtn.disabled = true;
        else nextBtn.disabled = false;
    }

    function updateFeeEstimate() {
        if (!selectedCourse || !selectedMode || !selectedBatch) return;

        const courseBaseFees = {
            'weboshop': 15000,
            'hackion': 12000,
            'summer': 8000,
            'winter': 6000,
            'campus': 5000,
            'school': 3500
        };

        const modeMultipliers = {
            'online': 0.85,
            'offline': 1.0
        };

        const batchMultipliers = {
            'regular': 1.0,
            'fast_track': 1.2
        };

        const base = courseBaseFees[selectedCourse] || 5000;
        const modeMult = modeMultipliers[selectedMode] || 1.0;
        const batchMult = batchMultipliers[selectedBatch] || 1.0;

        const calculatedFee = base * modeMult * batchMult;
        
        // Output rounded to nearest 100
        const roundedFee = Math.round(calculatedFee / 100) * 100;
        const feeStr = roundedFee.toLocaleString('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });

        feeVal.textContent = feeStr;
    }

    nextBtn.addEventListener('click', () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            updateStepUI();
        } else {
            submitRegistrationData();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateStepUI();
        }
    });

    function submitRegistrationData() {
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
        submitBtn.textContent = 'Registering...';

        const coursesMap = {
            'weboshop': 'weBOShop 2.0 (Full-Stack Coding)',
            'hackion': 'hackIon 2.0 (Cybersecurity & Ethical Hacking)',
            'summer': 'Summer Industrial Training',
            'winter': 'Winter Industrial Training',
            'campus': 'On-Campus Institutional Training',
            'school': 'School Tech Camps'
        };

        const readableCourse = coursesMap[selectedCourse] || selectedCourse;
        const readableDuration = `Mode: ${selectedMode.toUpperCase()} | Batch: ${selectedBatch.toUpperCase()}`;

        const formData = new FormData(estimatorForm);
        formData.append('action', 'register_academy');
        formData.append('course_name', readableCourse);
        formData.append('message', readableDuration);
        formData.append('estimated_fee', feeVal.textContent);

        fetch('submit_lead.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Registration successful! Check your email.', 'success');
                estimatorForm.reset();
                selectedCourse = '';
                selectedMode = '';
                selectedBatch = '';
                document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                feeVal.textContent = '₹0';
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
                showToast('Message submitted! Our team will contact you.', 'success');
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
