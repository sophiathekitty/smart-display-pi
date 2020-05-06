<?php
date_default_timezone_set("America/Denver");
require_once("clsDB.php");
require_once("clsClones.php");
require_once("clsTemperature.php");
require_once("clsNumber.php");
require_once("clsSettings.php");
require_once("clsStrainGrow.php");
// clone model
if(!defined('GARDEN_CLASS')){
	define('GARDEN_CLASS',true);
	class clsGarden{
		// class properties
		public $id;
		public $parent_id;
		public $name;
		public $stage;
		public $track_temps;
		public $track_measurements;
		public $track_watering;
		public $uses_sunlight;
		public $width;
		public $height;
		public $depth;
		public $clones;
		public $lights;
		public $lights_on;
		public $lights_off;
		public $temp_log;
		public $manager;
		
		public function clsGarden($garden_row){
			$this->id = $garden_row['id'];
			$this->parent_id = $garden_row['parent_id'];
			$this->name = $garden_row['name'];
			$this->stage = $garden_row['stage'];
			$this->track_temps = $garden_row['track_temps'];
			$this->track_measurements = $garden_row['track_measurements'];
			$this->track_watering = $garden_row['track_watering'];
			$this->uses_sunlight = $garden_row['uses_sunlight'];
			$this->width = new clsNumberLength($garden_row['width'],$_SESSION['metric']);
			$this->height = new clsNumberLength($garden_row['height'],$_SESSION['metric']);
			$this->depth = new clsNumberLength($garden_row['depth'],$_SESSION['metric']);
			$this->lights_on = $garden_row['lights_on'];
			$this->lights_off = $garden_row['lights_off'];
			$this->manager = $garden_row['manager'];
			//if($this->parent_id == 0)
				$this->temp_log = clsTemperature::latestTemp(clsDB::$db_g,$this->id);
			//else
				//$this->temp_log = clsTemperature::latestTemp(clsDB::$db_g,$this->parent_id);
		}
		
		public function measure_plants(){ //return true;
			foreach($this->clones as $clone){
				if($clone->measure_plant())
					return true;
			}
			return false;	
		}
		public function measure_pH(){ //return true;
			foreach($this->clones as $clone){
				if($clone->measure_pH())
					return true;
			}
			return false;	
		}
		public function photo_plants(){ //return true;
			foreach($this->clones as $clone){
				if($clone->photo_plant())
					return true;
			}
			return false;	
		}
		public function water_plants(){ //return true;
			if($this->type() == "drying" || $this->type() == "nursery")
				return false;
			foreach($this->clones as $clone){
				if($clone->water_plant())
					return true;
			}
			return false;	
		}
		public function transplant_plants(){ //return true;
			foreach($this->clones as $clone){
				if($clone->transplant_plant())
					return true;
			}
			return false;	
		}
		public function plant_to_clone(){
			if(strtolower($this->type()) != "vegitation")
				return NULL;
				
			if($this->cut_clones())
				return $this->cut_clones_id();
			
			$plants_to_clone = array();
			$plants_by_strain = array();
			foreach($this->clones as $clone){
				if(!isset($plants_by_strain[$clone->type()])){
					$plants_by_strain[$clone->type()] = array();
				}
				array_push($plants_by_strain[$clone->type()],$clone);
				if($clone->canopy->raw_value > 2000){
					array_push($plants_to_clone,$clone);
				}
			}
			if(count($plants_to_clone) == 1)
				return $plants_to_clone[0]->id;
			$strain_to_clone = ""; $strain_count = 100;
			foreach($plants_by_strain as $strain){
				if(count($strain) < $strain_count){
					$strain_to_clone = $strain[0]->type();
					$strain_count = count($strain);
				}
			}
			foreach($plants_to_clone as $plant){
				if($plant->type() == $strain_to_clone){
					return $plant->id;
				}
			}
			return NULL;
		}
		public function transfer_plants(){ //return true;
			foreach($this->clones as $clone){
				if($clone->transfer_plant()){
					if($this->type() != "vegitation")
						return true;
					$strainInfo = $clone->getStrainGrow();
					if($strainInfo->plants_in_veg() > 1)
						return true;
				}
			}
			return false;	
		}
		public function harvest_plants(){ //return true;
			foreach($this->clones as $clone){
				if($clone->harvest_plant())
					return true;
			}
			return false;	
		}
		public function measure_yield(){ //return true;
			foreach($this->clones as $clone){
				if($clone->measure_yield())
					return true;
			}
			return false;	
		}
		public function transplant_plants_id(){ //return true;
			foreach($this->clones as $clone){
				if($clone->transplant_plant())
					return $clone->id;
			}
			return false;	
		}
		public function transfer_plants_id(){ //return true;
			foreach($this->clones as $clone){
				if($clone->transfer_plant())
					return $clone->id;
			}
			return false;	
		}
		public function harvest_plants_id(){ //return true;
			foreach($this->clones as $clone){
				if($clone->harvest_plant())
					return $clone->id;
			}
			return false;	
		}
		public function measure_yield_id(){ //return true;
			foreach($this->clones as $clone){
				if($clone->measure_yield())
					return $clone->id;
			}
			return false;	
		}
		public function log_temp(){ //return true;
			if($this->track_temps){
				if(count($this->temp_log) > 0){
					
					$temp = clsTemperature::latestTemp(clsDB::$db_g,$this->id);
					$temp_time = strtotime($temp->datetime);
					$temp_time_since = time() - $temp_time;
					if($temp_time_since/60 > clsSettings::$settings->minutes_between_temps)
						return true;
				} else
					return true;
			}
			return false;	
		}
		public function cut_clones(){ //return true;
			if($this->type() == "vegitation"){
				foreach($this->clones as $clone){
					if($clone->cut_clones())
						return true;
				}
			}
			return false;	
		}
		public function cut_clones_id(){ //return true;
			if($this->type() == "vegitation"){
				foreach($this->clones as $clone){
					if($clone->cut_clones())
						return $clone->id;
				}
			}
			return NULL;
		}
		
		public function lights_on_now(){
			$lights_on_time = strtotime($this->lights_on);
			$lights_off_time = strtotime($this->lights_off);
			$current_time = time();
			if($lights_on_time > $lights_off_time)
				if($current_time > $lights_on_time || $current_time < $lights_off_time){
					return "sun";
				}
			else 
				if($current_time > $lights_on_time && $current_time < $lights_off_time){
					return "sun";
				}
			return "moon";
		}
		public function type(){
			switch($this->stage){
				case 1:
					return "nursery";
				case 2:
					return "vegitation";
				case 3:
					return "flowering";
				case 4:
					return "drying";
				case 5:
				case 9:
					return "vegitation flowering";
			}
			if(date("g",strtotime($this->lights_on)) == date("g",strtotime($this->lights_off))){
				return "flowering";
			}
			return "vegitation";
		}
		public function floorArea(){
			return new clsNumberArea($this->width->raw_value, $this->depth->raw_value,$_SESSION['metric']);
		}
		public function spaceAvailable(){
			$m = new clsNumberArea($this->width->raw_value, $this->depth->raw_value,$_SESSION['metric']);
			foreach($this->clones as $clone){
				$m->subtractArea($clone->width->scaled_value*$clone->width->scaled_value);
			}
			return $m;
		}
		public function spaceUsed(){
			$m = new clsNumberArea(0,0,$_SESSION['metric']);
			foreach($this->clones as $clone){
				$m->addArea(pow($clone->width->scaled_value,2));
			}
			return $m;
		}
		public function spaceUsedWidth(){
			$w = 0;
			foreach($this->clones as $clone){
				$w += $clone->width->scaled_value;
			}
			return $w;
		}
		public function plantHeight(){
			$height = new clsNumberLength(0,$_SESSION['metric']);
			foreach($this->clones as $clone){
				$height->maxLengthRaw($clone->height->raw_value+$clone->pot->height->raw_value);
			}
			return $height;
		}
		public function lightDistance(){
			$plants = $this->plantHeight();
			$light = $this->lights[0]->height;
			return new clsNumberLength($light->raw_value - $plants->raw_value,$_SESSION['metric']);
		}
		
		public static function loadGardens($db){
			$gardens = array();
			list($user) = $db->select("SELECT `id` FROM `gardeners` WHERE `user_key` = '".$_SESSION['user_key']."'");
			$user_id = $user['id'];
			$gardens_table = $db->select("SELECT `gardens`.* FROM `gardens` INNER JOIN `gardener_roles` on `gardener_roles`.`garden_id` = `gardens`.`id` WHERE `gardener_roles`.`gardener_id` = '$user_id'  ORDER BY `gardener_roles`.`role_id` ASC, `gardens`.`uses_sunlight` ASC, `gardens`.`stage` DESC ");
			foreach($gardens_table as $garden_row){
				$garden = new clsGarden($garden_row);
				$garden->clones = clsClones::loadCloneInGarden($db,$garden->id);
				$garden->lights = clsGrowLight::loadLights($db,$garden->id);
				array_push($gardens,$garden);
			}
			return $gardens;
		}
		public static function loadGarden($db,$id){
			$gardens_table = $db->select("SELECT * FROM `gardens` WHERE `id` = '$id'");
			$garden = new clsGarden($gardens_table[0]);
			$garden->clones = clsClones::loadCloneInGarden($db,$garden->id);
			$garden->lights = clsGrowLight::loadLights($db,$garden->id);
			return $garden;
		}
	}
	class clsGrowLight{
		public $id;
		public $garden_id;
		public $bulb_id;
		public $name;
		public $watts;
		public $spectrum;
		public $height;
		public $date_puchased;
		public $date_installed;
		public $date_replaced;
		public static function loadLights($db,$garden_id){
			$lights = array();
			$lights_table = $db->select("SELECT * FROM `lights` WHERE `garden_id` = '$garden_id'");
			foreach($lights_table as $lights_row){
				$light = new clsGrowLight();
				$light->id = $lights_row['id'];
				$light->garden_id = $lights_row['garden_id'];
				$light->bulb_id = $lights_row['bulb_id'];
				// load bulb info
				$bulbs_table = $db->select("SELECT * FROM `light_bulbs` WHERE `id` = '$light->bulb_id'");
				$light->name = $bulbs_table[0]['name'];
				$light->watts = $bulbs_table[0]['watt'];
				$light->spectrum = $bulbs_table[0]['spectrum'];
				$light->date_puchased = new clsNumberDate($bulbs_table[0]['date_purchased']);
				$light->date_installed = new clsNumberDate($bulbs_table[0]['date_installed']);
				$light->date_replaced = new clsNumberDate($bulbs_table[0]['date_replaced']);
				// get current height
				$heights_table = $db->select("SELECT * FROM `light_adjust` WHERE `light_id` = '$light->id' ORDER BY `date` DESC LIMIT 1");
				$light->height = new clsNumberLength($heights_table[0]['height'],$_SESSION['metric']);
				array_push($lights,$light);
			}
			return $lights;
		}
	}
}
?>