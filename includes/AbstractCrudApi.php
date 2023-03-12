<?php
namespace Shazzad\WpLogs;

use Exception;
use WP_Error;

abstract class AbstractCrudApi
{
	/* unique key */
	public $key;

	/* singular name of the object */
	public $name;

	/* plural format key */
	public $key_plural;

	/* plural name of the object */
	public $name_plural;

	/* pk_param */
	protected $pk_param = 'id';

	/* class that handles data manipulatation */
	protected $model_class_name;

	/* class that performs queries */
	protected $query_class_name;

	public function handle($action, $data)
	{
		if (method_exists($this, $action)) {
			return $this->$action($data);
		} else {
			return new WP_Error('apiError', __('Invalid request'), array('status' => 404));
		}
	}

	public function create($data)
	{
		if (!$this->can_create()) {
			return new WP_Error('restricted', __('Sorry, you can not perform this action'));
		}

		$data = $this->sanitize_data($data);

		try {
			$object = new $this->model_class_name();
			$object->set_props($data);
			$object->save();

			$return = $object->get_data();
		}
		catch (Exception $e) {
			$return = new WP_Error('apiError', $e->getMessage(), array('status' => 422));
		}

		return $return;
	}

	public function read($data)
	{
		if (!$this->can_read()) {
			return new WP_Error('restricted', __('Sorry, you can not perform read action'));
		}

		try {
			if (isset($data[$this->pk_param])) {
				$object = new $this->model_class_name((int) $data[$this->pk_param]);
			} else {
				$object = new $this->model_class_name($data);
			}

			if ($object->get_id() > 0) {
				$return = $object->get_data();
			} else {
				$return = new WP_Error('resourceNotExists', __('Requested resource does not exist.', 'served-admin'), array('status' => 404));
			}
		}
		catch (Exception $e) {
			$return = new WP_Error('apiError', $e->getMessage(), array('status' => 404));
		}

		return $return;
	}

	public function get($data)
	{
		return $this->read($data);
	}

	public function delete($data)
	{
		try {
			if (isset($data[$this->pk_param])) {
				$object = new $this->model_class_name((int) $data[$this->pk_param]);
			} else {
				$object = new $this->model_class_name($data);
			}

			if ($object->get_id() > 0) {
				if ($object->get_id() > 0 && $this->can_delete($object)) {
					$id = $object->get_id();
					$object->delete();
					return array('id' => $id);
				} else {
					return new WP_Error('noPermissing', __('You have no permission to delete this.', 'served-admin'), array('status' => 404));
				}
			} else {
				return new WP_Error('resourceNotExists', __('Requested resource does not exist.', 'served-admin'), array('status' => 404));
			}
		}
		catch (Exception $e) {
			return new WP_Error('apiError', $e->getMessage(), array('status' => 404));
		}
	}

	// batch action
	public function batch($data)
	{
		$return = array();
		foreach (array('delete', 'create', 'update') as $action) {
			if (isset($data[$action])) {
				if (!isset($return[$action])) {
					$return[$action] = array();
				}

				foreach ($data[$action] as $item) {
					$process = $this->{$action}($item);
					if (is_wp_error($process)) {
						$return[$action][] = array(
							'error'   => true,
							'message' => $process->get_error_message()
						);
					} else {
						$return[$action][] = $process;
					}
				}
			}
		}

		return $return;
	}

	public function update($data)
	{
		$data = $this->sanitize_data($data);

		$object = new $this->model_class_name((int) $data[$this->pk_param]);
		if (!$this->can_update($object)) {
			return new WP_Error('restricted', __('Sorry, you can not perform this action'));
		}

		try {
			$object->set_props($data);
			$object->save();

			$return = $object->get_data();
		}
		catch (Exception $e) {
			$return = new WP_Error('apiError', $e->getMessage(), array(
				'status' => 422
			));
		}

		return $return;
	}

	public function list($args, $paginate = false)
	{
		if (!$this->can_list()) {
			return new WP_Error('restricted', __('Sorry, you can not perform list action'));
		}

		$query = new $this->query_class_name($args);
		$query->query();

		$result = $query->get_results();

		$items = array();
		if (!empty($result)) {
			foreach ($result as $item) {
				if ($item instanceof $this->model_class_name) {
					$object = $item;
				} else {
					if (is_object($item)) {
						$item = get_object_vars($item);
					}
					$object = new $this->model_class_name($item[$this->pk_param]);
				}
				$items[] = $object->get_data();
			}
		}

		if ($paginate) {
			$return = (object) [
				'items'         => $items,
				'found_items'   => $query->found_items,
				'max_num_pages' => $query->max_num_pages
			];
		} else {
			$return = $items;
		}

		return $return;
	}

	protected function can_create()
	{
		return true;
	}

	protected function can_read()
	{
		return true;
	}

	protected function can_update($object)
	{
		return true;
	}

	protected function can_delete($object)
	{
		return true;
	}

	protected function can_list()
	{
		return true;
	}

	protected function sanitize_data($data)
	{
		return $data;
	}
}