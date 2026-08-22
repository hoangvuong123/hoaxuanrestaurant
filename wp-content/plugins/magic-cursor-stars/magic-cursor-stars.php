<?php
/**
 * Plugin Name: Magic Cursor Stars
 * Description: Thêm hiệu ứng chuột lấp lánh (20 kiểu phong cách "xịn xò") khi di chuyển trỏ chuột. Dễ dàng đổi style ở mục Cài đặt.
 * Version: 2.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Frontend Assets
function magic_cursor_stars_enqueue_assets() {
    if ( ! is_admin() ) {
        wp_enqueue_style( 'magic-cursor-stars-style', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.css', array(), '2.0.0' );
        wp_enqueue_script( 'magic-cursor-stars-script', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.js', array(), '2.0.0', true );
        
        $selected_style = get_option('magic_cursor_selected_style', 'style_1');
        wp_localize_script( 'magic-cursor-stars-script', 'magicCursorConfig', array(
            'style' => $selected_style
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'magic_cursor_stars_enqueue_assets' );

// Admin Preview Assets
function magic_cursor_stars_admin_enqueue( $hook ) {
    if ( $hook === 'settings_page_magic-cursor-stars' ) {
        wp_enqueue_style( 'magic-cursor-stars-style', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.css', array(), '2.0.0' );
        wp_enqueue_script( 'magic-cursor-stars-script', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.js', array(), '2.0.0', true );
        
        $selected_style = get_option('magic_cursor_selected_style', 'style_1');
        wp_localize_script( 'magic-cursor-stars-script', 'magicCursorConfig', array(
            'style' => $selected_style
        ) );
    }
}
add_action( 'admin_enqueue_scripts', 'magic_cursor_stars_admin_enqueue' );

// Setup Menu
function magic_cursor_stars_menu() {
    add_options_page(
        'Magic Cursor Styles',
        'Magic Cursor Stars',
        'manage_options',
        'magic-cursor-stars',
        'magic_cursor_stars_options_page'
    );
}
add_action('admin_menu', 'magic_cursor_stars_menu');

// Settings API
function magic_cursor_stars_settings() {
    register_setting( 'magic_cursor_stars_settings_group', 'magic_cursor_selected_style' );
}
add_action( 'admin_init', 'magic_cursor_stars_settings' );

// Settings Page
function magic_cursor_stars_options_page() {
    $styles = array(
        'style_1' => '1. Classic Gold Stars (Ngôi sao lấp lánh)',
        'style_2' => '2. Magic Pixie Dust (Bụi tiên thần kỳ)',
        'style_3' => '3. Bubble Stream (Bong bóng chìm nổi)',
        'style_4' => '4. Crimson Hearts (Trái tim nbay)',
        'style_5' => '5. Winter Snow (Tuyết mùa đông rơi)',
        'style_6' => '6. Fireworks Sparks (Pháo hoa rực rỡ)',
        'style_7' => '7. Autumn Leaves (Lá thu xào xạc)',
        'style_8' => '8. Matrix Rain (Mã code ma trận)',
        'style_9' => '9. Neon Pulse Orbs (Quả cầu Neon)',
        'style_10' => '10. Cherry Blossoms (Thiên đường Hoa Anh Đào)',
        'style_11' => '11. Lightning Zaps (Sét điện zig-zag)',
        'style_12' => '12. Confetti Party (Chúc mừng bung lụa)',
        'style_13' => '13. Musical Notes (Bản nhạc du dương)',
        'style_14' => '14. Campfire Embers (Tàn lửa rực đỏ)',
        'style_15' => '15. Ice Crystal / Diamonds (Tinh thể kim cương)',
        'style_16' => '16. Cyberpunk Geometry (Hình khối tương lai)',
        'style_17' => '17. Retro 8-Bit Pixels (Pixel cổ điển)',
        'style_18' => '18. Ghost Wisps (Linh hồn bí ẩn)',
        'style_19' => '19. Shooting Stars (Sao băng xẹt ngang)',
        'style_20' => '20. Rainbow Sparkles (Ánh kim lục sắc)'
    );
    $selected = get_option('magic_cursor_selected_style', 'style_1');
    ?>
    <div class="wrap" style="position: relative; z-index: 10;">
        <h1>✨ Magic Cursor Stars (20 Kiểu Xịn Xò) ✨</h1>
        <p style="font-size: 15px;">Chọn kiểu hiệu ứng (Style) bạn thích nhất. Bạn có thể <strong>rê chuột ngay tại màn hình này</strong> để xem trước (Preview) hiệu ứng áp dụng hiện tại!</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'magic_cursor_stars_settings_group' ); ?>
            <?php do_settings_sections( 'magic_cursor_stars_settings_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                <th scope="row">Kiểu phong cách:</th>
                <td>
                    <select name="magic_cursor_selected_style" style="min-width:350px; padding: 5px; font-size: 15px;">
                        <?php foreach ( $styles as $key => $name ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description" style="margin-top: 10px;">Nhấn Lưu để áp dụng kiểu. Tải lại trang (F5) nếu bạn muốn xem hiệu ứng vừa cập nhật.</p>
                </td>
                </tr>
            </table>
            <?php submit_button('Lưu Thay Đổi (Save Changes)', 'primary'); ?>
        </form>
    </div>
    <?php
}
