/**
 * EstateVista India - Admin Command Center Scripts
 * Sidebar toggle, modal dialogs, Chart.js graphs, and table filtering.
 */

// Universal Image Safety Fallback for Admin Command Center
window.addEventListener('error', function (e) {
    if (e.target && e.target.tagName === 'IMG') {
        var img = e.target;
        if (img.dataset.fallbackApplied) return;
        img.dataset.fallbackApplied = 'true';
        
        var baseUrl = window.BASE_URL || '../';
        var isAvatar = img.classList.contains('adm-sidebar-user-avatar') || 
                         img.closest('.adm-sidebar-user-avatar') ||
                         (img.alt && (img.alt.includes('Admin') || img.alt.includes('Agent') || img.alt.includes('Client')));
        var isCity = img.closest('.adm-card') && img.alt && !img.alt.includes('Photo') && !img.alt.includes('Image');

        if (isAvatar) {
            img.src = baseUrl + 'assets/images/avatar-placeholder.svg';
        } else {
            img.src = baseUrl + 'assets/images/property-placeholder.svg';
        }
    }
}, true);

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle for Mobile
    const toggleBtn = document.getElementById('admSidebarToggle');
    const sidebar = document.getElementById('admSidebar');
    const backdrop = document.getElementById('admSidebarBackdrop');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = sidebar.classList.toggle('open');
            if (backdrop) {
                backdrop.classList.toggle('show', isOpen);
            }
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                sidebar.classList.remove('open');
                backdrop.classList.remove('show');
            });
        }

        // Close sidebar on click outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    if (backdrop) backdrop.classList.remove('show');
                }
            }
        });
    }

    // 2. Generic Modal Open & Close Handlers
    document.querySelectorAll('[data-adm-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', function () {
            const targetId = this.getAttribute('data-adm-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add('show');
            }
        });
    });

    document.querySelectorAll('.adm-modal-close, [data-adm-modal-close]').forEach(closeBtn => {
        closeBtn.addEventListener('click', function () {
            const backdrop = this.closest('.adm-modal-backdrop');
            if (backdrop) {
                backdrop.classList.remove('show');
            }
        });
    });

    // Close on clicking backdrop
    document.querySelectorAll('.adm-modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });

    // 3. Quick Table Client-side Search Filter
    const searchInputs = document.querySelectorAll('[data-adm-table-search]');
    searchInputs.forEach(input => {
        const tableId = input.getAttribute('data-adm-table-search');
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('keyup', function () {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    // 4. Safe Delete Confirmation Handlers
    document.querySelectorAll('.adm-confirm-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const item = this.getAttribute('data-item-name') || 'this item';
            if (!confirm(`Are you sure you want to permanently delete ${item}? This action cannot be reversed.`)) {
                e.preventDefault();
            }
        });
    });

    // 5. Initialize Charts on Dashboard (Chart.js)
    if (typeof Chart !== 'undefined') {
        initPropertiesCityChart();
        initInquiriesStatusChart();
    }
});

// Chart 1: Properties by Prime City
function initPropertiesCityChart() {
    const ctx = document.getElementById('chartPropertiesCity');
    if (!ctx) return;

    const dataEl = document.getElementById('chartPropertiesCityData');
    if (!dataEl) return;

    try {
        const rawData = JSON.parse(dataEl.textContent);
        const labels = rawData.map(d => d.city_name);
        const counts = rawData.map(d => parseInt(d.property_count, 10));

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        '#0B1528', '#C5A880', '#1E3A8A', '#059669', '#D97706', '#7C3AED'
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '65%'
            }
        });
    } catch (e) {
        console.error('Error rendering chartPropertiesCity', e);
    }
}

// Chart 2: Inquiries by Lead Stage
function initInquiriesStatusChart() {
    const ctx = document.getElementById('chartInquiriesStatus');
    if (!ctx) return;

    const dataEl = document.getElementById('chartInquiriesStatusData');
    if (!dataEl) return;

    try {
        const rawData = JSON.parse(dataEl.textContent);
        const labels = rawData.map(d => d.status_name.toUpperCase());
        const counts = rawData.map(d => parseInt(d.count, 10));

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Inquiries & Leads',
                    data: counts,
                    backgroundColor: '#C5A880',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    } catch (e) {
        console.error('Error rendering chartInquiriesStatus', e);
    }
}
