<?php
/**
 * Plugin Name: 3D Restaurant Menu Book
 * Description: Full-page 3D restaurant menu book using the original PDF artwork as page textures.
 * Version: 1.2.3
 * Author: OpenAI
 * Text Domain: restaurant-3d-menu-book
 */
if (!defined('ABSPATH')) exit;

class Restaurant_3D_Menu_Book {
    const OPTION_KEY = 'r3dmb_settings';
    const VERSION = '1.2.3';
    const PAGE_COUNT = 28;

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'save_settings']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_filter('script_loader_tag', [$this, 'module_script_tag'], 10, 3);
        add_shortcode('restaurant_3d_menu_book', [$this, 'shortcode']);
    }

    public function activate() {
        $old = get_option(self::OPTION_KEY, []);
        if (!is_array($old)) $old = [];
        update_option(self::OPTION_KEY, wp_parse_args($old, $this->defaults()));
    }

    private function defaults() {
        return [
            'restaurant_name' => get_bloginfo('name') ?: 'Hoa Xuan Restaurant',
            'hero_title' => 'Speisekarte',
            'hero_subtitle' => 'Ziehen Sie die Seitenkante, um durch unsere Speisekarte zu blättern.',
            'accent_color' => '#e4b956',
            'full_page' => '1',
            'show_chapters' => '1',
            'show_thumbnails' => '1',
        ];
    }

    private function settings() {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args(is_array($saved) ? $saved : [], $this->defaults());
    }

    public function admin_menu() {
        add_menu_page(
            '3D Menu Book',
            '3D Menu Book',
            'manage_options',
            'restaurant-3d-menu-book',
            [$this, 'settings_page'],
            'dashicons-book-alt',
            58
        );
    }

    public function save_settings() {
        if (empty($_POST['r3dmb_save'])) return;
        if (!current_user_can('manage_options')) return;
        check_admin_referer('r3dmb_save');

        update_option(self::OPTION_KEY, [
            'restaurant_name' => sanitize_text_field(wp_unslash($_POST['restaurant_name'] ?? '')),
            'hero_title' => sanitize_text_field(wp_unslash($_POST['hero_title'] ?? '')),
            'hero_subtitle' => sanitize_textarea_field(wp_unslash($_POST['hero_subtitle'] ?? '')),
            'accent_color' => sanitize_hex_color(wp_unslash($_POST['accent_color'] ?? '')) ?: '#e4b956',
            'full_page' => !empty($_POST['full_page']) ? '1' : '0',
            'show_chapters' => !empty($_POST['show_chapters']) ? '1' : '0',
            'show_thumbnails' => !empty($_POST['show_thumbnails']) ? '1' : '0',
        ]);

        wp_safe_redirect(add_query_arg(['page' => 'restaurant-3d-menu-book', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    public function settings_page() {
        $s = $this->settings();
        ?>
        <div class="wrap">
            <h1>3D Restaurant Menu Book</h1>
            <?php if (!empty($_GET['updated'])): ?>
                <div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt.</p></div>
            <?php endif; ?>
            <p><strong>PDF artwork:</strong> 28 trang đã được chuyển thành ảnh chất lượng cao và đóng gói trong plugin.</p>
            <p>Dùng shortcode <code>[restaurant_3d_menu_book]</code>. Book mặc định full viewport và giữ nguyên typography/artwork từ PDF gốc.</p>
            <form method="post">
                <?php wp_nonce_field('r3dmb_save'); ?>
                <table class="form-table" role="presentation">
                    <tr><th><label for="restaurant_name">Tên nhà hàng</label></th><td><input class="regular-text" id="restaurant_name" name="restaurant_name" value="<?php echo esc_attr($s['restaurant_name']); ?>"></td></tr>
                    <tr><th><label for="hero_title">Tiêu đề</label></th><td><input class="regular-text" id="hero_title" name="hero_title" value="<?php echo esc_attr($s['hero_title']); ?>"></td></tr>
                    <tr><th><label for="hero_subtitle">Mô tả</label></th><td><textarea class="large-text" rows="3" id="hero_subtitle" name="hero_subtitle"><?php echo esc_textarea($s['hero_subtitle']); ?></textarea></td></tr>
                    <tr><th><label for="accent_color">Màu accent UI</label></th><td><input type="color" id="accent_color" name="accent_color" value="<?php echo esc_attr($s['accent_color']); ?>"></td></tr>
                    <tr><th>Hiển thị</th><td>
                        <label><input type="checkbox" name="full_page" value="1" <?php checked($s['full_page'], '1'); ?>> Full page / full viewport</label><br>
                        <label><input type="checkbox" name="show_chapters" value="1" <?php checked($s['show_chapters'], '1'); ?>> Chapter navigation</label><br>
                        <label><input type="checkbox" name="show_thumbnails" value="1" <?php checked($s['show_thumbnails'], '1'); ?>> Thumbnail strip</label>
                    </td></tr>
                </table>
                <p class="submit"><button type="submit" class="button button-primary" name="r3dmb_save" value="1">Lưu cài đặt</button></p>
            </form>
        </div>
        <?php
    }

    public function register_assets() {
        wp_register_style('r3dmb-style', plugins_url('assets/style.css', __FILE__), [], self::VERSION);
        wp_register_script('r3dmb-three-book', plugins_url('assets/book-three.js', __FILE__), [], self::VERSION, true);
    }

    public function module_script_tag($tag, $handle, $src) {
        if ($handle !== 'r3dmb-three-book') return $tag;
        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }

    private function page_urls() {
        $pages = [];
        for ($i = 1; $i <= self::PAGE_COUNT; $i++) {
            $pages[] = plugins_url('assets/pages/page-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . '.webp', __FILE__);
        }
        return $pages;
    }

    private function chapters() {
        return [
            ['title' => 'Willkommen', 'page' => 0],
            ['title' => 'Restaurant', 'page' => 1],
            ['title' => 'Empfehlung', 'page' => 2],
            ['title' => 'Vorspeisen', 'page' => 4],
            ['title' => 'Große Suppe', 'page' => 7],
            ['title' => 'Reis Spezialitäten', 'page' => 8],
            ['title' => 'Hauptspeisen', 'page' => 9],
            ['title' => 'Vegetarisch', 'page' => 11],
            ['title' => 'Deutsche Küche', 'page' => 12],
            ['title' => 'Bowl & Kinder', 'page' => 13],
            ['title' => 'Sushi', 'page' => 14],
            ['title' => 'Nigiri & Maki', 'page' => 15],
            ['title' => 'Sashimi', 'page' => 16],
            ['title' => 'Homemade Roll', 'page' => 17],
            ['title' => 'Big Roll', 'page' => 18],
            ['title' => 'Sushi Menü', 'page' => 19],
            ['title' => 'Dessert', 'page' => 20],
            ['title' => 'Getränke', 'page' => 21],
            ['title' => 'Softdrinks', 'page' => 22],
            ['title' => 'Tee & Kaffee', 'page' => 23],
            ['title' => 'Homemade Drinks', 'page' => 24],
            ['title' => 'Cocktails', 'page' => 25],
            ['title' => 'Bier & Wein', 'page' => 26],
            ['title' => 'Allergene', 'page' => 27],
        ];
    }

    public function shortcode($atts = []) {
        $s = $this->settings();
        $atts = shortcode_atts(['full' => $s['full_page']], $atts, 'restaurant_3d_menu_book');
        $full = filter_var($atts['full'], FILTER_VALIDATE_BOOLEAN) || $atts['full'] === '1';

        wp_enqueue_style('r3dmb-style');
        wp_enqueue_script('r3dmb-three-book');

        $config = [
            'restaurantName' => $s['restaurant_name'],
            'heroTitle' => $s['hero_title'],
            'heroSubtitle' => $s['hero_subtitle'],
            'accentColor' => $s['accent_color'],
            'pageUrls' => $this->page_urls(),
            'chapters' => $this->chapters(),
            'pageCount' => self::PAGE_COUNT,
            'showChapters' => $s['show_chapters'] === '1',
            'showThumbnails' => $s['show_thumbnails'] === '1',
        ];

        return '<div class="r3dmb-root' . ($full ? ' r3dmb-root--full' : '') . '" data-r3dmb-config="' . esc_attr(wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"><div class="r3dmb-init">Loading 3D menu...</div></div>';
    }
}

new Restaurant_3D_Menu_Book();
