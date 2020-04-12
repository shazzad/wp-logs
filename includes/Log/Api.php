<?php
namespace W4dev\Loggable\Log;

use W4dev\Loggable\AbstractCrudApi;

class Api extends AbstractCrudApi
{
	public function __construct()
	{
		$this->key = 'log';
		$this->name = __('Log', 'w4_loggable');
		$this->key_plural = 'logs';
		$this->name_plural = __('Logs', 'w4_loggable');
		$this->model_class_name = 'W4dev\Loggable\Log\Data';
		$this->query_class_name = 'W4dev\Loggable\Log\Query';
	}
}
