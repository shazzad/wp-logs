# WP Logs

A WordPress plugin that can store & display runtime log data. All of the log 
data are stored in additional database table.


## Save Log

Call the function `do_action` with appropriate parameter to store a log.

```php
do_action(
	'swpl_log',

	// $source | string | a name from where the log is stored
	'Example Plugin',

	// $message | string | log message
	'{user} updated his profile',

	// $context | array | a data that can be replaced with placeholder inside message.
	array(
		'user' => 'Some User'
	)
);
```

## Register Log menu

One can register zero to multiple menu items to display logs. Unless `parent_slug` 
value is defined, the log will be displayed as a submenu under this plugins admin menu.

```php
add_filter( 'swpl_menu_items', function( $menu_items ){

	$menu_items['my-example-logs'] = array(
	
		// Logs menu label
		'menu_title'  => __( 'Logs' ),

		// Logs page title.
		'page_title'  => __( 'Logs' ),
		
		// Label displayed on admin bar menu
		'bar_menu_title'  => __( 'Example Plugin' ),
		
		// You plugin's admin menu page name / slug
		'parent_slug' => 'example-plugin-options',
		
		// Log page access capability
		'capability'  => 'manage_options',
		
		// Limit showing logs from give sources
		'sources'     => array( 'Example Plugin' ),
	);

	return $menu_items;
});
```

## View Log

All logs can be viewed at `Wp Admin > Logs` page.

### Requirements

* WordPress: 5.0
* PHP: 5.7
* Tested: 5.7.2
