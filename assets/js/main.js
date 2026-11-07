/**
 * EstateVista India - Main Interactive Script
 * Vanilla JavaScript (Zero Dependencies)
 */

// Universal Image Safety Fallback (Captures all image load errors immediately with zero network delay)
window.addEventListener('error', (e) => {
    if (e.target && e.target.tagName === 'IMG') {
        const img = e.target;
        if (img.dataset.fallbackApplied) return;
        img.dataset.fallbackApplied = 'true';
        
        const baseUrl = window.BASE_URL || '/';
        const isAvatar = img.classList.contains('author-avatar') || 
                         img.classList.contains('agent-avatar') || 
                         img.closest('.agent-avatar-box') || 
                         img.closest('.dash-user-avatar') ||
                         img.closest('.nav-user-avatar') ||
                         img.closest('.adm-sidebar-user-avatar');
        const isCity = img.classList.contains('city-card-img') || 
                       img.closest('.city-card') || 
                       img.closest('.desk-card-media');
        const isFloorPlan = (img.alt && img.alt.toLowerCase().includes('floor plan')) || 
                            img.closest('.floor-plan-preview');

        if (isAvatar) {
            img.src = baseUrl + 'assets/images/avatar-placeholder.svg';
        } else if (isCity) {
            img.src = baseUrl + 'assets/images/city-placeholder.svg';
        } else if (isFloorPlan) {
            img.src = baseUrl + 'assets/images/floorplan-placeholder.svg';
        } else {
            img.src = baseUrl + 'assets/images/property-placeholder.svg';
        }
    }
}, true);

document.addEventListener('DOMContentLoaded', () => {
    initHeaderScroll();
    initMobileNav();
    initVipModal();
    initLoginModal();
    initOwnerModal();
    initNewsletter();
    initFavorites();
    initStatsCounter();
    initSmoothScroll();
    initGallerySliders();
});

/**
 * 1. Sticky Header & Top Bar Hide on Scroll
 */
function initHeaderScroll() {
    const header = document.getElementById('siteHeader');
    if (!header) return;

    const handleScroll = () => {
        if (window.scrollY > 45) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
}

/**
 * 2. Mobile Navigation Drawer
 */
function initMobileNav() {
    const toggleBtn = document.getElementById('mobileNavToggle');
    const closeBtn = document.getElementById('mobileNavClose');
    const drawer = document.getElementById('mobileNavDrawer');
    const backdrop = document.getElementById('mobileNavBackdrop');
    const mobileLinks = document.querySelectorAll('.mobile-link');

    if (!toggleBtn || !drawer) return;

    const openDrawer = () => {
        drawer.classList.add('active');
        if (backdrop) backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    const closeDrawer = () => {
        drawer.classList.remove('active');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    };

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openDrawer();
    });
    drawer.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    mobileLinks.forEach(link => {
        link.addEventListener('click', closeDrawer);
    });

    // Close when tapping outside
    document.addEventListener('click', (e) => {
        if (drawer.classList.contains('active') && !drawer.contains(e.target) && !toggleBtn.contains(e.target)) {
            closeDrawer();
        }
    });
}

/**
 * 3. VIP Consultation Modal & Inquiry Form
 */
function initVipModal() {
    const modal = document.getElementById('vipModalOverlay');
    const closeBtn = document.getElementById('modalCloseBtn');
    const modalTitle = document.getElementById('modalTitle');
    const propInterestInput = document.getElementById('modalPropertyInterest');
    const form = document.getElementById('vipInquiryForm');
    const feedback = document.getElementById('modalFormFeedback');
    const submitBtn = document.getElementById('inquirySubmitBtn');

    if (!modal) return;

    // Attach to all trigger buttons
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.open-inquiry-modal');
        if (trigger) {
            e.preventDefault();
            const propertyName = trigger.getAttribute('data-property');
            const subject = trigger.getAttribute('data-subject');

            if (propertyName) {
                modalTitle.textContent = `Inquire: ${propertyName}`;
                propInterestInput.value = propertyName;
            } else if (subject) {
                modalTitle.textContent = subject;
                propInterestInput.value = subject;
            } else {
                modalTitle.textContent = 'Request Discreet Consultation';
                propInterestInput.value = 'General Advisory';
            }

            // Reset feedback
            if (feedback) feedback.style.display = 'none';

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });

    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Handle Form Submit
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Encrypting & Sending...';

            const formData = new FormData(form);

            try {
                const response = await fetch('api/inquiry.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Request Received';

                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#ECFDF5';
                        feedback.style.color = '#065F46';
                        feedback.style.border = '1px solid #A7F3D0';
                        feedback.innerHTML = `<strong>Dossier Registered (#EV-${String(data.id || '').padStart(4, '0')}).</strong> ${data.message}`;
                    }

                    form.reset();

                    setTimeout(() => {
                        closeModal();
                        submitBtn.innerHTML = '<i class="fa-solid fa-lock"></i> Submit Confidential Request';
                        if (feedback) feedback.style.display = 'none';
                    }, 3000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Try Again';
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#FEF2F2';
                        feedback.style.color = '#991B1B';
                        feedback.style.border = '1px solid #FECACA';
                        feedback.innerHTML = `<strong>Submission Error:</strong> ${data.message}`;
                    }
                }
            } catch (err) {
                console.error('Inquiry submission error:', err);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-lock"></i> Submit Confidential Request';
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#FEF2F2';
                    feedback.style.color = '#991B1B';
                    feedback.style.border = '1px solid #FECACA';
                    feedback.innerHTML = 'Unable to reach advisory desk. Please call concierge directly at +91 22 6985 4000.';
                }
            }
        });
    }
}

