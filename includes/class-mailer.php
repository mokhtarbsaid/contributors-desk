<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Mailer {

	public static function to_user( $user, callable $compose ) {
		$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );

		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		$switched = switch_to_user_locale( $user->ID );
		$sent     = self::send( $user->user_email, $compose );

		if ( $switched ) {
			restore_previous_locale();
		}

		return $sent;
	}

	public static function to_address( $email, $locale, callable $compose ) {
		if ( ! is_email( $email ) ) {
			return false;
		}

		$user = get_user_by( 'email', $email );

		if ( $user ) {
			return self::to_user( $user, $compose );
		}

		$switched = switch_to_locale( $locale ? $locale : get_locale() );
		$sent     = self::send( $email, $compose );

		if ( $switched ) {
			restore_previous_locale();
		}

		return $sent;
	}

	public static function to_admin( callable $compose ) {
		return self::to_address( (string) get_option( 'admin_email' ), get_locale(), $compose );
	}

	private static function send( $email, callable $compose ) {
		$message = call_user_func( $compose );

		if ( empty( $message['subject'] ) || empty( $message['body'] ) ) {
			return false;
		}

		$subject = sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $message['subject'] );

		return wp_mail( $email, $subject, $message['body'] );
	}
}
