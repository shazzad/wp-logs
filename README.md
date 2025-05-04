# WP Logs

A WordPress plugin that can store & display runtime log data. Logs
data are stored in custom database table.

## Save Log

Call the function `do_action` with appropriate parameter to store a log.

```php
do_action(
	'swpl_log',

	// $source | string | a name from where the log is stored.
	'Example Plugin',

	// $message | string | log message.
	'{{user}} updated his profile',

	// $context | array | a data that can be replaced with placeholder inside message.
	array(
		'user' => 'Some User'
	)
);
```

## Save HTTP Requests.

Add the following code to your plugin or theme to log HTTP requests for specific URLs.

```php
add_filter( 'swpl_log_request', function ( $enabled, $url ) {
	$target_urls = [
		'https://example.com',
		'https://wordpress.org',
		'https://api.wordpress.org',
		get_option( 'api_endpoint' ),
	];

	foreach ( $target_urls as $target_url ) {
		if ( 0 === strpos( $url, $target_url ) ) {
			return true;
		}
	}

	return $enabled;
}, 10, 2 );
```

## View Log

All logs can be viewed at `Wp Admin > Logs` page.

### Requirements

- WordPress: 5.0
- PHP: 5.7
- Tested: 6.0.1
