<?php

defined( 'ABSPATH' ) || exit;

final class Contributor_Desk_Applications {

	const POST_TYPE       = 'cdesk_application';
	const STATUS_PENDING  = 'cdesk_pending';
	const STATUS_APPROVED = 'cdesk_approved';
	const STATUS_REJECTED = 'cdesk_rejected';
	const REVIEW_PAGE     = 'cdesk-application';
	const REVIEW_ACTION   = 'cdesk_review_application';

	private static $instance = null;

	private $review_hook = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'wp_untrash_post_status', array( $this, 'untrash_status' ), 10, 3 );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_review_page' ) );
		add_action( 'admin_head', array( $this, 'hide_review_page' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_menu' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::REVIEW_ACTION, array( $this, 'handle_review' ) );
		add_action( 'load-post.php', array( $this, 'redirect_editor' ) );
		add_filter( 'get_edit_post_link', array( $this, 'edit_link' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-' . self::POST_TYPE, array( $this, 'bulk_actions' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	public function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Applications', 'contributors-desk' ),
					'singular_name'      => __( 'Application', 'contributors-desk' ),
					'menu_name'          => __( 'Applications', 'contributors-desk' ),
					'all_items'          => __( 'Applications', 'contributors-desk' ),
					'search_items'       => __( 'Search applications', 'contributors-desk' ),
					'not_found'          => __( 'No applications found.', 'contributors-desk' ),
					'not_found_in_trash' => __( 'No applications found in Trash.', 'contributors-desk' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => Contributor_Desk_Settings::MENU_SLUG,
				'show_in_rest' => false,
				'rewrite'      => false,
				'query_var'    => false,
				'supports'     => false,
				'map_meta_cap' => false,
				'capabilities' => array(
					'edit_post'           => Contributor_Desk_Settings::CAPABILITY,
					'read_post'           => Contributor_Desk_Settings::CAPABILITY,
					'delete_post'         => Contributor_Desk_Settings::CAPABILITY,
					'edit_posts'          => Contributor_Desk_Settings::CAPABILITY,
					'edit_others_posts'   => Contributor_Desk_Settings::CAPABILITY,
					'delete_posts'        => Contributor_Desk_Settings::CAPABILITY,
					'delete_others_posts' => Contributor_Desk_Settings::CAPABILITY,
					'publish_posts'       => Contributor_Desk_Settings::CAPABILITY,
					'read_private_posts'  => Contributor_Desk_Settings::CAPABILITY,
					'create_posts'        => 'do_not_allow',
				),
			)
		);

		register_post_status(
			self::STATUS_PENDING,
			array(
				'label'                     => _x( 'Pending', 'application status', 'contributors-desk' ),
				'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'contributors-desk' ),
				'public'                    => false,
				'protected'                 => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
			)
		);

		register_post_status(
			self::STATUS_APPROVED,
			array(
				'label'                     => _x( 'Approved', 'application status', 'contributors-desk' ),
				'label_count'               => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'contributors-desk' ),
				'public'                    => false,
				'protected'                 => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
			)
		);

		register_post_status(
			self::STATUS_REJECTED,
			array(
				'label'                     => _x( 'Rejected', 'application status', 'contributors-desk' ),
				'label_count'               => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'contributors-desk' ),
				'public'                    => false,
				'protected'                 => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
			)
		);

		get_post_type_object( self::POST_TYPE )->labels->all_items .= $this->pending_bubble();
	}

	private function pending_bubble() {
		if ( ! is_admin() || ! current_user_can( Contributor_Desk_Settings::CAPABILITY ) ) {
			return '';
		}

		$counts = wp_count_posts( self::POST_TYPE );
		$count  = (int) ( $counts->{self::STATUS_PENDING} ?? 0 );

		if ( ! $count ) {
			return '';
		}

		return sprintf(
			' <span class="awaiting-mod count-%1$d"><span class="pending-count">%2$s</span></span>',
			$count,
			number_format_i18n( $count )
		);
	}

	public static function create( array $data ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => self::STATUS_PENDING,
				'post_title'   => $data['name'],
				'post_content' => $data['message'],
				'post_author'  => (int) $data['user_id'],
				'meta_input'   => array(
					'_cdesk_email'   => $data['email'],
					'_cdesk_website' => $data['website'],
					'_cdesk_user_id' => (int) $data['user_id'],
					'_cdesk_locale'  => $data['locale'],
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		do_action( 'cdesk_application_submitted', $post_id );

		return $post_id;
	}

	public static function find_pending( $email, $user_id = 0 ) {
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => '_cdesk_email',
				'value' => $email,
			),
		);

		if ( $user_id ) {
			$meta_query[] = array(
				'key'   => '_cdesk_user_id',
				'value' => (int) $user_id,
			);
		}

		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => self::STATUS_PENDING,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => $meta_query,
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	public static function get( $post ) {
		$post = get_post( $post );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return array(
			'id'             => $post->ID,
			'status'         => $post->post_status,
			'name'           => $post->post_title,
			'message'        => $post->post_content,
			'date'           => $post->post_date,
			'email'          => (string) get_post_meta( $post->ID, '_cdesk_email', true ),
			'website'        => (string) get_post_meta( $post->ID, '_cdesk_website', true ),
			'user_id'        => (int) get_post_meta( $post->ID, '_cdesk_user_id', true ),
			'locale'         => (string) get_post_meta( $post->ID, '_cdesk_locale', true ),
			'reason'         => (string) get_post_meta( $post->ID, '_cdesk_reason', true ),
			'reviewed_by'    => (int) get_post_meta( $post->ID, '_cdesk_reviewed_by', true ),
			'reviewed_at'    => (string) get_post_meta( $post->ID, '_cdesk_reviewed_at', true ),
			'contributor_id' => (int) get_post_meta( $post->ID, '_cdesk_contributor_id', true ),
		);
	}

	public static function review_url( $post_id ) {
		return add_query_arg(
			array(
				'page'        => self::REVIEW_PAGE,
				'application' => (int) $post_id,
			),
			admin_url( 'admin.php' )
		);
	}

	public function approve( $post_id ) {
		$application = self::get( $post_id );

		if ( ! $application || self::STATUS_PENDING !== $application['status'] ) {
			return new WP_Error( 'cdesk_not_pending', __( 'This application is no longer pending.', 'contributors-desk' ) );
		}

		$user = $application['user_id'] ? get_userdata( $application['user_id'] ) : false;

		if ( ! $user ) {
			$user = get_user_by( 'email', $application['email'] );
		}

		$created = false;

		if ( ! $user ) {
			$user = $this->create_user( $application );

			if ( is_wp_error( $user ) ) {
				return $user;
			}

			$created = true;
		} elseif ( ! in_array( Contributor_Desk_Roles::ROLE, (array) $user->roles, true ) ) {
			$user->add_role( Contributor_Desk_Roles::ROLE );
		}

		$this->close( $post_id, self::STATUS_APPROVED );
		update_post_meta( $post_id, '_cdesk_contributor_id', $user->ID );

		do_action( 'cdesk_application_approved', $post_id, $user->ID, $created );

		return $user->ID;
	}

	public function reject( $post_id, $reason ) {
		$application = self::get( $post_id );

		if ( ! $application || self::STATUS_PENDING !== $application['status'] ) {
			return new WP_Error( 'cdesk_not_pending', __( 'This application is no longer pending.', 'contributors-desk' ) );
		}

		$reason = trim( $reason );

		if ( '' === $reason ) {
			return new WP_Error( 'cdesk_reason_required', __( 'Please write the reason for rejecting this application.', 'contributors-desk' ) );
		}

		$this->close( $post_id, self::STATUS_REJECTED );
		update_post_meta( $post_id, '_cdesk_reason', $reason );

		do_action( 'cdesk_application_rejected', $post_id, $reason );

		return true;
	}

	private function close( $post_id, $status ) {
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => $status,
			)
		);

		update_post_meta( $post_id, '_cdesk_reviewed_by', get_current_user_id() );
		update_post_meta( $post_id, '_cdesk_reviewed_at', current_time( 'mysql' ) );
	}

	private function create_user( array $application ) {
		if ( ! current_user_can( 'create_users' ) ) {
			return new WP_Error( 'cdesk_cannot_create_users', __( 'You are not allowed to create user accounts, so this applicant cannot be approved.', 'contributors-desk' ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $this->unique_login( $application['email'] ),
				'user_email'   => $application['email'],
				'user_pass'    => wp_generate_password( 24 ),
				'user_url'     => $application['website'],
				'display_name' => $application['name'],
				'nickname'     => $application['name'],
				'role'         => Contributor_Desk_Roles::ROLE,
				'locale'       => get_locale() === $application['locale'] ? '' : $application['locale'],
			)
		);

		return is_wp_error( $user_id ) ? $user_id : get_userdata( $user_id );
	}

	private function unique_login( $email ) {
		$base = sanitize_user( strtolower( strstr( $email, '@', true ) ), true );
		$base = '' === $base ? 'contributor' : substr( $base, 0, 50 );

		$login  = $base;
		$suffix = 2;

		while ( username_exists( $login ) ) {
			$login = $base . $suffix;
			++$suffix;
		}

		return $login;
	}

	public function untrash_status( $new_status, $post_id, $previous_status ) {
		if ( self::POST_TYPE === get_post_type( $post_id ) && $previous_status ) {
			return $previous_status;
		}

		return $new_status;
	}

	public function register_review_page() {
		$this->review_hook = (string) add_submenu_page(
			Contributor_Desk_Settings::MENU_SLUG,
			__( 'Review application', 'contributors-desk' ),
			__( 'Review application', 'contributors-desk' ),
			Contributor_Desk_Settings::CAPABILITY,
			self::REVIEW_PAGE,
			array( $this, 'render_review_page' )
		);
	}

	public function hide_review_page() {
		remove_submenu_page( Contributor_Desk_Settings::MENU_SLUG, self::REVIEW_PAGE );
	}

	public function highlight_menu( $submenu_file, $parent_file ) {
		global $plugin_page;

		if ( Contributor_Desk_Settings::MENU_SLUG === $parent_file && self::REVIEW_PAGE === $plugin_page ) {
			return 'edit.php?post_type=' . self::POST_TYPE;
		}

		return $submenu_file;
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->review_hook ) {
			return;
		}

		wp_enqueue_style( 'cdesk-admin', CDESK_URL . 'assets/css/admin.css', array(), CDESK_VERSION );
	}

	public function redirect_editor() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( $post_id && self::POST_TYPE === get_post_type( $post_id ) ) {
			wp_safe_redirect( self::review_url( $post_id ) );
			exit;
		}
	}

	public function edit_link( $link, $post_id ) {
		return self::POST_TYPE === get_post_type( $post_id ) ? self::review_url( $post_id ) : $link;
	}

	public function row_actions( $actions, $post ) {
		if ( self::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		$review = array(
			'review' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( self::review_url( $post->ID ) ),
				esc_html__( 'Review', 'contributors-desk' )
			),
		);

		return array_merge( $review, array_intersect_key( $actions, array_flip( array( 'trash', 'untrash', 'delete' ) ) ) );
	}

	public function bulk_actions( $actions ) {
		unset( $actions['edit'] );

		return $actions;
	}

	public function columns( $columns ) {
		return array(
			'cb'           => $columns['cb'] ?? '<input type="checkbox" />',
			'title'        => __( 'Name', 'contributors-desk' ),
			'cdesk_email'  => __( 'Email', 'contributors-desk' ),
			'cdesk_status' => __( 'Status', 'contributors-desk' ),
			'cdesk_date'   => __( 'Submitted', 'contributors-desk' ),
		);
	}

	public function sortable_columns( $columns ) {
		$columns['cdesk_date'] = array( 'date', true );

		return $columns;
	}

	public function render_column( $column, $post_id ) {
		if ( 'cdesk_email' === $column ) {
			$email = (string) get_post_meta( $post_id, '_cdesk_email', true );
			printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
		}

		if ( 'cdesk_date' === $column ) {
			echo esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post_id ) );
		}

		if ( 'cdesk_status' === $column ) {
			$status = get_post_status_object( get_post_status( $post_id ) );
			echo esc_html( $status ? $status->label : '' );
		}
	}

	public function handle_review() {
		$post_id = isset( $_POST['application'] ) ? absint( $_POST['application'] ) : 0;

		if ( ! current_user_can( Contributor_Desk_Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to review applications.', 'contributors-desk' ), 403 );
		}

		check_admin_referer( self::REVIEW_ACTION . '_' . $post_id );

		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';

		if ( 'approve' === $decision ) {
			$result = $this->approve( $post_id );
		} elseif ( 'reject' === $decision ) {
			$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
			$result = $this->reject( $post_id, $reason );
		} else {
			$result = new WP_Error( 'cdesk_invalid_decision', __( 'Unknown decision.', 'contributors-desk' ) );
		}

		$args = is_wp_error( $result )
			? array( 'cdesk_error' => rawurlencode( $result->get_error_message() ) )
			: array( 'cdesk_done' => $decision );

		wp_safe_redirect( add_query_arg( $args, self::review_url( $post_id ) ) );
		exit;
	}

	public function render_review_page() {
		$post_id     = isset( $_GET['application'] ) ? absint( $_GET['application'] ) : 0;
		$application = self::get( $post_id );

		if ( ! $application || 'trash' === $application['status'] ) {
			wp_die( esc_html__( 'This application does not exist or has been deleted.', 'contributors-desk' ) );
		}

		$status   = get_post_status_object( $application['status'] );
		$reviewer = $application['reviewed_by'] ? get_userdata( $application['reviewed_by'] ) : false;
		$account  = $application['contributor_id'] ? $application['contributor_id'] : $application['user_id'];
		$account  = $account ? get_userdata( $account ) : false;
		$done     = isset( $_GET['cdesk_done'] ) ? sanitize_key( wp_unslash( $_GET['cdesk_done'] ) ) : '';
		$error    = isset( $_GET['cdesk_error'] ) ? sanitize_text_field( wp_unslash( $_GET['cdesk_error'] ) ) : '';
		?>
		<div class="wrap cdesk-review">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Review application', 'contributors-desk' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to applications', 'contributors-desk' ); ?></a>
			<hr class="wp-header-end">

			<?php if ( 'approve' === $done ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Application approved. The applicant has been notified by email.', 'contributors-desk' ); ?></p></div>
			<?php elseif ( 'reject' === $done ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Application rejected. The applicant has been notified by email.', 'contributors-desk' ); ?></p></div>
			<?php elseif ( $error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<div class="cdesk-review__grid">
				<div class="cdesk-review__main postbox">
					<div class="inside">
						<h2 class="cdesk-review__name"><?php echo esc_html( $application['name'] ); ?></h2>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Email', 'contributors-desk' ); ?></th>
								<td><a href="mailto:<?php echo esc_attr( $application['email'] ); ?>"><?php echo esc_html( $application['email'] ); ?></a></td>
							</tr>
							<?php if ( $application['website'] ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'Website', 'contributors-desk' ); ?></th>
									<td><a href="<?php echo esc_url( $application['website'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $application['website'] ); ?></a></td>
								</tr>
							<?php endif; ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Submitted', 'contributors-desk' ); ?></th>
								<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $application['date'] ) ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Account', 'contributors-desk' ); ?></th>
								<td>
									<?php if ( $account ) : ?>
										<a href="<?php echo esc_url( get_edit_user_link( $account->ID ) ); ?>"><?php echo esc_html( $account->user_login ); ?></a>
									<?php else : ?>
										<?php esc_html_e( 'None yet. An account is created on approval.', 'contributors-desk' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						</table>
						<h3><?php esc_html_e( 'About the applicant', 'contributors-desk' ); ?></h3>
						<div class="cdesk-review__message"><?php echo wp_kses_post( wpautop( esc_html( $application['message'] ) ) ); ?></div>
					</div>
				</div>

				<div class="cdesk-review__side">
					<div class="postbox">
						<div class="inside">
							<p class="cdesk-review__status cdesk-review__status--<?php echo esc_attr( $application['status'] ); ?>">
								<?php echo esc_html( $status ? $status->label : $application['status'] ); ?>
							</p>

							<?php if ( self::STATUS_PENDING === $application['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( self::REVIEW_ACTION . '_' . $application['id'] ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( self::REVIEW_ACTION ); ?>">
									<input type="hidden" name="application" value="<?php echo esc_attr( $application['id'] ); ?>">
									<input type="hidden" name="decision" value="approve">
									<p><?php echo esc_html( sprintf( __( 'Approving gives the applicant the "%s" role and emails them how to log in.', 'contributors-desk' ), Contributor_Desk_Roles::instance()->display_label() ) ); ?></p>
									<?php submit_button( __( 'Approve', 'contributors-desk' ), 'primary', 'submit', false ); ?>
								</form>

								<hr>

								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( self::REVIEW_ACTION . '_' . $application['id'] ); ?>
									<input type="hidden" name="action" value="<?php echo esc_attr( self::REVIEW_ACTION ); ?>">
									<input type="hidden" name="application" value="<?php echo esc_attr( $application['id'] ); ?>">
									<input type="hidden" name="decision" value="reject">
									<p>
										<label for="cdesk-reason"><strong><?php esc_html_e( 'Reason for rejection', 'contributors-desk' ); ?></strong></label>
										<textarea id="cdesk-reason" name="reason" rows="5" class="widefat" required></textarea>
										<span class="description"><?php esc_html_e( 'The applicant receives this text by email.', 'contributors-desk' ); ?></span>
									</p>
									<?php submit_button( __( 'Reject', 'contributors-desk' ), 'secondary cdesk-review__reject', 'submit', false ); ?>
								</form>
							<?php else : ?>
								<p>
									<?php
									echo esc_html(
										sprintf(
											__( 'Reviewed by %1$s on %2$s.', 'contributors-desk' ),
											$reviewer ? $reviewer->display_name : __( 'an unknown user', 'contributors-desk' ),
											mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $application['reviewed_at'] )
										)
									);
									?>
								</p>
								<?php if ( $application['reason'] ) : ?>
									<h3><?php esc_html_e( 'Reason for rejection', 'contributors-desk' ); ?></h3>
									<div class="cdesk-review__message"><?php echo wp_kses_post( wpautop( esc_html( $application['reason'] ) ) ); ?></div>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
