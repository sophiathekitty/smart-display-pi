<?php
// exit if stand alone
require_once("clsDB.php");
require_once("clsNumber.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('SETTINGS_CLASS')){
	define('SETTINGS_CLASS',true);
	class clsSettings{
		public $id;
		public $name;
		public $max_plants;
		public $max_flowering;
		public $current_plants;
		public $current_flowering;
		public $start_date;
		public $min_moisture;
		public $max_water_days;
		public $min_temp;
		public $max_temp;
		public $minutes_between_temps;
		public $days_between_watering;
		public $days_between_photos;
		public $days_between_measuring;
		public $target_yield;
		public $days_between_harvests;
		public $min_days_between_spray;
		public $max_days_between_spray;
		
		public static $settings;
		
		public function clsSettings($row){
			$this->id = $row['id'];
			$this->name = $row['name'];
			$flowering_clones = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `flower_date` IS NOT NULL AND `harvest_date` IS NULL AND `time_of_death` IS NULL");
			$all_clones = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `harvest_date` IS NULL AND `time_of_death` IS NULL");
			$this->current_flowering = count($flowering_clones);
			$this->current_plants = count($all_clones);
			$this->max_plants = $row['max_plants'];
			$this->max_flowering = $row['max_flowering'];
			$this->start_date = $row['start_date'];
			$this->min_moisture = $row['min_moisture'];
			$this->max_water_days = $row['max_water_days'];
			$this->min_temp = $row['min_temp'];
			$this->max_temp = $row['max_temp'];
			$this->minutes_between_temps = $row['minutes_between_temps'];
			$this->days_between_watering = $row['days_between_watering'];
			$this->days_between_photos = $row['days_between_photos'];
			$this->days_between_measuring = $row['days_between_measuring'];
			$this->target_yield = $row['target_yield'];
			$this->days_between_harvests = $row['days_between_harvests'];
			$this->min_days_between_spray = $row['min_days_between_spray'];
			$this->max_days_between_spray = $row['max_days_between_spray'];
			clsSettings::$settings = $this;
		}
		public static function loadSettings($db){
			$setting_table = $db->select("SELECT * FROM `grow_settings` LIMIT 1");
			return new clsSettings($setting_table[0]);
		}
	}
}
?>