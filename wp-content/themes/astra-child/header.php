<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ══════════════════════════════════
  DESKTOP HEADER
══════════════════════════════════ -->
<header class="lumy-hd" id="lumy-hd">

  <!-- Logo -->
  <a href="<?php echo esc_url( home_url('/') ); ?>" class="lumy-logo">
    <?php
      $logo_id = 5025;
      if ( $logo_id ) :
        echo wp_get_attachment_image( $logo_id, 'full', false, ['class' => 'lumy-logo-img', 'style' => 'height:50px;width:auto;'] );
      else :
    ?>
      <div class="lumy-logo-bowl">🍜</div>
      <div class="lumy-logo-name"><?php bloginfo('name'); ?></div>
      <div class="lumy-logo-sub">· RESTAURANT ·</div>
    <?php endif; ?>
  </a>

  <!-- Primary Nav -->
  <nav aria-label="Primary Navigation">
    <?php wp_nav_menu([
      'theme_location' => 'primary',
      'menu_class'     => 'lumy-nav-primary',
      'container'      => false,
      'depth'          => 1,
    ]); ?>
  </nav>

  <!-- Right: Lang + Order + Hamburger -->
  <div class="lumy-hd-right">
    <div class="lumy-lang">
        <?php echo do_shortcode('[language-switcher]'); ?>
    </div>
    <button class="lumy-hbg" id="lumy-hbg-desktop" aria-label="Open full menu" aria-expanded="false">
      <div class="lumy-hbg-lines">
        <span></span><span></span><span></span>
      </div>
      <span class="lumy-hbg-label">MENU</span>
    </button>

  </div>
</header>

<!-- ══════════════════════════════════
  DESKTOP FULLSCREEN OVERLAY
══════════════════════════════════ -->
<div class="lumy-overlay" id="lumy-overlay" aria-hidden="true" role="dialog" aria-modal="true">

  <!-- LEFT: Ảnh + Logo -->
  <div class="lumy-ov-left">
    <img src="https://hoaxuan.de/wp-content/uploads/2026/06/A1BA7ED2-6606-4F6B-8E7C-1FB4D781320A-1.jpg" alt="<?php bloginfo('name'); ?>">

    <div class="lumy-ov-logo">
      <?php if ($logo_id) : ?>
        <?php echo wp_get_attachment_image($logo_id, 'medium', false, ['style' => 'height:80px;width:auto;filter:brightness(0) invert(1);']); ?>
      <?php else : ?>
        <div class="lumy-ov-logo-bowl">🍜</div>
        <div class="lumy-ov-logo-name"><?php bloginfo('name'); ?></div>
        <div class="lumy-ov-logo-rule"></div>
        <div class="lumy-ov-logo-sub">· Restaurant ·</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RIGHT: Nav list -->
  <div class="lumy-ov-right">
    <button class="lumy-ov-close" id="lumy-ov-close" aria-label="Close menu">
      <div class="lumy-ov-close-x"></div>
      <span class="lumy-ov-close-lbl">MENU</span>
    </button>

    <?php wp_nav_menu([
      'theme_location' => 'primary',
      'menu_class'     => 'lumy-ov-nav',
      'container'      => 'nav',
      'container_attr' => ['aria-label' => 'Overlay Navigation'],
      'depth'          => 1,
      'link_before'    => '',
      'link_after'     => '',
    ]); ?>

    <div class="lumy-lang">
        <?php echo do_shortcode('[language-switcher]'); ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
  MOBILE HEADER (≤1024px)
══════════════════════════════════ -->
<header class="lumy-hm" id="lumy-hm">
  <div class="lumy-hm-inner">

    <div class="lumy-hm-left">
      <img src="https://hoaxuan.de/wp-content/uploads/2026/03/duong-ngan-siLfDZAgHsk-unsplash-landscape.jpg" alt="">
    </div>

    <div class="lumy-hm-center">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="lumy-hm-logo">
        <?php if ($logo_id) : ?>
          <?php echo wp_get_attachment_image($logo_id, 'thumbnail', false, ['style' => 'height:38px;width:auto;']); ?>
        <?php else : ?>
          <div class="lumy-hm-bowl">🍜</div>
          <span class="lumy-hm-name"><?php bloginfo('name'); ?></span>
          <span class="lumy-hm-sub">· RESTAURANT ·</span>
        <?php endif; ?>
      </a>
    </div>

    <div class="lumy-hm-right">
        <div class="lumy-lang">
           <?php echo do_shortcode('[language-switcher]'); ?>
        </div>
      <button class="lumy-hbg" id="lumy-hbg-mobile" aria-label="Open menu" aria-expanded="false">
        <div class="lumy-hbg-lines">
          <span></span><span></span><span></span>
        </div>
        <span class="lumy-hbg-label">MENU</span>
      </button>
    </div>

  </div>
</header>

<!-- ══════════════════════════════════
  MOBILE FULLSCREEN OVERLAY
══════════════════════════════════ -->
<div class="lumy-mob-overlay" id="lumy-mob-overlay" aria-hidden="true" role="dialog">

  <button class="lumy-mob-close" id="lumy-mob-close" aria-label="Close menu">
    <div class="lumy-mob-close-x"></div>
    <span class="lumy-mob-close-lbl">MENU</span>
  </button>

  <?php wp_nav_menu([
    'theme_location' => 'primary',
    'menu_class'     => 'lumy-mob-nav',
    'container'      => 'nav',
    'depth'          => 1,
  ]); ?>

  <div class="lumy-mob-actions">
    <a href="tel:+4917621927505" class="lumy-mob-action-btn">
      <span class="icon">📞</span>
      <span class="lbl">HOTLINE</span>
    </a>
    <a href="https://www.google.com/maps?cid=11026296762189377500" target="_blank" class="lumy-mob-action-btn">
      <span class="icon">🗺️</span>
      <span class="lbl">LOCATION</span>
    </a>
    <a href="https://hoaxuan.de/our-menu/"
       class="lumy-mob-action-btn">
      <span class="icon">📋</span>
      <span class="lbl">MENU</span>
    </a>
    <a href="https://hoaxuan.de/contact/"
       class="lumy-mob-action-btn gold">
      <span class="icon">🍽️</span>
      <span class="lbl">ORDER NOW</span>
    </a>
  </div>

</div>

<div id="page" class="site">
