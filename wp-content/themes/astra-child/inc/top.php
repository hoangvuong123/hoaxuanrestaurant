<?php

function menu_taxonomy_gallery_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'post_type'    => 'menu',
        'images'       => '',
        'hover_images' => '',
    ], $atts, 'menu_taxonomy_gallery' );

    $taxonomies = get_object_taxonomies(
        sanitize_text_field( $atts['post_type'] ),
        'objects'
    );

    $image_ids = array_map(
        'intval',
        explode( ',', $atts['images'] )
    );

    $hover_image_ids = array_map(
        'intval',
        explode( ',', $atts['hover_images'] )
    );

    $allowed = [
        'Getränke',
        'Sushi',
        'Dessert',
        'Andere'
    ];

    $filtered = [];

    foreach ( $allowed as $label ) {
        foreach ( $taxonomies as $taxonomy ) {
            if ( $taxonomy->label === $label ) {
                $filtered[] = $taxonomy;
                break;
            }
        }
    }

    ob_start();
    ?>

    <div class="mtg-wrapper">

        <div class="mtg-grid">

            <?php foreach ( $filtered as $index => $taxonomy ) :

                $img_id = $image_ids[ $index ] ?? 0;

                $thumbnail_url = $img_id
                    ? wp_get_attachment_image_url( $img_id, 'large' )
                    : '';

                $hover_img_id = $hover_image_ids[ $index ] ?? 0;

                $hover_image_url = $hover_img_id
                    ? wp_get_attachment_image_url( $hover_img_id, 'full' )
                    : '';

                $tax_url = home_url(
                    '/our-menu/#' . $taxonomy->name
                );

            ?>

                <a
                    href="<?php echo esc_url( $tax_url ); ?>"
                    class="mtg-card"
                >

                    <div
                        class="mtg-card-image"
                        style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>');"
                    ></div>

                    <?php if ( $hover_image_url ) : ?>

                        <img
                            src="<?php echo esc_url( $hover_image_url ); ?>"
                            class="mtg-card-hover-image"
                            alt="<?php echo esc_attr( $taxonomy->label ); ?>"
                        >

                    <?php endif; ?>

                    <span class="mtg-card-title">
                        <?php echo esc_html( $taxonomy->label ); ?>
                    </span>

                </a>

            <?php endforeach; ?>

        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'menu_taxonomy_gallery',
    'menu_taxonomy_gallery_shortcode'
);