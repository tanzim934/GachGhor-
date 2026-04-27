// ============================================================
// GachGhor (গাছঘর) — Main JavaScript
// File: assets/js/main.js
// ============================================================

"use strict";

/* ======== DARK MODE TOGGLE ======== */
(function initTheme() {
    const savedTheme = localStorage.getItem('gg-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcons(savedTheme);
})();

function updateThemeIcons(theme) {
    document.querySelectorAll('#themeToggle, #themeToggleDesktop').forEach(btn => {
        if (!btn) return;
        btn.innerHTML = theme === 'dark'
            ? '<i class="bi bi-sun-fill"></i>'
            : '<i class="bi bi-moon-stars-fill"></i>';
    });
}

document.addEventListener('DOMContentLoaded', () => {

    // Theme toggle buttons
    ['themeToggle', 'themeToggleDesktop'].forEach(id => {
        const btn = document.getElementById(id);
        if (!btn) return;
        btn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('gg-theme', next);
            updateThemeIcons(next);
        });
    });

    /* ======== ADD TO CART (AJAX) ======== */
    document.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', async function () {
            const productId = this.dataset.id;
            const qty = this.dataset.qty || 1;

            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            this.disabled = true;

            try {
                const res = await fetch(SITE_URL + '/backend/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', product_id: productId, quantity: qty })
                });
                const data = await res.json();

                if (data.success) {
                    showToast('✅ Added to cart!', 'success');
                    updateCartBadge(data.cart_count);
                    this.innerHTML = '<i class="bi bi-cart-check-fill"></i> Added';
                    this.classList.add('gg-btn-outline-green');
                    this.classList.remove('gg-btn-green');
                } else {
                    showToast(data.message || 'Please login first.', 'warning');
                    this.innerHTML = '<i class="bi bi-cart3"></i> Add to Cart';
                    this.disabled = false;
                }
            } catch (e) {
                showToast('Something went wrong.', 'danger');
                this.innerHTML = '<i class="bi bi-cart3"></i> Add to Cart';
                this.disabled = false;
            }
        });
    });

    /* ======== WISHLIST TOGGLE (AJAX) ======== */
    document.querySelectorAll('.btn-wishlist').forEach(btn => {
        btn.addEventListener('click', async function () {
            const productId = this.dataset.id;

            try {
                const res = await fetch(SITE_URL + '/backend/api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', product_id: productId })
                });
                const data = await res.json();

                if (data.success) {
                    const isNowWished = data.action === 'added';
                    this.classList.toggle('wishlisted', isNowWished);
                    this.innerHTML = isNowWished
                        ? '<i class="bi bi-heart-fill"></i>'
                        : '<i class="bi bi-heart"></i>';
                    showToast(isNowWished ? '❤️ Added to wishlist!' : 'Removed from wishlist', 'info');
                } else {
                    showToast(data.message || 'Please login first.', 'warning');
                }
            } catch (e) {
                showToast('Something went wrong.', 'danger');
            }
        });
    });

    /* ======== CART QUANTITY CONTROLS ======== */
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const input = this.closest('.quantity-control').querySelector('.quantity-input');
            const cartId = this.dataset.cartId;
            let qty = parseInt(input.value);

            if (this.dataset.action === 'increase') {
                qty = Math.min(qty + 1, 99);
            } else {
                qty = Math.max(qty - 1, 1);
            }

            input.value = qty;
            await updateCartItem(cartId, qty);
        });
    });

    /* ======== REMOVE FROM CART ======== */
    document.querySelectorAll('.btn-remove-cart').forEach(btn => {
        btn.addEventListener('click', async function () {
            const cartId = this.dataset.cartId;
            if (!confirm('Remove this item?')) return;

            try {
                const res = await fetch(SITE_URL + '/backend/api/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove', cart_id: cartId })
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('cart-item-' + cartId)?.remove();
                    updateCartBadge(data.cart_count);
                    refreshCartTotals(data.totals);
                    if (data.cart_count === 0) location.reload();
                }
            } catch (e) {
                showToast('Error removing item.', 'danger');
            }
        });
    });

    /* ======== COUPON APPLY ======== */
    const couponForm = document.getElementById('couponForm');
    if (couponForm) {
        couponForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = document.getElementById('couponCode').value.trim();
            if (!code) return;

            const res = await fetch(SITE_URL + '/backend/api/coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });
            const data = await res.json();
            const msgEl = document.getElementById('couponMsg');

            if (data.success) {
                msgEl.innerHTML = `<span class="text-success fw-bold">✅ Coupon applied! You saved ${data.discount_label}</span>`;
                refreshCartTotals(data.totals);
                document.getElementById('appliedCoupon').value = code;
            } else {
                msgEl.innerHTML = `<span class="text-danger">❌ ${data.message}</span>`;
            }
        });
    }

    /* ======== STAR RATING INPUT ======== */
    document.querySelectorAll('.stars-input .star').forEach((star, index, stars) => {
        star.addEventListener('click', function () {
            const rating = parseInt(this.dataset.value);
            document.getElementById('ratingValue').value = rating;
            stars.forEach((s, i) => s.classList.toggle('active', i < rating));
        });
        star.addEventListener('mouseover', function () {
            const rating = parseInt(this.dataset.value);
            stars.forEach((s, i) => s.style.color = i < rating ? '#f4a623' : '#ccc');
        });
        star.addEventListener('mouseout', () => {
            const rating = parseInt(document.getElementById('ratingValue')?.value || 0);
            stars.forEach((s, i) => s.style.color = i < rating ? '#f4a623' : '#ccc');
        });
    });

    /* ======== PRODUCT IMAGE GALLERY ======== */
    document.querySelectorAll('.product-thumb').forEach(thumb => {
        thumb.addEventListener('click', function () {
            const main = document.getElementById('mainProductImg');
            if (main) {
                main.src = this.src;
                document.querySelectorAll('.product-thumb').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    /* ======== PRICE RANGE FILTER ======== */
    const priceRange = document.getElementById('priceRange');
    const priceDisplay = document.getElementById('priceDisplay');
    if (priceRange && priceDisplay) {
        priceRange.addEventListener('input', function () {
            priceDisplay.textContent = '৳' + parseInt(this.value).toLocaleString();
        });
    }

    /* ======== FORM VALIDATION ======== */
    document.querySelectorAll('form.needs-validation').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });

}); // end DOMContentLoaded

/* ======== HELPER: Update cart quantity via API ======== */
async function updateCartItem(cartId, qty) {
    try {
        const res = await fetch(SITE_URL + '/backend/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', cart_id: cartId, quantity: qty })
        });
        const data = await res.json();
        if (data.success) {
            updateCartBadge(data.cart_count);
            refreshCartTotals(data.totals);
        }
    } catch(e) {}
}

