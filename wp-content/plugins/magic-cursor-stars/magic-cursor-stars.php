<?php
/**
 * Plugin Name: Magic Cursor Stars
 * Description: Thêm hiệu ứng chuột lấp lánh (20 kiểu phong cách "xịn xò") khi di chuyển trỏ chuột. Hỗ trợ xem trước và tuỳ chỉnh nâng cao.
 * Version: 2.1.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Frontend Assets
function magic_cursor_stars_enqueue_assets() {
    if ( ! is_admin() ) {
        wp_enqueue_style( 'magic-cursor-stars-style', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.css', array(), '2.1.0' );
        wp_enqueue_script( 'magic-cursor-stars-script', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.js', array(), '2.1.0', true );
        
        wp_localize_script( 'magic-cursor-stars-script', 'magicCursorConfig', array(
            'style'   => get_option('magic_cursor_selected_style', 'style_1'),
            'size'    => get_option('magic_cursor_size', 'normal'),
            'density' => get_option('magic_cursor_density', 'normal'),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'magic_cursor_stars_enqueue_assets' );

// Admin Preview Assets
function magic_cursor_stars_admin_enqueue( $hook ) {
    if ( $hook === 'settings_page_magic-cursor-stars' ) {
        wp_enqueue_style( 'magic-cursor-stars-style', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.css', array(), '2.1.0' );
        wp_enqueue_script( 'magic-cursor-stars-script', plugin_dir_url( __FILE__ ) . 'magic-cursor-stars.js', array(), '2.1.0', true );
        
        wp_localize_script( 'magic-cursor-stars-script', 'magicCursorConfig', array(
            'style'   => get_option('magic_cursor_selected_style', 'style_1'),
            'size'    => get_option('magic_cursor_size', 'normal'),
            'density' => get_option('magic_cursor_density', 'normal'),
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
    register_setting( 'magic_cursor_stars_settings_group', 'magic_cursor_size' );
    register_setting( 'magic_cursor_stars_settings_group', 'magic_cursor_density' );
}
add_action( 'admin_init', 'magic_cursor_stars_settings' );

// Settings Page
function magic_cursor_stars_options_page() {
    $styles = array(
        'style_1' => '1. Classic Gold Stars (Ngôi sao lấp lánh)',
        'style_2' => '2. Magic Pixie Dust (Bụi tiên thần kỳ)',
        'style_3' => '3. Bubble Stream (Bong bóng chìm nổi)',
        'style_4' => '4. Crimson Hearts (Trái tim bay)',
        'style_5' => '5. Winter Snow (Tuyết mùa đông rơi)',
        'style_6' => '6. Fireworks Sparks (Pháo hoa rực rỡ)',
        'style_7' => '7. Autumn Leaves (Lá thu xào xạc)',
        'style_8' => '8. Matrix Rain (Mã code ma trận)',
        'style_9' => '9. Neon Pulse Orbs (Quả cầu Neon)',
        'style_10' => '10. Cherry Blossoms (Hoa Anh Đào)',
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
    
    $selected_style = get_option('magic_cursor_selected_style', 'style_1');
    $selected_size = get_option('magic_cursor_size', 'normal');
    $selected_density = get_option('magic_cursor_density', 'normal');
    ?>
    <div class="wrap" style="position: relative; z-index: 10;">
        <h1>✨ Magic Cursor Stars (Thiết Lập Mở Rộng) ✨</h1>
        <p style="font-size: 15px;">Thoải mái tuỳ chỉnh cài đặt. <strong>Xem trước TRỰC TIẾP (Live Preview)</strong> bằng cách đổi lựa chọn và di chuyển chuột quanh trang này nhé!</p>
        
        <form method="post" action="options.php">
            <?php settings_fields( 'magic_cursor_stars_settings_group' ); ?>
            <?php do_settings_sections( 'magic_cursor_stars_settings_group' ); ?>
            
            <table class="form-table">
                <!-- Style Dropdown -->
                <tr valign="top">
                    <th scope="row">Kiểu phong cách:</th>
                    <td>
                        <select name="magic_cursor_selected_style" id="mcs_style" style="min-width:350px; padding: 5px; font-size: 15px;">
                            <?php foreach ( $styles as $key => $name ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_style, $key ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <!-- Size Dropdown -->
                <tr valign="top">
                    <th scope="row">Kích thước hạt:</th>
                    <td>
                        <select name="magic_cursor_size" id="mcs_size" style="min-width:350px; padding: 5px; font-size: 15px;">
                            <option value="small" <?php selected( $selected_size, 'small' ); ?>>Nhỏ xíu (Small)</option>
                            <option value="normal" <?php selected( $selected_size, 'normal' ); ?>>Tiêu chuẩn (Normal)</option>
                            <option value="large" <?php selected( $selected_size, 'large' ); ?>>Khổng lồ (Large)</option>
                        </select>
                    </td>
                </tr>
                <!-- Density Dropdown -->
                <tr valign="top">
                    <th scope="row">Mật độ rơi (Số lượng):</th>
                    <td>
                        <select name="magic_cursor_density" id="mcs_density" style="min-width:350px; padding: 5px; font-size: 15px;">
                            <option value="low" <?php selected( $selected_density, 'low' ); ?>>Thưa thớt (Low)</option>
                            <option value="normal" <?php selected( $selected_density, 'normal' ); ?>>Tiêu chuẩn (Normal)</option>
                            <option value="high" <?php selected( $selected_density, 'high' ); ?>>Dày đặc (High)</option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button('💾 Lưu Lại Thiết Lập (Save Changes)', 'primary'); ?>
        </form>
    </div>

    <!-- Live Preview Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elStyle = document.getElementById('mcs_style');
            var elSize = document.getElementById('mcs_size');
            var elDensity = document.getElementById('mcs_density');
            
            function updatePreview() {
                if (typeof magicCursorConfig !== 'undefined') {
                    magicCursorConfig.style = elStyle.value;
                    magicCursorConfig.size = elSize.value;
                    magicCursorConfig.density = elDensity.value;
                }
            }
            
            elStyle.addEventListener('change', updatePreview);
            elSize.addEventListener('change', updatePreview);
            elDensity.addEventListener('change', updatePreview);
        });
    </script>
    <?php
}
