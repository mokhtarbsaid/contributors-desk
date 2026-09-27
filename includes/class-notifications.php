<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Notifications {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'cdesk_application_submitted', array( $this, 'application_submitted' ) );
		add_action( 'cdesk_application_approved', array( $this, 'application_approved' ), 10, 3 );
		add_action( 'cdesk_application_rejected', array( $this, 'application_rejected' ), 10, 2 );
	}

	public function application_submitted( $post_id ) {
		$application = Contributor_Desk_Applications::get( $post_id );

		if ( ! $application ) {
			return;
		}

		Contributor_Desk_Mailer::to_admin(
			static function () use ( $application ) {
				$lines = array(
					sprintf( __( 'A new application to join as "%s" is waiting for your review.', 'contributors-desk' ), Contributor_Desk_Roles::instance()->display_label() ),
					'',
					sprintf( __( 'Name: %s', 'contributors-desk' ), $application['name'] ),
					sprintf( __( 'Email: %s', 'contributors-desk' ), $application['email'] ),
				);

				if ( $application['website'] ) {
					$lines[] = sprintf( __( 'Website: %s', 'contributors-desk' ), $application['website'] );
				}

				$lines[] = '';
				$lines[] = wp_trim_words( $application['message'], 60 );
				$lines[] = '';
				$lines[] = sprintf( __( 'Review the application: %s', 'contributors-desk' ), Contributor_Desk_Applications::review_url( $application['id'] ) );

				return array(
					'subject' => sprintf( __( 'New application from %s', 'contributors-desk' ), $application['name'] ),
					'body'    => implode( "\n", $lines ),
				);
			}
		);
	}

	public function application_approved( $post_id, $user_id, $created ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$password_url = '';

		if ( $created ) {
			$key = get_password_reset_key( $user );

			if ( ! is_wp_error( $key ) ) {
				$password_url = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );
			}
		}

		Contributor_Desk_Mailer::to_user(
			$user,
			static function () use ( $user, $password_url ) {
				$lines = array(
					sprintf( __( 'Hello %s,', 'contributors-desk' ), $user->display_name ),
					'',
					sprintf(
						__( 'Your application to join %1$s as "%2$s" has been approved.', 'contributors-desk' ),
						wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
						Contributor_Desk_Roles::instance()->display_label()
					),
					'',
				);

				if ( $password_url ) {
					$lines[] = sprintf( __( 'Your username: %s', 'contributors-desk' ), $user->user_login );
					$lines[] = __( 'Set your password using this link:', 'contributors-desk' );
					$lines[] = $password_url;
				} else {
					$lines[] = __( 'Log in with your existing account to start contributing:', 'contributors-desk' );
					$lines[] = wp_login_url();
				}

				$lines[] = '';
				$lines[] = sprintf( __( 'If you have any questions, contact us at %s.', 'contributors-desk' ), Contributor_Desk_Settings::support_email() );

				return array(
					'subject' => __( 'Your application has been approved', 'contributors-desk' ),
					'body'    => implode( "\n", $lines ),
				);
			}
		);
	}

	public function application_rejected( $post_id, $reason ) {
		$application = Contributor_Desk_Applications::get( $post_id );

		if ( ! $application ) {
			return;
		}

		Contributor_Desk_Mailer::to_address(
			$application['email'],
			$application['locale'],
			static function () use ( $application, $reason ) {
				$lines = array(
					sprintf( __( 'Hello %s,', 'contributors-desk' ), $application['name'] ),
					'',
					sprintf(
						__( 'Thank you for applying to join %s. After reviewing your application, we are unable to approve it at this time.', 'contributors-desk' ),
						wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
					),
					'',
					__( 'Reason:', 'contributors-desk' ),
					$reason,
					'',
					sprintf( __( 'If you have any questions, contact us at %s.', 'contributors-desk' ), Contributor_Desk_Settings::support_email() ),
				);

				return array(
					'subject' => __( 'About your application', 'contributors-desk' ),
					'body'    => implode( "\n", $lines ),
				);
			}
		);
	}
}
