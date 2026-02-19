<?php
/**
 * Plugin Name: WC GraphQL Member Dashboard
 * Plugin URI:  https://github.com/dilipraghavan/wc-graphql-member-dashboard
 * Description: A GraphQL-powered member dashboard built with WPGraphQL, custom types, and a Next.js frontend using NextAuth authentication.
 * Version:     1.0.0
 * Author:      WP Shift Studio
 * Author URI:  https://wpshiftstudio.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-graphql-member-dashboard
 * Domain Path: /languages
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WCGMD_VERSION', '1.0.0' );
define( 'WCGMD_PLUGIN_FILE', __FILE__ );
define( 'WCGMD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCGMD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCGMD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader.
 */
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'WpShiftStudio\\WCGraphQLMemberDashboard\\';
		$base_dir = WCGMD_PLUGIN_DIR . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		// Convert namespace separators to directory separators.
		$parts     = explode( '\\', $relative_class );
		$classname = array_pop( $parts );
		$subdir    = ! empty( $parts ) ? strtolower( implode( '/', $parts ) ) . '/' : '';

		// Convert CamelCase to kebab-case (e.g. TypeRegistry → type-registry).
		$kebab = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $classname ) );
		$kebab = str_replace( '_', '-', $kebab );

		$file = $base_dir . $subdir . 'class-' . $kebab . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Initialise the plugin.
 */
function wcgmd_init() {
	// Check WPGraphQL dependency.
	if ( ! class_exists( 'WPGraphQL' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\wcgmd_missing_wpgraphql_notice' );
		return;
	}

	Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\wcgmd_init' );

/**
 * Admin notice when WPGraphQL is missing.
 */
function wcgmd_missing_wpgraphql_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong>WC GraphQL Member Dashboard</strong> requires the
			<a href="https://wordpress.org/plugins/wp-graphql/" target="_blank">WPGraphQL</a>
			plugin to be installed and activated.
		</p>
	</div>
	<?php
}

/**
 * Activation hook.
 */
function wcgmd_activate() {
	require_once WCGMD_PLUGIN_DIR . 'includes/class-activator.php';
	Activator::activate();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\wcgmd_activate' );

/**
 * Deactivation hook.
 */
function wcgmd_deactivate() {
	require_once WCGMD_PLUGIN_DIR . 'includes/class-activator.php';
	Activator::deactivate();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\wcgmd_deactivate' );
