<?php
/**
 * WP-CLI command to generate dummy log entries.
 *
 * Uses Faker to create a specified number of log records for testing the log system.
 *
 * @package Shazzad\WpLogs\Commands
 * @since 1.0.0
 */
namespace Shazzad\WpLogs\Commands;

use WP_CLI;
use Faker\Factory;

/**
 * Class GenerateLogsCommand
 *
 * Implements the 'swpl-generate-logs' WP-CLI command for generating dummy logs.
 *
 * @package Shazzad\WpLogs\Commands
 * @since 1.0.0
 */
class GenerateLogsCommand {

	/**
	 * Handle the WP-CLI 'swpl-generate-logs' command invocation.
	 *
	 * @param array $args Positional command arguments.
	 * @param array $assoc_args Associative command arguments; supports 'count' (int).
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) {
		$count = isset( $assoc_args['count'] ) ? (int) $assoc_args['count'] : 1000;

		// Initialize Faker
		$faker = Factory::create();

		$companies = [];
		// Generate a list of unique company names
		for ( $i = 0; $i < 5; $i++ ) {
			$companies[] = $faker->company();
		}

		for ( $i = 0; $i < $count; $i++ ) {
			// Use company() method directly, not company()->name()
			$source = $faker->randomElement( $companies );

			$message = $faker->sentence();

			$data = [
				'key1'          => $faker->uuid(),
				'key2'          => $faker->randomNumber( 2 ),
				'large_payload' => $faker->paragraphs( 20, true ), // Generating a large payload around 10kb
			];

			$level = $faker->randomElement( [ 'info', 'warning', 'error' ] );

			do_action( 'swpl_log', $source, $message, $data, $level );
			if ( $i % 1000 == 0 ) {
				WP_CLI::line( "Generated $i records..." );
			}
		}

		WP_CLI::success( "$count log records generated successfully." );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'swpl-generate-logs', 'Shazzad\WpLogs\Commands\GenerateLogsCommand' );
}
