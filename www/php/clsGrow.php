<?php
date_default_timezone_set("America/Denver");
require_once("clsDB.php");
require_once("clsClones.php");
require_once("clsSettings.php");
require_once("clsGarden.php");
require_once("clsStrainGrow.php");
require_once("clsStash.php");
// clone model
if(!defined('GROW_CLASS')){
	define('GROW_CLASS',true);
	class clsGrow{
		// class properties
		public $id;
		public $user;
		public $settings;
		public $gardens;
		public $strains;
		
		private $_harvests;
		private $_stash;
		private $_harvestsMaths;
		public static $grow;
		
		public function clsGrow(){
			$this->settings = clsSettings::loadSettings(clsDB::$db_g);
			$this->gardens = clsGarden::loadGardens(clsDB::$db_g);
			$this->user = new clsUser();
			$this->user->login_cookie();
			
			clsDB::$db_g->_query("UPDATE `clones` SET `flower_schedule_date` = NULL"); //safe_update("clones",array("flower_schedule_date" => $veg[$i]->flower_schedule_date),array("id" => $veg[$i]->id));
			
			// figure out the flower schedule for the 
			$flower = $this->floweringClones();
			$last_harvest = $this->latest_harvest();
			if($last_harvest && count($flower) < 2)
				array_push($flower,$last_harvest);
			$veg = $this->vegClones();
			usort($veg,'sort_clones_by_pot');
//			usort($veg,'sort_clones_by_canopy');
			usort($flower,'sort_clones_by_harvest_date');
			$i = 0;
			foreach($flower as $f){
				if(count($veg) > $i){
					$veg[$i]->flower_schedule_date = date("Y-m-d",strtotime($f->harvest_date_projected())+(60*60*24));
					if(strtotime($veg[$i]->flower_schedule_date) < time()){
						$veg[$i]->flower_schedule_date = date("Y-m-d",time());
						if($veg[$i]->plants_in_veg() == 1){
							if($veg[$i]->plants_in_nursery()){
								$veg[$i]->flower_schedule_date = date("Y-m-d",time()+60*60*24*$veg[$i]->flower_parent_offset());
							} else {
								$veg[$i]->flower_schedule_date = date("Y-m-d",time()+60*60*24*28);
							}
						}
					}
					$veg[$i]->flower_schedule_index = $i;
					clsDB::$db_g->safe_update("clones",array("flower_schedule_date" => $veg[$i]->flower_schedule_date),array("id" => $veg[$i]->id));
				}
				$i++;
			}
			$i = count($flower);
			while($i < count($veg)){
				$veg[$i]->flower_schedule_date = date("Y-m-d",strtotime($veg[$i-count($flower)]->harvest_date_projected())+(60*60*24));
				$veg[$i]->flower_schedule_index = $i;
				clsDB::$db_g->safe_update("clones",array("flower_schedule_date" => $veg[$i]->flower_schedule_date),array("id" => $veg[$i]->id));
				$i++;
			}
			clsGrow::$grow = $this;
			$this->strains = clsStrainGrow::loadGrowingStrains($this->allClones());
			usort($this->strains,'sort_strains_by_growing_rating');
			clsGrow::$grow = $this;
		}
		public function plant_to_clone(){
			foreach($this->gardens as $garden){
				$ptc = $garden->plant_to_clone();
				if($ptc)
					return $ptc;
			}
		}
		public function weed_per_day(){
			
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `harvest_date` IS NOT NULL");
			$harvests = array();
			foreach($clones_table as $clone_row){
				array_push($harvests,new clsHarvest(new clsClones(clsDB::$db_g,$clone_row)));
			}
			$weed = 0; $h = 0;
			foreach($harvests as $harvest){
				if($harvest->weedGone()){
					$weed += $harvest->weed_per_day()->raw_value;
					$h++;
				}
			}
			return new clsNumberMass(round($weed/$h));
		}
		public function stash_lasts(){
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `harvest_date` IS NOT NULL");
			$harvests = array();
			foreach($clones_table as $clone_row){
				array_push($harvests,new clsHarvest(new clsClones(clsDB::$db_g,$clone_row)));
			}
			$weed = 0; $h = 0;
			foreach($harvests as $harvest){
				if($harvest->weedGone() && $harvest->hadWeedDays() < 100){
					$weed += $harvest->hadWeedDays();
					$h++;
				}
			}
			return round($weed/$h);
		}
		public function average_stash_size(){
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `harvest_date` IS NOT NULL");
			$harvests = array();
			foreach($clones_table as $clone_row){
				array_push($harvests,new clsHarvest(new clsClones(clsDB::$db_g,$clone_row)));
			}
			$weed = 0; $h = 0;
			foreach($harvests as $harvest){
				if($harvest->weedGone()){
					$weed += $harvest->yield_raw();
					$h++;
				}
			}
			return new clsNumberMass(round($weed/$h));
		}
		public function hasStash(){
			if($this->_stash)
				return $this->_stash;
			$stashes = clsStash::load_stash(1);
			foreach($stashes as $stash){
				if($stash->stashType() == "flower")
					return $stash;
			}
			if(count($stashes))
				return $this->_stash = $stashes[0];
			//echo "?clsGrow->hasStash()?";
			return false;
		}
		public function myStashes(){
			return clsStash::load_stash($this->user->id,true,true);
		}
		public function floweringClones(){
			$clones = array();
			foreach($this->gardens as $garden){
				foreach($garden->clones as $clone){
					if($clone->days_in_flower() > 0)
						array_push($clones,$clone);
				}
			}
			$harvests = $this->harvests();
			
			return $clones;
		}
		public function vegClones(){
			$clones = array();
			foreach($this->gardens as $garden){
				foreach($garden->clones as $clone){
					if($clone->days_in_veg() > 0 && $clone->days_in_flower() == 0 && $clone->harvest_date == "")
						array_push($clones,$clone);
				}
			}
			return $clones;
		}
		public function nurseryClones(){
			$clones = array();
			$c = new clsClones();
			foreach($this->gardens as $garden){
				foreach($garden->clones as $clone){
					if($clone->days_in_nursery() > 0 && $clone->days_in_flower() == 0 && $clone->days_in_flower() == 0 && $clone->harvest_date == "")
						array_push($clones,$clone);
				}
			}
			return $clones;
		}
		
		public function allClones(){
			$clones = array();
			foreach($this->gardens as $garden){
				if(count($garden->clones)){
					$clones = array_merge($clones,$garden->clones);
				}
			}
			$clones = array_merge($clones,clsClones::loadFinishedHarvests());
			$clones = array_merge($clones,$this->failedPlants());
			return $clones;
		}
		public function failedPlants(){
			$clones = array();
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `time_of_death` IS NOT NULL");
			foreach($clones_table as $clone_row){
				array_push($clones,new clsClones(clsDB::$db_g,$clone_row));
			}
			return $clones;
		}
		
		public function harvests(){
			if(!$this->_harvests){
				$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `harvest_date` IS NOT NULL");
				$this->_harvests = count($clones_table);
			}
			return $this->_harvests;
		}
		public function latest_harvest(){
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `harvest_date` IS NOT NULL ORDER BY `harvest_date` DESC LIMIT 1");
			if(count($clones_table) && $clones_table[0]['garden_id'] == 0)
				return new clsClones(clsDB::$db_g,$clones_table[0]);
			else 
				return NULL;
		}
		
		public function measure_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->measure_plants())
					return true;
			}
			return false;	
		}
		public function measure_pH(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->measure_pH())
					return true;
			}
			return false;	
		}
		public function photo_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->photo_plants())
					return true;
			}
			return false;	
		}
		public function water_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->water_plants())
					return true;
			}
			return false;	
		}
		public function transplant_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transplant_plants())
					return true;
			}
			return false;	
		}
		public function transfer_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transfer_plants() && $garden->type() != "nursery")
					return true;
			}
			return false;	
		}
		public function root_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transfer_plants() && $garden->type() == "nursery" && $garden->photo_plants())
					return true;
			}
			return false;	
		}
		public function flower_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transfer_plants() && $garden->type() == "vegitation")
					return true;
			}
			return false;	
		}
		public function harvest_plants(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->harvest_plants())
					return true;
			}
			return false;	
		}
		public function measure_yield(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->measure_yield())
					return true;
			}
			return false;	
		}
		public function measure_yield_id(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->measure_yield())
					return $garden->measure_yield_id();
			}
			return false;	
		}
		public function transplant_plants_id(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transplant_plants())
					return $garden->transplant_plants_id();
			}
			return false;	
		}
		public function transfer_plants_id(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transfer_plants() && $garden->type() != "nursery")
					return $garden->transfer_plants_id();
			}
			return false;	
		}
		public function root_plants_id(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transfer_plants() && $garden->type() == "nursery")
					return $garden->transfer_plants_id();
			}
			return false;	
		}
		public function flower_plants_id(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->transfer_plants() && $garden->type() == "vegitation")
					return $garden->transfer_plants_id();
			}
			return false;	
		}
		public function harvest_plants_id(){ //return true;
			foreach($this->gardens as $garden){
				if($garden->type() == "flowering"){
					foreach($garden->clones as $clone){
						$c = new clsClones();
						if($clone->harvest_date_projected() == date("Y-m-d")){
							return $clone->id;
						}
					}
				}
			}
			return false;	
		}
		public function log_temp(){ //return true;
			foreach($this->gardens as $garden){
				if(count($garden->clones) && $garden->log_temp())
					return true;	
			}
			return false;	
		}
		public function cut_clones(){ //return true;
			if(clsSettings::$settings->current_plants >= clsSettings::$settings->max_plants - 4)
				return false;
			foreach($this->gardens as $garden){
				if(count($garden->clones) && $garden->cut_clones())
					return true;	
			}
			return false;	
		}
		public function cut_clones_id(){ //return true;
			foreach($this->gardens as $garden){
				if(count($garden->clones) && $garden->cut_clones())
					return $garden->cut_clones_id();	
			}
			return false;	
		}
		
	}
}
?>