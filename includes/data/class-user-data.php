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
 * Centralises all database reads and writes for member profile,
 * settings, activity, and notifications. Keeps SQL out of resolvers.
 */
class UserData {

	/**
	 * Get member profile meta for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string, mixed>
	 */
	public static function get_profile( int $user_id ): array {
		$meta = get_user_meta( $user_id, 'wcgmd_profile', true );
		return is_array( $meta ) ? $meta : [];
	}

	/**
	 * Update member profile meta.
	 *
	 * @param int                  $user_id WordPress user ID.
	 * @param array<string, mixed> $data    Profile fields to update.
	 * @return bool
	 */
	public static function update_profile( int $user_id, array $data ): bool {
		$existing = self::get_profile( $user_id );
		$allowed  = [ 'bio', 'phone', 'location', 'website', 'socialLinks', 'avatarUrl' ];
		$updated  = $existing;

		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$updated[ $field ] = $data[ $field ];
			}
		}

		return (bool) update_user_meta( $user_id, 'wcgmd_profile', $updated );
	}

	/**
	 * Get member settings for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string, mixed>
	 */
	public static function get_settings( int $user_id ): array {
		$meta = get_user_meta( $user_id, 'wcgmd_settings', true );
		return is_array( $meta ) ? $meta : self::default_settings();
	}

	/**
	 * Update member settings.
	 *
	 * @param int                  $user_id WordPress user ID.
	 * @param array<string, mixed> $data    Settings fields to update.
	 * @return bool
	 */
	public static function update_settings( int $user_id, array $data ): bool {
		$existing = self::get_settings( $user_id );
		$allowed  = [ 'emailNotifications', 'marketingEmails', 'dashboardTheme', 'language' ];
		$updated  = $existing;

		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$updated[ $field ] = $data[ $field ];
			}
		}

		return (bool) update_user_meta( $user_id, 'wcgmd_settings', $updated );
	}

	/**
	 * Get activity log entries for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @param int $limit   Number of entries to return.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_activity( int $user_id, int $limit = 10 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wcgmd_user_activity';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
				$user_id,
				$limit
			)
		);

		return array_map(
			fn( $row ) => [
				'id'          => (int) $row->id,
				'type'        => $row->type,
				'description' => $row->description,
				'createdAt'   => $row->created_at,
			],
			$rows ?? []
		);
	}

	/**
	 * Log an activity entry.
	 *
	 * @param int    $user_id     WordPress user ID.
	 * @param string $type        Activity type.
	 * @param string $description Human-readable description.
	 * @param mixed  $meta        Optional metadata (will be JSON encoded).
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function log_activity( int $user_id, string $type, string $description, $meta = null ) {
		global $wpdb;

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'wcgmd_user_activity',
			[
				'user_id'     => $user_id,
				'type'        => $type,
				'description' => $description,
				'meta'        => $meta ? wp_json_encode( $meta ) : null,
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get notifications for a user.
	 *
	 * @param int  $user_id     WordPress user ID.
	 * @param bool $unread_only Return only unread notifications.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_notifications( int $user_id, bool $unread_only = false ): array {
		global $wpdb;
		$table      = $wpdb->prefix . 'wcgmd_notifications';
		$unread_sql = $unread_only ? 'AND is_read = 0' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d {$unread_sql} ORDER BY created_at DESC",
				$user_id
			)
		);

		return array_map(
			fn( $row ) => [
				'id'        => (int) $row->id,
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
	 * @param int $notification_id Notification ID.
	 * @param int $user_id         WordPress user ID (ownership check).
	 * @return bool
	 */
	public static function mark_notification_read( int $notification_id, int $user_id ): bool {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'wcgmd_notifications',
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

		return false !== $result;
	}

	/**
	 * Mark all notifications as read for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int Number of rows updated.
	 */
	public static function mark_all_notifications_read( int $user_id ): int {
		global $wpdb;

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'wcgmd_notifications',
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

	/**
	 * Default settings values.
	 *
	 * @return array<string, mixed>
	 */
	private static function default_settings(): array {
		return [
			'emailNotifications' => true,
			'marketingEmails'    => false,
			'dashboardTheme'     => 'light',
			'language'           => 'en',
		];
	}
}
