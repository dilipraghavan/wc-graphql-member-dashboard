<?php
/**
 * Main Plugin class.
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard;

/**
 * Class Plugin
 *
 * Singleton orchestrator — loads all sub-components.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public string $version = WCGMD_VERSION;

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {}

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialise all plugin components.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_textdomain();
		$this->register_graphql_types();
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'wc-graphql-member-dashboard',
			false,
			dirname( WCGMD_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register GraphQL types and mutations via WPGraphQL hooks.
	 *
	 * @return void
	 */
	private function register_graphql_types(): void {
		$type_registry     = new GraphQL\TypeRegistry();
		$mutation_registry = new GraphQL\MutationRegistry();

		add_action( 'graphql_register_types', [ $type_registry, 'register' ] );
		add_action( 'graphql_register_types', [ $mutation_registry, 'register' ] );
	}
}
