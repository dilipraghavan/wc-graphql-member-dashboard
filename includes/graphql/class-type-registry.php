<?php
/**
 * GraphQL Type Registry.
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard\GraphQL;

use WpShiftStudio\WCGraphQLMemberDashboard\Data\UserData;

/**
 * Class TypeRegistry
 *
 * Registers all custom WPGraphQL object types and fields.
 * Phase 2: Full resolver implementation backed by UserData DAL.
 */
class TypeRegistry {

	/**
	 * Register all custom GraphQL types.
	 * Hooked into: graphql_register_types
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_user_profile_type();
		$this->register_user_activity_type();
		$this->register_notification_type();
		$this->register_user_settings_type();
		$this->register_member_stats_type();
		$this->extend_user_type();
	}

	/**
	 * Register MemberProfile type.
	 *
	 * @return void
	 */
	private function register_user_profile_type(): void {
		register_graphql_object_type(
			'MemberProfile',
			[
				'description' => __( 'Extended member profile data', 'wc-graphql-member-dashboard' ),
				'fields'      => [
					'bio'         => [
						'type'        => 'String',
						'description' => __( 'User biography', 'wc-graphql-member-dashboard' ),
					],
					'avatarUrl'   => [
						'type'        => 'String',
						'description' => __( 'Custom avatar URL', 'wc-graphql-member-dashboard' ),
					],
					'phone'       => [
						'type'        => 'String',
						'description' => __( 'Contact phone number', 'wc-graphql-member-dashboard' ),
					],
					'location'    => [
						'type'        => 'String',
						'description' => __( 'User location / city', 'wc-graphql-member-dashboard' ),
					],
					'website'     => [
						'type'        => 'String',
						'description' => __( 'Personal website URL', 'wc-graphql-member-dashboard' ),
					],
					'socialLinks' => [
						'type'        => [ 'list_of' => 'String' ],
						'description' => __( 'Social media profile URLs', 'wc-graphql-member-dashboard' ),
					],
				],
			]
		);
	}

	/**
	 * Register MemberActivity type.
	 *
	 * @return void
	 */
	private function register_user_activity_type(): void {
		register_graphql_object_type(
			'MemberActivity',
			[
				'description' => __( 'A single user activity log entry', 'wc-graphql-member-dashboard' ),
				'fields'      => [
					'id'          => [
						'type'        => 'ID',
						'description' => __( 'Activity entry ID', 'wc-graphql-member-dashboard' ),
					],
					'type'        => [
						'type'        => 'String',
						'description' => __( 'Activity type (login, profile, settings, etc.)', 'wc-graphql-member-dashboard' ),
					],
					'description' => [
						'type'        => 'String',
						'description' => __( 'Human-readable description of the activity', 'wc-graphql-member-dashboard' ),
					],
					'createdAt'   => [
						'type'        => 'String',
						'description' => __( 'ISO 8601 timestamp of the activity', 'wc-graphql-member-dashboard' ),
					],
				],
			]
		);
	}

	/**
	 * Register MemberNotification type.
	 *
	 * @return void
	 */
	private function register_notification_type(): void {
		register_graphql_object_type(
			'MemberNotification',
			[
				'description' => __( 'A user notification', 'wc-graphql-member-dashboard' ),
				'fields'      => [
					'id'        => [
						'type'        => 'ID',
						'description' => __( 'Notification ID', 'wc-graphql-member-dashboard' ),
					],
					'type'      => [
						'type'        => 'String',
						'description' => __( 'Notification type (info, success, warning, error)', 'wc-graphql-member-dashboard' ),
					],
					'title'     => [
						'type'        => 'String',
						'description' => __( 'Notification title', 'wc-graphql-member-dashboard' ),
					],
					'message'   => [
						'type'        => 'String',
						'description' => __( 'Notification body text', 'wc-graphql-member-dashboard' ),
					],
					'isRead'    => [
						'type'        => 'Boolean',
						'description' => __( 'Whether the notification has been read', 'wc-graphql-member-dashboard' ),
					],
					'createdAt' => [
						'type'        => 'String',
						'description' => __( 'ISO 8601 timestamp', 'wc-graphql-member-dashboard' ),
					],
					'readAt'    => [
						'type'        => 'String',
						'description' => __( 'ISO 8601 timestamp when notification was read', 'wc-graphql-member-dashboard' ),
					],
				],
			]
		);
	}

