<?php
/**
 * Plugin Name: WhatsApp Order Manager
 * Description: Flexible restaurant menu cart and WhatsApp ordering system with notes, design settings and order statistics.
 * Version: 1.6.1
 * Author: Custom Plugin
 * Text Domain: hoa-xuan-order
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Hoa_Xuan_WhatsApp_Order {
    const VERSION = '1.6.3';
    const DB_VERSION = '1.1.0';
    const DB_VERSION_KEY = 'hoa_xuan_order_db_version';
    const OPTION_KEY = 'hoa_xuan_order_settings';

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_hoa_xuan_order_save_settings', array( $this, 'save_settings' ) );
        add_action( 'init', array( $this, 'maybe_upgrade' ) );
        add_action( 'wp_ajax_hx_track_whatsapp', array( $this, 'track_whatsapp_click' ) );
        add_action( 'wp_ajax_nopriv_hx_track_whatsapp', array( $this, 'track_whatsapp_click' ) );
        add_action( 'save_post_menu', array( $this, 'clear_menu_cache' ) );
        add_action( 'deleted_post', array( $this, 'clear_menu_cache' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ) );
        add_action( 'admin_notices', array( $this, 'orders_admin_notice' ) );
    }

    public static function activate() {
        self::create_orders_table();
        update_option( self::DB_VERSION_KEY, self::DB_VERSION );
        delete_transient( 'hoa_xuan_order_menu_data_v3' );
        if ( false === get_option( self::OPTION_KEY, false ) ) {
            add_option(
                self::OPTION_KEY,
                array(
                    'enabled' => '1',
                    'whatsapp_number' => '',
                    'restaurant_name' => get_bloginfo( 'name' ),
                    'message_intro' => 'Neue Bestellung über die Speisekarte',
                    'color_mode' => 'website',
                    'custom_color' => '#c9c518',
                )
            );
        }
    }

    private static function orders_table() {
        global $wpdb;
        return $wpdb->prefix . 'hx_whatsapp_orders';
    }

    public static function create_orders_table() {
        global $wpdb;
        $table = self::orders_table();
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_number varchar(64) NOT NULL DEFAULT '',
            visitor_id varchar(64) NOT NULL DEFAULT '',
            item_count int(10) unsigned NOT NULL DEFAULT 0,
            items longtext NULL,
            order_note text NULL,
            total decimal(12,2) NOT NULL DEFAULT 0.00,
            currency varchar(8) NOT NULL DEFAULT 'EUR',
            page_url text NULL,
            referrer text NULL,
            source varchar(64) NOT NULL DEFAULT '',
            device varchar(16) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY visitor_id (visitor_id),
            KEY order_number (order_number)
        ) {$charset_collate};";
        dbDelta( $sql );
    }

    public function maybe_upgrade() {
        if ( get_option( self::DB_VERSION_KEY ) !== self::DB_VERSION ) {
            self::create_orders_table();
            update_option( self::DB_VERSION_KEY, self::DB_VERSION );
        }
    }

    public function track_whatsapp_click() {
        check_ajax_referer( 'hoa_xuan_order_track', 'nonce' );

        global $wpdb;
        $items_raw = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '[]';
        $items_decoded = json_decode( $items_raw, true );
        $clean_items = array();

        if ( is_array( $items_decoded ) ) {
            foreach ( array_slice( $items_decoded, 0, 100 ) as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }
                $clean_items[] = array(
                    'code' => sanitize_text_field( $item['code'] ?? '' ),
                    'title' => sanitize_text_field( $item['title'] ?? '' ),
                    'choice' => sanitize_text_field( $item['choice'] ?? '' ),
                    'quantity' => min( 99, max( 1, absint( $item['quantity'] ?? 1 ) ) ),
                    'unitPrice' => round( max( 0, (float) ( $item['unitPrice'] ?? 0 ) ), 2 ),
                    'note' => sanitize_textarea_field( $item['note'] ?? '' ),
                );
            }
        }

        $visitor_id = sanitize_text_field( wp_unslash( $_POST['visitor_id'] ?? '' ) );
        $visitor_id = preg_replace( '/[^A-Za-z0-9_-]/', '', $visitor_id );
        $visitor_id = substr( $visitor_id, 0, 64 );

        $inserted = $wpdb->insert(
            self::orders_table(),
            array(
                'order_number' => substr( sanitize_text_field( wp_unslash( $_POST['order_number'] ?? '' ) ), 0, 64 ),
                'visitor_id' => $visitor_id,
                'item_count' => min( 999, absint( $_POST['item_count'] ?? 0 ) ),
                'items' => wp_json_encode( $clean_items, JSON_UNESCAPED_UNICODE ),
                'order_note' => sanitize_textarea_field( wp_unslash( $_POST['order_note'] ?? '' ) ),
                'total' => round( max( 0, (float) ( $_POST['total'] ?? 0 ) ), 2 ),
                'currency' => substr( sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'EUR' ) ), 0, 8 ),
                'page_url' => esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) ),
                'referrer' => esc_url_raw( wp_unslash( $_POST['referrer'] ?? '' ) ),
                'source' => substr( sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) ), 0, 64 ),
                'device' => substr( sanitize_text_field( wp_unslash( $_POST['device'] ?? '' ) ), 0, 16 ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            wp_send_json_error( array( 'message' => 'Could not save tracking event.' ), 500 );
        }

        wp_send_json_success( array( 'id' => (int) $wpdb->insert_id ) );
    }

    public function clear_menu_cache() {
        delete_transient( 'hoa_xuan_order_menu_data_v3' );
    }

    public function settings_link( $links ) {
        array_unshift(
            $links,
            '<a href="' . esc_url( admin_url( 'admin.php?page=hoa-xuan-whatsapp-orders' ) ) . '"><strong>Orders</strong></a>',
            '<a href="' . esc_url( admin_url( 'options-general.php?page=hoa-xuan-order' ) ) . '">Einstellungen</a>'
        );
        return $links;
    }

    public function orders_admin_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'plugins' !== $screen->base ) {
            return;
        }
        echo '<div class="notice notice-success is-dismissible"><p><strong>WhatsApp Order Manager ' . esc_html( self::VERSION ) . '</strong> ist aktiv. <a href="' . esc_url( admin_url( 'admin.php?page=hoa-xuan-whatsapp-orders' ) ) . '">WhatsApp Orders öffnen</a></p></div>';
    }

    public function register_settings() {
        // Keep the option registered for WordPress compatibility, but settings are
        // saved by our own guarded admin-post handler below. This avoids fatal
        // errors caused by environment-specific sanitization callbacks on options.php.
        register_setting(
            'hoa_xuan_order_group',
            self::OPTION_KEY,
            array(
                'type' => 'array',
                'default' => array(),
            )
        );
    }

    private function sanitize_color_value( $value ) {
        $value = is_scalar( $value ) ? trim( (string) $value ) : '';
        if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ) {
            return strtolower( $value );
        }
        if ( preg_match( '/^#[0-9a-fA-F]{3}$/', $value ) ) {
            $r = $value[1];
            $g = $value[2];
            $b = $value[3];
            return strtolower( '#' . $r . $r . $g . $g . $b . $b );
        }
        return '#c9c518';
    }

    public function sanitize_settings( $input ) {
        $input = is_array( $input ) ? $input : array();
        $color_mode = isset( $input['color_mode'] ) ? sanitize_key( (string) $input['color_mode'] ) : 'website';
        if ( ! in_array( $color_mode, array( 'website', 'custom' ), true ) ) {
            $color_mode = 'website';
        }

        return array(
            'enabled' => ! empty( $input['enabled'] ) ? '1' : '0',
            'whatsapp_number' => preg_replace( '/\D+/', '', (string) ( $input['whatsapp_number'] ?? '' ) ),
            'restaurant_name' => sanitize_text_field( (string) ( $input['restaurant_name'] ?? '' ) ),
            'message_intro' => sanitize_text_field( (string) ( $input['message_intro'] ?? '' ) ),
            'color_mode' => $color_mode,
            'custom_color' => $this->sanitize_color_value( $input['custom_color'] ?? '#c9c518' ),
        );
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to change these settings.', 'hoa-xuan-order' ) );
        }

        check_admin_referer( 'hoa_xuan_order_save_settings', 'hoa_xuan_order_nonce' );

        $raw = isset( $_POST[ self::OPTION_KEY ] ) ? wp_unslash( $_POST[ self::OPTION_KEY ] ) : array();
        $clean = $this->sanitize_settings( $raw );
        update_option( self::OPTION_KEY, $clean, false );

        $redirect = add_query_arg(
            array(
                'page' => 'hoa-xuan-order-design',
                'hx-saved' => '1',
            ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    public function add_settings_page() {
        add_menu_page(
            'WhatsApp Orders',
            'WhatsApp Orders',
            'manage_options',
            'hoa-xuan-whatsapp-orders',
            array( $this, 'render_orders_page' ),
            'dashicons-format-chat',
            58
        );

        add_submenu_page(
            'hoa-xuan-whatsapp-orders',
            'WhatsApp Orders',
            'Übersicht',
            'manage_options',
            'hoa-xuan-whatsapp-orders',
            array( $this, 'render_orders_page' )
        );

        add_submenu_page(
            'hoa-xuan-whatsapp-orders',
            'Design & Einstellungen',
            'Design & Einstellungen',
            'manage_options',
            'hoa-xuan-order-design',
            array( $this, 'render_settings_page' )
        );

        add_options_page(
            'WhatsApp Orders',
            'WhatsApp Orders',
            'manage_options',
            'hoa-xuan-whatsapp-orders-settings-shortcut',
            array( $this, 'render_orders_page' )
        );

        add_options_page(
            'WhatsApp Order Manager',
            'WhatsApp Order',
            'manage_options',
            'hoa-xuan-order',
            array( $this, 'render_settings_page' )
        );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1>WhatsApp Order Manager</h1>
            <?php if ( isset( $_GET['hx-saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['hx-saved'] ) ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><strong>Einstellungen gespeichert.</strong></p></div>
            <?php endif; ?>
            <p>Die Bestellung wird als klar formatierte WhatsApp-Nachricht gesendet. Beim Klick auf den WhatsApp-Button werden Bestellnummer, Warenkorb und Statistikdaten für die Auswertung gespeichert.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="hoa_xuan_order_save_settings">
                <?php wp_nonce_field( 'hoa_xuan_order_save_settings', 'hoa_xuan_order_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Bestellsystem</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], '1' ); ?>>
                                WhatsApp-Bestellungen aktivieren
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hx-whatsapp-number">WhatsApp-Nummer</label></th>
                        <td>
                            <input id="hx-whatsapp-number" class="regular-text" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[whatsapp_number]" value="<?php echo esc_attr( $settings['whatsapp_number'] ); ?>">
                            <p class="description">Nur Ziffern mit Ländervorwahl, ohne + oder führende 0. Beispiel: 4917621927505</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hx-restaurant-name">Restaurantname</label></th>
                        <td><input id="hx-restaurant-name" class="regular-text" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[restaurant_name]" value="<?php echo esc_attr( $settings['restaurant_name'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hx-message-intro">Nachrichtenkopf</label></th>
                        <td><input id="hx-message-intro" class="regular-text" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[message_intro]" value="<?php echo esc_attr( $settings['message_intro'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Designfarbe</th>
                        <td>
                            <fieldset class="hx-admin-color-settings">
                                <label style="display:block;margin-bottom:8px;">
                                    <input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_mode]" value="website" <?php checked( $settings['color_mode'], 'website' ); ?>>
                                    <strong>Primärfarbe der Website übernehmen</strong>
                                    <span class="description" style="display:block;margin-left:24px;">Erkennt automatisch gängige Astra-, Elementor- und WordPress-Primary-Variablen.</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:10px;">
                                    <input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[color_mode]" value="custom" <?php checked( $settings['color_mode'], 'custom' ); ?>>
                                    <strong>Eigene Farbe</strong>
                                    <input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[custom_color]" value="<?php echo esc_attr( $settings['custom_color'] ); ?>" style="width:46px;height:32px;padding:2px;border:1px solid #8c8f94;border-radius:4px;background:#fff;">
                                    <code><?php echo esc_html( strtoupper( $settings['custom_color'] ) ); ?></code>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_orders_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;
        $table = self::orders_table();
        $now = current_datetime();
        $today = $now->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
        $start_7 = $now->modify( '-6 days' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
        $start_30 = $now->modify( '-29 days' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );

        $today_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $today ) );
        $week_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $start_7 ) );
        $month_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $start_30 ) );
        $unique_30 = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE created_at >= %s AND visitor_id <> ''", $start_30 ) );
        $value_30 = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total), 0) FROM {$table} WHERE created_at >= %s", $start_30 ) );
        $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100", ARRAY_A );
        ?>
        <div class="wrap">
            <h1>WhatsApp Orders</h1>
            <p>Gezählt wird jeder Klick auf <strong>„Über WhatsApp bestellen“</strong>. Die Nachricht muss in WhatsApp nicht zwingend tatsächlich abgesendet worden sein.</p>
            <p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=hoa-xuan-whatsapp-orders' ) ); ?>">Aktualisieren</a></p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;max-width:1000px;margin:18px 0;">
                <?php
                $cards = array(
                    array( 'Heute', number_format_i18n( $today_count ) ),
                    array( 'Letzte 7 Tage', number_format_i18n( $week_count ) ),
                    array( 'Letzte 30 Tage', number_format_i18n( $month_count ) ),
                    array( 'Besucher 30 Tage', number_format_i18n( $unique_30 ) ),
                    array( 'Warenkorbwert 30 Tage', number_format_i18n( $value_30, 2 ) . ' €' ),
                );
                foreach ( $cards as $card ) : ?>
                    <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;">
                        <div style="color:#646970;font-size:13px;"><?php echo esc_html( $card[0] ); ?></div>
                        <div style="font-size:28px;font-weight:700;margin-top:6px;"><?php echo esc_html( $card[1] ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2>Letzte 100 WhatsApp-Klicks</h2>
            <div style="overflow:auto;background:#fff;border:1px solid #dcdcde;">
                <table class="widefat striped">
                    <thead><tr><th>Zeit</th><th>Bestellnummer</th><th>Gerichte</th><th>Hinweis</th><th>Gesamt</th><th>Quelle</th><th>Gerät</th></tr></thead>
                    <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="7">Noch keine WhatsApp-Klicks erfasst.</td></tr>
                    <?php else : foreach ( $rows as $row ) :
                        $items = json_decode( $row['items'], true );
                        $item_labels = array();
                        if ( is_array( $items ) ) {
                            foreach ( $items as $item ) {
                                $label = trim( ( $item['code'] ? $item['code'] . '. ' : '' ) . ( $item['title'] ?? '' ) );
                                $item_labels[] = (int) ( $item['quantity'] ?? 1 ) . '× ' . $label;
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( $row['created_at'] ); ?></td>
                            <td><strong><?php echo esc_html( $row['order_number'] ); ?></strong></td>
                            <td><?php echo esc_html( implode( ', ', $item_labels ) ); ?></td>
                            <td><?php echo esc_html( $row['order_note'] ?? '' ); ?></td>
                            <td><?php echo esc_html( number_format_i18n( (float) $row['total'], 2 ) . ' ' . $row['currency'] ); ?></td>
                            <td><?php echo esc_html( $row['source'] ?: 'Direct' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $row['device'] ) ); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private function get_settings() {
        $saved = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        $settings = wp_parse_args(
            $saved,
            array(
                'enabled' => '1',
                'whatsapp_number' => '',
                'restaurant_name' => get_bloginfo( 'name' ),
                'message_intro' => 'Neue Bestellung über die Speisekarte',
                'color_mode' => 'website',
                'custom_color' => '#c9c518',
            )
        );
        $settings['color_mode'] = in_array( $settings['color_mode'], array( 'website', 'custom' ), true ) ? $settings['color_mode'] : 'website';
        $settings['custom_color'] = $this->sanitize_color_value( $settings['custom_color'] );
        return $settings;
    }

    private function get_field_value( $field, $post_id ) {
        if ( function_exists( 'get_field' ) ) {
            return get_field( $field, $post_id );
        }
        return get_post_meta( $post_id, $field, true );
    }

    private function normalize_price( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return null;
        }
        $value = preg_replace( '/[^0-9,.-]/', '', $value );
        if ( false !== strpos( $value, ',' ) ) {
            $value = str_replace( '.', '', $value );
            $value = str_replace( ',', '.', $value );
        }
        return is_numeric( $value ) ? round( (float) $value, 2 ) : null;
    }

    private function get_menu_data() {
        $cached = get_transient( 'hoa_xuan_order_menu_data_v3' );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $posts = get_posts(
            array(
                'post_type' => 'menu',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
            )
        );
        $items = array();

        foreach ( $posts as $post ) {
            $variants = $this->get_field_value( 'variants', $post->ID );
            $food_types = $this->get_field_value( 'list_of_food_types', $post->ID );
            $normalized_variants = array();
            $normalized_food_types = array();

            if ( is_array( $variants ) ) {
                foreach ( $variants as $index => $variant ) {
                    $price = $this->normalize_price( $variant['price'] ?? '' );
                    if ( null === $price ) {
                        continue;
                    }
                    $normalized_variants[] = array(
                        'id' => 'variant-' . $index,
                        'code' => '',
                        'nameDe' => sanitize_text_field( $variant['size'] ?? '' ),
                        'nameEn' => sanitize_text_field( $variant['size'] ?? '' ),
                        'price' => $price,
                    );
                }
            }

            if ( is_array( $food_types ) ) {
                foreach ( $food_types as $index => $food_type ) {
                    $price = $this->normalize_price( $food_type['price'] ?? '' );
                    if ( null === $price ) {
                        continue;
                    }
                    $sub_img_url = '';
                    foreach (['image', 'img', 'photo', 'picture', 'hinh_anh'] as $img_key) {
                        if (!empty($food_type[$img_key])) {
                            $img_val = $food_type[$img_key];
                            if (is_array($img_val) && isset($img_val['url'])) {
                                $sub_img_url = $img_val['url'];
                            } elseif (is_numeric($img_val)) {
                                $sub_img_url = wp_get_attachment_url($img_val);
                            } elseif (is_string($img_val) && filter_var($img_val, FILTER_VALIDATE_URL)) {
                                $sub_img_url = $img_val;
                            }
                            if ($sub_img_url) break;
                        }
                    }
                    $normalized_food_types[] = array(
                        'id' => 'food-' . $index,
                        'code' => sanitize_text_field( $food_type['code'] ?? '' ),
                        'nameDe' => sanitize_text_field( $food_type['title-de'] ?? '' ),
                        'nameEn' => sanitize_text_field( $food_type['title-en'] ?? '' ),
                        'allergens' => sanitize_text_field( $food_type['allergens'] ?? '' ),
                        'price' => $price,
                        'image' => $sub_img_url ? esc_url($sub_img_url) : '',
                    );
                }
            }

            $base_price = $this->normalize_price( $this->get_field_value( 'price', $post->ID ) );
            $choices = ! empty( $normalized_food_types ) ? $normalized_food_types : $normalized_variants;
            if ( null === $base_price && empty( $choices ) ) {
                continue;
            }

            $img_url = get_the_post_thumbnail_url( $post->ID, 'large' );

            $items[ (string) $post->ID ] = array(
                'postId' => (int) $post->ID,
                'code' => sanitize_text_field( $this->get_field_value( 'code', $post->ID ) ),
                'titleDe' => sanitize_text_field( $post->post_title ),
                'titleEn' => sanitize_text_field( $this->get_field_value( 'title-en', $post->ID ) ),
                'additives' => sanitize_text_field( $this->get_field_value( 'additives', $post->ID ) ),
                'basePrice' => $base_price,
                'image' => $img_url ? esc_url($img_url) : '',
                'choices' => $choices,
                'choiceType' => ! empty( $normalized_food_types ) ? 'food' : ( ! empty( $normalized_variants ) ? 'variant' : 'none' ),
            );
        }

        set_transient( 'hoa_xuan_order_menu_data_v3', $items, 12 * HOUR_IN_SECONDS );
        return $items;
    }

    public function enqueue_assets() {
        if ( is_admin() ) {
            return;
        }
        if ( ! apply_filters( 'hoa_xuan_order_should_enqueue', true ) ) {
            return;
        }
        $settings = $this->get_settings();
        if ( '1' !== $settings['enabled'] ) {
            return;
        }

        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style(
            'hoa-xuan-whatsapp-order',
            plugins_url( 'assets/order.css', __FILE__ ),
            array(),
            self::VERSION
        );
        wp_enqueue_script(
            'hoa-xuan-whatsapp-order',
            plugins_url( 'assets/order.js', __FILE__ ),
            array(),
            self::VERSION,
            true
        );

        wp_localize_script(
            'hoa-xuan-whatsapp-order',
            'HXOrderConfig',
            array(
                'items' => $this->get_menu_data(),
                'whatsappNumber' => $settings['whatsapp_number'],
                'restaurantName' => $settings['restaurant_name'],
                'messageIntro' => $settings['message_intro'],
                'colorMode' => $settings['color_mode'],
                'customColor' => $settings['custom_color'],
                'storageKey' => 'hoa-xuan-whatsapp-cart-v1',
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'trackNonce' => wp_create_nonce( 'hoa_xuan_order_track' ),
            )
        );
    }
}

register_activation_hook( __FILE__, array( 'Hoa_Xuan_WhatsApp_Order', 'activate' ) );
new Hoa_Xuan_WhatsApp_Order();
