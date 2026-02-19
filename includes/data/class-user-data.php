<?php
/**
 * User Data Access Layer.
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard\Data;

/**
 * Class UserData
 *
 * Centralises all DB reads/writes for member data.
 * Keeps SQL out of resolver closures.
 */
class UserData {

	/**
	 * Activity table name (without prefix).
	 */
	const TABLE_ACTIVITY = 'wcgmd_user_activity';

	/**
	 * Notifications table name (without prefix).
	 */
	const TABLE_NOTIFICATIONS = 'wcgmd_notifications';

	// -------------------------------------------------------------------------
	// Profile
	// -------------------------------------------------------------------------

	/**
	 * Get member profile meta for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	public static function get_profile( int $user_id ): array {
		$meta = get_user_meta( $user_id, 'wcgmd_profile', true );
		return is_array( $meta ) ? $meta : [];
	}

	/**
	 * Update member profile meta for a user.
	 *
	 * @param int                $user_id WordPress user ID.
	 * @param array<string,mixed> $data    Fields to save.
	 * @return array<string,mixed> The saved profile.
	 */
	public static function update_profile( int $user_id, array $data ): array {
		$current = self::get_profile( $user_id );

		$allowed = [ 'bio', 'avatarUrl', 'phone', 'location', 'website', 'socialLinks' ];
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$current[ $field ] = $data[ $field ];
			}
		}

		update_user_meta( $user_id, 'wcgmd_profile', $current );
		return $current;
	}

	// -------------------------------------------------------------------------
	// Settings
	// -------------------------------------------------------------------------

	/**
	 * Default settings for new users.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_settings(): array {
		return [
			'emailNotifications' => true,
			'marketingEmails'    => false,
			'dashboardTheme'     => 'light',
			'language'           => 'en',
		];
	}

	/**
	 * Get member settings for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	public static function get_settings( int $user_id ): array {
		$meta = get_user_meta( $user_id, 'wcgmd_settings', true );
		return is_array( $meta ) ? array_merge( self::default_settings(), $meta ) : self::default_settings();
	}

	/**
	 * Update member settings for a user.
	 *
	 * @param int                $user_id WordPress user ID.
	 * @param array<string,mixed> $data    Fields to save.
	 * @return array<string,mixed> The saved settings.
	 */
	public static function update_settings( int $user_id, array $data ): array {
		$current = self::get_settings( $user_id );

		$allowed = [ 'emailNotifications', 'marketingEmails', 'dashboardTheme', 'language' ];
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$current[ $field ] = $data[ $field ];
			}
		}

		update_user_meta( $user_id, 'wcgmd_settings', $current );
		return $current;
	}

	// -------------------------------------------------------------------------
	// Activity
	// -------------------------------------------------------------------------

	/**
	 * Get activity log entries for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @param int $limit   Max rows to return.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_activity( int $user_id, int $limit = 10 ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_ACTIVITY;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
				$user_id,
				$limit
			)
		);

		return array_map(
			static fn( $row ) => [
				'id'          => (string) $row->id,
				'type'        => $row->type,
				'description' => $row->description,
				'createdAt'   => $row->created_at,
			],
			$rows ?? []
		);
	}

	/**
	 * Insert an activity log entry.
	 *
	 * @param int    $user_id     WordPress user ID.
	 * @param string $type        Activity type slug.
	 * @param string $description Human-readable description.
	 * @param array  $meta        Optional JSON-encodable metadata.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function log_activity( int $user_id, string $type, string $description, array $meta = [] ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_ACTIVITY;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			$table,
			[
				'user_id'     => $user_id,
				'type'        => $type,
				'description' => $description,
				'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	// -------------------------------------------------------------------------
	// Notifications
	// -------------------------------------------------------------------------

	/**
	 * Get notifications for a user.
	 *
	 * @param int  $user_id     WordPress user ID.
	 * @param bool $unread_only Return only unread notifications.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_notifications( int $user_id, bool $unread_only = false ): array {
		global $wpdb;

		$table      = $wpdb->prefix . self::TABLE_NOTIFICATIONS;
		$unread_sql = $unread_only ? 'AND is_read = 0' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d {$unread_sql} ORDER BY created_at DESC",
				$user_id
			)
		);

		return array_map(
			static fn( $row ) => [
				'id'        => (string) $row->id,
				'type'      => $row->type,
				'title'     => $row->title,
				'message'   => $row->message,
				'isRead'    => (bool) $row->is_read,
				'createdAt' => $row->created_at,
				'readAt'    => $row->read_at,
			],
			$rows ?? []
		);
	}

	/**
	 * Mark a single notification as read.
	 *
	 * @param int $notification_id Notification row ID.
	 * @param int $user_id         WordPress user ID (ownership check).
	 * @return array<string,mixed>|null The updated notification row, or null if not found.
	 */
	public static function mark_notification_read( int $notification_id, int $user_id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NOTIFICATIONS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$updated = $wpdb->update(
			$table,
			[
				'is_read' => 1,
				'read_at' => current_time( 'mysql' ),
			],
			[
				'id'      => $notification_id,
				'user_id' => $user_id,
			],
			[ '%d', '%s' ],
			[ '%d', '%d' ]
		);

		if ( ! $updated ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$notification_id
			)
		);

		if ( ! $row ) {
			return null;
		}

		return [
			'id'        => (string) $row->id,
			'type'      => $row->type,
			'title'     => $row->title,
			'message'   => $row->message,
			'isRead'    => (bool) $row->is_read,
			'createdAt' => $row->created_at,
			'readAt'    => $row->read_at,
		];
	}

	/**
	 * Mark all notifications read for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int Number of rows updated.
	 */
	public static function mark_all_notifications_read( int $user_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NOTIFICATIONS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			$table,
			[
				'is_read' => 1,
				'read_at' => current_time( 'mysql' ),
			],
			[
				'user_id' => $user_id,
				'is_read' => 0,
			],
			[ '%d', '%s' ],
			[ '%d', '%d' ]
		);

		return (int) $result;
	}

	// -------------------------------------------------------------------------
	// Stats
	// -------------------------------------------------------------------------

	/**
	 * Get computed dashboard stats for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	public static function get_stats( int $user_id ): array {
		global $wpdb;

		$activity_table      = $wpdb->prefix . self::TABLE_ACTIVITY;
		$notification_table  = $wpdb->prefix . self::TABLE_NOTIFICATIONS;

		// Activity count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$activity_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$activity_table} WHERE user_id = %d",
				$user_id
			)
		);

		// Unread notification count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$unread_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$notification_table} WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);

		// WooCommerce order count (if WooCommerce is active).
		$order_count    = 0;
		$total_spent    = 0.0;
		$membership_status = 'inactive';

		if ( function_exists( 'wc_get_orders' ) ) {
			$orders      = wc_get_orders(
				[
					'customer' => $user_id,
					'status'   => [ 'completed', 'processing' ],
					'limit'    => -1,
					'return'   => 'ids',
				]
			);
			$order_count = count( $orders );

			foreach ( $orders as $order_id ) {
				$order       = wc_get_order( $order_id );
				$total_spent += $order ? (float) $order->get_total() : 0;
			}
		}

		// Profile completeness percentage.
		$profile  = self::get_profile( $user_id );
		$fields   = [ 'bio', 'phone', 'location', 'website', 'avatarUrl' ];
		$filled   = count( array_filter( array_intersect_key( $profile, array_flip( $fields ) ) ) );
		$profile_completeness = (int) round( ( $filled / count( $fields ) ) * 100 );

		return [
			'activityCount'       => $activity_count,
			'unreadNotifications' => $unread_count,
			'orderCount'          => $order_count,
			'totalSpent'          => number_format( $total_spent, 2, '.', '' ),
			'membershipStatus'    => $membership_status,
			'profileCompleteness' => $profile_completeness,
		];
	}
}
