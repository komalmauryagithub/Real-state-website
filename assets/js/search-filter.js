/**
 * EstateVista India - Search & Filter Manager
 * High performance client-side filter engine
 */

document.addEventListener('DOMContentLoaded', () => {
    initCollectionFilter();
    initHeroSearchConsole();
    initNavSearchModeLinks();
});

/**
 * 1. Category Filter Tabs in Curated Collection Section
 */
function initCollectionFilter() {
    const filterBtns = document.querySelectorAll('.collection-filter-btn');
    const propertyCards = document.querySelectorAll('.property-card');
    const countDisplay = document.getElementById('collectionCountBadge');

    if (!filterBtns.length || !propertyCards.length) return;

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filterValue = btn.getAttribute('data-filter');
            let visibleCount = 0;

            propertyCards.forEach(card => {
                const category = card.getAttribute('data-category');
                
                if (filterValue === 'all' || category === filterValue || (filterValue === 'villas' && category.includes('villa'))) {
                    card.style.display = 'flex';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(12px)';
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 40);
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (countDisplay) {
                countDisplay.textContent = `${visibleCount} Available`;
            }
        });
    });
}

/**
 * 2. Hero Search Console Filtering & Tabs (Buy / Rent / Commercial)
 */
function initHeroSearchConsole() {
    const searchTabs = document.querySelectorAll('.search-tab-btn');
    const searchForm = document.getElementById('heroSearchForm');
    const citySelect = document.getElementById('heroCitySelect');
    const propertyTypeSelect = document.getElementById('heroTypeSelect');
    const budgetSelect = document.getElementById('heroBudgetSelect');
    const propertyCards = document.querySelectorAll('.property-card');
    const searchResultsNotice = document.getElementById('searchNoticeBanner');

    // Tab buttons (Buy / Rent / Commercial)
    if (searchTabs.length) {
        searchTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                searchTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const mode = tab.getAttribute('data-tab');
                updateSearchConsoleForMode(mode);
                const purposeInput = document.getElementById('heroPurposeInput');
                if (purposeInput) purposeInput.value = mode;
            });
        });
    }

    // Hero Form submit
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            if (searchForm.getAttribute('action') && searchForm.getAttribute('action').includes('properties.php')) {
                // Form submits naturally to properties.php with all GET parameters
                return true;
            }
            e.preventDefault();

            const selectedCity = (citySelect ? citySelect.value.trim().toLowerCase() : 'all');
            const selectedType = (propertyTypeSelect ? propertyTypeSelect.value.trim().toLowerCase() : 'all');
            const selectedBudget = (budgetSelect ? budgetSelect.value.trim() : 'all');

            let matchCount = 0;

            propertyCards.forEach(card => {
                const cardCity = (card.getAttribute('data-city') || '').toLowerCase();
                const cardCategory = (card.getAttribute('data-category') || '').toLowerCase();
                const cardPrice = parseInt(card.getAttribute('data-price') || '0', 10);

                let cityMatch = (selectedCity === 'all' || cardCity.includes(selectedCity) || selectedCity.includes(cardCity));
                let typeMatch = (selectedType === 'all' || cardCategory.includes(selectedType) || selectedType.includes(cardCategory));
                let budgetMatch = true;

                if (selectedBudget === 'under-15cr') {
                    budgetMatch = (cardPrice <= 150000000);
                } else if (selectedBudget === '15cr-25cr') {
                    budgetMatch = (cardPrice >= 150000000 && cardPrice <= 250000000);
                } else if (selectedBudget === 'above-25cr') {
                    budgetMatch = (cardPrice >= 250000000);
                }

                if (cityMatch && typeMatch && budgetMatch) {
                    card.style.display = 'flex';
                    matchCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Scroll down to results section
            const collectionSection = document.getElementById('curated-collection');
            if (collectionSection) {
                const offsetPosition = collectionSection.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }

            // Display results notice
            if (searchResultsNotice) {
                searchResultsNotice.style.display = 'block';
                searchResultsNotice.innerHTML = `
                    <div style="background: var(--gold-100); border: 1px solid var(--gold-300); padding: 0.85rem 1.5rem; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                        <span style="font-size: 0.85rem; color: var(--navy-900); font-weight: 600;">
                            <i class="fa-solid fa-filter" style="color: var(--gold-600); margin-right: 0.5rem;"></i>
                            Displaying <strong>${matchCount}</strong> properties matching your search criteria.
                        </span>
                        <button type="button" id="resetSearchFilterBtn" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--gold-700); cursor: pointer;">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filter
                        </button>
                    </div>
                `;

                const resetBtn = document.getElementById('resetSearchFilterBtn');
                if (resetBtn) {
                    resetBtn.addEventListener('click', () => {
                        propertyCards.forEach(c => c.style.display = 'flex');
                        searchResultsNotice.style.display = 'none';
                        if (searchForm) searchForm.reset();
                    });
                }
            }
        });
    }
}

/**
 * 3. Update Search Console Budget and Options based on Buy / Rent / Commercial Mode
 */
function updateSearchConsoleForMode(mode) {
    const budgetSelect = document.getElementById('heroBudgetSelect');
    const typeSelect = document.getElementById('heroTypeSelect');

    if (!budgetSelect) return;

    if (mode === 'rent') {
        budgetSelect.innerHTML = `
            <option value="all">Any Monthly Rental</option>
            <option value="under-15cr">Under ₹ 3 Lakh / month</option>
            <option value="15cr-25cr">₹ 3 Lakh – ₹ 7 Lakh / month</option>
            <option value="above-25cr">₹ 7 Lakh+ / month (Ultra-Luxury)</option>
        `;
        if (typeSelect) typeSelect.value = 'all';
    } else if (mode === 'commercial') {
        budgetSelect.innerHTML = `
            <option value="all">Any Commercial Capital</option>
            <option value="under-15cr">₹ 5 Cr – ₹ 15 Cr</option>
            <option value="15cr-25cr">₹ 15 Cr – ₹ 35 Cr</option>
            <option value="above-25cr">₹ 35 Cr+ (Corporate Floors)</option>
        `;
        if (typeSelect) typeSelect.value = 'commercial';
    } else { // 'buy'
        budgetSelect.innerHTML = `
            <option value="all">Any Investment Range</option>
            <option value="under-15cr">Up to ₹ 15.00 Cr</option>
            <option value="15cr-25cr">₹ 15.00 Cr – ₹ 25.00 Cr</option>
            <option value="above-25cr">₹ 25.00 Cr+ (Ultra-Prime)</option>
        `;
        if (typeSelect) typeSelect.value = 'all';
    }
}

/**
 * 4. Connect Navigation Bar Links (Buy / Rent / Commercial) directly to Search Console
 */
function initNavSearchModeLinks() {
    document.querySelectorAll('[data-search-mode]').forEach(link => {
        link.addEventListener('click', (e) => {
            const mode = link.getAttribute('data-search-mode');
            if (!mode) return;

            const targetTab = document.querySelector(`.search-tab-btn[data-tab="${mode}"]`);
            if (targetTab) {
                targetTab.click();
            }
        });
    });
}
