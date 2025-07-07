<?php
/**
 * Simple Signage - Single Slide Template
 *
 * This is a minimal, theme-independent template designed for kiosk displays.
 * It removes all theme interference and provides a clean slate for the slide content.
 *
 * @package SIMPLE_Signage
 * @version 1.6
 */

// Get slide-specific metadata
$post_id     = get_the_ID();
$orientation = get_post_meta($post_id, '_signage_orientation', true) ?: 'landscape';
$effect      = get_post_meta($post_id, '_signage_effect', true) ?: 'fade';

// Build our own classes array, ignoring the theme's.
$body_classes = ['signage-slide', 'orientation-' . esc_attr($orientation), 'effect-' . esc_attr($effect)];

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); // This will output our enqueued CSS and JS data ?>
</head>
<body class="<?php echo esc_attr(implode(' ', $body_classes)); ?>">

    <main id="slide-content" class="slide-content-wrapper">
        <?php
        // The WordPress loop to display the slide's content
        if (have_posts()) :
            while (have_posts()) : the_post();
                the_content();
            endwhile;
        endif;
        ?>
    </main>

    <?php wp_footer(); // This will output our enqueued JS file ?>
</body>
</html>

