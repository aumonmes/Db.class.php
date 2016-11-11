<?php
/* Class DB to extend MySQLi with new functionallities */
class Db extends mysqli{
	private $transaction_flag;
	public $error_msg = [];

	/* Execute a query with no return */
	public function exec($sql){
		if($this->query($sql)) return $this->affected_rows;
		else return false;
	}

	/* Return only first row of a query returning null for an empty result */
	public function query_one($str){
		if($r = $this->query($str)){
			$res = $r->fetch_assoc();
			if(isset($res)) return $res;
			else return null;
		}
		else{ return false; }
	}

	/* Return only one value of a query, if there's more than one field returns the first one */
	public function query_val($str){
		$res = $this->query_one($str);
		if(is_array($res)) $r = array_shift($res);
		if(isset($r)) return $r;
		else return false;
	}

	/* Return a query's results as an array instead as a mysqli object */
	public function query_arr($str){
		$res = [];
		if($r = $this->query($str)){
			while($r && $t = $r->fetch_assoc()) $res[] = $t;
		}
		return $res;
	}

	/* Returns a non associative array with the values of the first column of the result */
	public function query_col($str){
		$res = [];

		if($r = $this->query($str)){
			while($r && $t = $r->fetch_row()) $res[] = $t[0];
		}

		return $res;
	}

	/* Insert one single row using an associative array */
	public function insert($table, $values){
		if(empty($values)) return false;

		$keys = [];
		$vals = [];

		foreach($values as $k => $v){
			$keys[] = $k;
			$vals[] = "'".$this->sanitize($v)."'";
		}

		$sql = "INSERT INTO $table
			(".implode(",", $keys).")
			VALUES
			(".implode(",", $vals).")";
		return $this->exec($sql);
	}

	/* Executes a batch of queries in one array */
	public function multi_exec($sql){
		$ret = [
			"e" => false,
			"msg" => []
		];
		if($this->multi_query($sql)){
			do{
				d($this);
				if($this->errno !== 0){
					$ret['e'] = true;
					$ret['msg'][] = $this->error;
				}
			}while($this->next_result());
		}

		return $ret;
	}

	/* Escapes strings and removes slashes if possible */
	public function sanitize($str){
	  if(is_array($str)){
	      foreach($str as $k => $val) $output[$k] = $this->sanitize($val);
	  }else{
	      if(get_magic_quotes_gpc()) $str = stripslashes($str);
	      $output = $this->real_escape_string($str);
	  }
	  return $output;
	}

}
?>
