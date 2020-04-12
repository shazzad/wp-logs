# W4 loggable

A WordPress plugin that can store & display log.


### Store Log

Call the function `do_action` with appropriate parameter to store a log.

```php
do_action(
	'w4_loggable_log',
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