/**
 * 4. Property Favorites / Wishlist in LocalStorage and MySQL
 */
function initFavorites() {
    const storageKey = 'estatevista_shortlist';
    let saved = JSON.parse(localStorage.getItem(storageKey) || '[]');

    const updateFavButtons = () => {
        document.querySelectorAll('.property-fav-btn').forEach(btn => {
            const propId = btn.getAttribute('data-property-id');
            const icon = btn.querySelector('i');
            if (saved.includes(propId)) {
                btn.classList.add('active');
                if (icon) icon.className = 'fa-solid fa-heart';
            } else {
                btn.classList.remove('active');
                if (icon) icon.className = 'fa-regular fa-heart';
            }
        });
    };

    updateFavButtons();

    document.addEventListener('click', async (e) => {
        const favBtn = e.target.closest('.property-fav-btn');
        if (!favBtn) return;

        e.preventDefault();
        e.stopPropagation();

        const propId = favBtn.getAttribute('data-property-id');
        if (!propId) return;

        // Subtle scale feedback
        favBtn.style.transform = 'scale(1.25)';
        setTimeout(() => { favBtn.style.transform = ''; }, 200);

        // Optimistically update localStorage & UI
        if (saved.includes(propId)) {
            saved = saved.filter(id => id !== propId);
        } else {
            saved.push(propId);
        }
        localStorage.setItem(storageKey, JSON.stringify(saved));
        updateFavButtons();

        // Sync with MySQL favorites via API
        try {
            const res = await fetch('api/favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'property_id=' + encodeURIComponent(propId)
            });
            const data = await res.json();
            if (data.success) {
                // If server returns exact status, ensure local matches
                if (data.is_favorite && !saved.includes(propId)) {
                    saved.push(propId);
                } else if (!data.is_favorite && saved.includes(propId)) {
                    saved = saved.filter(id => id !== propId);
                }
                localStorage.setItem(storageKey, JSON.stringify(saved));
                updateFavButtons();
            }
        } catch (err) {
            console.error('Favorite sync notice:', err);
        }
    });
}

/**
 * 5. Animated Number Counters
 */
function initStatsCounter() {
    const statCards = document.querySelectorAll('.stat-number');
    if (!statCards.length) return;

    let animated = false;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !animated) {
                animated = true;
                statCards.forEach(el => {
                    const target = parseInt(el.getAttribute('data-target') || '0', 10);
                    const prefix = el.getAttribute('data-prefix') || '';
                    const suffix = el.getAttribute('data-suffix') || '';
                    const duration = 1800;
                    const startTime = performance.now();

                    const step = (now) => {
                        const progress = Math.min((now - startTime) / duration, 1);
                        const easeOutQuad = 1 - (1 - progress) * (1 - progress);
                        const currentVal = Math.floor(easeOutQuad * target);
                        
                        el.textContent = `${prefix}${currentVal.toLocaleString('en-IN')}${suffix}`;
                        
                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            el.textContent = `${prefix}${target.toLocaleString('en-IN')}${suffix}`;
                        }
                    };

                    requestAnimationFrame(step);
                });
            }
        });
    }, { threshold: 0.3 });

    const statsSection = document.getElementById('milestone-stats');
    if (statsSection) {
        observer.observe(statsSection);
    }
}

/**
 * 6. Smooth Scroll with Sticky Header Offset
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || targetId === '#virtual-tour') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * 7. Client Login Modal Handler
 */
function initLoginModal() {
    const modal = document.getElementById('loginModalOverlay');
    const closeBtn = document.getElementById('loginModalCloseBtn');
    const form = document.getElementById('clientLoginForm');
    const feedback = document.getElementById('loginFeedback');
    const submitBtn = document.getElementById('loginSubmitBtn');

    if (!modal) return;

    document.querySelectorAll('.open-login-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';
            }

            const identityVal = document.getElementById('login_identity')?.value || '';
            const passwordVal = document.getElementById('login_pass')?.value || '';

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'identity=' + encodeURIComponent(identityVal) + '&password=' + encodeURIComponent(passwordVal)
                });
                const data = await response.json();

                if (data.success) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Authenticated';
                    }
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#ECFDF5';
                        feedback.style.color = '#065F46';
                        feedback.style.border = '1px solid #A7F3D0';
                        feedback.innerHTML = `<strong>Welcome back!</strong> ${data.message || 'Redirecting...'}`;
                    }

                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 1200);
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Private Portal';
                    }
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#FEF2F2';
                        feedback.style.color = '#991B1B';
                        feedback.style.border = '1px solid #FECACA';
                        feedback.innerHTML = `<strong>Access Denied:</strong> ${data.message || 'Invalid credentials'}`;
                    }
                }
            } catch (err) {
                console.error('Login error:', err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Private Portal';
                }
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#FEF2F2';
                    feedback.style.color = '#991B1B';
                    feedback.style.border = '1px solid #FECACA';
                    feedback.innerHTML = 'Authentication server unreachable. Please try again.';
                }
            }
        });
    }
}

