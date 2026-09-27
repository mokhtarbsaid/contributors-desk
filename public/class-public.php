<?php

class Starter_Plugin_Public {
    public function init() {
        // Enqueue the frontend CSS and JS files.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'starter-plugin-style',
            STARTER_PLUGIN_URL . 'public/assets/css/style.css',
            [],
            STARTER_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'starter-plugin-script',
            STARTER_PLUGIN_URL . 'public/assets/js/script.js',
            ['jquery'],
            STARTER_PLUGIN_VERSION,
            true
        );
    }
}
