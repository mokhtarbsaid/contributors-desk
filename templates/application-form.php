<?php

defined( 'ABSPATH' ) || exit;

$cdesk_errors = $state['errors'];
$cdesk_values = $state['values'];
?>
<div id="cdesk-application" class="cdesk-application">
	<?php if ( $state['notice'] ) : ?>
		<p class="cdesk-application__notice"><?php echo esc_html( $state['notice'] ); ?></p>
	<?php endif; ?>

	<?php if ( $state['show_form'] ) : ?>
		<?php if ( ! empty( $cdesk_errors['form'] ) ) : ?>
			<p class="cdesk-application__notice cdesk-application__notice--error" role="alert"><?php echo esc_html( $cdesk_errors['form'] ); ?></p>
		<?php endif; ?>

		<form class="cdesk-application__form" method="post" action="#cdesk-application" novalidate>
			<input type="hidden" name="cdesk_action" value="apply">
			<input type="hidden" name="_cdesk_nonce" value="<?php echo esc_attr( $state['nonce'] ); ?>">
			<input type="hidden" name="cdesk_ts" value="<?php echo esc_attr( $state['timestamp'] ); ?>">

			<div class="cdesk-application__trap" aria-hidden="true">
				<label for="cdesk-company"><?php esc_html_e( 'Leave this field empty', 'contributors-desk' ); ?></label>
				<input type="text" id="cdesk-company" name="cdesk_company" value="" tabindex="-1" autocomplete="off">
			</div>

			<p class="cdesk-application__intro">
				<?php echo esc_html( sprintf( __( 'Apply to join as: %s', 'contributors-desk' ), $state['role_label'] ) ); ?>
			</p>

			<?php if ( $state['user'] ) : ?>
				<p class="cdesk-application__account">
					<?php echo esc_html( sprintf( __( 'You are applying with your account %1$s (%2$s).', 'contributors-desk' ), $state['user']->display_name, $state['user']->user_email ) ); ?>
				</p>
			<?php else : ?>
				<p class="cdesk-application__field<?php echo isset( $cdesk_errors['name'] ) ? ' has-error' : ''; ?>">
					<label for="cdesk-name"><?php esc_html_e( 'Full name', 'contributors-desk' ); ?> <span aria-hidden="true">*</span></label>
					<input type="text" id="cdesk-name" name="cdesk_name" value="<?php echo esc_attr( $cdesk_values['name'] ); ?>" maxlength="100" autocomplete="name" required<?php echo isset( $cdesk_errors['name'] ) ? ' aria-invalid="true" aria-describedby="cdesk-name-error"' : ''; ?>>
					<?php if ( isset( $cdesk_errors['name'] ) ) : ?>
						<span id="cdesk-name-error" class="cdesk-application__error"><?php echo esc_html( $cdesk_errors['name'] ); ?></span>
					<?php endif; ?>
				</p>

				<p class="cdesk-application__field<?php echo isset( $cdesk_errors['email'] ) ? ' has-error' : ''; ?>">
					<label for="cdesk-email"><?php esc_html_e( 'Email', 'contributors-desk' ); ?> <span aria-hidden="true">*</span></label>
					<input type="email" id="cdesk-email" name="cdesk_email" value="<?php echo esc_attr( $cdesk_values['email'] ); ?>" autocomplete="email" required<?php echo isset( $cdesk_errors['email'] ) ? ' aria-invalid="true" aria-describedby="cdesk-email-error"' : ''; ?>>
					<?php if ( isset( $cdesk_errors['email'] ) ) : ?>
						<span id="cdesk-email-error" class="cdesk-application__error"><?php echo wp_kses( $cdesk_errors['email'], array( 'a' => array( 'href' => array() ) ) ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $state['user'] && isset( $cdesk_errors['email'] ) ) : ?>
				<p class="cdesk-application__notice cdesk-application__notice--error" role="alert"><?php echo esc_html( $cdesk_errors['email'] ); ?></p>
			<?php endif; ?>

			<p class="cdesk-application__field">
				<label for="cdesk-website"><?php esc_html_e( 'Website or professional profile', 'contributors-desk' ); ?></label>
				<input type="url" id="cdesk-website" name="cdesk_website" value="<?php echo esc_attr( $cdesk_values['website'] ); ?>" autocomplete="url" placeholder="https://">
			</p>

			<p class="cdesk-application__field<?php echo isset( $cdesk_errors['message'] ) ? ' has-error' : ''; ?>">
				<label for="cdesk-message"><?php esc_html_e( 'About you and what you would like to contribute', 'contributors-desk' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="cdesk-message" name="cdesk_message" rows="7" maxlength="5000" required<?php echo isset( $cdesk_errors['message'] ) ? ' aria-invalid="true" aria-describedby="cdesk-message-error"' : ''; ?>><?php echo esc_textarea( $cdesk_values['message'] ); ?></textarea>
				<?php if ( isset( $cdesk_errors['message'] ) ) : ?>
					<span id="cdesk-message-error" class="cdesk-application__error"><?php echo esc_html( $cdesk_errors['message'] ); ?></span>
				<?php endif; ?>
			</p>

			<p class="cdesk-application__field cdesk-application__field--checkbox<?php echo isset( $cdesk_errors['consent'] ) ? ' has-error' : ''; ?>">
				<label>
					<input type="checkbox" name="cdesk_consent" value="1" required<?php checked( $cdesk_values['consent'] ); ?>>
					<?php esc_html_e( 'I agree that this site stores the information I submit in order to review my application.', 'contributors-desk' ); ?>
					<?php if ( $state['privacy_url'] ) : ?>
						<a href="<?php echo esc_url( $state['privacy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', 'contributors-desk' ); ?></a>
					<?php endif; ?>
				</label>
				<?php if ( isset( $cdesk_errors['consent'] ) ) : ?>
					<span class="cdesk-application__error"><?php echo esc_html( $cdesk_errors['consent'] ); ?></span>
				<?php endif; ?>
			</p>

			<p class="cdesk-application__actions">
				<button type="submit" class="cdesk-application__submit wp-element-button"><?php esc_html_e( 'Submit application', 'contributors-desk' ); ?></button>
			</p>
		</form>
	<?php endif; ?>
</div>
