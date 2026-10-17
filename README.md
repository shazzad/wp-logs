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

## Debug Log Exposure Check

On the `Wp Admin > Logs` screen, the plugin sends a HEAD request from the site to the URL of
`debug.log` (resolved from `WP_DEBUG_LOG`, mapped to a URL only when the file sits under
`WP_CONTENT_DIR`, `ABSPATH` or the server document root). A dismissible notice with Apache and
nginx deny rules appears only when the log answers HTTP 200, the answer is not served as
`text/html`, and a second HEAD request, for a file that does not exist next to the log, gets
HTTP 403, 404 or 410. Any other outcome is recorded as "unknown" and shows nothing: a 200 served
as HTML is usually a custom error or login page, and a control request that fails, times out, is
rate limited or returns another status cannot show that the server does not answer 200 for every
path. "Unknown" never means safe. The plugin never writes server configuration. Results are
cached for a day.

A request from the site to itself can be answered differently from a visitor's, so turn the
check off on hosts where that makes it meaningless:

```php
add_filter( 'swpl_debug_log_exposure_check', '__return_false' );
```

Override the URL that is requested (return an empty string to treat the log as not web-reachable):

```php
add_filter( 'swpl_debug_log_exposure_url', function ( $url, $path ) {
	return $url;
}, 10, 2 );
```

## View Log

All logs can be viewed at `Wp Admin > Logs` page.

### Requirements

* WordPress: 6.2
* PHP: 7.4
* Tested: 7.1
