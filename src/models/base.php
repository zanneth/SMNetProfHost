<?php
/*
 * base.php
 *
 * Author: Charles Magahern <charles@magahern.com>
 * Date Created: 02/11/2013
 */

require_once "src/database.php";
require_once "src/util.php";

class NotFoundException extends Exception {}

/* Quick-and-dirty database abstraction model */
class ModelBase {
	public $primary_key;

	public function __construct($values = array())
	{
		if (array_key_exists("id", $values)) {
			$this->primary_key = intval($values['id']);
		}

		if ($this->primary_key && count($values) == 1) {
			// Table and column names cannot be replaced by parameters in PDO.
			// See: http://us3.php.net/manual/en/book.pdo.php#69304
			$query = sprintf("SELECT * FROM %s WHERE `id` = :id", $this->get_table_name());
			$params = array(":id" => $this->primary_key);
			$db = new SMNetProfDatabase();
			$results = $db->execute_query($query, $params);

			if (count($results) > 0) {
				$values = $results[0];
			} else {
				throw new NotFoundException();
			}
		}

		foreach ($this as $var => &$value) {
			$key = $var;
			if ($key[0] == '_') {
				$key = substr($key, 1);
			}

			if (!is_null($values) && array_key_exists($key, $values)) {
				$value = $values[$key];
			}
		}
	}

	static function get_table_name()
	{
		// subclasses must override
		return NULL;
	}

	static function exists($id)
	{
		$db = new SMNetProfDatabase();
		$query = sprintf("SELECT * FROM %s WHERE `id` = :id", $this->get_table_name());
		$exists = count($db->execute_query($query, array($id))) > 0;
		return $exists;
	}

	public function save()
	{
		$is_updating = isset($this->primary_key);
		if (!$is_updating) {
			$this->before_create();
		}

		// Determine which values need to be updated
	    $new_values = array();
		foreach ($this as $var => $value) {
			if ($var == "primary_key" || $var == "table_name") continue;
			if (!is_null($value)) {
				$column = $var;
				if ($column[0] == '_') {
					$column = substr($column, 1);
				}

				$new_values[$column] = $value;
			}
		}

		// Setup the parameters and the query
		$query = NULL;
		$params = array();

		if ($is_updating) {
			$set_vars = array();
			foreach ($new_values as $key => $value) {
				$set_vars[] = "$key=:$key"; 
				$params[':' . $key] = $value;
			}

			$set_str = implode(", ", $set_vars);
			$query = sprintf("UPDATE %s SET $set_str WHERE `id` = :id", $this->get_table_name());
			$params[":id"] = $this->primary_key;
		} else {
			$fields_arr = array();
			$params_arr = array();
			foreach ($new_values as $key => $value) {
				$fields_arr[] = $key;
				$params_arr[] = ':' . $key;
				$params[':' . $key] = $value;
			}

			$fields_str = implode(", ", $fields_arr);
			$params_str = implode(", ", $params_arr);
			$query = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->get_table_name(), $fields_str, $params_str);
		}

		$db = new SMNetProfDatabase();
		$success = $db->execute_update($query, $params, $insert_id);
		if (!$this->primary_key) {
			$this->primary_key = intval($insert_id);
		}

		if ($success) {
			if (!$is_updating) {
				$this->after_create();
			}

			$this->on_save();
		} else {
			error_log(sprintf("Failed to save model: %s", Util::get_description($this)));
		}

		return $success;
	}

	public function delete()
	{
		if ($this->primary_key) {
			$query = sprintf("DELETE FROM %s WHERE id = :id", $this->get_table_name());
			$params = array("id" => $this->primary_key);
			$db = new SMNetProfDatabase();
			$db->execute_update($query, $params);
		} else {
			throw new NotFoundException();
		}
	}

	public function property_list_markup()
	{
		$rows_markup = "";
		foreach ($this as $key => $value) {
			$key_markup = "<td class='property-table-key'>$key</td>";
			$value_markup = "<td class='property-table-value'>$value</td>";
			$row_markup = sprintf("<tr>%s%s</tr>", $key_markup, $value_markup);
			$rows_markup .= $row_markup;
		}
		$markup = sprintf("<table class='property-table'>%s</table>", $rows_markup);
		return $markup;
	}

	protected function before_create() {}
	protected function after_create() {}
	protected function on_save() {}
}

?>
