<?php

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Contributor_Desk_';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$slug = strtolower( str_replace( '_', '-', substr( $class_name, strlen( $prefix ) ) ) );
		$file = CDESK_PATH . 'includes/class-' . $slug . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
