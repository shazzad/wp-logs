<?php
namespace Shazzad\WpLogs\Commands;

use WP_CLI;
use Faker\Factory;

class GenerateLogsCommand {
	public function __invoke( $args, $assoc_args ) {
		$count = isset( $assoc_args['count'] ) ? (int) $assoc_args['count'] : 1000;

		// Initialize Faker
		$faker = Factory::create();

		for ( $i = 0; $i < $count; $i++ ) {
			$source  = $faker->company;
			$message = $faker->sentence;
			$data    = [ 
				'key1'          => $faker->uuid,
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
