<?php
/**
 * Plugin Name: SIMPLE Signage
 * Plugin URI:  https://yourwebsite.com/
 * Description: Registers a custom post type for digital signage slides.
 * Version:     1.6
 * Author:      kbarends with the help of google gemini
 * Author URI:  https://yourwebsite.com/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-signage
 */

// Prevent direct access to the file for security reasons.
if (!defined('ABSPATH')) {
    exit;
}

// Ensure the class hasn't been declared elsewhere.
if (!class_exists('SIMPLE_Signage')) {

    final class SIMPLE_Signage {

        private static $instance = null;

        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
        }

        private function define_constants() {
            define('SIMPLE_SIGNAGE_PATH', plugin_dir_path(__FILE__));
            define('SIMPLE_SIGNAGE_URL', plugin_dir_url(__FILE__));
            define('SIMPLE_SIGNAGE_VERSION', '1.5');
        }

        private function includes() {
            if (is_admin()) {
                require_once SIMPLE_SIGNAGE_PATH . 'admin/class-simple-signage-admin.php';
            }
        }

        private function init_hooks() {
            add_action('init', [$this, 'register_signage_post_type']);

            if (is_admin()) {
                new SIMPLE_Signage_Admin();
            }

            // --- Frontend Hooks ---
            add_action('template_redirect', [$this, 'hijack_page_for_signage']);
            add_action('wp_enqueue_scripts', [$this, 'manage_frontend_assets'], 999);
            add_action('wp_head', [$this, 'prefetch_next_slide']);
        }

        /**
         * Hijacks the page rendering for signage CPT to show a minimal template.
         */
        public function hijack_page_for_signage() {
            // First, handle the redirect for the main /signage/ archive URL.
            if (is_post_type_archive('signage')) {
                $first_slide_url = $this->get_next_slide_url(0);
                if ($first_slide_url) {
                    wp_safe_redirect($first_slide_url);
                    exit;
                }
            }

            // If it's a single slide, include our own template and stop WordPress from doing anything else.
            if (is_singular('signage')) {
                $template = SIMPLE_SIGNAGE_PATH . 'templates/single-signage.php';
                if (file_exists($template)) {
                    include $template;
                    exit; // This is the crucial part that bypasses the theme.
                }
            }
        }

        public function manage_frontend_assets() {
            if (is_singular('signage')) {
                global $wp_styles, $wp_scripts;

                $allowed_styles = ['wp-block-library', 'simple-signage-style'];
                foreach ($wp_styles->queue as $handle) {
                    if (!in_array($handle, $allowed_styles)) {
                        wp_dequeue_style($handle);
                    }
                }

                $allowed_scripts = ['simple-signage-script'];
                foreach ($wp_scripts->queue as $handle) {
                    if (!in_array($handle, $allowed_scripts)) {
                        wp_dequeue_script($handle);
                    }
                }

                wp_enqueue_style('simple-signage-style', SIMPLE_SIGNAGE_URL . 'assets/signage.css', [], SIMPLE_SIGNAGE_VERSION);
                wp_enqueue_script('simple-signage-script', SIMPLE_SIGNAGE_URL . 'assets/signage.js', [], SIMPLE_SIGNAGE_VERSION, true);

                $slide_data = $this->get_current_slide_data();
                wp_localize_script('simple-signage-script', 'simpleSignage', $slide_data);
            }
        }
        
        public function prefetch_next_slide() {
            if (is_singular('signage')) {
                $next_url = $this->get_current_slide_data()['next_url'];
                if ($next_url) {
                    echo '<link rel="prefetch" href="' . esc_url($next_url) . '">';
                }
            }
        }

        private function get_current_slide_data() {
            $post_id = get_the_ID();
            $defaults = get_option('simple_signage_defaults', []);
            $default_duration = isset($defaults['duration']) ? absint($defaults['duration']) : 10;
            $duration = get_post_meta($post_id, '_signage_duration', true);
            
            return [
                'duration' => ($duration !== '') ? absint($duration) : $default_duration,
                'next_url' => $this->get_next_slide_url($post_id),
            ];
        }

        private function get_next_slide_url($current_post_id) {
            $slides_query = new WP_Query([
                'post_type'      => 'signage',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => '_signage_order',
                'orderby'        => 'meta_value_num title',
                'order'          => 'ASC',
            ]);

            $slides = $slides_query->posts;
            if (empty($slides)) {
                return '';
            }

            $current_index = array_search($current_post_id, $slides);
            $next_index = ($current_index !== false && $current_index < count($slides) - 1) ? $current_index + 1 : 0;
            
            return get_permalink($slides[$next_index]);
        }

        public function register_signage_post_type() {
            $labels = [
                'name'                  => _x('SIMPLE Signage', 'Post Type General Name', 'simple-signage'),
                'singular_name'         => _x('Slide', 'Post Type Singular Name', 'simple-signage'),
                'menu_name'             => __('SIMPLE Signage', 'simple-signage'),
                'all_items'             => __('All Slides', 'simple-signage'),
                'add_new_item'          => __('Add New Slide', 'simple-signage'),
                'add_new'               => __('Add New', 'simple-signage'),
                'edit_item'             => __('Edit Slide', 'simple-signage'),
                'new_item'              => __('New Slide', 'simple-signage'),
                'view_item'             => __('View Slide', 'simple-signage'),
            ];
            $args = [
                'labels'                => $labels,
                'description'           => __('Post Type for Digital Signage Slides', 'simple-signage'),
                'supports'              => ['title', 'editor', 'revisions'],
                'public'                => true,
                'show_in_menu'          => true,
                'menu_position'         => 5,
                'menu_icon'             => 'dashicons-slides',
                'has_archive'           => 'signage',
                'publicly_queryable'    => true,
                'show_in_rest'          => true,
                'rewrite'               => ['slug' => 'signage'],
            ];
            register_post_type('signage', $args);
        }
    }

    function simple_signage_run() {
        return SIMPLE_Signage::instance();
    }
    simple_signage_run();

    function simple_signage_activate() {
        SIMPLE_Signage::instance()->register_signage_post_type();
        flush_rewrite_rules();
    }
    register_activation_hook(__FILE__, 'simple_signage_activate');

    function simple_signage_deactivate() {
        flush_rewrite_rules();
    }
    register_deactivation_hook(__FILE__, 'simple_signage_deactivate');

}

