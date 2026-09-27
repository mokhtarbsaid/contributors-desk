<?php
// If accessed directly, deny access.
defined('ABSPATH') || exit;
class Starter_Plugin_Admin {
    public function init() {
        // Register menu for the panel.
        add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Load admin CSS and JS files.
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Add some plugin settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_admin_menu() {
        add_menu_page(
            __('Starter Plugin', 'starter-plugin'),
            __('Starter Plugin', 'starter-plugin'),
            'manage_options',
            'starter-plugin',
            [$this, 'display_admin_page'],
            'dashicons-admin-generic',
            25
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        // Verify that files are only loaded on the plugin page.
        if ($hook_suffix === 'toplevel_page_starter-plugin') {
            // Load admin CSS files.
            wp_enqueue_style(
                'starter-plugin-admin-style', // ID
                STARTER_PLUGIN_URL . 'admin/assets/css/admin-style.css', // file path
                [],
                STARTER_PLUGIN_VERSION // plugin version
            );

            // load JS files.
            wp_enqueue_script(
                'starter-plugin-admin-script', // ID
                STARTER_PLUGIN_URL . 'admin/assets/js/admin-script.js', // file path
                ['jquery'], // dependencies
                STARTER_PLUGIN_VERSION, // plugin version
                true // load in the footer?
            );

            // Pass PHP data to JS file (optional).
            wp_localize_script('starter-plugin-admin-script', 'starterPluginData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('starter_plugin_nonce'),
            ]);
        }
    }    

    public function display_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Starter Plugin Settings', 'starter-plugin') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('starter_plugin_options_group');
        do_settings_sections('starter-plugin');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function register_settings() {
        register_setting('starter_plugin_options_group', 'starter_plugin_options', [
            'sanitize_callback' => [$this, 'sanitize_options'],
        ]);

        add_settings_section(
            'starter_plugin_main_section',
            __('Main Settings', 'starter-plugin'),
            [$this, 'main_section_callback'],
            'starter-plugin'
        );

        add_settings_field(
            'setting_1',
            __('Setting 1', 'starter-plugin'),
            [$this, 'setting_1_callback'],
            'starter-plugin',
            'starter_plugin_main_section'
        );
    }

    public function main_section_callback() {
        echo '<p>' . esc_html__('Main settings section for Starter Plugin.', 'starter-plugin') . '</p>';
    }

    public function setting_1_callback() {
        $options = get_option('starter_plugin_options');
        ?>
        <input type="text" name="starter_plugin_options[setting_1]" value="<?php echo esc_attr($options['setting_1'] ?? ''); ?>" />
        <?php
    }

    public function sanitize_options($input) {
        $output = [];
        $output['setting_1'] = isset($input['setting_1']) ? sanitize_text_field($input['setting_1']) : '';
        return $output;
    }


}