(function ($) {
    'use strict';

    if (typeof menuAjax === 'undefined') return;

    const { ajaxUrl, nonce } = menuAjax;

    // ─── Helpers ─────────────────────────────────────────────
    const ALLOWED_LANGS = ['de', 'en'];

    function getCurrentLang() {
        const parts = window.location.pathname.replace(/^\//, '').split('/');
        if (parts[0] && ALLOWED_LANGS.indexOf(parts[0]) !== -1) {
            return parts[0];
        }
        return 'de';
    }

    function getI18n() {
        if (getCurrentLang() === 'en') {
            return {
                noPost: 'No dishes in this category.',
                error: 'An error occurred. Please try again.',
            };
        }
        return {
            noPost: 'Keine Gerichte in dieser Kategorie.',
            error: 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.',
        };
    }

    function setLoading($section, on) {
        $section.find('.menu-loading').attr('aria-hidden', on ? 'false' : 'true');
        $section.find('.menu-items-list, .menu-categories-wrap, .menu-pagination')
            .css('opacity', on ? '0.4' : '1')
            .css('pointer-events', on ? 'none' : '');
    }

    function buildPagination($nav, totalPages, currentPage) {
        $nav.empty()
            .attr('data-total-pages', totalPages)
            .attr('data-current-page', currentPage);

        for (let p = 1; p <= totalPages; p++) {
            $nav.append(
                $('<button>', {
                    class: 'menu-page-btn' + (p === currentPage ? ' menu-page-btn--active' : ''),
                    'data-page': p,
                    text: p,
                })
            );
        }
    }

    // ─── Core AJAX loader ────────────────────────────────────

    function loadItems($section, termId, page) {
        const taxonomy = $section.data('taxonomy');
        const i18n = getI18n();

        setLoading($section, true);

        $.post(ajaxUrl, {
            action: 'restaurant_menu_filter',
            nonce: nonce,
            term_id: termId,
            page: page,
            taxonomy: taxonomy,
        })
            .done(function (res) {
                if (!res.success) {
                    $section.find('.menu-items-list')
                        .html('<p class="menu-empty">' + i18n.error + '</p>');
                    return;
                }

                const d = res.data;
                const $list = $section.find('.menu-items-list');

                // 1. Cập nhật danh sách món
                $list.html(
                    d.items_html
                        ? d.items_html
                        : '<p class="menu-empty">' + i18n.noPost + '</p>'
                );

                $list.attr('data-current-term', String(termId));
                $list.attr('data-page', String(page));

                // 2. Cập nhật term description
                $section.find('.menu-term-desc')
                    .html(d.term_desc_html || '')
                    .attr('data-term-id', String(termId));

                // 3. Cập nhật categories block
                let $catWrap = $section.find('.menu-categories-wrap');
                if (d.categories_html) {
                    if (!$catWrap.length) {
                        $catWrap = $('<div class="menu-categories-wrap">');
                        $section.find('.menu-loading').before($catWrap);
                    }
                    $catWrap.html(d.categories_html).show();
                } else {
                    $catWrap.hide();
                }

                // 4. Cập nhật pagination
                let $nav = $section.find('.menu-pagination');
                if (d.total_pages > 1) {
                    if (!$nav.length) {
                        $nav = $('<nav class="menu-pagination">');
                        $section.find('.menu-grid-wrap').after($nav);
                    }
                    buildPagination($nav, d.total_pages, page);
                    $nav.show();
                } else {
                    if ($nav.length) $nav.hide();
                }
            })
            .fail(function () {
                const i18n = getI18n();
                $section.find('.menu-items-list')
                    .html('<p class="menu-empty">' + i18n.error + '</p>');
            })
            .always(function () {
                setLoading($section, false);
            });
    }

    // ─── Event: click tab ────────────────────────────────────

    $(document).on('click', '.menu-tab', function () {
        const $tab = $(this);
        const $section = $tab.closest('.menu-section');

        if ($tab.hasClass('menu-tab--active')) return;

        $section.find('.menu-tab')
            .removeClass('menu-tab--active')
            .attr('aria-selected', 'false');

        $tab.addClass('menu-tab--active')
            .attr('aria-selected', 'true');

        const termId = parseInt($tab.attr('data-term-id'), 10);

        // Reset về trang 1 khi đổi tab
        loadItems($section, termId, 1);
    });

    // ─── Event: click page button ────────────────────────────

    $(document).on('click', '.menu-page-btn', function () {
        const $btn = $(this);
        const $section = $btn.closest('.menu-section');

        if ($btn.hasClass('menu-page-btn--active')) return;

        const page = parseInt($btn.attr('data-page'), 10);

        const termId = parseInt(
            $section.find('.menu-items-list').attr('data-current-term'),
            10
        );

        if (!termId || isNaN(termId)) return;

        loadItems($section, termId, page);

        $('html, body').animate({
            scrollTop: $section.offset().top - 80
        }, 300);
    });

    function activateFromHash() {
        const hash = window.location.hash.replace('#', '');
        if (!hash) return;

        const [sectionId, termSlug] = hash.split('__');
        const $section = $('#' + sectionId);
        if (!$section.length) return;

        if (termSlug) {
            const $tab = $section.find('.menu-tab[data-term-slug="' + termSlug + '"]');
            if ($tab.length && !$tab.hasClass('menu-tab--active')) {
                $tab.trigger('click');
            }
        }

        setTimeout(function () {
            $('html, body').animate({
                scrollTop: $section.offset().top - 80
            }, 300);
        }, 150);
    }

    $(window).on('load', activateFromHash);

    $(function () {
        // ─── Event: Sticky Nav Scroll ────────────────────────────
        $('.menu-quick-nav-btn').on('click', function () {
            const targetSlug = $(this).data('target');
            const $targetSection = $('#' + targetSlug);
            if ($targetSection.length) {
                $('html, body').animate({
                    scrollTop: $targetSection.offset().top - 140
                }, 300);
            }
        });

        let isNavUserDragging = false;
        $(window).on('scroll', function () {
            const scrollPos = $(window).scrollTop() + 180;
            let currentTarget = null;
            $('.menu-section').each(function () {
                if ($(this).offset().top <= scrollPos && ($(this).offset().top + $(this).outerHeight() > scrollPos)) {
                    currentTarget = $(this).data('taxonomy');
                }
            });

            if (currentTarget) {
                $('.menu-quick-nav-btn').removeClass('active');
                const $activeBtn = $('.menu-quick-nav-btn[data-target="' + currentTarget + '"]');
                $activeBtn.addClass('active');

                if ($activeBtn.length && !isNavUserDragging) {
                    const $navLinks = $('.menu-sticky-nav__links');
                    const btnLeft = $activeBtn.position().left;
                    const btnWidth = $activeBtn.outerWidth();
                    const containerWidth = $navLinks.width();
                    const scrollLeft = $navLinks.scrollLeft();

                    if (btnLeft < 20 || btnLeft + btnWidth > containerWidth - 20) {
                        $navLinks.stop().animate({
                            scrollLeft: scrollLeft + btnLeft - (containerWidth / 2) + (btnWidth / 2)
                        }, 150);
                    }
                }
            }
        });

        // ─── Event: Image Popup ──────────────────────────────────
        let $imgPopup = $('<div class="menu-img-popup"><div class="menu-img-popup__content"><img class="menu-img-popup__img" src="" alt="Menu image"><button class="menu-img-popup-close" aria-label="Close">×</button></div></div>');
        $('body').append($imgPopup);

        const closePopup = function () {
            $imgPopup.removeClass('on');
            setTimeout(() => { $imgPopup.find('img').attr('src', ''); }, 350);
        };

        $(document).on('click', '.menu-item--has-image, .menu-item__ft-row--has-image', function (e) {
            e.stopPropagation();
            const imgSrc = $(this).attr('data-image');
            if (imgSrc) {
                $imgPopup.find('img').attr('src', imgSrc);
                $imgPopup.addClass('on');
            }
        });

        $imgPopup.on('click', function (e) {
            if ($(e.target).is('.menu-img-popup') || $(e.target).closest('.menu-img-popup-close').length) {
                closePopup();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $imgPopup.hasClass('on')) {
                closePopup();
            }
        });

    });

})(jQuery);