/**
 * 8. Property Owner Listing Modal Handler
 */
function initOwnerModal() {
    const modal = document.getElementById('ownerModalOverlay');
    const closeBtn = document.getElementById('ownerModalCloseBtn');
    const form = document.getElementById('ownerListingForm');
    const feedback = document.getElementById('ownerFeedback');
    const submitBtn = document.getElementById('ownerSubmitBtn');

    if (!modal) return;

    document.querySelectorAll('.open-owner-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting Dossier...';
            }

            const formData = new FormData(form);

            try {
                const response = await fetch('api/owner-listing.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Received by Advisory Desk';
                    }
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#ECFDF5';
                        feedback.style.color = '#065F46';
                        feedback.style.border = '1px solid #A7F3D0';
                        feedback.innerHTML = `<strong>Dossier Registered.</strong> ${data.message}`;
                    }

                    form.reset();

                    setTimeout(() => {
                        closeModal();
                        if (submitBtn) submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Residence for Listing Evaluation';
                        if (feedback) feedback.style.display = 'none';
                    }, 3000);
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Resubmit';
                    }
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.style.background = '#FEF2F2';
                        feedback.style.color = '#991B1B';
                        feedback.style.border = '1px solid #FECACA';
                        feedback.innerHTML = `<strong>Evaluation Error:</strong> ${data.message}`;
                    }
                }
            } catch (err) {
                console.error('Owner submission error:', err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Residence for Listing Evaluation';
                }
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#FEF2F2';
                    feedback.style.color = '#991B1B';
                    feedback.style.border = '1px solid #FECACA';
                    feedback.innerHTML = 'Unable to reach advisory desk. Please call concierge directly at +91 22 6985 4000.';
                }
            }
        });
    }
}

/**
 * 9. Dedicated Newsletter Section Handler
 */
function initNewsletter() {
    const newsletterForm = document.getElementById('mainNewsletterForm');
    if (!newsletterForm) return;

    newsletterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = newsletterForm.querySelector('input[type="email"]');
        const btn = newsletterForm.querySelector('button[type="submit"]');
        const feedback = document.getElementById('newsletterFeedback');

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subscribing...';
        }

        const formData = new FormData(newsletterForm);

        try {
            const response = await fetch('api/newsletter.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Subscribed';
                }
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#ECFDF5';
                    feedback.style.color = '#065F46';
                    feedback.style.border = '1px solid #A7F3D0';
                    feedback.innerHTML = `<i class="fa-solid fa-circle-check"></i> <strong>Subscribed:</strong> ${data.message}`;
                }
                if (input) input.value = '';

                setTimeout(() => {
                    if (btn) btn.innerHTML = '<span>Subscribe</span>';
                }, 3500);
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span>Subscribe</span>';
                }
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#FEF2F2';
                    feedback.style.color = '#991B1B';
                    feedback.style.border = '1px solid #FECACA';
                    feedback.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${data.message}`;
                }
            }
        } catch (err) {
            console.error('Newsletter error:', err);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Subscribe</span>';
            }
            if (feedback) {
                feedback.style.display = 'block';
                feedback.style.background = '#FEF2F2';
                feedback.style.color = '#991B1B';
                feedback.style.border = '1px solid #FECACA';
                feedback.innerHTML = 'Subscription service temporarily unavailable. Please retry later.';
            }
        }
    });
}

/**
 * Universal Property Card Image Gallery Preview Sliders
 */
function initGallerySliders() {
    const sliders = document.querySelectorAll('.gallery-slider:not([data-slider-ready="true"])');

    sliders.forEach(slider => {
        slider.setAttribute('data-slider-ready', 'true');
        const track = slider.querySelector('.gallery-track');
        const slides = slider.querySelectorAll('.gallery-slide');
        const prevBtn = slider.querySelector('.gallery-arrow.prev');
        const nextBtn = slider.querySelector('.gallery-arrow.next');
        const dots = slider.querySelectorAll('.gallery-dot');

        if (!track || slides.length <= 1) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            return;
        }

        let currentIndex = 0;
        const totalSlides = slides.length;

        const updateSlide = (index) => {
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            currentIndex = index;

            track.style.transform = `translateX(-${currentIndex * 100}%)`;

            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                updateSlide(currentIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                updateSlide(currentIndex + 1);
            });
        }

        dots.forEach((dot, idx) => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                updateSlide(idx);
            });
        });
    });
}

