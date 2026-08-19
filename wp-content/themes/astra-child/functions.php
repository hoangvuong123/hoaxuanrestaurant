<?php

/* =========================
Load CSS
========================= */
function astra_child_enqueue_styles() {

    // parent css
    wp_enqueue_style(
        'astra-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // common css
    wp_enqueue_style(
        'common-css',
        get_stylesheet_directory_uri() . '/css/common.css',
        array(),
        '1.1'
    );

    // top css
    if ( is_front_page() ) {
        wp_enqueue_style(
            'top-css',
            get_stylesheet_directory_uri() . '/css/top.css',
            array(),
            '1.1'
        );
    }
    
    // our-menu css
    if ( is_page( 'our-menu' ) ) {
        wp_enqueue_style(
            'our-menu-css',
            get_stylesheet_directory_uri() . '/css/our-menu.css',
            array(),
            filemtime( get_stylesheet_directory() . '/css/our-menu.css' )
        );
    }
    
    // header css
    wp_enqueue_style(
        'header-css',
        get_stylesheet_directory_uri() . '/css/header.css',
        array(),
        '1.0'
    );

}
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );


/* =========================
Load JS
========================= */
function astra_child_enqueue_scripts() {

    // common js
    wp_enqueue_script(
        'common-js',
        get_stylesheet_directory_uri() . '/js/common.js',
        array('jquery'),
        '1.0',
        true
    );

    // top js
    if ( is_front_page() ) {
        wp_enqueue_script(
            'top-js',
            get_stylesheet_directory_uri() . '/js/top.js',
            array('jquery'),
            '1.0',
            true
        );
    }
    
     // our-menu js
    if ( is_page( 'our-menu' ) ) {
        wp_enqueue_script(
            'our-menu-js',
            get_stylesheet_directory_uri() . '/js/our-menu.js',
            array('jquery'),
            '1.0',
            true
        );
    }
    
    // header js
    wp_enqueue_script(
        'header-js',
        get_stylesheet_directory_uri() . '/js/header.js',
        array(),
        '1.0',
        true
    );

}
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_scripts' );


/* =========================
Include PHP files
========================= */
require_once get_stylesheet_directory() . '/inc/top.php';
require_once get_stylesheet_directory() . '/inc/featured-menu.php';
require_once get_stylesheet_directory() . '/inc/our-menu.php';
add_filter( 'astra_header_enabled', '__return_false' );

function lumy_register_menus() {
    register_nav_menus([
        'primary' => __( 'Primary Menu', 'lumy' ),
    ]);
}
add_action( 'after_setup_theme', 'lumy_register_menus' );
 
 function lumy_customizer_settings( $wp_customize ) {
    $wp_customize->add_section( 'lumy_header_section', [
        'title'    => __( 'Lumy Header', 'lumy' ),
        'priority' => 30,
    ]);
 
    $wp_customize->add_setting( 'lumy_overlay_image', [
        'default'   => '',
        'transport' => 'refresh',
    ]);
    $wp_customize->add_control( new WP_Customize_Media_Control(
        $wp_customize,
        'lumy_overlay_image',
        [
            'label'     => __( 'Ảnh nền Overlay Menu (Desktop)', 'lumy' ),
            'section'   => 'lumy_header_section',
            'mime_type' => 'image',
        ]
    ));
}
add_action( 'customize_register', 'lumy_customizer_settings' );

/* ============================================
   POPUP NOTIFICATION - Duy Anh Restaurant
============================================ */
define( 'DA_PHONE_DISPLAY',  '0176 21927505' );
define( 'DA_PHONE_LINK',     'tel:017621927505' );
define( 'DA_WHATSAPP_LINK',  'https://wa.me/4917621927505' );
 
function da_popup_enqueue_assets() {
    $theme_uri = get_stylesheet_directory_uri();
    $theme_dir = get_stylesheet_directory();
 
    if ( file_exists( $theme_dir . '/css/popup.css' ) ) {
        wp_enqueue_style( 'da-popup-style', $theme_uri . '/css/popup.css', array(), '1.0.2' );
    }
    if ( file_exists( $theme_dir . '/js/popup.js' ) ) {
        wp_enqueue_script( 'da-popup-script', $theme_uri . '/js/popup.js', array(), '1.0.2', true );
    }
}
add_action( 'wp_enqueue_scripts', 'da_popup_enqueue_assets' );
 
function da_popup_render_html() {
    $phone    = DA_PHONE_DISPLAY;
    $tel      = DA_PHONE_LINK;
    $whatsapp = DA_WHATSAPP_LINK;
    ?>
 
<div id="da-overlay">
    <div id="da-popup">
        <div id="da-lang-switcher">
            <button class="da-lang-btn" data-lang="de" title="Deutsch">🇩🇪</button>
            <button class="da-lang-btn" data-lang="en" title="English">🇬🇧</button>
        </div>
 
        <div id="da-popup-icon">🛵</div>
        <h2 id="da-popup-title">Bestellhinweis</h2>
 
        <p id="da-popup-body">
            Für Bestellungen <strong>zum Mitnehmen</strong> können Sie uns telefonisch oder per WhatsApp erreichen:
        </p>
 
        <div style="display:flex; justify-content:center;">
        <span class="da-phone-highlight">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f0e6d3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.5 12 19.79 19.79 0 0 1 1.49 3.18A2 2 0 0 1 3.47 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 5.55 5.55l.81-.81a2 2 0 0 1 2.11        -.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <?php echo esc_html( $phone ); ?>
        </span>
        </div>
 
        <p style="font-size:.85rem; color:#a08060;">
            <a href="<?php echo esc_url( $whatsapp ); ?>"
               target="_blank" rel="noopener noreferrer"
               id="da-popup-wa" class="da-wa-link">
                💬 WhatsApp schreiben
            </a>
        </p>
 
        <hr class="da-divider">
 
        <p id="da-popup-delivery" style="font-size:.85rem;">
            🚗 <strong>Lieferung</strong> – bitte nachfragen.
        </p>
 
        <button id="da-confirm-btn">Verstanden ✓</button>
 
    </div>
</div>
 
<!-- FLOATING BUTTONS -->
<div id="da-fab-wrap">
    <div id="da-notif-fab" title="Hinweis erneut anzeigen" role="button" tabindex="0">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
    </div>
 
    <a id="da-phone-fab"
       href="<?php echo esc_attr( $tel ); ?>"
       data-phone="<?php echo esc_attr( $phone ); ?>"
       title="<?php echo esc_attr( $phone ); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.5 12 19.79 19.79 0 0 1 1.49 3.18A2 2 0 0 1 3.47 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 5.55 5.55l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
    </a>
</div>
 
    <?php
}
add_action( 'wp_footer', 'da_popup_render_html' );

