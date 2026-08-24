<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Restaurant_Menu_Shortcode {

    private static $instance = null;

    private $taxonomies = [
        [ 'slug' => 'empfehlung_vom_haus',   'label_de' => 'Empfehlung Vom Haus',   'label_en' => 'House Special'  ],
        [ 'slug' => 'vorspeisen',   'label_de' => 'Vorspeisen',   'label_en' => 'Appetizers'  ],
        [ 'slug' => 'hauptspeisen', 'label_de' => 'Hauptspeisen', 'label_en' => 'Main Courses' ],
        [ 'slug' => 'sushi',        'label_de' => 'Sushi',        'label_en' => 'Sushi'        ],
        [ 'slug' => 'dessert',      'label_de' => 'Dessert',      'label_en' => 'Desserts'     ],
        [ 'slug' => 'getranke',     'label_de' => 'Getränke',     'label_en' => 'Beverages'    ],
        [ 'slug' => 'andere',       'label_de' => 'Andere',       'label_en' => 'Others'       ],
    ];

    private $post_type      = 'menu';
    private $posts_per_page = 8;

    private $allowed_langs = [ 'de', 'en' ];

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_shortcode( 'restaurant_menu', [ $this, 'render_shortcode' ] );
        add_action( 'wp_ajax_restaurant_menu_filter',        [ $this, 'ajax_handler' ] );
        add_action( 'wp_ajax_nopriv_restaurant_menu_filter', [ $this, 'ajax_handler' ] );
    }

    // =========================================================
    // HELPER: Phát hiện ngôn ngữ (TranslatePress)
    // =========================================================
    private function get_current_lang() {
        global $TRP_LANGUAGE;
        if ( ! empty( $TRP_LANGUAGE ) ) {
            return strtolower( substr( $TRP_LANGUAGE, 0, 2 ) );
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ( ! empty( $referer ) ) {
            $path  = parse_url( $referer, PHP_URL_PATH );
            $parts = explode( '/', trim( $path, '/' ) );
            if ( ! empty( $parts[0] ) && in_array( strtolower( $parts[0] ), $this->allowed_langs, true ) ) {
                return strtolower( $parts[0] );
            }
        }

        // Fallback: REQUEST_URI (non-AJAX)
        $url_parts = explode( '/', trim( $_SERVER['REQUEST_URI'], '/' ) );
        if ( ! empty( $url_parts[0] ) && in_array( strtolower( $url_parts[0] ), $this->allowed_langs, true ) ) {
            return strtolower( $url_parts[0] );
        }

        return 'de';
    }

    // =========================================================
    // HELPER: Lấy label taxonomy theo ngôn ngữ
    // =========================================================
    private function get_taxonomy_label( $tax, $lang ) {
        return $lang === 'en' ? $tax['label_en'] : $tax['label_de'];
    }

    // =========================================================
    // HELPER: Lấy tên term theo ngôn ngữ
    // ACF fields trên term: name-de, name-en
    // =========================================================
    private function get_term_name( $term, $lang ) {
        $name_en = get_field( 'name-en', $term );
        $name_de = get_field( 'name-de', $term );

        if ( $lang === 'en' && ! empty( $name_en ) ) return $name_en;
        if ( ! empty( $name_de ) ) return $name_de;
        return $term->name;
    }

    // =========================================================
    // HELPER: Lấy description của term theo ngôn ngữ
    // ACF fields trên term: note-de, note-en
    // =========================================================
    private function get_term_desc( $term, $lang ) {
        if ( $lang === 'en' ) {
            $desc_en = get_field( 'description-en', $term );
            if ( ! empty( $desc_en ) ) return $desc_en;
    
            $note_en = get_field( 'note-en', $term );
            if ( ! empty( $note_en ) ) return $note_en;
    
            $note_de = get_field( 'note-de', $term );
            if ( ! empty( $note_de ) ) return $note_de;
    
            return ! empty( $term->description ) ? $term->description : '';
        }
    
        // DE
        $note_de = get_field( 'note-de', $term );
        if ( ! empty( $note_de ) ) return $note_de;
        return ! empty( $term->description ) ? $term->description : '';
    }
    // =========================================================
    // HELPER: Lấy title post theo ngôn ngữ
    // =========================================================
    private function get_post_title( $post_id, $lang ) {
        if ( $lang === 'en' ) {
            $en = get_field( 'title-en', $post_id );
            if ( ! empty( $en ) ) return $en;
        }
        return get_the_title( $post_id );
    }

    // =========================================================
    // HELPER: Lấy description post theo ngôn ngữ
    // =========================================================
    private function get_post_desc( $post_id, $lang ) {
        if ( $lang === 'en' ) {
            $en = get_field( 'describe-en', $post_id );
            if ( ! empty( $en ) ) return $en;
        }
        return get_field( 'describe-de', $post_id ) ?: '';
    }
    // =========================================================
    // HELPER: Lấy quantity theo ngôn ngữ
    // =========================================================
    private function get_post_quantity( $post_id, $lang ) {
        if ( $lang === 'en' ) {
            $en = get_field( 'quantity-en', $post_id );
            if ( ! empty( trim( (string) $en ) ) ) return trim( (string) $en );
        }
        return trim( (string) ( get_field( 'quantity', $post_id ) ?: '' ) );
    }

    // =========================================================
    // HELPER: Lấy dữ liệu giá
    // =========================================================
    private function get_price_data( $post_id ) {
        $variants = get_field( 'variants', $post_id );
        if ( ! empty( $variants ) && is_array( $variants ) ) {
            $result = [];
            foreach ( $variants as $v ) {
                $size  = trim( (string) ( $v['size']  ?? '' ) );
                $price = trim( (string) ( $v['price'] ?? '' ) );
                if ( $price !== '' ) {
                    $price = str_replace( ',', '.', $price );
                    $result[] = [
                        'size'  => $size,
                        'price' => number_format( (float) $price, 2, ',', '.' ) . '€',
                    ];
                }
            }
            if ( ! empty( $result ) ) return $result;
        }
    
        $price = get_field( 'price', $post_id );
        if ( $price !== '' && $price !== null && $price !== false ) {
            $price = str_replace( ',', '.', (string) $price ); 
            $size  = trim( (string) ( get_field( 'size', $post_id ) ?? '' ) );
            return [ [
                'size'  => $size,
                'price' => number_format( (float) $price, 2, ',', '.' ) . '€',
            ] ];
        }
    
        return [];
    }

    // =========================================================
    // HELPER: Render HTML badges giá
    // =========================================================
    private function render_price_html( $price_data ) {
        if ( empty( $price_data ) ) return '';

        $html = '<span class="menu-item-prices">';
        foreach ( $price_data as $p ) {
            $label = ! empty( $p['size'] )
                ? esc_html( $p['size'] ) . ': ' . esc_html( $p['price'] )
                : esc_html( $p['price'] );
            $html .= '<span class="menu-item-price-badge">' . $label . '</span>';
        }
        $html .= '</span>';
        return $html;
    }

    // =========================================================
    // HELPER: Sắp xếp posts theo ACF 'code' (số học)
    // =========================================================
    private function sort_posts_by_code( $posts ) {
        $with_code    = [];
        $without_code = [];

        foreach ( $posts as $post ) {
            $code = trim( (string) get_field( 'code', $post->ID ) );
            if ( $code !== '' ) {
                preg_match( '/(\d+)/', $code, $m );
                $num         = isset( $m[1] ) ? (int) $m[1] : PHP_INT_MAX;
                $with_code[] = [ 'post' => $post, 'num' => $num, 'code' => $code ];
            } else {
                $without_code[] = [ 'post' => $post, 'num' => PHP_INT_MAX, 'code' => '' ];
            }
        }

        usort( $with_code, fn( $a, $b ) => $a['num'] <=> $b['num'] );
        return array_merge( $with_code, $without_code );
    }

    // =========================================================
    // HELPER: Sắp xếp food types theo code số học
    // =========================================================
    private function sort_food_types( $rows ) {
        if ( empty( $rows ) ) return [];
        usort( $rows, function ( $a, $b ) {
            $ca = trim( (string) ( $a['code'] ?? '' ) );
            $cb = trim( (string) ( $b['code'] ?? '' ) );
            preg_match( '/(\d+)/', $ca, $ma );
            preg_match( '/(\d+)/', $cb, $mb );
            $na = isset( $ma[1] ) ? (int) $ma[1] : PHP_INT_MAX;
            $nb = isset( $mb[1] ) ? (int) $mb[1] : PHP_INT_MAX;
            if ( $na !== $nb ) return $na <=> $nb;
            return strcmp( $ca, $cb );
        } );
        return $rows;
    }

    // =========================================================
    // RENDER: Food types bên trong món ăn
    // =========================================================
    private function render_food_types( $rows, $lang ) {
        if ( empty( $rows ) ) return '';

        $sorted = $this->sort_food_types( $rows );
        $html   = '<ul class="menu-item__food-types">';

        foreach ( $sorted as $row ) {
            $code  = esc_html( trim( (string) ( $row['code'] ?? '' ) ) );
            $title = '';
            $translation = '';

            if ( $lang === 'en' ) {
                $en          = trim( (string) ( $row['title-en'] ?? '' ) );
                $de          = trim( (string) ( $row['title-de'] ?? '' ) );
                $title       = $en !== '' ? $en : $de;
                $translation = $en !== '' ? $de : '';
            } else {
                $title       = trim( (string) ( $row['title-de'] ?? '' ) );
                $translation = trim( (string) ( $row['title-en'] ?? '' ) );
            }
            if ( $translation === $title ) $translation = '';

            $allergens = esc_html( trim( (string) ( $row['allergens'] ?? '' ) ) );
            $price     = trim( (string) ( $row['price'] ?? '' ) );
            $price_norm = str_replace( ',', '.', $price );
            $price_fmt  = $price !== '' ? number_format( (float) $price_norm, 2, ',', '.' ) . '€' : '';

            $sub_img_url = '';
            foreach (['image', 'img', 'photo', 'picture', 'hinh_anh'] as $img_key) {
                if (!empty($row[$img_key])) {
                    $img_val = $row[$img_key];
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
            $img_attr  = $sub_img_url ? ' data-image="' . esc_url( $sub_img_url ) . '"' : '';
            $img_class = $sub_img_url ? ' menu-item__ft-row--has-image' : '';

            $sup_html   = $allergens ? '<sup class="menu-item__sup">' . $allergens . '</sup>' : '';
            $translation_html = $translation
                ? '<span class="menu-item__ft-translation">/ ' . esc_html( $translation ) . '</span>'
                : '';
            $price_html = $price_fmt ? '<span class="menu-item__ft-price">' . esc_html( $price_fmt ) . '</span>' : '';

            $html .= sprintf(
                '<li class="menu-item__ft-row%s"%s>
                    <span class="menu-item__ft-left">%s<span class="menu-item__ft-text"><span class="menu-item__ft-title">%s</span>%s%s</span></span>
                    %s
                </li>',
                $img_class,
                $img_attr,
                $code ? '<span class="menu-item__ft-code">' . $code . '.</span>' : '',
                esc_html( $title ),
                $sup_html,
                $translation_html,
                $price_html
            );
        }

        $html .= '</ul>';
        return $html;
    }

    // =========================================================
    // RENDER: Menu Category block (Extra Beilage v.v.)
    // ACF fields trên term: title-main, list
    // =========================================================
    private function render_menu_category( $term, $lang ) {

        $title_main = trim( (string) get_field( 'title-main', $term ) );
        $list       = get_field( 'list', $term );
        if ( ! is_array( $list ) ) $list = [];

        if ( empty( $list ) && empty( $title_main ) ) return '';

        usort( $list, function( $a, $b ) {
            $ca = trim( (string) ( $a['code'] ?? '' ) );
            $cb = trim( (string) ( $b['code'] ?? '' ) );
            preg_match( '/(\d+)/', $ca, $ma );
            preg_match( '/(\d+)/', $cb, $mb );
            $na = isset( $ma[1] ) ? (int) $ma[1] : PHP_INT_MAX;
            $nb = isset( $mb[1] ) ? (int) $mb[1] : PHP_INT_MAX;
            if ( $na !== $nb ) return $na <=> $nb;
            return strcmp( $ca, $cb );
        } );

        ob_start(); ?>
        <div class="menu-category-block">

            <?php if ( $title_main ) : ?>
                <p class="menu-category-block__title"><?php echo esc_html( $title_main ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $list ) ) : ?>
                <ul class="menu-category-block__list">
                    <?php foreach ( $list as $item ) :

                        if ( $lang === 'en' ) {
                            $en_name   = trim( (string) ( $item['name_en'] ?? '' ) );
                            $item_name = $en_name !== '' ? $en_name : trim( (string) ( $item['name_de'] ?? '' ) );
                        } else {
                            $item_name = trim( (string) ( $item['name_de'] ?? '' ) );
                        }

                        $item_code      = trim( (string) ( $item['code']      ?? '' ) );
                        $item_add       = trim( (string) ( $item['additives'] ?? '' ) );
                        $item_price     = trim( (string) ( $item['price']     ?? '' ) );
                        $item_price_norm = str_replace( ',', '.', $item_price );
                        $item_price_fmt  = $item_price !== ''
                            ? number_format( (float) $item_price_norm, 2, ',', '.' ) . '€'
                            : '';
                    ?>
                    <li class="menu-category-block__item">
                        <span class="menu-category-block__item-left">
                            <?php if ( $item_code ) : ?>
                                <span class="menu-category-block__item-code">
                                    <?php echo esc_html( $item_code ); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo esc_html( $item_name ); ?>
                            <?php if ( $item_add ) : ?>
                                <sup class="menu-item__sup"><?php echo esc_html( $item_add ); ?></sup>
                            <?php endif; ?>
                        </span>
                        <?php if ( $item_price_fmt ) : ?>
                            <span class="menu-category-block__item-price">
                                <?php echo esc_html( $item_price_fmt ); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // RENDER: Một dòng món ăn
    // =========================================================
    public function render_menu_item( $post_id, $lang ) {
        $code       = trim( (string) get_field( 'code', $post_id ) );
        $title_de   = trim( (string) get_the_title( $post_id ) );
        $title_en   = trim( (string) get_field( 'title-en', $post_id ) );
        $title      = $lang === 'en' ? ( $title_en ?: $title_de ) : $title_de;
        $title_trans = $lang === 'en' ? ( $title_en ? $title_de : '' ) : $title_en;
        if ( $title_trans === $title ) $title_trans = '';
        $desc_de    = trim( (string) get_field( 'describe-de', $post_id ) );
        $desc_en    = trim( (string) get_field( 'describe-en', $post_id ) );
        $desc       = $lang === 'en' ? ( $desc_en ?: $desc_de ) : $desc_de;
        $desc_trans = $lang === 'en' ? ( $desc_en ? $desc_de : '' ) : $desc_en;
        if ( $desc_trans === $desc ) $desc_trans = '';
        $desc_layout = get_post_meta( $post_id, '_hoa_xuan_description_layout', true );
        $details     = trim( (string) get_post_meta( $post_id, '_hoa_xuan_menu_details', true ) );
        if ( '' === $details ) {
            $details = trim( (string) get_post_meta( $post_id, 'hoa_xuan_menu_details', true ) );
        }
        if ( '' === $details ) {
            $details = trim( (string) get_post_field( 'post_content', $post_id ) );
        }
        if ( '' === $details ) {
            $details = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
        }
        $additives  = trim( (string) get_field( 'additives', $post_id ) );
        $title_parts = json_decode( (string) get_post_meta( $post_id, '_hoa_xuan_title_parts', true ), true );
        if ( ! is_array( $title_parts ) ) $title_parts = [];
        $quantity   = $this->get_post_quantity( $post_id, $lang );
        $price_data = $this->get_price_data( $post_id );
        $price_html = $this->render_price_html( $price_data );

        $badge_new = get_field('badge_new', $post_id);
        $badge_new_type = get_field('badge_new_type', $post_id) ?: 'text';
        $badge_fav = get_field('badge_favorite', $post_id);
        $badge_fav_type = get_field('badge_favorite_type', $post_id) ?: 'icon';
        
        $badges_html = '';
        if ($badge_new || $badge_fav) {
            $badges_html .= '<span class="menu-item-badges">';
            if ($badge_new) {
                if ($badge_new_type === 'icon') {
                    $badges_html .= '<span class="menu-badge badge-new badge-icon" title="New">🔥</span>';
                } else {
                    $badges_html .= '<span class="menu-badge badge-new">' . ($lang === 'de' ? 'Neu' : 'New') . '</span>';
                }
            }
            if ($badge_fav) {
                if ($badge_fav_type === 'icon') {
                    $badges_html .= '<span class="menu-badge badge-fav badge-icon" title="Favorite">❤️</span>';
                } else {
                    $badges_html .= '<span class="menu-badge badge-fav">' . ($lang === 'de' ? 'Tipp' : 'Popular') . '</span>';
                }
            }
            $badges_html .= '</span>';
        }

        $food_rows = get_field( 'list_of_food_types', $post_id );
        if ( ! is_array( $food_rows ) ) $food_rows = [];
        $food_html = $this->render_food_types( $food_rows, $lang );

        $img_url   = get_the_post_thumbnail_url( $post_id, 'large' );

        ob_start(); ?>
        <div class="menu-item" data-post-id="<?php echo esc_attr( $post_id ); ?>">
            <?php if ( $img_url ) : ?>
                <div class="menu-item__thumb">
                    <img src="<?php echo esc_url($img_url); ?>" loading="lazy" alt="<?php echo esc_attr($title); ?>">
                </div>
            <?php endif; ?>
            <div class="menu-item__info">
            <div class="menu-item__title-row">
                <span class="menu-item__title-left">
                    <?php if ( $code ) : ?>
                        <span class="menu-item__code"><?php echo esc_html( $code ); ?>.</span>
                    <?php endif; ?>
                    <?php if ( $title_parts ) : ?>
                        <?php foreach ( $title_parts as $part_index => $part ) : ?>
                            <?php if ( $part_index > 0 ) : ?><span class="menu-item__title-separator">/</span><?php endif; ?>
                            <span class="menu-item__name"><?php echo esc_html( trim( (string) ( $part['title'] ?? '' ) ) ); ?></span>
                            <?php if ( ! empty( $part['additives'] ) ) : ?>
                                <sup class="menu-item__sup"><?php echo esc_html( trim( (string) $part['additives'] ) ); ?></sup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php echo $badges_html; ?>
                    <?php else : ?>
                        <span class="menu-item__name"><?php echo esc_html( $title ); ?></span>
                        <?php if ( $additives ) : ?>
                            <sup class="menu-item__sup"><?php echo esc_html( $additives ); ?></sup>
                        <?php endif; ?>
                        <?php echo $badges_html; ?>
                        <?php if ( $title_trans ) : ?>
                            <span class="menu-item__name-translation">/ <?php echo esc_html( $title_trans ); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ( $quantity ) : ?>
                        <span class="menu-item__quantity">(<?php echo esc_html( $quantity ); ?>)</span>
                    <?php endif; ?>
                </span>
                <?php if ( $price_html ) : ?>
                    <span class="menu-item__dots" aria-hidden="true"></span>
                    <?php echo $price_html; ?>
                <?php endif; ?>
            </div>
            <?php if ( $desc || $desc_trans ) : ?>
                <div class="menu-item__descriptions">
                    <?php if ( 'inline' === $desc_layout && $desc ) : ?>
                        <p class="menu-item__desc menu-item__desc--inline">
                            <span><?php echo esc_html( $desc ); ?></span>
                            <?php if ( $desc_trans ) : ?>
                                <span class="menu-item__desc-translation-inline">/ <?php echo esc_html( $desc_trans ); ?></span>
                            <?php endif; ?>
                        </p>
                    <?php else : ?>
                        <?php if ( $desc ) : ?>
                            <p class="menu-item__desc"><?php echo esc_html( $desc ); ?></p>
                        <?php endif; ?>
                        <?php if ( $desc_trans ) : ?>
                            <p class="menu-item__desc menu-item__desc--translation"><?php echo esc_html( $desc_trans ); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ( $details ) : ?>
                <p class="menu-item__details"><?php echo esc_html( $details ); ?></p>
            <?php endif; ?>
            <?php if ( $food_html ) : ?>
                <?php echo $food_html; ?>
            <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // RENDER: Danh sách món ăn cho 1 term
    // =========================================================
    private function render_items_for_term( $taxonomy_slug, $term_id, $page, $lang ) {
        $query = new WP_Query( [
            'post_type'      => $this->post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'tax_query'      => [ [
                'taxonomy' => $taxonomy_slug,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ],
        ] );

        $total_items = count( $query->posts );
        $total_pages = $total_items > 0
            ? ceil( $total_items / $this->posts_per_page )
            : 1;
        $items_html  = '';

        if ( $query->have_posts() ) {
            $sorted = $this->sort_posts_by_code( $query->posts );
            $offset = max( 0, ( (int) $page - 1 ) * $this->posts_per_page );
            $sorted = array_slice( $sorted, $offset, $this->posts_per_page );
            wp_reset_postdata();

            foreach ( $sorted as $entry ) {
                $items_html .= $this->render_menu_item( $entry['post']->ID, $lang );
            }
        }

        $term = get_term( $term_id, $taxonomy_slug );
        $categories_html = ( $term && ! is_wp_error( $term ) )
            ? $this->render_menu_category( $term, $lang )
            : '';

        return [
            'items_html'      => $items_html,
            'categories_html' => $categories_html,
            'total_pages'     => $total_pages,
            'found_posts'     => $query->found_posts,
        ];
    }

    // =========================================================
    // RENDER SHORTCODE chính
    // =========================================================
    public function render_shortcode( $atts ) {
        $lang = $this->get_current_lang();

        wp_localize_script( 'our-menu-js', 'menuAjax', [
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'menu_ajax_nonce' ),
            'postsPerPage' => $this->posts_per_page,
            'postType'     => $this->post_type,
            'lang'         => $lang,
            'i18n'         => [
                'noPost' => $lang === 'de'
                    ? 'Keine Gerichte in dieser Kategorie.'
                    : 'No dishes in this category.',
                'error'  => $lang === 'de'
                    ? 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.'
                    : 'An error occurred. Please try again.',
            ],
        ] );

        ob_start();
        ?>
        <div class="our-menu-page" data-lang="<?php echo esc_attr( $lang ); ?>">
            <div class="menu-wrapper">
                
                <?php foreach ( $this->taxonomies as $tax ) :
                    $taxonomy_slug  = $tax['slug'];
                    $taxonomy_label = $this->get_taxonomy_label( $tax, $lang );

                    $terms = get_terms( [
                        'taxonomy'   => $taxonomy_slug,
                        'hide_empty' => true,
                    ] );

                    if ( empty( $terms ) || is_wp_error( $terms ) ) continue;

                    $single_term    = count( $terms ) === 1;
                    $active_term    = $terms[0];
                    $active_term_id = $active_term->term_id;

                    $render      = $this->render_items_for_term( $taxonomy_slug, $active_term_id, 1, $lang );
                    $total_pages = $render['total_pages'];
                    $term_desc   = $this->get_term_desc( $active_term, $lang );
                ?>

                <section
                    class="menu-section"
                    id="<?php echo esc_attr( $taxonomy_slug ); ?>"
                    data-taxonomy="<?php echo esc_attr( $taxonomy_slug ); ?>"
                    data-post-type="<?php echo esc_attr( $this->post_type ); ?>"
                >
                    <!-- Header taxonomy -->
                    <div class="menu-section__header">
                        <div class="menu-section__header-inner">
                            <div class="menu-section__title-wrap">
                                <img
                                    class="menu-section__icon"
                                    src="https://chihouse.de/wp-content/uploads/2026/06/logo-menu.png"
                                    alt=""
                                >
                                <h2 class="menu-section__title">
                                    <?php echo esc_html( $taxonomy_label ); ?>
                                </h2>
                            </div>
                        </div>
                        <div class="menu-section__line"></div>
                    </div>

                    <?php if ( ! $single_term ) : ?>
                    <div class="menu-tabs" role="tablist" aria-label="<?php echo esc_attr( $taxonomy_label ); ?>">
                        <?php foreach ( $terms as $ti => $term ) :
                            $term_name = $this->get_term_name( $term, $lang );
                        ?>
                            <button
                                class="menu-tab <?php echo $ti === 0 ? 'menu-tab--active' : ''; ?>"
                                role="tab"
                                aria-selected="<?php echo $ti === 0 ? 'true' : 'false'; ?>"
                                data-term-id="<?php echo esc_attr( $term->term_id ); ?>"
                                data-term-slug="<?php echo esc_attr( $term->slug ); ?>"
                            >
                                <?php echo esc_html( $term_name ); ?>
                                <span class="menu-tab__count"><?php echo intval( $term->count ); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="menu-grid-wrap">
                        <div class="menu-term-desc" data-term-id="<?php echo esc_attr( $active_term_id ); ?>">
                            <?php if ( $term_desc ) : ?>
                                <p class="menu-term-desc__text"><?php echo esc_html( $term_desc ); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="menu-items-list"
                            data-current-term="<?php echo esc_attr( $active_term_id ); ?>"
                            data-page="1"
                        >
                            <?php if ( $render['items_html'] ) : ?>
                                <?php echo $render['items_html']; ?>
                            <?php else : ?>
                                <p class="menu-empty">
                                    <?php echo $lang === 'de'
                                        ? 'Keine Gerichte in dieser Kategorie.'
                                        : 'No dishes in this category.'; ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ( $render['categories_html'] ) : ?>
                            <div class="menu-categories-wrap">
                                <?php echo $render['categories_html']; ?>
                            </div>
                        <?php endif; ?>

                        <div class="menu-loading" aria-hidden="true">
                            <div class="menu-loading__spinner"></div>
                        </div>

                    </div>

                    <!-- Phân trang -->
                    <?php if ( $total_pages > 1 ) : ?>
                        <nav class="menu-pagination"
                            data-total-pages="<?php echo $total_pages; ?>"
                            data-current-page="1"
                        >
                            <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                                <button
                                    class="menu-page-btn <?php echo $p === 1 ? 'menu-page-btn--active' : ''; ?>"
                                    data-page="<?php echo $p; ?>"
                                ><?php echo $p; ?></button>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>

                </section>

                <?php endforeach; ?>

                <aside class="menu-allergen-note" aria-labelledby="menu-allergen-note-title">
                    <h3 class="menu-allergen-note__title" id="menu-allergen-note-title">
                        Allergene &amp; Zusatzstoffe:
                    </h3>

                    <div class="menu-allergen-note__codes">
                        <p>
                            A - Hühnereier<br>
                            B1 - Erdnussbutter, B2 - Erdnuss<br>
                            C - Fisch, C1 - Fischsauce, C2 - Kaviar<br>
                            D1 - Weizen, D2 - Kartoffeln, D3 - Paniermehl<br>
                            E - Garnelen, E2 - Krebspulver<br>
                            G1 - Frischkäse, G2 - Butter, G3 - Sahne, G4 - Vollmilch, G5 - Kokosnussmilch<br>
                            H - Nudeln<br>
                            N1 - Ketchup, N2 - Mayo, N3 - Feinkostsalate, N4 - Senf<br>
                            K1 - Sesam (schwarzer und weißer Sesam), K2 - Sesamöl<br>
                            M1 - Soja, M2 - Sojasauce
                        </p>

                        <p>
                            1 - koffeinhaltig; 2 - mit Antioxidationsmitteln;<br>
                            3 - mit Farbstoffen; 4 - Säurungsmittel; 5 - Konservierungsstoffe;<br>
                            6 - mit Süßstoffen; 7 - chininhaltig; 8 - mit Phosphat; 9 - Stabilisatoren<br>
                            10 - Milch
                        </p>
                    </div>

                    <p>Gerichte können Glutamat enthalten, auf Wunsch kann jedes Gericht auch glutamatfrei zubereitet werden.</p>
                    <p>Ausführliche Zusatzstoffe &amp; Allergene fragen Sie bitte das Ladenpersonal.</p>
                </aside>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // AJAX HANDLER
    // =========================================================
    public function ajax_handler() {
        $term_id  = intval( $_POST['term_id']        ?? 0 );
        $page     = intval( $_POST['page']           ?? 1 );
        $taxonomy = sanitize_key( $_POST['taxonomy'] ?? '' );

        // Luôn detect ngôn ngữ từ server — không dùng $_POST['lang']
        $lang = $this->get_current_lang();

        $allowed = array_column( $this->taxonomies, 'slug' );
        if ( ! $term_id || ! in_array( $taxonomy, $allowed, true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid parameters.' ], 400 );
        }

        $render = $this->render_items_for_term( $taxonomy, $term_id, $page, $lang );

        $term           = get_term( $term_id, $taxonomy );
        $term_desc_html = '';
        if ( $term && ! is_wp_error( $term ) ) {
            $desc = $this->get_term_desc( $term, $lang );
            if ( $desc ) {
                $term_desc_html = '<p class="menu-term-desc__text">' . esc_html( $desc ) . '</p>';
            }
        }

        wp_send_json_success( [
            'items_html'      => $render['items_html'],
            'categories_html' => $render['categories_html'],
            'term_desc_html'  => $term_desc_html,
            'total_pages'     => $render['total_pages'],
            'found_posts'     => $render['found_posts'],
            'current_page'    => $page,
            'term_id'         => $term_id,
        ] );
    }
}

Restaurant_Menu_Shortcode::get_instance();