/* ======== HELPER: Update cart badge count ======== */
function updateCartBadge(count) {
    document.querySelectorAll('.cart-badge, .cart-badge-bottom').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}

/* ======== HELPER: Refresh cart totals on page ======== */
function refreshCartTotals(totals) {
    if (!totals) return;
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('cartSubtotal', '৳' + parseFloat(totals.subtotal).toFixed(2));
    set('cartDiscount', '৳' + parseFloat(totals.discount).toFixed(2));
    set('cartShipping', '৳' + parseFloat(totals.shipping).toFixed(2));
    set('cartTotal',    '৳' + parseFloat(totals.total).toFixed(2));
}

/* ======== HELPER: Toast notifications ======== */
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;bottom:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }

    const colors = { success: '#2d7a4f', danger: '#e53e3e', warning: '#f4a623', info: '#0ea5e9' };
    const toast = document.createElement('div');
    toast.style.cssText = `background:${colors[type]||colors.info};color:white;padding:12px 20px;border-radius:10px;font-weight:600;font-family:'Hind Siliguri',sans-serif;box-shadow:0 4px 20px rgba(0,0,0,0.2);max-width:280px;animation:slideInRight 0.3s ease;`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => { toast.style.animation = 'fadeOut 0.3s ease'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// Inject toast animations
const toastStyle = document.createElement('style');
toastStyle.textContent = `
@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOut { to { opacity: 0; transform: translateX(100%); } }`;
document.head.appendChild(toastStyle);
