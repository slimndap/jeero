<?php
/**
 * WP-CLI commands for disposable Jeero guide-test imports.
 */
namespace Jeero\Cli;

use Jeero\Test_Cleanup;

class Test_Cleanup_Command {

	/**
	 * Mark an import before running a guide test.
	 *
	 * ## OPTIONS
	 *
	 * <subscription-id>
	 * : The Jeero subscription ID created for this test.
	 */
	public function mark( $args ) {
		if ( 'local' !== wp_get_environment_type() ) {
			\WP_CLI::error( 'Test imports can only be marked in the local WordPress environment.' );
		}
		if ( ! \Jeero\Db\Subscriptions\get_subscription( $args[0] ) ) {
			\WP_CLI::error( 'Cannot mark an unknown subscription. Create and sync the test import first.' );
		}

		Test_Cleanup\mark( $args[0] );
		\WP_CLI::success( sprintf( 'Marked subscription %s as a guide-test import.', $args[0] ) );
	}

	/**
	 * Preview or permanently delete the local content of a marked test import.
	 *
	 * The command is a dry run unless --confirm is supplied.
	 *
	 * ## OPTIONS
	 *
	 * <subscription-id>
	 * : The marked Jeero subscription ID.
	 *
	 * [--confirm]
	 * : Permanently delete the listed posts and uniquely-owned attachments.
	 */
	public function cleanup( $args, $assoc_args ) {
		$subscription_id = $args[0];

		if ( ! Test_Cleanup\is_marked( $subscription_id ) ) {
			\WP_CLI::error( 'Refusing cleanup: this subscription is not marked as a guide-test import.' );
		}

		$plan = Test_Cleanup\get_plan( $subscription_id );
		\WP_CLI::log( sprintf( 'Posts: %s', empty( $plan['posts'] ) ? 'none' : implode( ', ', $plan['posts'] ) ) );
		\WP_CLI::log( sprintf( 'Unique attachments: %s', empty( $plan['attachments'] ) ? 'none' : implode( ', ', $plan['attachments'] ) ) );

		if ( ! isset( $assoc_args['confirm'] ) ) {
			\WP_CLI::success( 'Dry run complete. Re-run with --confirm to permanently delete this content.' );
			return;
		}

		$result = Test_Cleanup\cleanup( $subscription_id );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		\WP_CLI::success(
			sprintf(
				'Deleted %d posts and %d unique attachments for subscription %s.',
				count( $result['posts'] ),
				count( $result['attachments'] ),
				$subscription_id
			)
		);
	}
}

\WP_CLI::add_command( 'jeero test-import', __NAMESPACE__ . '\\Test_Cleanup_Command' );
