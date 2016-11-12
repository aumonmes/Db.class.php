<?php
/* Class DB to extend MySQLi with new functionallities */
class Db extends mysqli{
	private $transaction_flag = false;
	private $transaction_error = 0;
	public $transaction_msg= [];

	/**
	**	Data selection
	**/

	/* Return a query's results as an array instead of a mysqli object */
	public function query_arr($query){
		$return = [];
		if($rows = $this->query($query)){
			while($rows && $r = $rows->fetch_assoc()) $return[] = $r;
			return $return;
		}else return false;
	}

	/* Returns a non associative array with the values of the first column of the result */
	public function query_col($str){
		$return = [];
		if($rows = $this->query($str)){
			while($rows && $r = $rows->fetch_row()) $return[] = $r[0];
			return $return;
		}else{
			if($this->transaction_flag) $this->trans_error();
			return false;
		}
	}

	/* Return only first row of a query as associative array returning null for an empty result */
	public function query_one($query){
		if($rows = $this->query($query)){
			$return = $rows->fetch_assoc();
			if(isset($return)) return $return;
			else return null;
		}else{
			if($this->transaction_flag) $this->trans_error();
			return false;
		}
	}

	/* Return only one value for a query, if there's more than one field returns the first one */
	public function query_val($query){
		$return = null;
		if($row = $this->query_one($query)){
			if(is_array($row)) $return = array_shift($row);
			return $return;
		}else return false;
	}


	/**
	**	Data manipulation
	**/

	/* Execute a query returning number of affected rows, it might return int(0) therefore use === when checking it's result */
	public function exec($query){
		if($this->query($query)) return $this->affected_rows;
		else{
			if($this->transaction_flag) $this->trans_error();
			return false;
		}
	}

	/* Insert one single row using an associative array */
	public function insert($table, $data){
		if(empty($data)) return false;

		$keys = [];
		$values = [];
		$table = $this->sanitize($table);

		foreach($data as $key => $val){
			$keys[] = $key;
			$values[] = "'".$this->sanitize($val)."'";
		}

		$sql = "INSERT INTO $table
				(".implode(",", $keys).")
				VALUES
				(".implode(",", $values).")";
		return $this->exec($sql);
	}


	/**
	**	Transaction related methods
	**/

	/* Start a new transaction */
	public function trans_start(){
		if(!$this->transaction_flag){
			$this->transaction_error = 0;
			$this->transaction_msg = [];
			if($this->exec("START TRANSACTION") !== false){
				$this->transaction_flag = true;
				return true;
			}else{
				$this->trans_error("Error when starting transaction");
				return false;
			}
		}else{
			$this->trans_error("One transaction is already running");
			return false;
		}
	}

	/* End a transaction */
	public function trans_end(){
		if(!$this->transaction_flag){
			$this->trans_error("No transaction to end");
			return false;
		}

		$commit = false;
		if($this->transaction_error > 0) $this->exec("ROLLBACK");
		else{
			$this->exec("COMMIT");
			$commit = true;
		}
		$this->transaction_flag = false;
		return $commit;
	}

	/* Manage transaction's errors */
	private function trans_error($str = false){
		$str = $str ? $str : $this->error;
		$this->transaction_error++;
		$this->transaction_msg[] = $str;
	}

	/**
	**	Utils
	**/

	/* Escapes strings and removes slashes if possible */
	public function sanitize($str){
		if(is_array($str)){
			foreach($str as $key => $val) $output[$key] = $this->sanitize($val);
		}else{
			if(get_magic_quotes_gpc()) $str = stripslashes($str);
			$output = $this->real_escape_string($str);
		}
		return $output;
	}
}
