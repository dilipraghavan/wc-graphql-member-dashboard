<?php
/**
 * GraphQL Mutation Registry.
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard\GraphQL;

/**
 * Class MutationRegistry
 *
 * Registers all custom WPGraphQL mutations.
 * Fully implemented in Phase 3.
 */
class MutationRegistry {

	/**
	 * Register all custom GraphQL mutations.
	 * Hooked into: graphql_register_types
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_update_profile_mutation();
		$this->register_update_settings_mutation();
		$this->register_mark_notification_read_mutation();
		$this->register_mark_all_notifications_read_mutation();
	}

	/**
	 * Mutation: updateMemberProfile
	 *
	 * @return void
	 */
	private function register_update_profile_mutation(): void {
		register_graphql_mutation(
			'updateMemberProfile',
			[
				'description'         => __( 'Update the authenticated user\'s profile fields.', 'wc-graphql-member-dashboard' ),
				'inputFields'         => [
					'bio'         => [ 'type' => 'String' ],
					'phone'       => [ 'type' => 'String' ],
					'location'    => [ 'type' => 'String' ],
					'website'     => [ 'type' => 'String' ],
					'socialLinks' => [ 'type' => [ 'list_of' => 'String' ] ],
				],
				'outputFields'        => [
					'success' => [
						'type'        => 'Boolean',
						'description' => __( 'Whether the update was successful', 'wc-graphql-member-dashboard' ),
					],
					'profile' => [
						'type'        => 'MemberProfile',
						'description' => __( 'The updated profile data', 'wc-graphql-member-dashboard' ),
					],
				],
				'mutateAndGetPayload' => function ( $input ) {
					// Phase 3: full implementation with auth checks.
					// Stubbed for Phase 1 bootstrap.
					return [
						'success' => false,
						'profile' => null,
					];
				},
			]
		);
	}

	/**
	 * Mutation: updateMemberSettings
	 *
	 * @return void
	 */
	private function register_update_settings_mutation(): void {
		register_graphql_mutation(
			'updateMemberSettings',
			[
				'description'         => __( 'Update the authenticated user\'s dashboard settings.', 'wc-graphql-member-dashboard' ),
				'inputFields'         => [
					'emailNotifications' => [ 'type' => 'Boolean' ],
					'marketingEmails'    => [ 'type' => 'Boolean' ],
					'dashboardTheme'     => [ 'type' => 'String' ],
					'language'           => [ 'type' => 'String' ],
				],
				'outputFields'        => [
					'success'  => [ 'type' => 'Boolean' ],
					'settings' => [ 'type' => 'MemberSettings' ],
				],
				'mutateAndGetPayload' => function ( $input ) {
					// Phase 3: full implementation.
					return [
						'success'  => false,
						'settings' => null,
					];
				},
			]
		);
	}

	/**
	 * Mutation: markNotificationRead
	 *
	 * @return void
	 */
	private function register_mark_notification_read_mutation(): void {
		register_graphql_mutation(
			'markNotificationRead',
			[
				'description'         => __( 'Mark a single notification as read.', 'wc-graphql-member-dashboard' ),
				'inputFields'         => [
					'notificationId' => [
						'type'        => [ 'non_null' => 'ID' ],
						'description' => __( 'The notification ID to mark as read', 'wc-graphql-member-dashboard' ),
					],
				],
				'outputFields'        => [
					'success'      => [ 'type' => 'Boolean' ],
					'notification' => [ 'type' => 'MemberNotification' ],
				],
				'mutateAndGetPayload' => function ( $input ) {
					// Phase 3: full implementation.
					return [
						'success'      => false,
						'notification' => null,
					];
				},
			]
		);
	}

	/**
	 * Mutation: markAllNotificationsRead
	 *
	 * @return void
	 */
	private function register_mark_all_notifications_read_mutation(): void {
		register_graphql_mutation(
			'markAllNotificationsRead',
			[
				'description'         => __( 'Mark all notifications as read for the authenticated user.', 'wc-graphql-member-dashboard' ),
				'inputFields'         => [],
				'outputFields'        => [
					'success' => [ 'type' => 'Boolean' ],
					'count'   => [
						'type'        => 'Int',
						'description' => __( 'Number of notifications marked as read', 'wc-graphql-member-dashboard' ),
					],
				],
				'mutateAndGetPayload' => function ( $input ) {
					// Phase 3: full implementation.
					return [
						'success' => false,
						'count'   => 0,
					];
				},
			]
		);
	}
}
