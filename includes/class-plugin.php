<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		Contributor_Desk_Settings::instance();
		Contributor_Desk_Roles::instance();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'contributors-desk', false, dirname( CDESK_BASENAME ) . '/languages' );
	}

	public static function activate() {
		delete_option( Contributor_Desk_Roles::VERSION_OPTION );
		Contributor_Desk_Roles::instance()->sync();
		update_option( '_cdesk_version', CDESK_VERSION, false );
	}

	public static function deactivate() {
		delete_option( Contributor_Desk_Roles::VERSION_OPTION );
	}
}
