<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Roles {

	const ROLE           = 'cdesk_contributor';
	const DEFAULT_LABEL  = 'External Contributor';
	const VERSION_OPTION = '_cdesk_roles_version';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_sync' ) );
		add_filter( 'gettext_with_context', array( $this, 'translate_role_label' ), 10, 4 );
	}

	public function maybe_sync() {
		if ( get_option( self::VERSION_OPTION ) !== CDESK_VERSION ) {
			$this->sync();
		}
	}

	public function sync() {
		$label = $this->label();
		$caps  = $this->capabilities();
		$role  = get_role( self::ROLE );
		$roles = wp_roles();

		if ( $role && ( $roles->roles[ self::ROLE ]['name'] ?? '' ) !== $label ) {
			remove_role( self::ROLE );
			$role = null;
		}

		if ( ! $role ) {
			add_role( self::ROLE, $label, $caps );
		} else {
			foreach ( $caps as $cap => $grant ) {
				if ( empty( $role->capabilities[ $cap ] ) ) {
					$role->add_cap( $cap, $grant );
				}
			}
		}

		update_option( self::VERSION_OPTION, CDESK_VERSION, false );
	}

	public function reset() {
		delete_option( self::VERSION_OPTION );
	}

	public function label() {
		$label = trim( (string) Contributor_Desk_Settings::get( 'role_label' ) );

		return '' === $label ? self::DEFAULT_LABEL : $label;
	}

	public function display_label() {
		return translate_user_role( $this->label() );
	}

	public function capabilities() {
		return (array) apply_filters( 'cdesk_contributor_capabilities', array( 'read' => true ) );
	}

	public function translate_role_label( $translation, $text, $context, $domain ) {
		if ( 'User role' === $context && 'default' === $domain && self::DEFAULT_LABEL === $text ) {
			return _x( 'External Contributor', 'User role', 'contributors-desk' );
		}

		return $translation;
	}

	public static function user_is_contributor( $user = null ) {
		$user = $user ? $user : wp_get_current_user();

		return $user instanceof WP_User && in_array( self::ROLE, (array) $user->roles, true );
	}
}
