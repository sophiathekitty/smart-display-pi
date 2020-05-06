<?php 
// exit if stand alone
require_once("clsDB.php");
require_once("clsNumber.php");
require_once("clsStash.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('GEAR_CLASS')){
	define('GEAR_CLASS',true);
	class clsGear{
		public $id;
		public $user_id;
		public $name;
		public $bowl_size;
		public $resin_rate;
		public $type;
		public $type_class;
		public $type_id;
		public $sub_type_index;
		public $date;
		public $date_used;
		public $date_removed;
		
		public $stash_id;
		
		private $stashes;	// stashes that have been loaded into this gear
		private $emptied;	// the last time it was emptied (grinder)
		private $cleaned;	// the last time it was cleaned (kief emptied for grinder)
		private $stash_level;	// how much flower is in the grinder
		private $kief_level;	// how much kief is in the grinder
		
		//
		// innit
		//
		public function clsGear($gear_row){
			$this->id = $gear_row['id'];
			if(isset($gear_row['gear_id']))
				$this->id = $gear_row['gear_id'];
			$this->user_id = $gear_row['user_id'];
			$this->name = $gear_row['name'];
			$this->type_id = $gear_row['type'];
			$this->sub_type_index = $gear_row['sub_type'];
			$this->bowl_size = $gear_row['bowl_size'];
			$this->resin_rate = $gear_row['resin_rate'];
			$this->id = $gear_row['id'];
			$this->date = new clsNumberDate($gear_row['got_date']);
			$this->date_removed = new clsNumberDate($gear_row['date_removed']);
			list($gear_type_row) = clsDB::$db_g->select("SELECT `name` FROM `gear_types` WHERE `id` = '$this->type_id'");
			$gear_sub_type_table = clsDB::$db_g->select("SELECT `name` FROM `gear_sub_types` WHERE `type_id` = '$this->type_id'");
			if($this->sub_type_index)
				$this->type = $gear_type_row['name']." ".$gear_sub_type_table[$this->sub_type_index-1]['name'];
			else
				$this->type = $gear_type_row['name'];
			$this->type_class = strtolower($this->type);
			$cleaned_table = clsDB::$db_g->select("SELECT `date` FROM `gear_cleaned` WHERE `gear_id` = '$this->id' ORDER BY `date` DESC LIMIT 1");
			if(count($cleaned_table)){
				$this->cleaned = new clsNumberDate($cleaned_table[0]['date']);
			} else {
				$this->cleaned = $this->date;
			}
		}
		public function loadStashes(){
			$this->stashes = array();
			if($this->stash_id)
				$stash_bowls = clsDB::$db_g->select("SELECT `smoke_session`.`stash_id` FROM `smoke_session` JOIN (`smoke_bowl`) ON(`smoke_bowl`.`session_id` = `smoke_session`.`id`) WHERE `smoke_bowl`.`gear_id` = '$this->id' AND `smoke_session`.`stash_id` = '$this->stash_id' AND `smoke_bowl`.`date` > '$this->cleaned';");
			else
				$stash_bowls = clsDB::$db_g->select("SELECT `smoke_session`.`stash_id` FROM `smoke_session` JOIN (`smoke_bowl`) ON(`smoke_bowl`.`session_id` = `smoke_session`.`id`) WHERE `smoke_bowl`.`gear_id` = '$this->id' AND `smoke_bowl`.`date` > '$this->cleaned';");
			foreach($stash_bowls as $stash_row){
				$id = $stash_row['stash_id'];
				$stash_table = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `id` = '$id' LIMIT 1;");
				if(count($stash_table)){
					array_push($this->stashes,new clsStash($stash_table[0]));
				}
			}
		}
		public function isGrinder(){
			if($this->type_id == 1 && $this->sub_type_index == 1)
				return true;
			return false;
		}
		public function isStash(){
			if($this->type_id == 1 && $this->sub_type_index != 1)
				return true;
			return false;
		}
		public function isBowl(){
			if($this->type_id > 1)
				return true;
			return false;
		}
		public function bowlNoun($plural = false){
			switch($this->type){
				case "Vape Volcano":
					if($plural)
						return "bags";
					return "bag";
				default:
					if($plural)
						return "bowls";
					return "bowl";
			}
		}
		
		public function stashLevel(){
			if($this->stash_level)
				return $this->stash_level;
			if(!count($this->stashes))
				$this->loadStashes();
			$this->stash_level = floor(count($this->stashes));
			if($this->stash_level > 5)
				$this->stash_level = 5;
			return $this->stash_level;
		}
		public function stashWeight(){
			if(!count($this->stashes))
				$this->loadStashes();
			if(!count($this->stashes)){
				return new clsNumberMass(0);
			}
			return new clsNumberMass($this->bowl_size * count($this->stashes));
		}
		public function kiefWeight(){
			return new clsNumberMass($this->stashWeight()->raw_value/($this->resin_rate*5));
		}
		public function kiefLevel(){
			if($this->isStash()){
				echo "yes?";
				$this->kief_level = 0;
				return 0;
			}
			if($this->kief_level)
				return $this->kief_level;
			if(!count($this->stashes))
				$this->loadStashes();
			$this->kief_level = floor(count($this->stashes)/$this->resin_rate);
			if($this->kief_level > 5)
				$this->kief_level = 5;
			return $this->kief_level;
		}
		public function resinLevel(){
			return $this->kiefLevel();
		}
		
		public function grinderLevels(){
			$levels = "";
			if($this->stashLevel() > 0){
				$levels .= " flower flower_".$this->stashLevel();
			}
			if($this->kiefLevel() > 0){
				$levels .= " kief kief_".$this->kiefLevel();
			}
			return $levels;
		}
		public function pipeLevels(){
			$levels = "";
			if($this->resinLevel() > 0){
				$levels .= " resin resin_".$this->resinLevel();
			}
			return $levels;
		}
		
		public function stashBlend(){
			
		}
		public function kiefBlend(){
			
		}
		public function resinBlend(){
			return $this->kiefBlend();
		}
		
		//
		// public static load functions
		//
		public static function loadGearUser($user_id){
			$gear_table = clsDB::$db_g->select("SELECT * FROM `smoke_gear` WHERE `user_id` = '$user_id'");
			return clsGear::gearTableToArray($gear_table);
		}
		public static function loadGearAt($id){
			list($gear_row) = clsDB::$db_g->select("SELECT * FROM `smoke_gear` WHERE `id` = '$id'");
			return new clsGear($gear_row);
		}
		
		public static function gearTableToArray($gear_table){
			$gear = array();
			foreach($gear_table as $gear_row){
				array_push($gear, new clsGear($gear_row));
			}
			return $gear;
		}
		
		//
		// public static save functions
		//
		public static function addGear($user_id, $name, $type){
			clsDB::$db_g->safe_insert("smoke_gear",
					array(
						"user_id" => $user_id,
						"name" => $name,
						"type" => $type,
						"date" => date("Y-m-d H:i:s",time())
					));
		}
		
	}// end of class
} // end of defined

?>