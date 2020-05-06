<?php 
// exit if stand alone
require_once("clsDB.php");
require_once("clsClones.php");
require_once("clsStash.php");
require_once("clsGrow.php");
require_once("clsNumber.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('STRAIN_GROW_CLASS')){
	define('STRAIN_GROW_CLASS',true);
	class clsStrainGrow{
		public $strain_id;
		public $strain2_id;
		public $plants = array();
		public $harvests = array();
		public $dead = array();
		
		public function clsStrainGrow($strain_id, $strain2_id = 0){
			$this->strain_id = $strain_id;
			$this->strain2_id = $strain2_id;
			$this->loadPlants();
			$this->loadHarvests();
			if(!count($this->plants) && !count($this->harvests))
				$this->loadDead();
		}
		
		private function loadPlants(){
			if(!is_null(clsGrow::$grow)){
				foreach(clsGrow::$grow->gardens as $garden){
					foreach($garden->clones as $clone){
						if($clone->strain->id == $this->strain_id){
							if($this->strain2_id == 0){
								array_push($this->plants,$clone);
							} elseif($clone->strain2->id == $this->strain2_id) {
								array_push($this->plants,$clone);
							}
						}
						
					}
				}
			} else {
				$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = $this->strain_id AND `second_strain_id` = $this->strain2_id AND `harvest_date` IS NULL AND `time_of_death` IS NULL ");
				$this->plants = array();
				foreach($clones_table as $clone_row){
					array_push($this->plants, new clsClones(clsDB::$db_g,$clone_row));
				}
			}
		}
		private function loadHarvests(){
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = $this->strain_id AND `second_strain_id` = $this->strain2_id AND `harvest_date` IS NOT NULL ");
			$this->harvests = array();
			foreach($clones_table as $clone_row){
				array_push($this->harvests, new clsHarvest(new clsClones(clsDB::$db_g,$clone_row)));
			}
		}
		private function loadDead(){
			$clones_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = $this->strain_id AND `second_strain_id` = $this->strain2_id AND `time_of_death` IS NOT NULL ");
			$this->dead = array();
			foreach($clones_table as $clone_row){
				array_push($this->dead, new clsClones(clsDB::$db_g,$clone_row));
			}
		}
		public function name(){
			if(count($this->plants))
				return $this->plants[0]->strain_name();
			if(count($this->harvests))
				return $this->harvests[0]->plant->strain_name();
			if(count($this->dead))
				return $this->dead[0]->strain_name();
			return "";
		}
		public function type(){
			if(count($this->plants))
				return $this->plants[0]->type();
			if(count($this->harvests))
				return $this->harvests[0]->plant->type();
			if(count($this->dead))
				return $this->dead[0]->type();
			return "";
		}
		public function url(){
			if($this->strain2_id == 0)
				return "strain.php?strain_id=".$this->strain_id;
			return "strain.php?strain_id=".$this->strain_id."&strain2_id=".$this->strain2_id;
		}
		public function get_a_clone(){
			if(count($this->plants))
				return $this->plants[0];
			if(count($this->harvests))
				return $this->harvests[0]->plant;
		}
		public function get_all_clones(){
			if(count($this->dead) == 0){
				$this->loadDead();
			}
			$clones = array();
			foreach($this->plants as $clone)
				array_push($clones,$clone);
			foreach($this->harvests as $harvest)
				array_push($clones,$harvest->plant);
			foreach($this->dead as $clone)
				array_push($clones,$clone);
			return $clones;
		}
		public function nursery_clone_age_days(){
			$time = time();
			foreach($this->plants as $plant){
				if($plant->days_in_nursery() > 0 && $plant->days_in_veg() == 0 && $plant->days_in_flower() == 0)
					return $plant->age_days();
			}
			return NULL;
		}
		public function average_days_in_nursary(){
			foreach($this->plants as $plant){
				if($plant->days_in_nursery() > 0 && $plant->days_in_veg() == 0 && $plant->days_in_flower() == 0)
					return $plant->average_days_in_nursery();
			}
			return 28;
		}
		public function plants_in_nursery(){
			$plants = 0;
			foreach($this->plants as $plant){
				if($plant->days_in_nursery() > 0 && $plant->days_in_veg() == 0 && $plant->days_in_flower() == 0)
					$plants++;
			}
			return $plants;
		}
		public function plants_in_veg(){
			$plants = 0;
			foreach($this->plants as $plant){
				if($plant->days_in_veg() > 0 &&	$plant->flower_date == "")
					$plants++;
			}
			return $plants;
		}
		public function plants_in_flower(){
			$plants = 0;
			foreach($this->plants as $plant){
				//$p = new clsClones();
				if($plant->flower_date != "" && $plant->harvest_date == "")
					$plants++;
			}
			return $plants;
		}
		public function flowering_plants(){
			$plants = array();
			foreach($this->plants as $plant){
				if($plant->flower_date != "" && $plant->harvest_date == ""){
					array_push($plants,$plant);
				}
			}
			usort($plants,'sort_clones_by_harvest_date');
			return $plants;
		}
		public function average_days_flowering(){
			$days = 0;
			foreach($this->harvests as $harvest){
				$days += $harvest->plant->days_in_flower();
			}
			return round($days/count($this->harvests));
		}
		public function scheduled_plants(){
			$plants = array();
			foreach($this->plants as $plant){
				if($plant->flower_schedule_date != "" && $plant->flower_date == "")
					array_push($plants,$plant);
			}
			usort($plants,'sort_clones_by_flower_schedule');
			return $plants;
		}
		public function plants_harvested(){
			return count($this->harvests);
		}
		
		public function yield_rate(){
			if(count($this->plants))
				return $this->plants[0]->average_yield_rate();
			if(count($this->harvests))
				return $this->harvests[0]->plant->average_yield_rate();
		}
		public function rating(){
			if(count($this->harvests)){
				$total = 0; $count = 0;
				foreach($this->harvests as $harvest){
					if($harvest->rating()){
						$total += $harvest->rating();
						$count++;
					}
				}
				if($count > 0){
					return round($total/$count,1);
				}
			}
			return NULL;
		}
		public function rating_css(){
			$rating = $this->rating();
			if($rating){
				$floor = floor($rating);
				if($rating == $floor)
					return $rating;
				$round = round($rating);
				$tenth = round(10*($rating - $floor));
				switch($tenth){
					case 3:
					case 4:
					case 5:
					case 6:
					case 7:
						return $floor."_5";
					default:
						return round($rating);
				}
					
			}
			return $rating;
		}
		// list effects
		public function effects(){
			if(count($this->harvests)){
				$effects = array();
				foreach($this->harvests as $stash){
					$ef = $stash->effects();
					foreach($ef as $e){
						$has = false;
						if(count($effects)){
							foreach($effects as $ee){
								if($ee->effect == $e->effect){
									$has = true;
									$ee->total++;
								}
							}
						}
						if(!$has){
							array_push($effects,$e);
						}
					}
				}
				return $effects;
			}
			return NULL;
		}
		// list effects html
		public function effects_html($limit = 5){
			$effects = $this->effects();
			usort($effects,'sort_smoke_effects_totals');
			$html = ""; $i = 0;
			foreach($effects as $effect){
				if($effect->good)
					$html .= "<span class='good total_".$effect->totalFlattened()."'>";
				elseif(!is_null($effect->good) && $effect->good == 0)
					$html .= "<span class='bad total_".$effect->totalFlattened()."'>";
				else
					$html .= "<span class='total_".$effect->totalFlattened()."'>";
				$html .= $effect->effect;
				$html .= "</span>";
				if($i++ < count($effects)-1 && $i < $limit)
					$html .= ", ";
				if($i == $limit)
					return $html;
			}
			return $html;
			print_r($effects);
		}
		public function strain_growing_status(){
			if($this->plants_in_veg() > 0){
				return "growing";
			}
			if($this->plants_in_flower() > 0){
				return "retiring";
			}
			if(count($this->plants)){
				return "reviving";
			}
			if(count($this->harvests)){
				return "retired";
			}
			return "failed";
		}
		public function strain_growing_index(){
			if($this->plants_in_veg() > 0){
				return 3;
			}
			if($this->plants_in_flower() > 0){
				return 2;
			}
			if(count($this->plants)){
				return 3;
			}
			if(count($this->harvests)){
				return 1;
			}
			return 0;
		}
		public function photos($c = 3){
			$photos = array();
			if(count($this->plants))
				array_push($photos,$this->plants[0]->photo);
			if(count($this->plants) > 1)
				array_push($photos,$this->plants[rand(1,count($this->plants)-1)]->photo);
			if(count($this->harvests))
				array_push($photos,$this->harvests[rand(0,count($this->harvests)-1)]->plant->photo);
				
			if(count($photos) == 0 && count($this->dead))
				array_push($photos,$this->dead[rand(0,count($this->dead)-1)]->photo);
			return $photos;
		}
		
		public function averageYield(){
			$y = 0; $c = 0;
			foreach($this->harvests as $harvest){
				$y += $harvest->yield_raw();
				$c++;
			}
			return new clsNumberMass($y/$c);
		}
		public function bestYield(){
			$y = 0;
			foreach($this->harvests as $harvest){
				if($y < $harvest->yield_raw()){
					$y = $harvest->yield_raw();
				}
			}
			return new clsNumberMass($y);
		}
		public function totalYield(){
			$y = 0;
			foreach($this->harvests as $harvest){
				$y += $harvest->yield_raw();
			}
			return new clsNumberMass($y);
		}
		
		public function averageDaysBetweenTransplant(){
			$plants = $this->get_all_clones();
			$time = 0;
			foreach($plants as $plant){
				$time += $plant->averageDaysBetweenTransplant();
			}
			return round($time/count($plants));
		}
		public static function loadGrowingStrains($clones){
			$strains = array();
			foreach($clones as $clone){
				$already_found = false;
				foreach($strains as $strain){
					if($clone->strain->id == $strain->strain_id){
						if($clone->strain2){
							if($strain->strain2_id == $clone->strain2->id){
								$already_found = true;
							}
						} else {
							if(!$strain->strain2_id){
								$already_found = true;
							}
						}
					}
				}
				// ok it's a new one
				if(!$already_found){
					if($clone->strain2)
						array_push($strains,new clsStrainGrow($clone->strain->id,$clone->strain2->id));
					else
						array_push($strains,new clsStrainGrow($clone->strain->id,0));
				}
			}
			return $strains;
		}
		

		
	}// end of class
	


function sort_strains_by_rating($a, $b) {
	if($a->rating() == $b->rating()){ return 0 ; }
	return ($a->rating() < $b->rating()) ? 1 : -1;
}
function sort_strains_by_growing_rating($a, $b) {
	if($a->strain_growing_index() == $b->strain_growing_index()){
		if($a->rating() == $b->rating()){ return 0 ; }
		return ($a->rating() < $b->rating()) ? 1 : -1;
	}
	return ($a->strain_growing_index() < $b->strain_growing_index()) ? 1 : -1;
}

/*
function sort_clones_by_pot($a, $b) {
	if($a->pot->volume->raw_value == $b->pot->volume->raw_value){ 
		if($a->canopy->raw_value == $b->canopy->raw_value){ return 0 ; }
		return ($a->canopy->raw_value < $b->canopy->raw_value) ? 1 : -1;
	}
	return ($a->pot->volume->raw_value < $b->pot->volume->raw_value) ? 1 : -1;
}
*/
	
} // end of defined

?>