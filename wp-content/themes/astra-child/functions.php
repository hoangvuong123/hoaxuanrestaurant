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
        filemtime( get_stylesheet_directory() . '/css/common.css' )
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
        filemtime( get_stylesheet_directory() . '/js/common.js' ),
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

/* =======================================
   Auto-activate Restaurant Notice Popup
======================================= */
add_action('init', function() {
    if ( ! function_exists( 'activate_plugin' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if ( ! is_plugin_active( 'restaurant-notice-popup/restaurant-notice-popup.php' ) ) {
        activate_plugin( 'restaurant-notice-popup/restaurant-notice-popup.php' );
    }
});

/* =======================================
   Floating Phone Action Button
======================================= */
if ( ! defined( 'DA_PHONE_DISPLAY' ) ) {
    define( 'DA_PHONE_DISPLAY', '0176 21927505' );
}
if ( ! defined( 'DA_PHONE_LINK' ) ) {
    define( 'DA_PHONE_LINK', 'tel:017621927505' );
}

function hx_render_floating_phone_button() {
    $phone = DA_PHONE_DISPLAY;
    $tel   = DA_PHONE_LINK;
    ?>
    <div id="da-fab-wrap">
        <a id="da-phone-fab" href="<?php echo esc_attr( $tel ); ?>" data-phone="<?php echo esc_attr( $phone ); ?>" title="<?php echo esc_attr( $phone ); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.5 12 19.79 19.79 0 0 1 1.49 3.18A2 2 0 0 1 3.47 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 5.55 5.55l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
        </a>
    </div>
    <?php
}
add_action( 'wp_footer', 'hx_render_floating_phone_button' );

/* =======================================
   Register Menu Badges ACF Fields
======================================= */
add_action('acf/init', 'my_acf_add_local_field_groups');
function my_acf_add_local_field_groups() {
    if( function_exists('acf_add_local_field_group') ):
        acf_add_local_field_group(array(
            'key' => 'group_menu_badges',
            'title' => 'Trạng thái món ăn (Badges)',
            'fields' => array(
                array(
                    'key' => 'field_badge_new',
                    'label' => 'Món Mới (New)',
                    'name' => 'badge_new',
                    'type' => 'true_false',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_badge_new_type',
                    'label' => 'Kiểu hiển thị Món Mới',
                    'name' => 'badge_new_type',
                    'type' => 'select',
                    'choices' => array(
                        'text' => 'Chữ (Text)',
                        'icon' => 'Biểu tượng (Icon)',
                    ),
                    'default_value' => 'text',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_badge_new',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_badge_favorite',
                    'label' => 'Được Yêu Thích (Favorite)',
                    'name' => 'badge_favorite',
                    'type' => 'true_false',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_badge_favorite_type',
                    'label' => 'Kiểu hiển thị Yêu Thích',
                    'name' => 'badge_favorite_type',
                    'type' => 'select',
                    'choices' => array(
                        'text' => 'Chữ (Text)',
                        'icon' => 'Biểu tượng (Icon)',
                    ),
                    'default_value' => 'icon',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_badge_favorite',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'menu',
                    ),
                ),
            ),
            'position' => 'side',
        ));
    endif;
}
