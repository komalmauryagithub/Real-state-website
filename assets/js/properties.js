/**
 * EstateVista India - Properties Listing & Interactive Gallery Manager
 */

document.addEventListener('DOMContentLoaded', () => {
    initViewToggle();
    initGallerySliders();
    initFilterSidebar();
    initSortChange();
});

/**
 * 1. Grid & List View Toggle
 */
function initViewToggle() {
    const container = document.getElementById('propertiesListingContainer');
    const toggleBtns = document.querySelectorAll('.view-toggle-btn');
    if (!container || !toggleBtns.length) return;

    // Load saved or URL view
    const urlParams = new URLSearchParams(window.location.search);
    const initialView = urlParams.get('view') || localStorage.getItem('estatevista_listing_view') || 'grid';
    applyView(initialView);

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view');
            applyView(view);
            localStorage.setItem('estatevista_listing_view', view);

            // Update URL query without full reload
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('view', view);
            window.history.replaceState({}, '', currentUrl);
        });
    });

    function applyView(view) {
        toggleBtns.forEach(b => b.classList.remove('active'));
        const activeBtn = document.querySelector(`.view-toggle-btn[data-view="${view}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        if (view === 'list') {
            container.classList.remove('grid-view');
            container.classList.add('list-view');
        } else {
            container.classList.remove('list-view');
            container.classList.add('grid-view');
        }
    }
}

/**
 * 2. Property Card Image Gallery Preview Sliders
 */
function initGallerySliders() {
    const sliders = document.querySelectorAll('.gallery-slider');

    sliders.forEach(slider => {
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

/**
 * 3. Filter Sidebar Controls & Mobile Drawer
 */
function initFilterSidebar() {
    // Purpose Pills
    const purposePills = document.querySelectorAll('.purpose-pill-btn');
    const purposeInput = document.getElementById('filter_purpose');

    purposePills.forEach(pill => {
        pill.addEventListener('click', () => {
            purposePills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            if (purposeInput) {
                purposeInput.value = pill.getAttribute('data-purpose');
            }
        });
    });

    // Mobile Drawer Toggle
    const mobileTrigger = document.getElementById('mobileFilterTrigger');
    const closeBtn = document.getElementById('closeFilterDrawerBtn');
    const sidebar = document.getElementById('filterSidebar');

    if (mobileTrigger && sidebar) {
        mobileTrigger.addEventListener('click', () => {
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
}

/**
 * 4. Sort Dropdown Trigger
 */
function initSortChange() {
    const sortSelect = document.getElementById('sortSelect');
    if (!sortSelect) return;

    sortSelect.addEventListener('change', () => {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('sort', sortSelect.value);
        currentUrl.searchParams.set('page', '1'); // reset to page 1 on sort change
        window.location.href = currentUrl.toString();
    });
}
