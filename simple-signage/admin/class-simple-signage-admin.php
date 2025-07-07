<?php
/**
 * Simple Signage Admin Class.
 *
 * @package SIMPLE_Signage
 * @version 1.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIMPLE_Signage_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes_signage', [$this, 'add_meta_box']);
        add_action('save_post_signage', [$this, 'save_meta_data'], 10, 2);
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=signage',
            __('Signage Settings', 'simple-signage'),
            __('Settings', 'simple-signage'),
            'manage_options',
            'signage-settings',
            [$this, 'render_settings_page_html']
        );
    }

    public function render_settings_page_html() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Simple Signage Settings', 'simple-signage'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('simple_signage_settings_group');
                do_settings_sections('signage-settings');
                submit_button(__('Save Settings', 'simple-signage'));
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        //register_setting('simple_signage_settings_group', 'simple_signage_defaults');
	register_setting(
		'simple_signage_settings_group',
		'simple_signage_defaults',
    		[
		        'sanitize_callback' => [$this, 'sanitize_settings'],
    		]
	);
        add_settings_section(
            'simple_signage_defaults_section',
            __('Default Slide Values', 'simple-signage'),
            '__return_false',
            'signage-settings'
        );
        add_settings_field('default_duration', __('Default Duration (Seconds)', 'simple-signage'), [$this, 'render_duration_field'], 'signage-settings', 'simple_signage_defaults_section');
        add_settings_field('default_effect', __('Default Transition Effect', 'simple-signage'), [$this, 'render_effect_field'], 'signage-settings', 'simple_signage_defaults_section');
        add_settings_field('default_orientation', __('Default Orientation', 'simple-signage'), [$this, 'render_orientation_field'], 'signage-settings', 'simple_signage_defaults_section');
    }

	public function sanitize_settings($options) {
	    $clean = [];
	    $clean['duration'] = isset($options['duration']) ? absint($options['duration']) : 10;
	    $clean['effect'] = isset($options['effect']) ? sanitize_key($options['effect']) : 'fade';
	    $clean['orientation'] = isset($options['orientation']) ? sanitize_key($options['orientation']) : 'landscape';
	    return $clean;
	}

    public function render_duration_field() {
        $options = get_option('simple_signage_defaults');
        $duration = isset($options['duration']) ? absint($options['duration']) : 10;
        echo '<input type="number" name="simple_signage_defaults[duration]" value="' . esc_attr($duration) . '" min="0" step="1">';
        echo '<p class="description">' . __('Default time in seconds for new slides.', 'simple-signage') . '</p>';
    }

    public function render_effect_field() {
        $options = get_option('simple_signage_defaults');
        $effect = isset($options['effect']) ? sanitize_key($options['effect']) : 'fade';
        $effects = $this->get_transition_effects();
        echo '<select name="simple_signage_defaults[effect]">';
        foreach ($effects as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($effect, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function render_orientation_field() {
        $options = get_option('simple_signage_defaults');
        $orientation = isset($options['orientation']) ? sanitize_key($options['orientation']) : 'landscape';
        $orientations = [
            'landscape' => __('Landscape (1960x1080)', 'simple-signage'),
            'portrait'  => __('Portrait (1080x1960)', 'simple-signage'),
            'custom'    => __('Custom', 'simple-signage'),
        ];
        foreach ($orientations as $value => $label) {
            echo '<label><input type="radio" name="simple_signage_defaults[orientation]" value="' . esc_attr($value) . '" ' . checked($orientation, $value, false) . '> ' . esc_html($label) . '</label><br>';
        }
        echo '<p class="description">' . __('"Custom" uses the content\'s natural aspect ratio.', 'simple-signage') . '</p>';
    }

    public function add_meta_box($post) {
        add_meta_box('simple_signage_settings_meta_box', __('Slide Settings', 'simple-signage'), [$this, 'render_meta_box_content'], 'signage', 'side', 'high');
    }

    public function render_meta_box_content($post) {
        wp_nonce_field('save_signage_slide_settings', 'signage_meta_nonce');
        $defaults = get_option('simple_signage_defaults', []);
        $default_duration    = isset($defaults['duration']) ? absint($defaults['duration']) : 10;
        $default_effect      = isset($defaults['effect']) ? sanitize_key($defaults['effect']) : 'fade';
        $default_orientation = isset($defaults['orientation']) ? sanitize_key($defaults['orientation']) : 'landscape';
        $order       = get_post_meta($post->ID, '_signage_order', true);
        $duration    = get_post_meta($post->ID, '_signage_duration', true);
        $effect      = get_post_meta($post->ID, '_signage_effect', true);
        $orientation = get_post_meta($post->ID, '_signage_orientation', true);
        $order       = $order !== '' ? absint($order) : 0;
        $duration    = $duration !== '' ? absint($duration) : $default_duration;
        $effect      = !empty($effect) ? $effect : $default_effect;
        $orientation = !empty($orientation) ? $orientation : $default_orientation;
        ?>
        <p>
            <label><strong><?php esc_html_e('Order', 'simple-signage'); ?></strong></label><br>
            <input type="number" name="signage_order" value="<?php echo esc_attr($order); ?>" min="0" step="1" style="width:100%;">
        </p>
        <p>
            <label><strong><?php esc_html_e('Duration (Seconds)', 'simple-signage'); ?></strong></label><br>
            <input type="number" name="signage_duration" value="<?php echo esc_attr($duration); ?>" min="0" step="1" style="width:100%;">
        </p>
        <p>
            <label><strong><?php esc_html_e('Transition Effect', 'simple-signage'); ?></strong></label><br>
            <select name="signage_effect" style="width:100%;">
                <?php foreach ($this->get_transition_effects() as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($effect, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong><?php esc_html_e('Orientation', 'simple-signage'); ?></strong></label><br>
            <?php
            $orientations = ['landscape' => 'Landscape', 'portrait' => 'Portrait', 'custom' => 'Custom'];
            foreach ($orientations as $value => $label) {
                echo '<label><input type="radio" name="signage_orientation" value="' . esc_attr($value) . '" ' . checked($orientation, $value, false) . '> ' . esc_html($label) . '</label><br>';
            }
            ?>
        </p>
        <?php
    }

/*
    public function save_meta_data($post_id, $post) {
        if (!isset($_POST['signage_meta_nonce']) || !wp_verify_nonce($_POST['signage_meta_nonce'], 'save_signage_slide_settings') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
            return;
        }
        $fields_to_save = [
            '_signage_order'        => isset($_POST['signage_order']) ? absint($_POST['signage_order']) : 0,
            '_signage_duration'     => isset($_POST['signage_duration']) ? absint($_POST['signage_duration']) : 10,
            '_signage_effect'       => isset($_POST['signage_effect']) ? sanitize_key($_POST['signage_effect']) : 'fade',
            '_signage_orientation'  => isset($_POST['signage_orientation']) ? sanitize_key($_POST['signage_orientation']) : 'landscape',
        ];
        foreach ($fields_to_save as $meta_key => $value) {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
*/

