<?php
/* ------- The only class that handles all methods of the library ------- */
class DataBase
{
	/**
	* @var PDO instance
	*/
	private $connection;

	/**
	* Appends or prepands specified string to a specified string
	* @param $text string - string to append or prepand to
	* @param $add string - string to be appended or prepanded to the spedified string
	* @param $type string - append or prepand
	*/
	private static function addString($text, $add, $type = 'prepand')
	{
	if ($type == 'prepand') {
        	return $add . $text;
    	} else if ($type == 'append') {
        	return $text . $add;
    	} else {
        	throw new \Exception("Internal error.");
    	}
  	}

	/**
	* Constructor takes a PDO instance
	*/
	public function __Construct($PDO)
	{
		$this->connection = $PDO;
	}

	/**
	* Inserts into a user provided table
	* @param string $table - table to insert data in
	* @param array $keys_and_values - data to insert
	* @return bool. True in case of success, otherwise false
	*/
	public function insert($table,$keys_and_values)
	{
		// Check if the parameters are not null. If they are, throw an exception
		if (is_null($table) || is_null($keys_and_values)) {
			throw new \Exception("Enough data was not provided.");
		}

		// Start building SQL the query
		$sql = "INSERT INTO $table (" . implode(', ', array_keys($keys_and_values)) . ") VALUES ";

		// Create a new array
		$newArray = [];

		// Iterate through the $keys_and_values array and fill the $newArray with its keys and values. Prepand ':' to each key
		foreach ($keys_and_values as $key => $value) {
			$key = ':' . $key;
			$newArray[$key] = $value;
		}

		// Finish builing our SQL query.
		$sql .= "(" . implode(", ", array_keys($newArray)) . ")";

		// Prepare the statement
		$stmt = $this->connection->prepare($sql);

		// Bind values to each keys in the query
		foreach ($newArray as $key => $value) {
			$stmt->bindValue($key, $value);
		}

		// Execute the query
		if ($stmt->execute()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* Realises a select query.
	* @param $what array - what columns to select 
	* @param $from string - the table name from which to select
	* @param $where string (optional) - where clause
	* @param $limit integer (optional) - limit of rows
	* It's a generator, so each time a row is obtained, it yields it.
	*/
	public function select($what, $from, $where="", $limit=null)
	{
		// Table name and columns to select are required. Throw an exception if a user does not provide them
		if (is_null($from) || is_null($what)) {
			throw new \Exception("Too few arguments.");
		}

		// In case of selecting multiple rows and not '*'
		if (count($what) > 1) {

			// Without where clause and limit
			if ($where == "" && $limit == null) {

				// Build an sql query by creating a string of array keys seperated by commas
				$sql = "SELECT " . implode(", ", $what) . " FROM $from";

				// Each time a new row is obtained from the database, yield it instantly
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			} else if ($where != "" && $limit == null) { // Same with where clause and without count
				$sql = "SELECT " . implode(", ", $what) . " FROM $from WHERE $where";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			} else if ($where == "" && $limit != null) { // Same without where clause and with count
				$sql = "SELECT " . implode(", ", $what) . " FROM $from LIMIT $limit";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			} else { // Same with where clause and count
				$sql = "SELECT " . implode(", ", $what) . " FROM $from WHERE $where LIMIT $limit";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			}
		} else { // Same, in case of selecting everything with '*' or selecting only one row
			if ($where == "" && $limit == null) {
				$sql = "SELECT " . $what[0] . " FROM $from";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			} else if ($where != "" && $limit == null) {
				$sql = "SELECT " . $what[0] . " FROM $from WHERE $where";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			} else if ($where == "" && $limit != null) {
				$sql = "SELECT " . $what[0] . " FROM $from LIMIT $limit";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			} else {
				$sql = "SELECT " . $what[0] . " FROM $from WHERE $where LIMIT $limit";
				foreach ($this->connection->query($sql) as $row) {
					yield $row;
				}
			}
		}
	}

	/**
	* Updates rows in a database table
	* @param $table string - table to update
	* @param $data associative array - array containing keys and values of the table columns
	* @param $where (optional) string - where clause
	* @return bool - true in case of success, otherwise false
	*/
	public function update($table, $data, $where="")
	{
		// Table name and data array are required. Leaving them out makes the update function throw an exception
		if (is_null($table) && is_null($data)) {
			throw new \Exception("Too few arguments.");
		}

		// We will need this array later
		$processed_data = [];

		// Counter
		$i = 0;

		// Length of the data array
		$len = count($data);

		// Iterate through the data array. Modify each element so they look like: key = 'value' AND anotherKey = 'anotherValue'
		foreach ($data as $key => $value) {

			// Prepand quote to the current value
			$first_changed_value = self::addString($value, "'", $type = 'prepand');

			// Append quote to the current value
			$second_changed_value = self::addString($first_changed_value, "'", $type = 'append');

			// If this is the last element from the array, then don't appen a comma at the and of the element
			if ($i == $len - 1) {
				$processed_data[] = $key . " = " . $second_changed_value;
			} else {
				// Add a comma at the and of the element so we can then build a query
				$processed_data[] = $key . " = " . $second_changed_value . ", ";
			}
			// Increment the counter
			$i++; 
		}

		// Build the query, based on the data provided by user
		$sql = "UPDATE $table SET ";
		foreach ($processed_data as $row) {
			$sql .= $row;
		}

		// With where clause, if it is provided
		if ($where != "") {
			$sql .= " WHERE $where";
		}

		// Return true in case of successful execution of the query, otherwise false
		if($this->connection->query($sql)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* Deletes from a user provided table based on user provided input
	* @param $from string table - from which table to delete
	* @param $where string - where clause
	* @return bool - true in case of success, false otherwise
	*/
	public function delete($from, $where)
	{

		// Table name and where clause are required. Their absence makes this function throw an exception.
		if (is_null($from) || is_null($where)) {
			throw new \Exception("Too few parameters.");
		}

		$sql = "DELETE FROM $from WHERE $where";
		if ($this->connection->query($sql)) {
			return true;
		} else {
			return false;
		}
	}
}


?>
