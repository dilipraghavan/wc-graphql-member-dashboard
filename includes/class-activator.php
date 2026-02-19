<?php
/**
 * Activator class.
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard;

/**
 * Class Activator
 *
 * Handles plugin activation and deactivation:
 * - Creates custom DB tables.
 * - Stores the plugin version in options.
 * - Schedules cron events.
 */
class Activator {

	/**
	 * Table: user activity log.
	 */
	const TABLE_ACTIVITY = 'wcgmd_user_activity';

	/**
	 * Table: notifications.
	 */
	const TABLE_NOTIFICATIONS = 'wcgmd_notifications';

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::seed_demo_data();
		update_option( 'wcgmd_version', WCGMD_VERSION );
		update_option( 'wcgmd_activated_at', current_time( 'mysql' ) );
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Nothing destructive on deactivation — tables preserved.
		delete_option( 'wcgmd_activated_at' );
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// ── Activity Log ──────────────────────────────────────────────────────
		$activity_table = $wpdb->prefix . self::TABLE_ACTIVITY;
		$sql_activity   = "CREATE TABLE {$activity_table} (
			id          BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id     BIGINT(20)   UNSIGNED NOT NULL,
			type        VARCHAR(100) NOT NULL DEFAULT 'general',
			description TEXT         NOT NULL,
			meta        LONGTEXT     NULL,
			created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id   (user_id),
			KEY type      (type),
			KEY created_at (created_at)
		) {$charset_collate};";

		// ── Notifications ─────────────────────────────────────────────────────
		$notification_table = $wpdb->prefix . self::TABLE_NOTIFICATIONS;
		$sql_notifications  = "CREATE TABLE {$notification_table} (
			id         BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id    BIGINT(20)   UNSIGNED NOT NULL,
			type       VARCHAR(100) NOT NULL DEFAULT 'info',
			title      VARCHAR(255) NOT NULL,
			message    TEXT         NOT NULL,
			is_read    TINYINT(1)   NOT NULL DEFAULT 0,
			created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			read_at    DATETIME     NULL,
			PRIMARY KEY (id),
			KEY user_id  (user_id),
			KEY is_read  (is_read),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_activity );
		dbDelta( $sql_notifications );
	}

	/**
	 * Seed demo data for the current admin user (portfolio demo purposes).
	 *
	 * @return void
	 */
	private static function seed_demo_data(): void {
		global $wpdb;

		$admin = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
		if ( empty( $admin ) ) {
			return;
		}

		$user_id            = $admin[0]->ID;
		$activity_table     = $wpdb->prefix . self::TABLE_ACTIVITY;
		$notification_table = $wpdb->prefix . self::TABLE_NOTIFICATIONS;

		// Seed activity entries.
		$activities = [
			[ 'type' => 'login',    'description' => 'User logged in from 192.168.1.1' ],
			[ 'type' => 'profile',  'description' => 'Profile bio updated' ],
			[ 'type' => 'settings', 'description' => 'Email notifications enabled' ],
			[ 'type' => 'login',    'description' => 'User logged in from 10.0.0.5' ],
			[ 'type' => 'profile',  'description' => 'Avatar image updated' ],
		];

		foreach ( $activities as $activity ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$activity_table,
				[
					'user_id'     => $user_id,
					'type'        => $activity['type'],
					'description' => $activity['description'],
					'created_at'  => current_time( 'mysql' ),
				],
				[ '%d', '%s', '%s', '%s' ]
			);
		}

		// Seed notification entries.
		$notifications = [
			[ 'type' => 'info',    'title' => 'Welcome!',            'message' => 'Your member dashboard is ready.' ],
			[ 'type' => 'success', 'title' => 'Profile complete',    'message' => 'You have filled in all profile fields.' ],
			[ 'type' => 'warning', 'title' => 'Password expiry',     'message' => 'Your password expires in 30 days.' ],
			[ 'type' => 'info',    'title' => 'New feature',         'message' => 'Activity log is now available in your dashboard.' ],
		];

		foreach ( $notifications as $notification ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$notification_table,
				[
					'user_id'    => $user_id,
					'type'       => $notification['type'],
					'title'      => $notification['title'],
					'message'    => $notification['message'],
					'is_read'    => 0,
					'created_at' => current_time( 'mysql' ),
				],
				[ '%d', '%s', '%s', '%s', '%d', '%s' ]
			);
		}
	}
}
