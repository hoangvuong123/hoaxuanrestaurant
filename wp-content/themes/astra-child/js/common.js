console.log('🔥 HOA XUAN JS FILE ĐANG CHẠY');

/* ============================================
   POPUP NOTIFICATION - Duy Anh Restaurant
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var STORAGE_PREFIX = 'da_popup_seen_';
    var LANG_KEY = 'da_popup_lang';

    var overlay = document.getElementById('da-overlay');
    var confirmBtn = document.getElementById('da-confirm-btn');
    var notifFab = document.getElementById('da-notif-fab');
    var tabButtons = document.querySelectorAll('[data-da-tab]');
    var tabPanels = document.querySelectorAll('[data-da-panel]');

    if (!overlay || !confirmBtn || !notifFab) return;

    var popupVersion = overlay.dataset.daPopupVersion || 'v1';
    var STORAGE_KEY = STORAGE_PREFIX + popupVersion;

    var currentLang = localStorage.getItem(LANG_KEY) || 'de';
    if (currentLang !== 'de' && currentLang !== 'en') {
        currentLang = 'de';
    }

    function applyLang(lang) {
        currentLang = lang;
        localStorage.setItem(LANG_KEY, lang);

        document.querySelectorAll('[data-da-lang]').forEach(function (el) {
            el.style.display = el.dataset.daLang === lang ? '' : 'none';
        });

        document.querySelectorAll('.da-lang-btn').forEach(function (btn) {
            btn.classList.toggle('da-lang-active', btn.dataset.lang === lang);
        });
    }

    function activateTab(tabKey) {
        tabButtons.forEach(function (btn) {
            var isActive = btn.dataset.daTab === tabKey;
            btn.classList.toggle('da-popup-tab--active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        tabPanels.forEach(function (panel) {
            var isActive = panel.dataset.daPanel === tabKey;
            panel.classList.toggle('da-popup-panel--active', isActive);
            panel.hidden = !isActive;
        });
    }

    document.querySelectorAll('.da-lang-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyLang(btn.dataset.lang);
        });
    });

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.dataset.daTab);
        });
    });

    if (tabButtons.length) {
        var activeBtn = document.querySelector('.da-popup-tab--active[data-da-tab]') || tabButtons[0];
        activateTab(activeBtn.dataset.daTab);
    }

    applyLang(currentLang);

    if (!sessionStorage.getItem(STORAGE_KEY)) {
        overlay.classList.add('da-visible');
    } else {
        notifFab.style.display = 'flex';
    }

    function closePopup() {
        overlay.classList.remove('da-visible');
        notifFab.style.display = 'flex';
        sessionStorage.setItem(STORAGE_KEY, '1');
    }

    function openPopup() {
        overlay.classList.add('da-visible');
        notifFab.style.display = 'none';
    }

    confirmBtn.addEventListener('click', closePopup);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closePopup(); });
    notifFab.addEventListener('click', openPopup);
    notifFab.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openPopup();
        }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePopup(); });

    /* ---- Add-to-cart → trigger WhatsApp order plugin modal ---- */
    overlay.addEventListener('click', function (e) {
        var btn = e.target.closest('.da-add-to-cart');
        if (!btn) return;
        var postId = btn.dataset.postId;
        var wrapper = postId && document.querySelector('.da-dish-wrapper[data-post-id="' + postId + '"]');
        var titleRow = wrapper && wrapper.querySelector('.hx-order-title-row');
        if (titleRow) {
            titleRow.click();
        }
    });

});

/* ============================================
   ANIMATION -SCROLL
   ============================================ */
document.addEventListener('DOMContentLoaded', function () {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('ani');
            }
        });
    }, { threshold: 0.3 });

    const targets = document.querySelectorAll('.animation');
    targets.forEach(el => observer.observe(el));
});
