<?php
/**
 * Plugin Name: Restaurant Notice Popup
 * Description: A customizable modular popup for Restaurant notifications (Closure, New Dishes, Order Notice).
 * Version: 1.0.0
 * Author: Developer
 * Text Domain: restaurant-notice-popup
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function rnp_enqueue_assets() {
    $css_ver = filemtime( plugin_dir_path( __FILE__ ) . 'assets/popup.css' );
    $js_ver  = filemtime( plugin_dir_path( __FILE__ ) . 'assets/popup.js' );
    wp_enqueue_style( 'rnp-popup', plugin_dir_url( __FILE__ ) . 'assets/popup.css', [], $css_ver );
    wp_enqueue_script( 'rnp-popup', plugin_dir_url( __FILE__ ) . 'assets/popup.js', [], $js_ver, true );
}
add_action( 'wp_enqueue_scripts', 'rnp_enqueue_assets' );

if ( ! defined( 'DA_PHONE_DISPLAY' ) ) {
    define( 'DA_PHONE_DISPLAY', '0176 21927505' );
}
if ( ! defined( 'DA_PHONE_LINK' ) ) {
    define( 'DA_PHONE_LINK', 'tel:017621927505' );
}
if ( ! defined( 'DA_WHATSAPP_LINK' ) ) {
    define( 'DA_WHATSAPP_LINK', 'https://wa.me/4917621927505' );
}

if ( ! defined( 'rnp_OPTION_KEY' ) ) {
    define( 'rnp_OPTION_KEY', 'rnp_settings' );
}

function rnp_default_settings() {
    return [
        'order_notice_enabled'   => '1',
        'new_dishes_enabled'     => '0',
        'new_dish_ids'           => [],
        'closure_notice_enabled' => '0',
        'closure_text_de'        => '',
        'closure_text_en'        => '',
    ];
}

function rnp_get_settings() {
    $settings = get_option( rnp_OPTION_KEY, [] );
    if ( ! is_array( $settings ) ) {
        $settings = [];
    }

    $settings = wp_parse_args( $settings, rnp_default_settings() );
    
    $dish_ids = [];
    if ( isset( $settings['new_dish_ids'] ) && is_array( $settings['new_dish_ids'] ) ) {
        foreach ( $settings['new_dish_ids'] as $id ) {
            $abs_id = absint( $id );
            if ( $abs_id > 0 && ! in_array( $abs_id, $dish_ids, true ) ) {
                $dish_ids[] = $abs_id;
            }
        }
    }
    $settings['new_dish_ids'] = $dish_ids;

    return $settings;
}

function rnp_sanitize_settings( $input ) {
    $log_file = WP_CONTENT_DIR . '/debug-popup.log';
    try {
        if ( ! is_array( $input ) ) {
            $input = [];
        }

        $clean = rnp_default_settings();

        $clean['order_notice_enabled']   = empty( $input['order_notice_enabled'] ) ? '0' : '1';
        $clean['new_dishes_enabled']     = empty( $input['new_dishes_enabled'] ) ? '0' : '1';
        $clean['closure_notice_enabled'] = empty( $input['closure_notice_enabled'] ) ? '0' : '1';

        $text_de = isset( $input['closure_text_de'] ) && is_scalar( $input['closure_text_de'] ) ? (string) $input['closure_text_de'] : '';
        $text_en = isset( $input['closure_text_en'] ) && is_scalar( $input['closure_text_en'] ) ? (string) $input['closure_text_en'] : '';

        $clean['closure_text_de'] = wp_kses_post( wp_unslash( $text_de ) );
        $clean['closure_text_en'] = wp_kses_post( wp_unslash( $text_en ) );

        $dish_ids = [];
        if ( isset( $input['new_dish_ids'] ) && is_array( $input['new_dish_ids'] ) ) {
            foreach ( $input['new_dish_ids'] as $id ) {
                $abs_id = absint( $id );
                if ( $abs_id > 0 && ! in_array( $abs_id, $dish_ids, true ) ) {
                    $dish_ids[] = $abs_id;
                }
            }
        }
        $clean['new_dish_ids'] = $dish_ids;

        @file_put_contents( $log_file, date('[Y-m-d H:i:s]') . ' rnp_sanitize_settings: OK' . PHP_EOL, FILE_APPEND );
        return $clean;

    } catch ( \Throwable $e ) {
        @file_put_contents( $log_file, date('[Y-m-d H:i:s]') . ' EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL, FILE_APPEND );
        return rnp_default_settings();
    }
}

function rnp_register_settings() {
    register_setting(
        'rnp_settings_group',
        rnp_OPTION_KEY,
        [
            'type'              => 'array',
            'sanitize_callback' => 'rnp_sanitize_settings',
            'default'           => rnp_default_settings(),
        ]
    );
}
add_action( 'admin_init', 'rnp_register_settings' );

function rnp_add_admin_page() {
    add_options_page(
        'Hoa Xuan Popup',
        'Hoa Xuan Popup',
        'manage_options',
        'hoa-xuan-popup',
        'rnp_render_settings_page'
    );
}
add_action( 'admin_menu', 'rnp_add_admin_page' );

function rnp_get_field( $field_name, $post_id ) {
    if ( function_exists( 'get_field' ) ) {
        return get_field( $field_name, $post_id );
    }

    return get_post_meta( $post_id, $field_name, true );
}

function rnp_menu_sort_value( $post_id ) {
    $code = trim( (string) rnp_get_field( 'code', $post_id ) );
    if ( '' === $code ) {
        return PHP_INT_MAX;
    }

    preg_match( '/(\d+)/', $code, $match );
    return isset( $match[1] ) ? (int) $match[1] : PHP_INT_MAX;
}

function rnp_get_menu_posts_for_admin() {
    $posts = get_posts( [
        'post_type'      => 'menu',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    usort( $posts, function ( $a, $b ) {
        $code_a = rnp_menu_sort_value( $a->ID );
        $code_b = rnp_menu_sort_value( $b->ID );

        if ( $code_a !== $code_b ) {
            return $code_a <=> $code_b;
        }

        return strcasecmp( $a->post_title, $b->post_title );
    } );

    return $posts;
}

function rnp_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings     = rnp_get_settings();
    $selected_ids = array_map( 'absint', $settings['new_dish_ids'] );
    $menu_posts   = rnp_get_menu_posts_for_admin();
    ?>
    <div class="wrap">
        <h1>Hoa Xuan Popup Settings</h1>
        <p>Manage the popup tabs: order notice, new dishes, and closure notice.</p>

        <form method="post" action="options.php">
            <?php settings_fields( 'rnp_settings_group' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Order Notice</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( rnp_OPTION_KEY ); ?>[order_notice_enabled]" value="1" <?php checked( '1', $settings['order_notice_enabled'] ); ?>>
                            Show order notice / WhatsApp tab
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">New Dishes</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( rnp_OPTION_KEY ); ?>[new_dishes_enabled]" value="1" <?php checked( '1', $settings['new_dishes_enabled'] ); ?>>
                            Show new dishes tab
                        </label>

                        <p>
                            <select name="<?php echo esc_attr( rnp_OPTION_KEY ); ?>[new_dish_ids][]" multiple size="12" style="min-width:360px;max-width:100%;">
                                <?php foreach ( $menu_posts as $post ) :
                                    $code     = trim( (string) rnp_get_field( 'code', $post->ID ) );
                                    $title_en = trim( (string) rnp_get_field( 'title-en', $post->ID ) );
                                    $label    = ( $code ? $code . '. ' : '' ) . $post->post_title;
                                    if ( $title_en ) {
                                        $label .= ' / ' . $title_en;
                                    }
                                    ?>
                                    <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( in_array( $post->ID, $selected_ids, true ) ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="description">Hold Ctrl/Cmd to select multiple dishes. List is pulled from the <code>menu</code> post type.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Closure Notice</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( rnp_OPTION_KEY ); ?>[closure_notice_enabled]" value="1" <?php checked( '1', $settings['closure_notice_enabled'] ); ?>>
                            Show closure notice tab
                        </label>

                        <p>
                            <label for="hx-popup-closure-de"><strong>German text</strong></label><br>
                            <textarea id="hx-popup-closure-de" name="<?php echo esc_attr( rnp_OPTION_KEY ); ?>[closure_text_de]" rows="4" class="large-text"><?php echo esc_textarea( $settings['closure_text_de'] ); ?></textarea>
                        </p>

                        <p>
                            <label for="hx-popup-closure-en"><strong>English text</strong></label><br>
                            <textarea id="hx-popup-closure-en" name="<?php echo esc_attr( rnp_OPTION_KEY ); ?>[closure_text_en]" rows="4" class="large-text"><?php echo esc_textarea( $settings['closure_text_en'] ); ?></textarea>
                        </p>
                        <p class="description">Enter the closure message shown in the popup, e.g.: We are closed today and will reopen tomorrow.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save settings' ); ?>
        </form>
    </div>
    <?php
}

function rnp_price_data( $post_id ) {
    $variants = rnp_get_field( 'variants', $post_id );
    if ( is_array( $variants ) && ! empty( $variants ) ) {
        $items = [];
        foreach ( $variants as $variant ) {
            $price = trim( (string) ( $variant['price'] ?? '' ) );
            if ( '' === $price ) {
                continue;
            }

            $size    = trim( (string) ( $variant['size'] ?? '' ) );
            $numeric = str_replace( ',', '.', $price );
            $items[] = [
                'size'  => $size,
                'price' => number_format( (float) $numeric, 2, ',', '.' ) . '€',
            ];
        }

        if ( $items ) {
            return $items;
        }
    }

    $price = rnp_get_field( 'price', $post_id );
    if ( '' !== $price && null !== $price && false !== $price ) {
        $numeric = str_replace( ',', '.', (string) $price );
        $size    = trim( (string) rnp_get_field( 'size', $post_id ) );

        return [ [
            'size'  => $size,
            'price' => number_format( (float) $numeric, 2, ',', '.' ) . '€',
        ] ];
    }

    return [];
}

function rnp_render_price_badges( $post_id ) {
    $prices = rnp_price_data( $post_id );
    if ( empty( $prices ) ) {
        return '';
    }

    $html = '<div class="da-dish-prices">';
    foreach ( $prices as $price ) {
        $label = $price['size']
            ? $price['size'] . ': ' . $price['price']
            : $price['price'];

        $html .= '<span>' . esc_html( $label ) . '</span>';
    }
    $html .= '</div>';

    return $html;
}

function rnp_get_new_dishes( array $settings ) {
    if ( '1' !== $settings['new_dishes_enabled'] || empty( $settings['new_dish_ids'] ) ) {
        return [];
    }

    $posts = get_posts( [
        'post_type'      => 'menu',
        'post_status'    => 'publish',
        'posts_per_page' => count( $settings['new_dish_ids'] ),
        'post__in'       => $settings['new_dish_ids'],
        'orderby'        => 'post__in',
    ] );

    return $posts;
}

function rnp_lang_copy( $de, $en, $class = '', $allow_html = false ) {
    $de = trim( (string) $de );
    $en = trim( (string) $en );

    if ( '' === $de ) {
        $de = $en;
    }
    if ( '' === $en ) {
        $en = $de;
    }

    $class_attr = $class ? ' class="' . esc_attr( $class ) . '"' : '';

    return sprintf(
        '<span data-da-lang="de"%1$s>%2$s</span><span data-da-lang="en"%1$s>%3$s</span>',
        $class_attr,
        $allow_html ? wp_kses_post( nl2br( $de ) ) : nl2br( esc_html( $de ) ),
        $allow_html ? wp_kses_post( nl2br( $en ) ) : nl2br( esc_html( $en ) )
    );
}

function rnp_menu_page_url() {
    $page = get_page_by_path( 'our-menu' );
    if ( $page ) {
        return get_permalink( $page );
    }

    return home_url( '/our-menu/' );
}

function rnp_render_tab_button( $key, $active, $label_de, $label_en, $badge = '' ) {
    ?>
    <button
        class="da-popup-tab <?php echo $active ? 'da-popup-tab--active' : ''; ?>"
        type="button"
        role="tab"
        aria-selected="<?php echo $active ? 'true' : 'false'; ?>"
        aria-controls="da-panel-<?php echo esc_attr( $key ); ?>"
        id="da-tab-<?php echo esc_attr( $key ); ?>"
        data-da-tab="<?php echo esc_attr( $key ); ?>"
    >
        <?php echo rnp_lang_copy( $label_de, $label_en ); ?>
        <?php if ( '' !== $badge ) : ?>
            <span class="da-popup-tab__badge"><?php echo esc_html( $badge ); ?></span>
        <?php endif; ?>
    </button>
    <?php
}

function rnp_render_panel_open( $key, $active, $has_tabs = true ) {
    $labelledby = $has_tabs ? 'da-tab-' . $key : 'da-popup-title';
    ?>
    <section
        class="da-popup-panel <?php echo $active ? 'da-popup-panel--active' : ''; ?>"
        id="da-panel-<?php echo esc_attr( $key ); ?>"
        role="tabpanel"
        aria-labelledby="<?php echo esc_attr( $labelledby ); ?>"
        data-da-panel="<?php echo esc_attr( $key ); ?>"
        <?php echo $active ? '' : 'hidden'; ?>
    >
    <?php
}

function rnp_render_html() {
    $settings      = rnp_get_settings();
    $new_dishes    = rnp_get_new_dishes( $settings );
    $closure_de    = trim( (string) $settings['closure_text_de'] );
    $closure_en    = trim( (string) $settings['closure_text_en'] );
    $menu_url      = rnp_menu_page_url();
    $tabs          = [];

    if ( '1' === $settings['closure_notice_enabled'] && ( '' !== $closure_de || '' !== $closure_en ) ) {
        $tabs[] = 'closure';
    }
    if ( ! empty( $new_dishes ) ) {
        $tabs[] = 'new';
    }
    if ( '1' === $settings['order_notice_enabled'] ) {
        $tabs[] = 'order';
    }

    $has_popup = ! empty( $tabs );
    $version   = substr( md5( wp_json_encode( [ $settings, wp_list_pluck( $new_dishes, 'ID' ) ] ) ), 0, 12 );
    $phone     = DA_PHONE_DISPLAY;
    $tel       = DA_PHONE_LINK;
    $whatsapp  = DA_WHATSAPP_LINK;

    if ( $has_popup ) :
        $first_tab = $tabs[0];
        $has_tabs  = count( $tabs ) > 1;
        ?>
        <div id="da-overlay" data-da-popup-version="<?php echo esc_attr( $version ); ?>">
            <div id="da-popup" role="dialog" aria-modal="true" aria-labelledby="da-popup-title">
                <div id="da-lang-switcher">
                    <button class="da-lang-btn" type="button" data-lang="de" title="Deutsch">🇩🇪</button>
                    <button class="da-lang-btn" type="button" data-lang="en" title="English">🇬🇧</button>
                </div>

                <div id="da-popup-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <h2 id="da-popup-title">
                    <?php echo rnp_lang_copy( 'Aktuelle Hinweise', 'Current Notices' ); ?>
                </h2>

                <?php if ( $has_tabs ) : ?>
                    <div class="da-popup-tabs" role="tablist" aria-label="Popup notices">
                        <?php
                        foreach ( $tabs as $tab ) {
                            if ( 'closure' === $tab ) {
                                rnp_render_tab_button( 'closure', $first_tab === $tab, 'Ruhetag', 'Closed', '!' );
                            } elseif ( 'new' === $tab ) {
                                rnp_render_tab_button( 'new', $first_tab === $tab, 'Neu', 'New', count( $new_dishes ) );
                            } else {
                                rnp_render_tab_button( 'order', $first_tab === $tab, 'Bestellung', 'Order' );
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="da-popup-panels">
                    <?php if ( in_array( 'closure', $tabs, true ) ) : ?>
                        <?php rnp_render_panel_open( 'closure', $first_tab === 'closure', $has_tabs ); ?>
                            <div class="da-closure-card">
                                <div class="da-closure-illustration">
                                    <svg viewBox="0 0 24 24" width="48" height="48" stroke="#c9a96e" stroke-width="1" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <path d="M9 22V12h6v10"></path>
                                        <rect x="11" y="15" width="2" height="3" fill="#c9a96e"></rect>
                                    </svg>
                                </div>
                                <h3 class="da-closure-title">
                                    <span data-da-lang="de">Ruhetag</span>
                                    <span data-da-lang="en">Closed Today</span>
                                </h3>
                                <div class="da-closure-divider">
                                    <svg width="80" height="12" viewBox="0 0 100 12" fill="none" aria-hidden="true">
                                        <path d="M0 6h40M60 6h40M50 2L54 6L50 10L46 6L50 2Z" stroke="#c9a96e" stroke-width="0.75" fill="#c9a96e" fill-opacity="0.2"/>
                                    </svg>
                                </div>
                                <div class="da-closure-text">
                                    <?php echo rnp_lang_copy( $closure_de, $closure_en, '', true ); ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( in_array( 'new', $tabs, true ) ) : ?>
                        <?php rnp_render_panel_open( 'new', $first_tab === 'new', $has_tabs ); ?>
                            <div class="da-dish-list">
                                <?php
                                $menu_sc = Restaurant_Menu_Shortcode::get_instance();
                                foreach ( $new_dishes as $dish ) :
                                    $dish_id = $dish->ID;
                                ?>
                                    <div class="da-dish-wrapper" data-post-id="<?php echo esc_attr( $dish_id ); ?>">
                                        <?php echo $menu_sc->render_menu_item( $dish_id, 'de' ); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="da-actions">
                                <a class="da-secondary-link" href="<?php echo esc_url( $menu_url ); ?>">
                                    <?php echo rnp_lang_copy( 'Menü ansehen', 'View menu' ); ?>
                                </a>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( in_array( 'order', $tabs, true ) ) : ?>
                        <?php rnp_render_panel_open( 'order', $first_tab === 'order', $has_tabs ); ?>
                            <p id="da-popup-body">
                                <?php echo rnp_lang_copy(
                                    'Für Bestellungen <strong>zum Mitnehmen</strong> können Sie uns telefonisch oder per WhatsApp erreichen:',
                                    'To place a <strong>takeaway order</strong>, please call or send us a WhatsApp message:',
                                    '',
                                    true
                                ); ?>
                            </p>

                            <div class="da-phone-row">
                                <span class="da-phone-highlight">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f0e6d3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.5 12 19.79 19.79 0 0 1 1.49 3.18A2 2 0 0 1 3.47 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 5.55 5.55l.81-.81a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                    <?php echo esc_html( $phone ); ?>
                                </span>
                            </div>

                            <p class="da-whatsapp-row">
                                <a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener noreferrer" id="da-popup-wa" class="da-wa-link">
                                    <?php echo rnp_lang_copy( 'WhatsApp schreiben', 'Message on WhatsApp' ); ?>
                                </a>
                            </p>

                            <hr class="da-divider">

                            <p id="da-popup-delivery">
                                <?php echo rnp_lang_copy(
                                    '<strong>Lieferung</strong> - bitte nachfragen.',
                                    '<strong>Delivery</strong> - please enquire.',
                                    '',
                                    true
                                ); ?>
                            </p>
                        </section>
                    <?php endif; ?>
                </div>

                <button id="da-confirm-btn" type="button">
                    <?php echo rnp_lang_copy( 'Verstanden', 'Got it' ); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div id="rnp-fab-wrap">
        <?php if ( $has_popup ) : ?>
            <div id="da-notif-fab" title="Hinweis erneut anzeigen" role="button" tabindex="0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
add_action( 'wp_footer', 'rnp_render_html' );
