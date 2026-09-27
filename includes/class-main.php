<?php
// If accessed directly, deny access.
defined('ABSPATH') || exit;

class Starter_Plugin_Main {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // You can load the translated texts here.
        add_action('init', [$this, 'load_textdomain']);
        $this->run();
    }

    public function load_textdomain() {
        load_plugin_textdomain('starter-plugin', false, dirname(STARTER_PLUGIN_BASENAME) . '/languages');
    }

    public function run() {
        // Loads admin functions
        if (is_admin()) {
            require_once STARTER_PLUGIN_PATH . 'admin/class-admin.php';
            $admin = new Starter_Plugin_Admin();
            $admin->init();
        }

        // Loads frontend functions
        require_once STARTER_PLUGIN_PATH . 'public/class-public.php';
        $public = new Starter_Plugin_Public();
        $public->init();
    }
}