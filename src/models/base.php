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

class ModelBase {
	public $table_name;
	public $primary_key;

	public function __construct($values = array())
	{
		if (array_key_exists("id", $values)) {
			$this->primary_key = intval($values['id']);
		}

		if ($this->primary_key && count($values) == 1) {
			// Table and column names cannot be replaced by parameters in PDO.
			// See: http://us3.php.net/manual/en/book.pdo.php#69304
			$query = "SELECT * FROM $this->table_name WHERE `id` = :id";
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

	static public function exists($id)
	{
		$db = new SMNetProfDatabase();
		$exists = count($db->execute_query("SELECT * FROM $this->table_name WHERE `id` = :id", array($id))) > 0;
		return $exists;
	}

	public function save()
	{
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

		if ($this->primary_key) {
			$set_vars = array();
			foreach ($new_values as $key => $value) {
				$set_vars[] = "$key=:$key"; 
				$params[':' . $key] = $value;
			}

			$set_str = implode(", ", $set_vars);
			$query = "UPDATE $this->table_name SET $set_str WHERE `id` = :id";
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
			$query = "INSERT INTO $this->table_name ($fields_str)
					  VALUES ($params_str)";
		}

		$db = new SMNetProfDatabase();
		$db->execute_update($query, $params, $insert_id);
		if (!$this->primary_key) {
			$this->primary_key = intval($insert_id);
		}

		return true;
	}

	public function delete()
	{
		if ($this->primary_key) {
			$query = "DELETE FROM $this->table_name WHERE id = :id";
			$params = array("id" => $this->primary_key);
			$db = new SMNetProfDatabase();
			$db->execute_update($query, $params);
		} else {
			throw new NotFoundException();
		}
	}
}

?>