//fixed save_meta_date() function 
public function save_meta_data($post_id, $post) {
    // Security checks
    if (!isset($_POST['signage_meta_nonce']) || !wp_verify_nonce($_POST['signage_meta_nonce'], 'save_signage_slide_settings') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    // Get plugin-wide defaults to use as a fallback
    $defaults = get_option('simple_signage_defaults', []);
    $default_duration    = isset($defaults['duration']) ? absint($defaults['duration']) : 10;
    $default_effect      = isset($defaults['effect']) ? sanitize_key($defaults['effect']) : 'fade';
    $default_orientation = isset($defaults['orientation']) ? sanitize_key($defaults['orientation']) : 'landscape';

    // Sanitize and prepare data for saving
    $fields_to_save = [
        '_signage_order'        => isset($_POST['signage_order']) ? absint($_POST['signage_order']) : 0,
        '_signage_duration'     => !empty($_POST['signage_duration']) ? absint($_POST['signage_duration']) : $default_duration,
        '_signage_effect'       => !empty($_POST['signage_effect']) ? sanitize_key($_POST['signage_effect']) : $default_effect,
        '_signage_orientation'  => !empty($_POST['signage_orientation']) ? sanitize_key($_POST['signage_orientation']) : $default_orientation,
    ];

    // Save each meta field
    foreach ($fields_to_save as $meta_key => $value) {
        update_post_meta($post_id, $meta_key, $value);
    }
}


    private function get_transition_effects() {
        return [
            'fade'         => __('Fade', 'simple-signage'),
            'slide-up'     => __('Slide Up', 'simple-signage'),
            'slide-down'   => __('Slide Down', 'simple-signage'),
            'slide-left'   => __('Slide Left', 'simple-signage'),
            'slide-right'  => __('Slide Right', 'simple-signage'),
            'none'         => __('None', 'simple-signage'),
        ];
    }
}

