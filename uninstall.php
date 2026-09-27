<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$cdesk_application_ids = $wpdb->get_col(
	$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'cdesk_application' )
);

foreach ( $cdesk_application_ids as $cdesk_application_id ) {
	wp_delete_post( (int) $cdesk_application_id, true );
}

remove_role( 'cdesk_contributor' );

delete_option( '_cdesk_settings' );
delete_option( '_cdesk_roles_version' );
delete_option( '_cdesk_version' );
