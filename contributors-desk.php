<?php
/**
 * Plugin Name:       Contributors Desk
 * Plugin URI:        https://mokhtarbensaid.com
 * Description:       Vet, receive, review and hold accountable the external contributors of your site.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Mokhtar Bensaid
 * Author URI:        https://mokhtarbensaid.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       contributors-desk
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'CDESK_VERSION', '0.1.0' );
define( 'CDESK_FILE', __FILE__ );
define( 'CDESK_BASENAME', plugin_basename( __FILE__ ) );
define( 'CDESK_PATH', plugin_dir_path( __FILE__ ) );
define( 'CDESK_URL', plugin_dir_url( __FILE__ ) );

require_once CDESK_PATH . 'includes/autoloader.php';

register_activation_hook( __FILE__, array( 'Contributor_Desk_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Contributor_Desk_Plugin', 'deactivate' ) );

Contributor_Desk_Plugin::instance();
