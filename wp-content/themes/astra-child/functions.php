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
