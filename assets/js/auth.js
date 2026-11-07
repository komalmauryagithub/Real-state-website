/**
 * EstateVista India - Auth & Customer Dashboard Client Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------------------
    // 1. Password Visibility Toggle
    // -------------------------------------------------------------------------
    const togglePassBtns = document.querySelectorAll('.auth-toggle-pass-btn');
    togglePassBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // 2. Real-Time Password Strength Meter
    // -------------------------------------------------------------------------
    const passInput = document.getElementById('reg_password');
    const strengthFill = document.getElementById('passStrengthFill');
    const strengthLabel = document.getElementById('passStrengthLabel');

    if (passInput && strengthFill && strengthLabel) {
        passInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;

            if (val.length >= 8) score += 25;
            if (val.length >= 12) score += 15;
            if (/[A-Z]/.test(val)) score += 20;
            if (/[0-9]/.test(val)) score += 20;
            if (/[^A-Za-z0-9]/.test(val)) score += 20;

            if (score > 100) score = 100;

            let color = '#EF4444';
            let label = 'Weak';

            if (score === 0) {
                label = 'None';
                color = '#E2E8F0';
            } else if (score < 45) {
                label = 'Weak (Needs Uppercase/Number)';
                color = '#EF4444';
            } else if (score < 75) {
                label = 'Moderate';
                color = '#F59E0B';
            } else if (score < 90) {
                label = 'Strong';
                color = '#10B981';
            } else {
                label = 'Ultra-Secure';
                color = '#059669';
            }

            strengthFill.style.width = score + '%';
            strengthFill.style.backgroundColor = color;
            strengthLabel.textContent = label;
            strengthLabel.style.color = color;
        });
    }

    // -------------------------------------------------------------------------
    // 3. 1-Click Demo Account Quick Fill (Login page)
    // -------------------------------------------------------------------------
    const btnFillDemo = document.getElementById('btnFillDemo');
    if (btnFillDemo) {
        btnFillDemo.addEventListener('click', function () {
            const idInput = document.getElementById('login_identity');
            const passInput = document.getElementById('login_pass');
            if (idInput) idInput.value = 'client@estatevistaindia.com';
            if (passInput) passInput.value = 'Password@123';
        });
    }

    // -------------------------------------------------------------------------
    // 4. Remove Saved Property (Dashboard AJAX)
    // -------------------------------------------------------------------------
    const removeSavedBtns = document.querySelectorAll('.btn-remove-saved');
    removeSavedBtns.forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const propId = this.getAttribute('data-prop-id');
            const card = document.getElementById(`saved-card-${propId}`);

            if (!confirm('Remove this residence from your saved luxury portfolio?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_wishlist');
                formData.append('property_id', propId);
                const csrfInput = document.querySelector('input[name="csrf_token"]');
                if (csrfInput) {
                    formData.append('csrf_token', csrfInput.value);
                }

                const res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    if (card) {
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.remove();
                            // Check if empty
                            const grid = document.getElementById('savedPropsGrid');
                            if (grid && grid.children.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                }
            } catch (err) {
                console.error('Failed to remove saved property:', err);
            }
        });
    });
});
