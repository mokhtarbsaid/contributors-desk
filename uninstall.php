<?php
// If accessed directly, deny access.
defined('ABSPATH') || exit;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete stored options.
delete_option('starter_plugin_options');