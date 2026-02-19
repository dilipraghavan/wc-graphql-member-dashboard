<?php
/**
 * GraphQL Mutation Registry.
 *
 * @package WpShiftStudio\WCGraphQLMemberDashboard
 */

namespace WpShiftStudio\WCGraphQLMemberDashboard\GraphQL;

use WpShiftStudio\WCGraphQLMemberDashboard\Data\UserData;

/**
 * Class MutationRegistry
 *
 * Registers all custom WPGraphQL mutations.
 * Phase 2: Full implementation with authentication and data writes.
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
	 * Get the currently authenticated user ID.
	 * Returns 0 if not authenticated.
	 *
	 * @return int
	 */
	private static function get_current_user_id(): int {
		return get_current_user_id();
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
				'description'         => __( "Update the authenticated user's profile fields.", 'wc-graphql-member-dashboard' ),
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
					'errors'  => [
						'type'        => [ 'list_of' => 'String' ],
						'description' => __( 'Validation error messages, if any', 'wc-graphql-member-dashboard' ),
					],
				],
				'mutateAndGetPayload' => static function ( array $input ): array {
					$user_id = self::get_current_user_id();

					if ( ! $user_id ) {
						return [
							'success' => false,
							'profile' => null,
							'errors'  => [ __( 'You must be logged in to update your profile.', 'wc-graphql-member-dashboard' ) ],
						];
					}

					// Sanitise inputs.
					$data = [];
					if ( isset( $input['bio'] ) ) {
						$data['bio'] = sanitize_textarea_field( $input['bio'] );
					}
					if ( isset( $input['phone'] ) ) {
						$data['phone'] = sanitize_text_field( $input['phone'] );
					}
					if ( isset( $input['location'] ) ) {
						$data['location'] = sanitize_text_field( $input['location'] );
					}
					if ( isset( $input['website'] ) ) {
						$data['website'] = esc_url_raw( $input['website'] );
					}
					if ( isset( $input['socialLinks'] ) && is_array( $input['socialLinks'] ) ) {
						$data['socialLinks'] = array_map( 'esc_url_raw', $input['socialLinks'] );
					}

					$updated = UserData::update_profile( $user_id, $data );

					// Log the activity.
					UserData::log_activity( $user_id, 'profile', 'Profile updated via dashboard' );

					return [
						'success' => true,
						'profile' => $updated,
						'errors'  => [],
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
				'description'         => __( "Update the authenticated user's dashboard settings.", 'wc-graphql-member-dashboard' ),
				'inputFields'         => [
					'emailNotifications' => [ 'type' => 'Boolean' ],
					'marketingEmails'    => [ 'type' => 'Boolean' ],
					'dashboardTheme'     => [ 'type' => 'String' ],
					'language'           => [ 'type' => 'String' ],
				],
				'outputFields'        => [
					'success'  => [ 'type' => 'Boolean' ],
					'settings' => [ 'type' => 'MemberSettings' ],
					'errors'   => [ 'type' => [ 'list_of' => 'String' ] ],
				],
				'mutateAndGetPayload' => static function ( array $input ): array {
					$user_id = self::get_current_user_id();

					if ( ! $user_id ) {
						return [
							'success'  => false,
							'settings' => null,
							'errors'   => [ __( 'You must be logged in to update your settings.', 'wc-graphql-member-dashboard' ) ],
						];
					}

					// Validate dashboardTheme.
					if ( isset( $input['dashboardTheme'] ) && ! in_array( $input['dashboardTheme'], [ 'light', 'dark' ], true ) ) {
						return [
							'success'  => false,
							'settings' => null,
							'errors'   => [ __( 'Invalid dashboardTheme. Must be "light" or "dark".', 'wc-graphql-member-dashboard' ) ],
						];
					}

					$data = array_intersect_key(
						$input,
						array_flip( [ 'emailNotifications', 'marketingEmails', 'dashboardTheme', 'language' ] )
					);

					if ( isset( $data['language'] ) ) {
						$data['language'] = sanitize_text_field( $data['language'] );
					}

					$updated = UserData::update_settings( $user_id, $data );

					// Log the activity.
					UserData::log_activity( $user_id, 'settings', 'Dashboard settings updated' );

					return [
						'success'  => true,
						'settings' => $updated,
						'errors'   => [],
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
					'errors'       => [ 'type' => [ 'list_of' => 'String' ] ],
				],
				'mutateAndGetPayload' => static function ( array $input ): array {
					$user_id = self::get_current_user_id();

					if ( ! $user_id ) {
						return [
							'success'      => false,
							'notification' => null,
							'errors'       => [ __( 'You must be logged in.', 'wc-graphql-member-dashboard' ) ],
						];
					}

					$notification_id = absint( $input['notificationId'] );
					$updated         = UserData::mark_notification_read( $notification_id, $user_id );

					if ( ! $updated ) {
						return [
							'success'      => false,
							'notification' => null,
							'errors'       => [ __( 'Notification not found or already read.', 'wc-graphql-member-dashboard' ) ],
						];
					}

					return [
						'success'      => true,
						'notification' => $updated,
						'errors'       => [],
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
					'errors'  => [ 'type' => [ 'list_of' => 'String' ] ],
				],
				'mutateAndGetPayload' => static function ( array $input ): array {
					$user_id = self::get_current_user_id();

					if ( ! $user_id ) {
						return [
							'success' => false,
							'count'   => 0,
							'errors'  => [ __( 'You must be logged in.', 'wc-graphql-member-dashboard' ) ],
						];
					}

					$count = UserData::mark_all_notifications_read( $user_id );

					return [
						'success' => true,
						'count'   => $count,
						'errors'  => [],
					];
				},
			]
		);
	}
}
