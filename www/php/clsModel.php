<?php
/*
Title: Catgirl Games PHP Model
Author: sophia daniels
Purpose: To extend my database access class and give me some MVC features without commiting to cakephp
Requires: clsDB.php (database access)
*/
if(!defined('KittyMVC_Model')){
	define('KittyMVC_Model',true);
	
	// localization.... here for now....
	$__database = 'grow_log';
	$__username = 'php';
	$__password = 'pinkstarsarefalling';

	require_once("clsDB.php");
	class clsModel {
		// shared database access class (clsDB.php)
		private static $db = NULL;
		// local data
		private $data = array();
		
		/*/
		Magic functions! 
		for making the model a magical model. (no really these are actually called magical functions. :3 did you just learn some new php?)
		ok. so these just make it possible to access the shared database and dynamic properties on your models.
		/*/
		// magical getter of properties
		public function __get($name) {
			if($name == "db"){
				if(isset(self::$db))
					return self::$db;
				// the database isn't connected?
				// lets set it up then... and return it in one fell swooop.
				global $__database, $__username, $__password;
				return self::$db = new clsDB($__database, $__username, $__password);
			}
			if(isset($this->data[$name])){
				return $this->data[$name];
			}
			return NULL;
		}
		// magical setter of property.
		public function __set($name,$value) {
			switch($name){
				case 'db':
					// they're trying to set the db?
					die("why are you trying to set $name?");
					break;
				default:
					$this->data[$name] = $value;
			}
		}
		// magical validation of the existence of properties.
		public function __isset($name){
			switch($name){
				case 'db':
					return isset(self::$db);
					break;
				default:
				return isset($this->data[$name]);
			}
		}
		private function get_table($create = false,$check = true){
			if(is_subclass_of($this,"clsModel")){
				// get the table name
				$table = get_class($this);
				// should we make sure it exists?
				if($create){
					// ok... need to do the create table stuff here... with the data? for now...
					// need to setup a structure array... maybe leave in whatever i do now as "sloppy mode"
					// that all sounds good to me.
					$this->db->create_table($table,$this->data); 
				} elseif($check){
					echo "|-[$table]-|";
					if(!$this->db->has_table($table))
						return false;
				}
				return $table;
			}
			die("not a subclass. you need to extend clsModel to use it.... this check also needs to work. so sophi. don't forget....");
		}
		/*/
		Utility functinos or whatever
		these all handle the basic data functions that you'll use. the top can be collapsed once it's working. it's just magics. :3 coding kitty magics
		/*/
		// save data
		public function save(){
			$table = $this->get_table(true); // get the table name. and make sure it exists.
			// do we has an id?
			// do a safe insert. let the database handler figure out the difference between insert/update.
			if(isset($this->id))
				$this->db->safe_update($table,$this->data);
			else
				$this->id = $this->db->safe_insert($table,$this->data);
			// check for errors and stuff
			if($this->db->get_err())
				die($this->db->get_err());			
		}
		// load data using a sql where request (generated and called by other load functions)
		private function loadData($sql){
			// run a simple select query.
			list($data) = $this->db->select($sql);
			if(empty($data))
				return false;
			// now sanatize the loaded datas.
			foreach($data as $key => $value){
				if(gettype($key) == 'string'){
					echo "[$key] = $value;";
					$this->data[$key] = $value;
				}
			}
			return true;
		}
		private function where($where){
			$table = $this->get_table();
			return $this->loadData("SELECT * FROM `$table` WHERE $where LIMIT 1;");
		}
		// load data by id
		public function load($id){
			return $this->where("`id` = '$id'");
		}
		// load the last one
		public function last(){
			$table = $this->get_table();
			if(!$table)
				die("no table?!");
			return $this->loadData("SELECT * FROM `$table` ORDER BY `created` DESC LIMIT 1;");
		}
		// load the first one
		public function first(){
			$table = $this->get_table();
			return $this->loadData("SELECT * FROM `$table` ORDER BY `created` ASC LIMIT 1;");
		}
		// share data
		public function share(){
			// need to attach facebook object somehow.... or something.... idk. probably should be it's own class... or somethang.
		}
	}
}

// tests

class KittyMVC extends clsModel {
	// declare it and set the structure.
	function KittyMVC(){
		$this->author = "sophia";
		$this->coding = "kitty";
		$this->sadface = true;
		$this->points = 10;
	}
}
$kitty = new KittyMVC();
//print_r($kitty);
echo "<hr>";
$kitty->save();
echo "<hr>";
$kitty->first();
//print_r($kitty);
echo "<hr>";
$kitty->points += 10;
//print_r($kitty);
echo "<hr>";
$kitty->save();
echo "<hr>";
$kitty->last();
//print_r($kitty);
echo "<hr>";
$kitty->points += 10;
//print_r($kitty);
echo "<hr>";
$kitty->save();
?>