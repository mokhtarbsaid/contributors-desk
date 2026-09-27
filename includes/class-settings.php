<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Settings {

	const OPTION     = '_cdesk_settings';
	const GROUP      = 'cdesk_settings';
	const MENU_SLUG  = 'contributors-desk';
	const PAGE_SLUG  = 'cdesk-settings';
	const CAPABILITY = 'manage_options';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'update_option_' . self::OPTION, array( $this, 'after_update' ), 10, 2 );
	}

	public static function defaults() {
		return array(
			'applications_open' => 1,
			'role_label'        => '',
			'support_email'     => '',
		);
	}

	public static function get( $key ) {
		$settings = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );

		return $settings[ $key ] ?? null;
	}

	public static function support_email() {
		$email = (string) self::get( 'support_email' );

		return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Contributors Desk', 'contributors-desk' ),
			__( 'Contributors Desk', 'contributors-desk' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			'__return_null',
			'dashicons-groups',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Contributors Desk Settings', 'contributors-desk' ),
			__( 'Settings', 'contributors-desk' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		remove_submenu_page( self::MENU_SLUG, self::MENU_SLUG );
	}

	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'cdesk_applications', __( 'Join applications', 'contributors-desk' ), '__return_null', self::PAGE_SLUG );
		add_settings_section( 'cdesk_general', __( 'General', 'contributors-desk' ), '__return_null', self::PAGE_SLUG );

		add_settings_field(
			'applications_open',
			__( 'Accept applications', 'contributors-desk' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'cdesk_applications',
			array(
				'key'   => 'applications_open',
				'label' => __( 'Visitors can apply to join through the application form.', 'contributors-desk' ),
			)
		);

		add_settings_field(
			'role_label',
			__( 'Contributor role name', 'contributors-desk' ),
			array( $this, 'render_text' ),
			self::PAGE_SLUG,
			'cdesk_general',
			array(
				'key'         => 'role_label',
				'type'        => 'text',
				'label_for'   => 'cdesk-role_label',
				'placeholder' => _x( 'External Contributor', 'User role', 'contributors-desk' ),
				'description' => __( 'Leave empty to use the default translatable name.', 'contributors-desk' ),
			)
		);

		add_settings_field(
			'support_email',
			__( 'Support email', 'contributors-desk' ),
			array( $this, 'render_text' ),
			self::PAGE_SLUG,
			'cdesk_general',
			array(
				'key'         => 'support_email',
				'type'        => 'email',
				'label_for'   => 'cdesk-support_email',
				'placeholder' => get_option( 'admin_email' ),
				'description' => __( 'Shown to contributors and applicants when they need to contact you. Defaults to the site admin email.', 'contributors-desk' ),
			)
		);
	}

	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'applications_open' => empty( $input['applications_open'] ) ? 0 : 1,
			'role_label'        => sanitize_text_field( $input['role_label'] ?? '' ),
			'support_email'     => sanitize_email( $input['support_email'] ?? '' ),
		);
	}

	public function after_update( $old_value, $value ) {
		if ( ( $old_value['role_label'] ?? '' ) !== ( $value['role_label'] ?? '' ) ) {
			Contributor_Desk_Roles::instance()->reset();
		}
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function render_checkbox( $args ) {
		$name = self::OPTION . '[' . $args['key'] . ']';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( 1, (int) self::get( $args['key'] ) ); ?>>
			<?php echo esc_html( $args['label'] ); ?>
		</label>
		<?php
	}

	public function render_text( $args ) {
		$name = self::OPTION . '[' . $args['key'] . ']';
		?>
		<input
			type="<?php echo esc_attr( $args['type'] ); ?>"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( (string) self::get( $args['key'] ) ); ?>"
			placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
			class="regular-text"
		>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php
	}
}
