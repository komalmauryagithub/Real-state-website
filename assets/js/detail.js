/**
 * EstateVista India - Property Detail Interactions & Inquiry Controller
 */

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------------------
    // 1. Toast Notification Helper
    // -------------------------------------------------------------------------
    const toast = document.getElementById('propToast');
    let toastTimeout = null;

    function showToast(message, icon = 'fa-circle-check') {
        if (!toast) return;
        const msgEl = toast.querySelector('.toast-msg');
        const iconEl = toast.querySelector('i');
        if (msgEl) msgEl.textContent = message;
        if (iconEl) iconEl.className = `fa-solid ${icon}`;

        toast.classList.add('active');
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.remove('active');
        }, 3200);
    }

    // -------------------------------------------------------------------------
    // 2. Fullscreen Lightbox Gallery
    // -------------------------------------------------------------------------
    const lightbox = document.getElementById('propLightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxCounter = document.getElementById('lightboxCounter');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    const thumbsContainer = document.getElementById('lightboxThumbs');

    // Collect all gallery images from data attributes
    const galleryItems = [];
    const triggerElements = document.querySelectorAll('[data-gallery-src]');

    triggerElements.forEach((el, index) => {
        const src = el.getAttribute('data-gallery-src');
        const caption = el.getAttribute('data-gallery-caption') || `Residence View ${index + 1}`;
        if (src) {
            galleryItems.push({ src, caption });
        }
    });

    let currentPhotoIdx = 0;

    function initLightboxThumbs() {
        if (!thumbsContainer) return;
        thumbsContainer.innerHTML = '';
        galleryItems.forEach((item, idx) => {
            const thumb = document.createElement('div');
            thumb.className = `lightbox-thumb ${idx === currentPhotoIdx ? 'active' : ''}`;
            thumb.innerHTML = `<img src="${item.src}" alt="${item.caption}">`;
            thumb.addEventListener('click', () => {
                showPhoto(idx);
            });
            thumbsContainer.appendChild(thumb);
        });
    }

    function showPhoto(idx) {
        if (!lightboxImg || galleryItems.length === 0) return;
        if (idx < 0) idx = galleryItems.length - 1;
        if (idx >= galleryItems.length) idx = 0;

        currentPhotoIdx = idx;
        lightboxImg.style.opacity = '0';

        setTimeout(() => {
            lightboxImg.src = galleryItems[currentPhotoIdx].src;
            lightboxImg.alt = galleryItems[currentPhotoIdx].caption;
            lightboxImg.style.opacity = '1';
        }, 150);

        if (lightboxCounter) {
            lightboxCounter.textContent = `${currentPhotoIdx + 1} / ${galleryItems.length}`;
        }

        // Update active thumb
        if (thumbsContainer) {
            const thumbs = thumbsContainer.querySelectorAll('.lightbox-thumb');
            thumbs.forEach((t, i) => {
                t.classList.toggle('active', i === currentPhotoIdx);
            });
            const activeThumb = thumbs[currentPhotoIdx];
            if (activeThumb) {
                activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }
    }

    function openLightbox(startIdx = 0) {
        if (!lightbox) return;
        if (galleryItems.length === 0) return;
        initLightboxThumbs();
        showPhoto(startIdx);
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Attach click events to gallery triggers
    triggerElements.forEach((el) => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const idx = parseInt(el.getAttribute('data-gallery-idx') || '0', 10);
            openLightbox(idx);
        });
    });

    // Floor plan preview lightbox trigger
    const floorPlanTrigger = document.getElementById('floorPlanTrigger');
    if (floorPlanTrigger) {
        floorPlanTrigger.addEventListener('click', function () {
            const fpSrc = this.getAttribute('data-plan-src');
            if (fpSrc) {
                // Find if floor plan exists in gallery or open it directly
                const foundIdx = galleryItems.findIndex(item => item.src === fpSrc);
                if (foundIdx !== -1) {
                    openLightbox(foundIdx);
                } else {
                    // Temporarily append
                    galleryItems.push({ src: fpSrc, caption: 'Architectural Layout & Floor Plan' });
                    openLightbox(galleryItems.length - 1);
                }
            }
        });
    }

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxPrev) lightboxPrev.addEventListener('click', () => showPhoto(currentPhotoIdx - 1));
    if (lightboxNext) lightboxNext.addEventListener('click', () => showPhoto(currentPhotoIdx + 1));

    // Keyboard controls
    document.addEventListener('keydown', function (e) {
        if (!lightbox || !lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showPhoto(currentPhotoIdx - 1);
        if (e.key === 'ArrowRight') showPhoto(currentPhotoIdx + 1);
    });

    // Click outside image stage to close
    if (lightbox) {
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox || e.target.classList.contains('lightbox-stage')) {
                closeLightbox();
            }
        });
    }

    // -------------------------------------------------------------------------
    // 3. Tabbed Inquiry Form Controller
    // -------------------------------------------------------------------------
    const tabButtons = document.querySelectorAll('.inquiry-tab-btn');
    const tabPanels = document.querySelectorAll('.inquiry-form-panel');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-tab');

            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    // -------------------------------------------------------------------------
    // 4. AJAX Submission for All 3 Inquiry Forms
    // -------------------------------------------------------------------------
    const inquiryForms = [
        document.getElementById('formEnquireNow'),
        document.getElementById('formCallback'),
        document.getElementById('formSiteVisit')
    ];

    inquiryForms.forEach(form => {
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const responseBox = form.closest('.inquiry-box-body').querySelector('.inquiry-response-box');
            const originalBtnContent = submitBtn ? submitBtn.innerHTML : 'Submit';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            }

            const formData = new FormData(form);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }

                if (responseBox) {
                    responseBox.className = 'inquiry-response-box ' + (data.success ? 'success' : 'error');
                    responseBox.innerHTML = `
                        <div class="inquiry-response-icon">
                            <i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
                        </div>
                        <div class="inquiry-response-title">${data.success ? 'Inquiry Confirmed' : 'Action Required'}</div>
                        <p class="inquiry-response-msg">${data.message}</p>
                        ${data.id ? `<span class="inquiry-ref-badge">Reference ID: #EV-${String(data.id).padStart(5, '0')}</span>` : ''}
                    `;
                    responseBox.style.display = 'block';

                    if (data.success) {
                        form.reset();
                        form.style.display = 'none';

                        // Show toast
                        showToast('Inquiry submitted to Senior Acquisition Partner');
                    }
                }
            } catch (err) {
                console.error('Inquiry error:', err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
                if (responseBox) {
                    responseBox.className = 'inquiry-response-box error';
                    responseBox.innerHTML = `
                        <div class="inquiry-response-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="inquiry-response-title">Network Disruption</div>
                        <p class="inquiry-response-msg">Unable to connect to advisory servers. Please call our concierge desk directly or connect via WhatsApp.</p>
                    `;
                    responseBox.style.display = 'block';
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // 5. Wishlist / Heart Portfolio Toggle
    // -------------------------------------------------------------------------
    const wishlistBtn = document.getElementById('btnSaveWishlist');
    const currentPropId = wishlistBtn ? wishlistBtn.getAttribute('data-prop-id') : null;

    function getWishlist() {
        try {
            return JSON.parse(localStorage.getItem('estatevista_wishlist') || '[]');
        } catch (e) {
            return [];
        }
    }

    function setWishlist(arr) {
        localStorage.setItem('estatevista_wishlist', JSON.stringify(arr));
    }

    function updateWishlistBtnUI() {
        if (!wishlistBtn || !currentPropId) return;
        const list = getWishlist();
        const isSaved = list.includes(currentPropId);
        const icon = wishlistBtn.querySelector('i');
        const text = wishlistBtn.querySelector('span');

        if (isSaved) {
            wishlistBtn.classList.add('active-saved');
            if (icon) {
                icon.className = 'fa-solid fa-heart';
            }
            if (text) text.textContent = 'Saved in Portfolio';
        } else {
            wishlistBtn.classList.remove('active-saved');
            if (icon) {
                icon.className = 'fa-regular fa-heart';
            }
            if (text) text.textContent = 'Save Property';
        }
    }

    if (wishlistBtn && currentPropId) {
        updateWishlistBtnUI();

        wishlistBtn.addEventListener('click', async function () {
            let list = getWishlist();
            const idx = list.indexOf(currentPropId);

            // Attempt server-side toggle
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_wishlist');
                formData.append('property_id', currentPropId);

                const resp = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const result = await resp.json();

                if (result.need_login) {
                    showToast('Sign in to sync saved properties with your client dashboard', 'fa-lock');
                } else if (result.success) {
                    if (result.action === 'added') {
                        if (idx === -1) list.push(currentPropId);
                        showToast('Saved to your private luxury portfolio', 'fa-heart');
                    } else {
                        if (idx !== -1) list.splice(idx, 1);
                        showToast('Removed from your saved portfolio', 'fa-heart-crack');
                    }
                    setWishlist(list);
                    updateWishlistBtnUI();
                    return;
                }
            } catch (err) {
                // Fallback to client localStorage
            }

            // Local fallback
            if (idx !== -1) {
                list.splice(idx, 1);
                setWishlist(list);
                showToast('Removed from your saved portfolio', 'fa-heart-crack');
            } else {
                list.push(currentPropId);
                setWishlist(list);
                showToast('Saved to your private luxury portfolio', 'fa-heart');
            }
            updateWishlistBtnUI();
        });
    }

    // -------------------------------------------------------------------------
    // 6. Share Property Button
    // -------------------------------------------------------------------------
    const shareBtn = document.getElementById('btnShareProperty');
    if (shareBtn) {
        shareBtn.addEventListener('click', async function () {
            const propTitle = document.querySelector('.prop-title')?.textContent || 'EstateVista Residence';
            const shareData = {
                title: propTitle,
                text: `Discover ${propTitle} on EstateVista India:`,
                url: window.location.href
            };

            if (navigator.share && /mobile|android|iphone/i.test(navigator.userAgent)) {
                try {
                    await navigator.share(shareData);
                } catch (e) {
                    // user cancelled or unsupported
                }
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showToast('Property link copied to clipboard', 'fa-link');
                }).catch(() => {
                    prompt('Copy property URL:', window.location.href);
                });
            }
        });
    }

    // -------------------------------------------------------------------------
    // 7. RERA Copy Button
    // -------------------------------------------------------------------------
    const reraCopyBtn = document.getElementById('btnCopyRera');
    if (reraCopyBtn) {
        reraCopyBtn.addEventListener('click', function () {
            const reraCode = this.getAttribute('data-rera') || '';
            if (reraCode) {
                navigator.clipboard.writeText(reraCode).then(() => {
                    showToast(`RERA Number ${reraCode} copied`, 'fa-shield-halved');
                });
            }
        });
    }

    // -------------------------------------------------------------------------
    // 8. Mobile Scroll to Inquiry Widget
    // -------------------------------------------------------------------------
    const mobileEnquireBtn = document.getElementById('mobileBtnEnquire');
    if (mobileEnquireBtn) {
        mobileEnquireBtn.addEventListener('click', function () {
            const widget = document.getElementById('inquiryWidget');
            if (widget) {
                widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Subtle pulse highlight
                widget.style.transition = 'box-shadow 0.4s ease';
                widget.style.boxShadow = '0 0 0 4px var(--gold-500)';
                setTimeout(() => {
                    widget.style.boxShadow = '';
                }, 1500);
            }
        });
    }
});
