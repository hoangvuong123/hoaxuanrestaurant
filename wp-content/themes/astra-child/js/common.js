console.log('🔥 HOA XUAN JS FILE ĐANG CHẠY');

/* ============================================
   POPUP NOTIFICATION - Duy Anh Restaurant
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var STORAGE_KEY = 'da_popup_seen';
    var LANG_KEY    = 'da_popup_lang';

    var overlay    = document.getElementById('da-overlay');
    var confirmBtn = document.getElementById('da-confirm-btn');
    var notifFab   = document.getElementById('da-notif-fab');

    if (!overlay || !confirmBtn || !notifFab) return;

    var i18n = {
        de: {
            title    : 'Bestellhinweis',
            body     : 'Für Bestellungen <strong>zum Mitnehmen</strong> können Sie uns telefonisch oder per WhatsApp erreichen:',
            wa       : '💬 WhatsApp schreiben',
            delivery : '🚗 <strong>Lieferung</strong> – bitte nachfragen.',
            btn      : 'Verstanden ✓',
        },
        en: {
            title    : 'Order Notice',
            body     : 'To place a <strong>takeaway order</strong>, please call or send us a WhatsApp message:',
            wa       : '💬 Message on WhatsApp',
            delivery : '🚗 <strong>Delivery</strong> – please enquire.',
            btn      : 'Got it ✓',
        },
    };

    var currentLang = localStorage.getItem(LANG_KEY) || 'de';

    function applyLang(lang) {
        currentLang = lang;
        localStorage.setItem(LANG_KEY, lang);

        var t = i18n[lang] || i18n['de'];

        document.getElementById('da-popup-title').textContent    = t.title;
        document.getElementById('da-popup-body').innerHTML       = t.body;
        document.getElementById('da-popup-wa').textContent       = t.wa;
        document.getElementById('da-popup-delivery').innerHTML   = t.delivery;
        document.getElementById('da-confirm-btn').textContent    = t.btn;

        document.querySelectorAll('.da-lang-btn').forEach(function (btn) {
            btn.classList.toggle('da-lang-active', btn.dataset.lang === lang);
        });
    }

    applyLang(currentLang);

    document.querySelectorAll('.da-lang-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyLang(btn.dataset.lang);
        });
    });

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
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePopup(); });

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