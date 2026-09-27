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
					/* translators: %s: contributor role name. */
					sprintf( __( 'A new application to join as "%s" is waiting for your review.', 'contributors-desk' ), Contributor_Desk_Roles::instance()->display_label() ),
					'',
					/* translators: %s: applicant name. */
					sprintf( __( 'Name: %s', 'contributors-desk' ), $application['name'] ),
					/* translators: %s: applicant email address. */
					sprintf( __( 'Email: %s', 'contributors-desk' ), $application['email'] ),
				);

				if ( $application['website'] ) {
					/* translators: %s: applicant website URL. */
					$lines[] = sprintf( __( 'Website: %s', 'contributors-desk' ), $application['website'] );
				}

				$lines[] = '';
				$lines[] = wp_trim_words( $application['message'], 60 );
				$lines[] = '';
				/* translators: %s: URL of the application review screen. */
				$lines[] = sprintf( __( 'Review the application: %s', 'contributors-desk' ), Contributor_Desk_Applications::review_url( $application['id'] ) );

				return array(
					/* translators: %s: applicant name. */
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
					/* translators: %s: user display name. */
					sprintf( __( 'Hello %s,', 'contributors-desk' ), $user->display_name ),
					'',
					sprintf(
						/* translators: %1$s: site name, %2$s: contributor role name. */
						__( 'Your application to join %1$s as "%2$s" has been approved.', 'contributors-desk' ),
						wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
						Contributor_Desk_Roles::instance()->display_label()
					),
					'',
				);

				if ( $password_url ) {
					/* translators: %s: username. */
					$lines[] = sprintf( __( 'Your username: %s', 'contributors-desk' ), $user->user_login );
					$lines[] = __( 'Set your password using this link:', 'contributors-desk' );
					$lines[] = $password_url;
				} else {
					$lines[] = __( 'Log in with your existing account to start contributing:', 'contributors-desk' );
					$lines[] = wp_login_url();
				}

				$lines[] = '';
				/* translators: %s: support email address. */
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
					/* translators: %s: applicant name. */
					sprintf( __( 'Hello %s,', 'contributors-desk' ), $application['name'] ),
					'',
					sprintf(
						/* translators: %s: site name. */
						__( 'Thank you for applying to join %s. After reviewing your application, we are unable to approve it at this time.', 'contributors-desk' ),
						wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
					),
					'',
					__( 'Reason:', 'contributors-desk' ),
					$reason,
					'',
					/* translators: %s: support email address. */
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
