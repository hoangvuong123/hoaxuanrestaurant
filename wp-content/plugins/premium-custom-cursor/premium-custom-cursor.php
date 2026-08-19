<?php
/**
 * Plugin Name: Premium Custom Cursor FX
 * Description: Advanced custom cursor with 16 visual presets, 3D tilt, bling effects, trails, hover labels and rich click animations.
 * Version: 1.2.1
 * Author: Custom Build
 * License: GPL-2.0-or-later
 * Text Domain: premium-custom-cursor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PCC_Premium_Custom_Cursor_FX_V120', false ) ) :

final class PCC_Premium_Custom_Cursor_FX_V120 {
	const VERSION = '1.2.1';
	const OPTION  = 'pcc_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );
	}

	public static function defaults() {
		return array(
			'enabled'              => 1,
			'preset'               => 'glass',
			'hide_native'          => 1,
			'disable_below'        => 769,

			'dot_enabled'          => 1,
			'dot_size'             => 6,
			'dot_color'            => '#ffffff',
			'dot_opacity'          => 1,

			'ring_size'            => 42,
			'ring_border_width'    => 1,
			'ring_border_color'    => '#ffffff',
			'ring_border_opacity'  => 0.70,
			'ring_bg_color'        => '#ffffff',
			'ring_bg_opacity'      => 0.02,
			'ring_text_color'      => '#ffffff',
			'ring_radius'          => 50,
			'ring_blur'            => 0,
			'blend_mode'           => 'difference',
			'z_index'              => 999999,

			'follow_speed'         => 0.12,
			'dot_speed'            => 0.65,
			'tilt_enabled'         => 1,
			'tilt_strength'        => 12,

			'ambient_fx'           => 1,
			'fx_primary'           => '#ffffff',
			'fx_secondary'         => '#f4c95d',
			'glow_strength'        => 1,
			'trail_enabled'        => 0,
			'trail_length'         => 8,
			'trail_opacity'        => 0.26,

			'hover_enabled'        => 1,
			'hover_size'           => 68,
			'hover_bg_color'       => '#ffffff',
			'hover_bg_opacity'     => 0.10,
			'hover_border_color'   => '#ffffff',
			'hover_border_opacity' => 0.25,

			'label_enabled'        => 1,
			'default_label'        => 'VIEW',
			'label_size'           => 84,
			'label_font_size'      => 9,
			'label_letter_spacing' => 0.14,
			'label_bg_color'       => '#ffffff',
			'label_text_color'     => '#111111',
			'label_border_color'   => '#ffffff',
			'label_border_opacity' => 0,

			'click_effect'         => 'ripple',
			'click_particles'      => 14,
			'click_size'           => 120,

			'interactive_selector' => 'a, button, [role="button"], input[type="submit"], input[type="button"], .elementor-button',
			'label_selector'       => '.mtg-card, [data-pcc-label]',
		);
	}

	private function get_settings() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $this->normalize_settings_array( $saved ), self::defaults() );
	}

	public function register_settings() {
		register_setting(
			'pcc_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	private function scalar_value( $value, $default = '' ) {
		if ( is_scalar( $value ) || null === $value ) {
			return null === $value ? $default : $value;
		}

		return $default;
	}

	private function normalize_settings_array( $settings ) {
		$defaults = self::defaults();

		if ( ! is_array( $settings ) ) {
			return array();
		}

		foreach ( $settings as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				unset( $settings[ $key ] );
				continue;
			}

			$settings[ $key ] = $this->scalar_value( $value, $defaults[ $key ] );
		}

		return $settings;
	}

	private function clamp( $value, $min, $max, $default ) {
		$value = $this->scalar_value( $value, $default );
		if ( ! is_numeric( $value ) ) {
			return $default;
		}
		return max( $min, min( $max, (float) $value ) );
	}

	private function hex( $value, $fallback ) {
		$value = $this->scalar_value( $value, $fallback );
		$value = sanitize_hex_color( $value );
		return $value ? $value : $fallback;
	}

	public function sanitize_settings( $input ) {
		$d = self::defaults();
		if ( ! is_array( $input ) ) {
			return $d;
		}

		$input = $this->normalize_settings_array( $input );

		$out = array();

		$checkboxes = array(
			'enabled',
			'hide_native',
			'dot_enabled',
			'tilt_enabled',
			'ambient_fx',
			'trail_enabled',
			'hover_enabled',
			'label_enabled',
		);

		foreach ( $checkboxes as $key ) {
			$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		$presets = array(
			'minimal', 'outline', 'glass', 'solid', 'neon', 'invert',
			'hologram', 'crystal3d', 'chrome3d', 'aurora', 'bling',
			'orbit', 'cyber', 'liquid', 'comet', 'luxe_gold'
		);
		$preset = isset( $input['preset'] ) ? (string) $input['preset'] : $d['preset'];
		$out['preset'] = in_array( $preset, $presets, true ) ? $preset : $d['preset'];

		$blend_modes = array( 'normal', 'difference', 'exclusion', 'screen', 'multiply' );
		$blend_mode = isset( $input['blend_mode'] ) ? (string) $input['blend_mode'] : $d['blend_mode'];
		$out['blend_mode'] = in_array( $blend_mode, $blend_modes, true ) ? $blend_mode : $d['blend_mode'];

		$click_effects = array(
			'none', 'shrink', 'pulse', 'ripple', 'double_ripple',
			'spark_burst', 'diamond_burst', 'shockwave', 'firework'
		);
		$click_effect = isset( $input['click_effect'] ) ? (string) $input['click_effect'] : $d['click_effect'];
		$out['click_effect'] = in_array( $click_effect, $click_effects, true ) ? $click_effect : $d['click_effect'];

		$out['disable_below'] = (int) $this->clamp( isset( $input['disable_below'] ) ? $input['disable_below'] : null, 0, 2560, $d['disable_below'] );
		$out['z_index']       = (int) $this->clamp( isset( $input['z_index'] ) ? $input['z_index'] : null, 1, 2147483000, $d['z_index'] );

		$numeric = array(
			'dot_size'             => array( 1, 40 ),
			'dot_opacity'          => array( 0, 1 ),
			'ring_size'            => array( 10, 240 ),
			'ring_border_width'    => array( 0, 12 ),
			'ring_border_opacity'  => array( 0, 1 ),
			'ring_bg_opacity'      => array( 0, 1 ),
			'ring_radius'          => array( 0, 50 ),
			'ring_blur'            => array( 0, 30 ),
			'follow_speed'         => array( 0.02, 1 ),
			'dot_speed'            => array( 0.05, 1 ),
			'tilt_strength'        => array( 0, 28 ),
			'glow_strength'        => array( 0, 3 ),
			'trail_length'         => array( 2, 20 ),
			'trail_opacity'        => array( 0.02, 0.9 ),
			'hover_size'           => array( 10, 300 ),
			'hover_bg_opacity'     => array( 0, 1 ),
			'hover_border_opacity' => array( 0, 1 ),
			'label_size'           => array( 20, 320 ),
			'label_font_size'      => array( 6, 40 ),
			'label_letter_spacing' => array( 0, 1 ),
			'label_border_opacity' => array( 0, 1 ),
			'click_particles'      => array( 4, 30 ),
			'click_size'           => array( 50, 260 ),
		);

		foreach ( $numeric as $key => $range ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : null;
			$out[ $key ] = $this->clamp( $value, $range[0], $range[1], $d[ $key ] );
		}

		$colors = array(
			'dot_color',
			'ring_border_color',
			'ring_bg_color',
			'ring_text_color',
			'fx_primary',
			'fx_secondary',
			'hover_bg_color',
			'hover_border_color',
			'label_bg_color',
			'label_text_color',
			'label_border_color',
		);

		foreach ( $colors as $key ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : '';
			$out[ $key ] = $this->hex( $value, $d[ $key ] );
		}

		$out['default_label']        = sanitize_text_field( $this->scalar_value( isset( $input['default_label'] ) ? $input['default_label'] : null, $d['default_label'] ) );
		$out['interactive_selector'] = sanitize_textarea_field( $this->scalar_value( isset( $input['interactive_selector'] ) ? $input['interactive_selector'] : null, $d['interactive_selector'] ) );
		$out['label_selector']       = sanitize_textarea_field( $this->scalar_value( isset( $input['label_selector'] ) ? $input['label_selector'] : null, $d['label_selector'] ) );

		return $out;
	}

	public function admin_menu() {
		add_options_page(
			__( 'Premium Custom Cursor FX', 'premium-custom-cursor' ),
			__( 'Custom Cursor FX', 'premium-custom-cursor' ),
			'manage_options',
			'premium-custom-cursor',
			array( $this, 'settings_page' )
		);
	}

	public function admin_assets( $hook ) {
		if ( 'settings_page_premium-custom-cursor' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'pcc-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'pcc-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			array(),
			self::VERSION,
			true
		);
	}

	private function rgba( $hex, $opacity ) {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) ) {
			return 'rgba(255,255,255,' . (float) $opacity . ')';
		}

		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		return 'rgba(' . (int) $r . ',' . (int) $g . ',' . (int) $b . ',' . (float) $opacity . ')';
	}

	public function frontend_assets() {
		$s = $this->get_settings();

		if ( empty( $s['enabled'] ) ) {
			return;
		}

		wp_enqueue_style(
			'pcc-frontend',
			plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
			array(),
			self::VERSION
		);

		$vars  = ':root{';
		$vars .= '--pcc-dot-size:' . (float) $s['dot_size'] . 'px;';
		$vars .= '--pcc-dot-color:' . $s['dot_color'] . ';';
		$vars .= '--pcc-dot-opacity:' . (float) $s['dot_opacity'] . ';';
		$vars .= '--pcc-ring-size:' . (float) $s['ring_size'] . 'px;';
		$vars .= '--pcc-ring-border-width:' . (float) $s['ring_border_width'] . 'px;';
		$vars .= '--pcc-ring-border:' . $this->rgba( $s['ring_border_color'], $s['ring_border_opacity'] ) . ';';
		$vars .= '--pcc-ring-bg:' . $this->rgba( $s['ring_bg_color'], $s['ring_bg_opacity'] ) . ';';
		$vars .= '--pcc-ring-text:' . $s['ring_text_color'] . ';';
		$vars .= '--pcc-ring-radius:' . (float) $s['ring_radius'] . '%;';
		$vars .= '--pcc-ring-blur:' . (float) $s['ring_blur'] . 'px;';
		$vars .= '--pcc-blend-mode:' . $s['blend_mode'] . ';';
		$vars .= '--pcc-z:' . (int) $s['z_index'] . ';';
		$vars .= '--pcc-fx-primary:' . $s['fx_primary'] . ';';
		$vars .= '--pcc-fx-secondary:' . $s['fx_secondary'] . ';';
		$vars .= '--pcc-glow:' . (float) $s['glow_strength'] . ';';
		$vars .= '--pcc-trail-opacity:' . (float) $s['trail_opacity'] . ';';
		$vars .= '--pcc-hover-size:' . (float) $s['hover_size'] . 'px;';
		$vars .= '--pcc-hover-bg:' . $this->rgba( $s['hover_bg_color'], $s['hover_bg_opacity'] ) . ';';
		$vars .= '--pcc-hover-border:' . $this->rgba( $s['hover_border_color'], $s['hover_border_opacity'] ) . ';';
		$vars .= '--pcc-label-size:' . (float) $s['label_size'] . 'px;';
		$vars .= '--pcc-label-font-size:' . (float) $s['label_font_size'] . 'px;';
		$vars .= '--pcc-label-letter-spacing:' . (float) $s['label_letter_spacing'] . 'em;';
		$vars .= '--pcc-label-bg:' . $s['label_bg_color'] . ';';
		$vars .= '--pcc-label-text:' . $s['label_text_color'] . ';';
		$vars .= '--pcc-label-border:' . $this->rgba( $s['label_border_color'], $s['label_border_opacity'] ) . ';';
		$vars .= '--pcc-click-size:' . (float) $s['click_size'] . 'px;';
		$vars .= '}';

		wp_add_inline_style( 'pcc-frontend', $vars );

		wp_enqueue_script(
			'pcc-frontend',
			plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js',
			array(),
			self::VERSION,
			true
		);

		wp_localize_script(
			'pcc-frontend',
			'PCCursorSettings',
			array(
				'enabled'             => (bool) $s['enabled'],
				'preset'              => $s['preset'],
				'hideNative'          => (bool) $s['hide_native'],
				'disableBelow'        => (int) $s['disable_below'],
				'dotEnabled'          => (bool) $s['dot_enabled'],
				'hoverEnabled'        => (bool) $s['hover_enabled'],
				'labelEnabled'        => (bool) $s['label_enabled'],
				'defaultLabel'        => $s['default_label'],
				'followSpeed'         => (float) $s['follow_speed'],
				'dotSpeed'            => (float) $s['dot_speed'],
				'tiltEnabled'         => (bool) $s['tilt_enabled'],
				'tiltStrength'        => (float) $s['tilt_strength'],
				'ambientFx'           => (bool) $s['ambient_fx'],
				'trailEnabled'        => (bool) $s['trail_enabled'],
				'trailLength'         => (int) $s['trail_length'],
				'clickEffect'         => $s['click_effect'],
				'clickParticles'      => (int) $s['click_particles'],
				'fxPrimary'           => $s['fx_primary'],
				'fxSecondary'         => $s['fx_secondary'],
				'interactiveSelector' => $s['interactive_selector'],
				'labelSelector'       => $s['label_selector'],
			)
		);
	}

	private function field_name( $key ) {
		return self::OPTION . '[' . $key . ']';
	}

	private function checkbox( $s, $key, $label ) {
		printf(
			'<label class="pcc-switch"><input type="checkbox" name="%1$s" value="1" %2$s><span class="pcc-slider"></span><span>%3$s</span></label>',
			esc_attr( $this->field_name( $key ) ),
			checked( ! empty( $s[ $key ] ), true, false ),
			esc_html( $label )
		);
	}

	private function number( $s, $key, $min, $max, $step = '1', $suffix = '' ) {
		printf(
			'<div class="pcc-number"><input type="number" id="pcc_%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s"><span>%7$s</span></div>',
			esc_attr( $key ),
			esc_attr( $this->field_name( $key ) ),
			esc_attr( $s[ $key ] ),
			esc_attr( $min ),
			esc_attr( $max ),
			esc_attr( $step ),
			esc_html( $suffix )
		);
	}

	private function color( $s, $key ) {
		printf(
			'<input type="color" id="pcc_%1$s" name="%2$s" value="%3$s">',
			esc_attr( $key ),
			esc_attr( $this->field_name( $key ) ),
			esc_attr( $s[ $key ] )
		);
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s = $this->get_settings();

		$presets = array(
			'Clean' => array(
				'minimal' => 'Minimal Dot',
				'outline' => 'Premium Outline',
				'glass'   => 'Soft Glass',
				'solid'   => 'Solid Bubble',
				'invert'  => 'Smart Invert',
			),
			'Glow / Bling' => array(
				'neon'      => 'Neon Glow',
				'bling'     => 'Bling Bling ✦',
				'luxe_gold' => 'Luxe Gold',
				'aurora'    => 'Aurora Glow',
				'hologram'  => 'Holographic',
			),
			'3D / Motion' => array(
				'crystal3d' => 'Crystal 3D',
				'chrome3d'  => 'Chrome 3D',
				'orbit'     => 'Orbit Rings',
				'liquid'    => 'Liquid Blob',
				'comet'     => 'Comet Trail',
				'cyber'     => 'Cyber HUD',
			),
		);

		$clicks = array(
			'none'          => 'None',
			'shrink'        => 'Press / Shrink',
			'pulse'         => 'Pulse',
			'ripple'        => 'Ripple',
			'double_ripple' => 'Double Ripple',
			'spark_burst'   => 'Spark Burst ✦',
			'diamond_burst' => 'Diamond Burst ◆',
			'shockwave'     => 'Neon Shockwave',
			'firework'      => 'Mini Firework',
		);
		?>
		<div class="wrap pcc-admin">
			<div class="pcc-heading">
				<div>
					<h1><?php esc_html_e( 'Premium Custom Cursor FX', 'premium-custom-cursor' ); ?></h1>
					<p><?php esc_html_e( '16 cursor styles, 3D motion, bling effects, trails and click animations — no GSAP required.', 'premium-custom-cursor' ); ?></p>
				</div>
				<span class="pcc-badge">v<?php echo esc_html( self::VERSION ); ?></span>
			</div>

			<form method="post" action="options.php" id="pcc-settings-form">
				<?php settings_fields( 'pcc_settings_group' ); ?>

				<div class="pcc-layout">
					<div class="pcc-settings">

						<section class="pcc-card">
							<div class="pcc-section-title">
								<h2><?php esc_html_e( 'Style & General', 'premium-custom-cursor' ); ?></h2>
								<span>16 presets</span>
							</div>
							<div class="pcc-grid">
								<div class="pcc-field pcc-wide">
									<?php $this->checkbox( $s, 'enabled', __( 'Enable custom cursor', 'premium-custom-cursor' ) ); ?>
								</div>

								<div class="pcc-field pcc-wide">
									<label for="pcc_preset"><?php esc_html_e( 'Cursor style', 'premium-custom-cursor' ); ?></label>
									<select id="pcc_preset" name="<?php echo esc_attr( $this->field_name( 'preset' ) ); ?>">
										<?php foreach ( $presets as $group => $items ) : ?>
											<optgroup label="<?php echo esc_attr( $group ); ?>">
												<?php foreach ( $items as $value => $label ) : ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['preset'], $value ); ?>><?php echo esc_html( $label ); ?></option>
												<?php endforeach; ?>
											</optgroup>
										<?php endforeach; ?>
									</select>
									<small>Presets change both the shape and animation personality.</small>
								</div>

								<div class="pcc-field">
									<label><?php esc_html_e( 'Disable below width', 'premium-custom-cursor' ); ?></label>
									<?php $this->number( $s, 'disable_below', 0, 2560, '1', 'px' ); ?>
								</div>

								<div class="pcc-field">
									<label>Z-index</label>
									<?php $this->number( $s, 'z_index', 1, 2147483000, '1' ); ?>
								</div>

								<div class="pcc-field pcc-wide">
									<?php $this->checkbox( $s, 'hide_native', __( 'Hide browser cursor', 'premium-custom-cursor' ) ); ?>
								</div>
							</div>
						</section>

						<section class="pcc-card">
							<h2><?php esc_html_e( '3D, Bling & Motion FX', 'premium-custom-cursor' ); ?></h2>
							<div class="pcc-grid">
								<div class="pcc-field pcc-wide"><?php $this->checkbox( $s, 'tilt_enabled', __( '3D tilt following mouse direction', 'premium-custom-cursor' ) ); ?></div>
								<div class="pcc-field"><label>3D tilt strength</label><?php $this->number( $s, 'tilt_strength', 0, 28, '1', '°' ); ?></div>
								<div class="pcc-field"><label>Glow strength</label><?php $this->number( $s, 'glow_strength', 0, 3, '0.1', 'x' ); ?></div>
								<div class="pcc-field pcc-wide"><?php $this->checkbox( $s, 'ambient_fx', __( 'Enable ambient shimmer / orbit / bling FX', 'premium-custom-cursor' ) ); ?></div>
								<div class="pcc-field"><label>Primary FX color</label><?php $this->color( $s, 'fx_primary' ); ?></div>
								<div class="pcc-field"><label>Secondary FX color</label><?php $this->color( $s, 'fx_secondary' ); ?></div>

								<div class="pcc-field pcc-wide pcc-subline"><?php $this->checkbox( $s, 'trail_enabled', __( 'Force trail on any preset', 'premium-custom-cursor' ) ); ?></div>
								<div class="pcc-field"><label>Trail length</label><?php $this->number( $s, 'trail_length', 2, 20, '1', '' ); ?></div>
								<div class="pcc-field"><label>Trail opacity</label><?php $this->number( $s, 'trail_opacity', 0.02, 0.9, '0.02', '' ); ?></div>
							</div>
						</section>

						<section class="pcc-card">
							<h2><?php esc_html_e( 'Dot & Movement', 'premium-custom-cursor' ); ?></h2>
							<div class="pcc-grid">
								<div class="pcc-field pcc-wide"><?php $this->checkbox( $s, 'dot_enabled', __( 'Show center dot', 'premium-custom-cursor' ) ); ?></div>
								<div class="pcc-field"><label>Dot size</label><?php $this->number( $s, 'dot_size', 1, 40, '1', 'px' ); ?></div>
								<div class="pcc-field"><label>Dot color</label><?php $this->color( $s, 'dot_color' ); ?></div>
								<div class="pcc-field"><label>Dot opacity</label><?php $this->number( $s, 'dot_opacity', 0, 1, '0.05' ); ?></div>
								<div class="pcc-field"><label>Dot follow speed</label><?php $this->number( $s, 'dot_speed', 0.05, 1, '0.01' ); ?></div>
								<div class="pcc-field"><label>Ring follow speed</label><?php $this->number( $s, 'follow_speed', 0.02, 1, '0.01' ); ?></div>
							</div>
						</section>

						<section class="pcc-card">
							<h2><?php esc_html_e( 'Base Ring', 'premium-custom-cursor' ); ?></h2>
							<div class="pcc-grid">
								<div class="pcc-field"><label>Size</label><?php $this->number( $s, 'ring_size', 10, 240, '1', 'px' ); ?></div>
								<div class="pcc-field"><label>Border width</label><?php $this->number( $s, 'ring_border_width', 0, 12, '1', 'px' ); ?></div>
								<div class="pcc-field"><label>Border color</label><?php $this->color( $s, 'ring_border_color' ); ?></div>
								<div class="pcc-field"><label>Border opacity</label><?php $this->number( $s, 'ring_border_opacity', 0, 1, '0.05' ); ?></div>
								<div class="pcc-field"><label>Background</label><?php $this->color( $s, 'ring_bg_color' ); ?></div>
								<div class="pcc-field"><label>Background opacity</label><?php $this->number( $s, 'ring_bg_opacity', 0, 1, '0.05' ); ?></div>
								<div class="pcc-field"><label>Text color</label><?php $this->color( $s, 'ring_text_color' ); ?></div>
								<div class="pcc-field"><label>Corner radius</label><?php $this->number( $s, 'ring_radius', 0, 50, '1', '%' ); ?></div>
								<div class="pcc-field"><label>Backdrop blur</label><?php $this->number( $s, 'ring_blur', 0, 30, '1', 'px' ); ?></div>
								<div class="pcc-field">
									<label for="pcc_blend_mode">Blend mode</label>
									<select id="pcc_blend_mode" name="<?php echo esc_attr( $this->field_name( 'blend_mode' ) ); ?>">
										<?php foreach ( array( 'normal', 'difference', 'exclusion', 'screen', 'multiply' ) as $mode ) : ?>
											<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $s['blend_mode'], $mode ); ?>><?php echo esc_html( ucfirst( $mode ) ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</section>

						<section class="pcc-card">
							<h2><?php esc_html_e( 'Hover & Label', 'premium-custom-cursor' ); ?></h2>
							<div class="pcc-grid">
								<div class="pcc-field pcc-wide"><?php $this->checkbox( $s, 'hover_enabled', __( 'Enable hover expansion', 'premium-custom-cursor' ) ); ?></div>
								<div class="pcc-field"><label>Hover size</label><?php $this->number( $s, 'hover_size', 10, 300, '1', 'px' ); ?></div>
								<div class="pcc-field"><label>Hover background</label><?php $this->color( $s, 'hover_bg_color' ); ?></div>
								<div class="pcc-field"><label>Background opacity</label><?php $this->number( $s, 'hover_bg_opacity', 0, 1, '0.05' ); ?></div>
								<div class="pcc-field"><label>Hover border</label><?php $this->color( $s, 'hover_border_color' ); ?></div>
								<div class="pcc-field"><label>Border opacity</label><?php $this->number( $s, 'hover_border_opacity', 0, 1, '0.05' ); ?></div>

								<div class="pcc-field pcc-wide pcc-subline"><?php $this->checkbox( $s, 'label_enabled', __( 'Enable VIEW / MENU label cursor', 'premium-custom-cursor' ) ); ?></div>
								<div class="pcc-field"><label>Default text</label><input id="pcc_default_label" type="text" name="<?php echo esc_attr( $this->field_name( 'default_label' ) ); ?>" value="<?php echo esc_attr( $s['default_label'] ); ?>"></div>
								<div class="pcc-field"><label>Label size</label><?php $this->number( $s, 'label_size', 20, 320, '1', 'px' ); ?></div>
								<div class="pcc-field"><label>Font size</label><?php $this->number( $s, 'label_font_size', 6, 40, '1', 'px' ); ?></div>
								<div class="pcc-field"><label>Letter spacing</label><?php $this->number( $s, 'label_letter_spacing', 0, 1, '0.01', 'em' ); ?></div>
								<div class="pcc-field"><label>Label background</label><?php $this->color( $s, 'label_bg_color' ); ?></div>
								<div class="pcc-field"><label>Label text</label><?php $this->color( $s, 'label_text_color' ); ?></div>
								<div class="pcc-field"><label>Label border</label><?php $this->color( $s, 'label_border_color' ); ?></div>
								<div class="pcc-field"><label>Border opacity</label><?php $this->number( $s, 'label_border_opacity', 0, 1, '0.05' ); ?></div>
							</div>
						</section>

						<section class="pcc-card">
							<div class="pcc-section-title">
								<h2><?php esc_html_e( 'Click Effects', 'premium-custom-cursor' ); ?></h2>
								<span>9 effects</span>
							</div>
							<div class="pcc-grid">
								<div class="pcc-field pcc-wide">
									<label for="pcc_click_effect">Effect when mouse is pressed</label>
									<select id="pcc_click_effect" name="<?php echo esc_attr( $this->field_name( 'click_effect' ) ); ?>">
										<?php foreach ( $clicks as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['click_effect'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="pcc-field"><label>Particle count</label><?php $this->number( $s, 'click_particles', 4, 30, '1', '' ); ?></div>
								<div class="pcc-field"><label>Effect diameter</label><?php $this->number( $s, 'click_size', 50, 260, '1', 'px' ); ?></div>
								<div class="pcc-field pcc-wide"><small>Particle effects use the Primary + Secondary FX colors above.</small></div>
							</div>
						</section>

						<section class="pcc-card">
							<h2><?php esc_html_e( 'Targeting', 'premium-custom-cursor' ); ?></h2>
							<div class="pcc-grid">
								<div class="pcc-field pcc-wide">
									<label for="pcc_interactive_selector">Interactive selector</label>
									<textarea id="pcc_interactive_selector" name="<?php echo esc_attr( $this->field_name( 'interactive_selector' ) ); ?>" rows="3"><?php echo esc_textarea( $s['interactive_selector'] ); ?></textarea>
								</div>
								<div class="pcc-field pcc-wide">
									<label for="pcc_label_selector">Label selector</label>
									<textarea id="pcc_label_selector" name="<?php echo esc_attr( $this->field_name( 'label_selector' ) ); ?>" rows="3"><?php echo esc_textarea( $s['label_selector'] ); ?></textarea>
								</div>
							</div>
							<div class="pcc-code-tip">
								<strong>Custom text:</strong>
								<code>&lt;a data-pcc-label="MENU"&gt;Speisekarte&lt;/a&gt;</code>
							</div>
						</section>

						<?php submit_button( __( 'Save Cursor FX Settings', 'premium-custom-cursor' ), 'primary large' ); ?>
					</div>

					<aside class="pcc-preview-wrap">
						<div class="pcc-preview-card">
							<div class="pcc-preview-title">
								<span>Live Preview</span>
								<small id="pcc-preview-style-name"><?php echo esc_html( $s['preset'] ); ?></small>
							</div>
							<div class="pcc-preview" id="pcc-preview">
								<div class="pcc-preview-grid"></div>
								<div class="pcc-preview-content">
									<strong>MOVE CURSOR</strong>
									<span>3D / BLING / CLICK</span>
									<a href="#" data-pcc-demo-label="VIEW">Hover + click here</a>
								</div>
								<div class="pcc-preview-trails" id="pcc-preview-trails"></div>
								<div class="pcc-preview-dot" id="pcc-preview-dot"></div>
								<div class="pcc-preview-ring" id="pcc-preview-ring">
									<div class="pcc-preview-visual" id="pcc-preview-visual">
										<i class="pcc-demo-shine"></i>
										<i class="pcc-demo-orbit pcc-demo-orbit-a"></i>
										<i class="pcc-demo-orbit pcc-demo-orbit-b"></i>
										<i class="pcc-demo-spark s1"></i>
										<i class="pcc-demo-spark s2"></i>
										<i class="pcc-demo-spark s3"></i>
										<span class="pcc-demo-label"></span>
									</div>
								</div>
							</div>
							<p class="description">Click inside preview to test the selected click effect.</p>
						</div>
					</aside>
				</div>
			</form>
		</div>
		<?php
	}
}

new PCC_Premium_Custom_Cursor_FX_V120();

endif;
