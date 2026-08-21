<?php
if ( ! function_exists( 'emvh_get_lang' ) ) {
    function emvh_get_lang() {
        global $TRP_LANGUAGE;
        if ( ! empty( $TRP_LANGUAGE ) ) {
            return strtolower( substr( $TRP_LANGUAGE, 0, 2 ) );
        }
        $url_parts = explode( '/', trim( $_SERVER['REQUEST_URI'], '/' ) );
        if ( ! empty( $url_parts[0] ) && strlen( $url_parts[0] ) === 2 ) {
            return strtolower( $url_parts[0] );
        }
        return 'de';
    }
}

if ( ! function_exists( 'emvh_get_title' ) ) {
    function emvh_get_title( $post_id, $lang ) {
        if ( $lang === 'en' ) {
            $en = get_field( 'title-en', $post_id );
            if ( ! empty( $en ) ) return $en;
        }
        return get_the_title( $post_id );
    }
}

if ( ! function_exists( 'emvh_get_desc' ) ) {
    function emvh_get_desc( $post_id, $lang ) {
        if ( $lang === 'en' ) {
            $en = get_field( 'describe-en', $post_id );
            if ( ! empty( $en ) ) return $en;
        }
        return get_field( 'describe-de', $post_id ) ?: '';
    }
}

if ( ! function_exists( 'emvh_get_price' ) ) {
    function emvh_get_price( $post_id ) {
        $price = get_field( 'price', $post_id );
        if ( $price === '' || $price === null || $price === false ) return '';
        return number_format( (float) $price, 2, ',', '.' ) . '€';
    }
}

if ( ! function_exists( 'emvh_sort_by_code' ) ) {
    function emvh_sort_by_code( $posts ) {
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
}

if ( ! function_exists( 'emvh_sort_food_types' ) ) {
    function emvh_sort_food_types( $rows ) {
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
}

if ( ! function_exists( 'emvh_render_food_types' ) ) {
    function emvh_render_food_types( $rows, $lang ) {
        if ( empty( $rows ) ) return '';

        $sorted = emvh_sort_food_types( $rows );
        $html   = '<ul class="emvh-food-types">';

        foreach ( $sorted as $row ) {
            $code  = esc_html( trim( (string) ( $row['code'] ?? '' ) ) );
            $title = '';

            if ( $lang === 'en' ) {
                $en    = trim( (string) ( $row['title-en'] ?? '' ) );
                $title = $en !== '' ? $en : trim( (string) ( $row['title-de'] ?? '' ) );
            } else {
                $title = trim( (string) ( $row['title-de'] ?? '' ) );
            }

            $allergens = esc_html( trim( (string) ( $row['allergens'] ?? '' ) ) );
            $price     = trim( (string) ( $row['price'] ?? '' ) );
            $price_fmt = $price !== '' ? number_format( (float) $price, 2, ',', '.' ) . '€' : '';

            $allergens_html = $allergens ? '<sup class="emvh-sup">' . $allergens . '</sup>' : '';
            $price_html     = $price_fmt ? '<span class="emvh-ft-price">' . esc_html( $price_fmt ) . '</span>' : '';

            $html .= sprintf(
                '<li class="emvh-ft-row">
                    <span class="emvh-ft-left">%s%s%s</span>
                    %s
                </li>',
                $code ? '<span class="emvh-ft-code">' . $code . '.</span>' : '',
                esc_html( $title ),
                $allergens_html,
                $price_html
            );
        }

        $html .= '</ul>';
        return $html;
    }
}

if ( ! function_exists( 'empfehlung_menu_shortcode' ) ) {
    function empfehlung_menu_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'post_type' => 'menu',
            'taxonomy'  => 'empfehlung_vom_haus',
            'limit'     => -1,
        ], $atts, 'empfehlung_menu' );

        $lang = emvh_get_lang();

        $terms = get_terms( [
            'taxonomy'   => sanitize_text_field( $atts['taxonomy'] ),
            'hide_empty' => true,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p style="color:red;">[empfehlung_menu] No dishes found in="EMPFEHLUNG VOM HAUS'
                . esc_html( $atts['taxonomy'] ) . '"</p>';
        }

        $term_slugs = wp_list_pluck( $terms, 'slug' );

        $query_args = [
            'post_type'      => sanitize_text_field( $atts['post_type'] ),
            'posts_per_page' => (int) $atts['limit'],
            'post_status'    => 'publish',
            'tax_query'      => [ [
                'taxonomy' => sanitize_text_field( $atts['taxonomy'] ),
                'field'    => 'slug',
                'terms'    => $term_slugs,
                'operator' => 'IN',
            ] ],
        ];

        $posts = get_posts( $query_args );

        if ( empty( $posts ) ) {
            return '<p style="color:red;">[empfehlung_menu] No dishes found in="EMPFEHLUNG VOM HAUS'
                . esc_html( $atts['taxonomy'] ) . '"</p>';
        }

        $sorted_entries = emvh_sort_by_code( $posts );

        ob_start(); ?>
        <div class="emvh-list">
            <?php foreach ( $sorted_entries as $entry ) :
                $post  = $entry['post'];
                $id    = $post->ID;
                $code  = esc_html( $entry['code'] );
                $title = esc_html( emvh_get_title( $id, $lang ) );
                $desc  = emvh_get_desc( $id, $lang );
                $price = emvh_get_price( $id );

                $additives = trim( (string) get_field( 'additives', $id ) );
                $quantity  = trim( (string) get_field( 'quantity',  $id ) );
                $food_rows = get_field( 'list_of_food_types', $id );
                if ( ! is_array( $food_rows ) ) $food_rows = [];

                $badge_new = get_field('badge_new', $id);
                $badge_new_type = get_field('badge_new_type', $id) ?: 'text';
                $badge_fav = get_field('badge_favorite', $id);
                $badge_fav_type = get_field('badge_favorite_type', $id) ?: 'icon';
                
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
            ?>
            <div class="emvh-item">

                <div class="emvh-title-row">
                    <span class="emvh-title-left">
                        <?php if ( $code ) : ?>
                            <span class="emvh-code"><?php echo $code; ?>.</span>
                        <?php endif; ?>
                        <span class="emvh-title"><?php echo $title; ?></span>
                        <?php if ( $additives ) : ?>
                            <sup class="emvh-sup"><?php echo esc_html( $additives ); ?></sup>
                        <?php endif; ?>
                        <?php echo $badges_html; ?>
                        <?php if ( $quantity ) : ?>
                            <span class="emvh-quantity">(<?php echo esc_html( $quantity ); ?>)</span>
                        <?php endif; ?>
                    </span>
                    <?php if ( $price ) : ?>
                        <span class="emvh-dots" aria-hidden="true"></span>
                        <span class="emvh-price"><?php echo esc_html( $price ); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ( $desc ) : ?>
                    <p class="emvh-desc"><?php echo esc_html( $desc ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $food_rows ) ) :
                    echo emvh_render_food_types( $food_rows, $lang );
                endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode( 'empfehlung_menu', 'empfehlung_menu_shortcode' );