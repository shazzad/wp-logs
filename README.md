# Shazzad Wp Logs

A WordPress plugin that can store & display runtime log data. All of the log 
data are stored in additional database table.


### Save Log

Call the function `do_action` with appropriate parameter to store a log.

```php
do_action(
	'swpl_log',
	// string, usually a name from where you are storing this log
	$source,
	// string, log message
	$message,
	// array, a data that can be replaced with placeholder inside message.
	$context
);
```

### View Log

All logs can be viewed at `Wp Admin > Tools > Logs` page.

### Requirements
* WordPress: 5.0
* PHP: 5.7
* Tested: 5.7.2
