<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Application_Form {

	const SHORTCODE   = 'cdesk_application_form';
	const NONCE       = 'cdesk_apply';
	const MIN_SECONDS = 3;

	private static $instance = null;

	private $errors = array();

	private $values = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_submission' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		wp_register_style( 'cdesk-application-form', CDESK_URL . 'assets/css/application-form.css', array(), CDESK_VERSION );
	}

	public function handle_submission() {
		if ( ! isset( $_POST['cdesk_action'] ) || 'apply' !== sanitize_key( wp_unslash( $_POST['cdesk_action'] ) ) ) {
			return;
		}

		if ( ! isset( $_POST['_cdesk_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_cdesk_nonce'] ) ), self::NONCE ) ) {
			$this->errors['form'] = __( 'Your session has expired. Please reload the page and submit the form again.', 'contributors-desk' );
			return;
		}

		if ( ! $this->accepting() || $this->unavailable_reason() ) {
			return;
		}

		$honeypot = sanitize_text_field( wp_unslash( $_POST['cdesk_company'] ?? '' ) );
		$token    = sanitize_text_field( wp_unslash( $_POST['cdesk_ts'] ?? '' ) );

		if ( $this->looks_like_spam( $honeypot, $token ) ) {
			$this->redirect_success();
		}

		$user = wp_get_current_user();

		$this->values = array(
			'name'    => $user->exists() ? $user->display_name : sanitize_text_field( wp_unslash( $_POST['cdesk_name'] ?? '' ) ),
			'email'   => $user->exists() ? $user->user_email : sanitize_email( wp_unslash( $_POST['cdesk_email'] ?? '' ) ),
			'website' => esc_url_raw( wp_unslash( $_POST['cdesk_website'] ?? '' ) ),
			'message' => sanitize_textarea_field( wp_unslash( $_POST['cdesk_message'] ?? '' ) ),
			'consent' => ! empty( $_POST['cdesk_consent'] ),
		);

		$this->validate( $user );

		if ( $this->errors ) {
			return;
		}

		$result = Contributor_Desk_Applications::create(
			array(
				'name'    => $this->values['name'],
				'email'   => $this->values['email'],
				'website' => $this->values['website'],
				'message' => $this->values['message'],
				'user_id' => $user->ID,
				'locale'  => $user->exists() ? get_user_locale( $user ) : determine_locale(),
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->errors['form'] = __( 'Your application could not be saved. Please try again later.', 'contributors-desk' );
			return;
		}

		$this->redirect_success();
	}

	private function validate( WP_User $user ) {
		$values = $this->values;

		if ( '' === $values['name'] ) {
			$this->errors['name'] = __( 'Please enter your name.', 'contributors-desk' );
		} elseif ( mb_strlen( $values['name'] ) > 100 ) {
			$this->errors['name'] = __( 'Your name is too long.', 'contributors-desk' );
		}

		if ( ! is_email( $values['email'] ) ) {
			$this->errors['email'] = __( 'Please enter a valid email address.', 'contributors-desk' );
		} elseif ( ! $user->exists() && email_exists( $values['email'] ) ) {
			$this->errors['email'] = sprintf(
				__( 'An account with this email already exists. Please <a href="%s">log in</a> first, then apply.', 'contributors-desk' ),
				esc_url( wp_login_url( $this->current_url() ) )
			);
		} elseif ( Contributor_Desk_Applications::find_pending( $values['email'], $user->ID ) ) {
			$this->errors['email'] = __( 'An application with this email is already waiting for review.', 'contributors-desk' );
		}

		if ( '' === $values['message'] ) {
			$this->errors['message'] = __( 'Please tell us about yourself.', 'contributors-desk' );
		} elseif ( mb_strlen( $values['message'] ) > 5000 ) {
			$this->errors['message'] = __( 'Your message is too long. Please keep it under 5000 characters.', 'contributors-desk' );
		}

		if ( ! $values['consent'] ) {
			$this->errors['consent'] = __( 'Please agree to the storage of your data so we can review your application.', 'contributors-desk' );
		}
	}

	private function looks_like_spam( $honeypot, $token ) {
		if ( '' !== $honeypot ) {
			return true;
		}

		$parts = explode( '|', $token );

		if ( 2 !== count( $parts ) || ! hash_equals( wp_hash( 'cdesk_ts' . $parts[0] ), $parts[1] ) ) {
			return true;
		}

		return ( time() - (int) $parts[0] ) < self::MIN_SECONDS;
	}

	private function redirect_success() {
		$url = add_query_arg( 'cdesk_applied', '1', $this->current_url() );

		wp_safe_redirect( $url . '#cdesk-application' );
		exit;
	}

	private function current_url() {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return remove_query_arg( 'cdesk_applied', home_url( $path ) );
	}

	private function accepting() {
		return (bool) Contributor_Desk_Settings::get( 'applications_open' );
	}

	private function unavailable_reason() {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return '';
		}

		if ( Contributor_Desk_Roles::user_is_contributor( $user ) ) {
			return __( 'You are already a contributor on this site.', 'contributors-desk' );
		}

		if ( user_can( $user, 'edit_others_posts' ) ) {
			return __( 'Your account already has editorial access to this site.', 'contributors-desk' );
		}

		if ( Contributor_Desk_Applications::find_pending( $user->user_email, $user->ID ) ) {
			return __( 'Your application is waiting for review. We will email you once it has been reviewed.', 'contributors-desk' );
		}

		return '';
	}

	public function render() {
		wp_enqueue_style( 'cdesk-application-form' );

		$user = wp_get_current_user();
		$now  = time();

		$state = array(
			'notice'      => '',
			'show_form'   => true,
			'errors'      => $this->errors,
			'values'      => wp_parse_args(
				$this->values,
				array(
					'name'    => '',
					'email'   => '',
					'website' => '',
					'message' => '',
					'consent' => false,
				)
			),
			'user'        => $user->exists() ? $user : null,
			'role_label'  => Contributor_Desk_Roles::instance()->display_label(),
			'privacy_url' => get_privacy_policy_url(),
			'timestamp'   => $now . '|' . wp_hash( 'cdesk_ts' . $now ),
			'nonce'       => wp_create_nonce( self::NONCE ),
		);

		if ( isset( $_GET['cdesk_applied'] ) ) {
			$state['notice']    = __( 'Thank you. Your application has been received and we will email you once it has been reviewed.', 'contributors-desk' );
			$state['show_form'] = false;
		} elseif ( ! $this->accepting() ) {
			$state['notice']    = __( 'We are not accepting new applications at the moment.', 'contributors-desk' );
			$state['show_form'] = false;
		} else {
			$reason = $this->unavailable_reason();

			if ( $reason ) {
				$state['notice']    = $reason;
				$state['show_form'] = false;
			}
		}

		$template = locate_template( 'contributors-desk/application-form.php' );
		$template = $template ? $template : CDESK_PATH . 'templates/application-form.php';

		ob_start();
		include $template;

		return ob_get_clean();
	}
}
