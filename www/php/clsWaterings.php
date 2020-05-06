<?php
// clone model
require_once("clsNumber.php");
require_once("clsDB.php");
if(!defined('WATERINGS_CLASS')){
	define('WATERINGS_CLASS',true);
	class clsWaterings{
		public $id;
		public $clone_id;
		public $nutriens;
		public $ph;
		public $datetime;
		
		public function clsWaterings($db,$waterings_row){
				$this->id = $waterings_row['id'];
				$this->clone_id = $waterings_row['clone_id'];
				$nutrients_table = $db->select("SELECT * FROM `nutrients` WHERE `id` = '".$waterings_row['nutrients_id']."'");
				$this->nutriens = new clsNutrients($nutrients_table[0]);
//				$this->nutriens->id = $nutrients_table[0]['id'];
//				$this->nutriens->name = $nutrients_table[0]['name'];
//				$this->nutriens->pH = $nutrients_table[0]['pH'];
//				$this->nutriens->food = $nutrients_table[0]['food'];
//				$this->nutriens->ingredients = $nutrients_table[0]['ingredients'];
				$this->ph = $waterings_row['ph'];
				$this->datetime = new clsNumberDate($waterings_row['date']);
		}
		public function is_food(){
			if($this->nutriens->food)
				return true;
			return false;
		}
		// class properties
		public static function recentWaterings($db, $clone_id){
			$waterings_table = $db->select("SELECT * FROM `waterings` WHERE `clone_id` = '$clone_id' ORDER BY `date` DESC LIMIT 4");
			$waterings = array();
			foreach($waterings_table as $waterings_row){
				$watering = new clsWaterings($db,$waterings_row);
/*				$watering->id = $waterings_row['id'];
				$watering->clone_id = $waterings_row['clone_id'];
				$watering->nutriens = new clsNutrients();
				$nutrients_table = $db->select("SELECT * FROM `nutrients` WHERE `id` = '".$waterings_row['nutrients_id']."'");
				$watering->nutriens->id = $nutrients_table[0]['id'];
				$watering->nutriens->name = $nutrients_table[0]['name'];
				$watering->nutriens->pH = $nutrients_table[0]['pH'];
				$watering->nutriens->food = $nutrients_table[0]['food'];
				$watering->nutriens->ingredients = $nutrients_table[0]['ingredients'];
				$watering->datetime = new clsNumberDate($waterings_row['date']); */
				array_push($waterings,$watering);
			}
			return $waterings;
		}
		public static function waterings($db, $clone_id){
			$waterings_table = $db->select("SELECT * FROM `waterings` WHERE `clone_id` = '$clone_id' ORDER BY `date` DESC");
			$waterings = array();
			foreach($waterings_table as $waterings_row){
				$watering = new clsWaterings($db,$waterings_row);
/*				$watering->id = $waterings_row['id'];
				$watering->clone_id = $waterings_row['clone_id'];
				$watering->nutriens = new clsNutrients();
				$nutrients_table = $db->select("SELECT * FROM `nutrients` WHERE `id` = '".$waterings_row['nutrients_id']."'");
				$watering->nutriens->id = $nutrients_table[0]['id'];
				$watering->nutriens->name = $nutrients_table[0]['name'];
				$watering->nutriens->pH = $nutrients_table[0]['pH'];
				$watering->nutriens->food = $nutrients_table[0]['food'];
				$watering->nutriens->ingredients = $nutrients_table[0]['ingredients'];
				$watering->datetime = new clsNumberDate($waterings_row['date']);*/
				array_push($waterings,$watering);
			}
			return $waterings;
		}
	}
	
	class clsNutrients {
		public $id;
		public $name;
		public $pH;
		public $ingredients;
		public $food;
		public $spray;
		
		public function clsNutrients($nutrients_row){
			$this->id = $nutrients_row['id'];
			$this->name = $nutrients_row['name'];
			$this->pH = $nutrients_row['pH'];
			$this->food = $nutrients_row['food'];
			$this->ingredients = array();//$nutrients_row['ingredients'];
			$ingredients = clsDB::$db_g->select("SELECT `nutrient_mixes`.`id`, `nutrient_mixes`.`volume`, `nutrient_ingredients`.`name`, `nutrient_ingredients`.`url` FROM `nutrient_ingredients` INNER JOIN `nutrient_mixes` on `nutrient_ingredients`.`id` = `nutrient_mixes`.`ingredients_id` WHERE `nutrient_mixes`.`nutrients_id` = '$this->id' ");
			foreach($ingredients as $ingredient){
				array_push($this->ingredients,new clsNutrientIngredient($ingredient));
			}
			return $this;
		}
		
		public static function getNutrients($db){
			$nutrients_table = $db->select("SELECT * FROM `nutrients` ORDER BY `spray` ASC, `food` DESC, `pH` DESC");
			$nutrients = array();
			foreach($nutrients_table as $nutrients_row){
				$nutrient = new clsNutrients($nutrients_row);
				/*
				$nutrient->id = $nutrients_row['id'];
				$nutrient->name = $nutrients_row['name'];
				$nutrient->pH = $nutrients_row['pH'];
				$nutrient->food = $nutrients_row['food'];
				$nutrient->spray = $nutrients_row['spray'];
				$nutrient->ingredients = $nutrients_row['ingredients'];
				*/
				array_push($nutrients,$nutrient);
			}
			return $nutrients;
		}
	}
	
	class clsNutrientIngredient {
		public $id;
		public $name;
		public $volume;
		public $url;
		
		public function clsNutrientIngredient($row){
			$this->id = $row['id'];
			$this->name = $row['name'];
			$this->volume = new clsNumberVolume($row['volume']);
			$this->url = $row['url'];
		}
	}
	
}

?>