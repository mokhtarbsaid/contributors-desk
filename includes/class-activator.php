<?php

class Starter_Plugin_Activator {
    public static function activate() {
        // Set default tables or options.
        if (!get_option('starter_plugin_options')) {
            update_option('starter_plugin_options', [
                'setting_1' => 'default_value',
            ]);
        }
    }
}