	/**
	 * Register MemberSettings type.
	 *
	 * @return void
	 */
	private function register_user_settings_type(): void {
		register_graphql_object_type(
			'MemberSettings',
			[
				'description' => __( 'User dashboard preferences and settings', 'wc-graphql-member-dashboard' ),
				'fields'      => [
					'emailNotifications' => [
						'type'        => 'Boolean',
						'description' => __( 'Receive email notifications', 'wc-graphql-member-dashboard' ),
					],
					'marketingEmails'    => [
						'type'        => 'Boolean',
						'description' => __( 'Receive marketing emails', 'wc-graphql-member-dashboard' ),
					],
					'dashboardTheme'     => [
						'type'        => 'String',
						'description' => __( 'Preferred dashboard theme (light/dark)', 'wc-graphql-member-dashboard' ),
					],
					'language'           => [
						'type'        => 'String',
						'description' => __( 'Preferred language code', 'wc-graphql-member-dashboard' ),
					],
				],
			]
		);
	}

	/**
	 * Register MemberStats type — computed dashboard statistics.
	 *
	 * @return void
	 */
	private function register_member_stats_type(): void {
		register_graphql_object_type(
			'MemberStats',
			[
				'description' => __( 'Computed statistics for the member dashboard', 'wc-graphql-member-dashboard' ),
				'fields'      => [
					'activityCount'       => [
						'type'        => 'Int',
						'description' => __( 'Total number of activity log entries', 'wc-graphql-member-dashboard' ),
					],
					'unreadNotifications' => [
						'type'        => 'Int',
						'description' => __( 'Number of unread notifications', 'wc-graphql-member-dashboard' ),
					],
					'orderCount'          => [
						'type'        => 'Int',
						'description' => __( 'Total WooCommerce orders placed', 'wc-graphql-member-dashboard' ),
					],
					'totalSpent'          => [
						'type'        => 'String',
						'description' => __( 'Total amount spent (formatted decimal)', 'wc-graphql-member-dashboard' ),
					],
					'membershipStatus'    => [
						'type'        => 'String',
						'description' => __( 'Current membership status', 'wc-graphql-member-dashboard' ),
					],
					'profileCompleteness' => [
						'type'        => 'Int',
						'description' => __( 'Profile completeness percentage (0-100)', 'wc-graphql-member-dashboard' ),
					],
				],
			]
		);
	}

	/**
	 * Extend the WPGraphQL User type with our custom fields.
	 *
	 * @return void
	 */
	private function extend_user_type(): void {

		// Member profile field.
		register_graphql_field(
			'User',
			'memberProfile',
			[
				'type'        => 'MemberProfile',
				'description' => __( 'Extended member profile data', 'wc-graphql-member-dashboard' ),
				'resolve'     => static function ( $user ): array {
					return UserData::get_profile( $user->userId );
				},
			]
		);

		// Member settings field.
		register_graphql_field(
			'User',
			'memberSettings',
			[
				'type'        => 'MemberSettings',
				'description' => __( 'User dashboard preferences', 'wc-graphql-member-dashboard' ),
				'resolve'     => static function ( $user ): array {
					return UserData::get_settings( $user->userId );
				},
			]
		);

		// Activity log field.
		register_graphql_field(
			'User',
			'memberActivity',
			[
				'type'        => [ 'list_of' => 'MemberActivity' ],
				'description' => __( 'User activity log entries', 'wc-graphql-member-dashboard' ),
				'args'        => [
					'limit' => [
						'type'        => 'Int',
						'description' => __( 'Number of entries to return (default 10)', 'wc-graphql-member-dashboard' ),
					],
				],
				'resolve'     => static function ( $user, array $args ): array {
					$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 10;
					return UserData::get_activity( $user->userId, $limit );
				},
			]
		);

		// Notifications field.
		register_graphql_field(
			'User',
			'memberNotifications',
			[
				'type'        => [ 'list_of' => 'MemberNotification' ],
				'description' => __( 'User notifications', 'wc-graphql-member-dashboard' ),
				'args'        => [
					'unreadOnly' => [
						'type'        => 'Boolean',
						'description' => __( 'Return only unread notifications', 'wc-graphql-member-dashboard' ),
					],
				],
				'resolve'     => static function ( $user, array $args ): array {
					$unread_only = ! empty( $args['unreadOnly'] );
					return UserData::get_notifications( $user->userId, $unread_only );
				},
			]
		);

		// Member stats field.
		register_graphql_field(
			'User',
			'memberStats',
			[
				'type'        => 'MemberStats',
				'description' => __( 'Computed dashboard statistics for this member', 'wc-graphql-member-dashboard' ),
				'resolve'     => static function ( $user ): array {
					return UserData::get_stats( $user->userId );
				},
			]
		);
	}
}
