<?php 
// exit if stand alone
require_once("clsNumber.php");
//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('MYSQL_CLASS')){
	define('MYSQL_CLASS',true);
	class clsDB{
		
		// class properties
		var $db;
		private $database;
		private $id;
		
		public static $db_g;
						
		function clsDB($dbname, $username, $password){
			$this->database = $dbname;
			$this->db = @mysqli_connect('localhost', $username, $password) 
				or die("Unable to connnect to the DB server! ERROR: " . mysqli_errno($this->db) . " <b>" . mysqli_error($this->db) . "</b");
			mysqli_select_db($this->db, $dbname)
				or die("Unable to select DB! ERROR: " . mysqli_errno($this->db) . " <b>" . mysqli_error($this->db) . "</b");
			
			clsDB::$db_g = $this;
		}
		
		function _query($sql){
			//$sql = preg_replace("/;/","",$sql);
			$result = @mysqli_query($this->db, $sql);
			return $result;
		}
		
		function insert($sql){ // runs query without processing or returning results
			$result = $this->_query($sql);
			@$id = mysqli_insert_id($this->db);
			@mysqli_free_result($result);
			return $id;
		}
		
		function select($sql){ // runs query and returns results as an array.
			$data = array();
			$result = $this->_query($sql);
			try{
				while($row = mysqli_fetch_array($result)){
					// clean row...
					$row_clean = array();
					foreach($row as $key => $value)
						if(!is_integer($key))
							$row_clean[$key] = $value;
					$data[] = $row_clean;
				}
				mysqli_free_result($result);
			} catch (Exception $e){
				
			}
			return $data;
		}
		
		function safe_insert($table, $data, $where = ""){ // generates a sanatized insert command 
			/*
				$date is required to be a keyed array. 
				
				array('first_name' => "sophia", 'last_name' => "daniels")
				
			*/
			
			// sanatize input
			$regex = array("/\"/","/\'/");
			$replace = array("&quot;","&apos;");
			$data = preg_replace($regex,$replace,$data);
			
			// generate sql
			$structure = ""; $values = ""; $i = 1;
			foreach($data as $key => $value){
				if($i++ < count($data)){
					$structure .= "`$key`,";
					if($value == "NOW()")
						$values .= "NOW(),";
					else
						$values .= "'$value',";
				} else {
					$structure .= "`$key`";
					if($value == "NOW()")
						$values .= "NOW()";
					else
						$values .= "'$value'";
				}
			}
			if($where == "")
				$sql = "INSERT INTO `$table` ($structure) VALUES ($values)";
			else 
				$sql = "REPLACE INTO `$table` ($structure) VALUES ($values) WHERE $where";
			return $this->insert($sql);
		}
		function safe_update($table, $data, $where = NULL){ // generates a sanatized update command 
			/*
				$date is required to be a keyed array. 
				
				array('first_name' => "sophia", 'last_name' => "daniels")
				
			*/
			
			// sanatize input
			$regex = array("/\"/","/\'/");
			$replace = array("&quot;","&apos;");
			$data = preg_replace($regex,$replace,$data);
			
			// generate sql
			$sql = "UPDATE `$table` SET \n";
			$c = 1;
			foreach($data as $key => $value){
				$sql .= "	`$key` = '$value'";
				if($c++ < count($data))
					$sql .= ",";
				$sql .= " \n";
			}
			$sql .= "WHERE ";
			if($where){
				foreach($where as $key => $value){
					$sql .= "`$key` = '$value'";
				}
			} else {
				$sql .= "`id` = '".$data['id']."'";
			}
			$sql .= ";";
			//echo $sql;
			return $this->insert($sql);
		}
		function last_insert(){
			return $this->id;
		}
		
		function get_err(){
			return mysqli_error($this->db);
		}
		function has_table($name){
			$sql = "SELECT COUNT(*)
FROM information_schema.tables 
WHERE table_schema = '$this->database' 
AND table_name = '$name';";
			$count = $this->select($sql);
			if(isset($count[0][0]) && $count[0][0] == 1){
				return true;
			}
			return false;
		}
		function create_table($name,$data,$comment,$set_defaults = false){
			// create the sql for this!!! :O
			$sql = "CREATE TABLE IF NOT EXISTS `$name` (\n";
			$sql .= " `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,\n";
			$sql .= " `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,\n";
			$sql .= " `modified` TIMESTAMP ";
			foreach($data as $key => $value){
				$sql .=", \n";
				$vtype = gettype($value);
				// translate php types to sql types
				switch($vtype){
					case "boolean":
						$type = 'BOOL';
						break;
					case "integer":
						$type = 'INT';
						break;
					case "double":
						$type = 'FLOAT';
						break;
					case "string":
						$type = 'VARCHAR( 100 )';
						break;
					default:
						// errors! omg
				}
				$sql .= " `$key` $type";
				if($set_defaults)
					$sql .= " NOT NULL DEFAULT  '$value'";
			}
			$sql .= ") ENGINE = MYISAM COMMENT =  '$comment';";
			$this->insert($sql);
		}
	
	}// end of class
} // end of defined

?>