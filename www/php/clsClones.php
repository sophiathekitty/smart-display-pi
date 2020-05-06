<?php
session_start("grow_session");
require_once("clsDB.php");
require_once("clsSettings.php");
require_once("clsGrowth.php");
require_once("clsWaterings.php");
require_once("clsNumber.php");
require_once("clsStash.php");
require_once("clsStrainGrow.php");
require_once("clsGrow.php");
// clone model
if(!defined('CLONES_CLASS')){
	define('CLONES_CLASS',true);
	class clsClones{
		// class properties
		public $id;
		public $garden_id;
		public $parent_id;
		public $parent_name;
		public $name;
		public $label_id;
		public $label;
		public $label_full;
		public $garden_name;
		public $garden_stage;
		public $photo;
		public $photo_date;
		public $height;
		public $height_goal;
		public $width;
		public $depth;
		public $variation;
		public $canopy;
		public $growth;
		public $strain;
		public $strain2;
		public $start_date;
		public $flower_date;
		public $harvest_date;
		public $end_date;
		public $pot;
		public $grow_medium;
		public $waterings;
		public $phLevels;
		public $location_history;
		public $flower_schedule;
		public $flower_schedule_date;
		public $flower_schedule_index;
		public $harvest_timeline;
		public $harvest_timeline_index;
		public $harvest_schedule;
		public $track_measurements;
		public $track_watering;
		public $yield;
		public $yield_date;
		public $is_dead;
		private $fam;
		
		public function clsClones($db,$clones_row){
			$db = clsDB::$db_g;
			$this->id = $clones_row['id'];
			$this->name = $clones_row['name'];
			$label_table = $db->select("SELECT `labels`.`id`, `labels`.`name`, `labels`.`full_name` FROM `labels` INNER JOIN `label_clone` on `labels`.`id` = `label_clone`.`label_id` WHERE `label_clone`.`clone_id` = '$this->id'");
			$this->label = $label_table[0]['name'];
			$this->label_full = $label_table[0]['full_name'];
			$this->label_id = $label_table[0]['id'];
			$this->parent_id = $clones_row['parent_id'];
			$this->is_dead = true;
			if(is_null($clones_row['time_of_death']))
				$this->is_dead = false;
			if($this->parent_id > 0){
				$parent = $db->select("SELECT `name` FROM `clones` WHERE `id` = '$this->parent_id'");
				$this->parent_name = $parent[0]['name'];
			}
			$this->height_goal = new clsNumberLength($clones_row['height_goal'],$_SESSION['metric']);
			// load the latest photo
			$photo_table = $db->select("SELECT * FROM `clone_photos` WHERE `clone_id` = '$this->id' ORDER BY `date` DESC");
			$this->photo = $photo_table[0]['path'];
			$this->photo_date = $photo_table[0]['date'];
			// load the latest photo
			$waterings_table = $db->select("SELECT * FROM `waterings` WHERE `clone_id` = '$this->id' ORDER BY `date` DESC");
			// load the latest photo
			
			$this->growth = clsGrowth::loadGrowth($db,$this->id);
			$this->height = $this->growth[0]->height;
			$this->width = $this->growth[0]->width;
			$this->depth = $this->growth[0]->depth;
			$this->variation = $this->growth[0]->variation;
			if($this->growth[0])
				$this->canopy = $this->growth[0]->area();
			//$grwoth_table = $db->select("SELECT * FROM `clone_growth` WHERE `clone_id` = '$this->id' ORDER BY `date` DESC");
			//$this->height = $grwoth_table[0]['height'];
			//$this->width = $grwoth_table[0]['width'];

			// load teh garden id and name
			$location_table = $db->select("SELECT `garden_id` FROM `clone_location` WHERE `clone_id` = '$this->id'");
			$this->garden_id = $clones_row['garden_id']; //$location_table[0]['garden_id'];
			$garden_table = $db->select("SELECT `name`, `track_measurements`, `track_watering`, `stage` FROM `gardens` WHERE `id` = '$this->garden_id'");
			$this->garden_name = $garden_table[0]['name'];
			$this->garden_stage = $garden_table[0]['stage'];
			$this->track_watering = $garden_table[0]['track_watering'];
			$this->track_measurements = $garden_table[0]['track_measurements'];
			
			// load teh strains
			$strain_table = $db->select("SELECT * FROM `strains` WHERE `id` = '".$clones_row['strain_id']."' OR `id` = '".$clones_row['second_strain_id']."'");
			if($strain_table[0]['id'] == $clones_row['strain_id']){
				$this->strain = new clsStrain($strain_table[0]);
				if(is_array($strain_table[1])){
					$this->strain2 = new clsStrain($strain_table[1]);
				}
			} elseif(is_array($strain_table[1])){
				$this->strain = new clsStrain($strain_table[1]);
				$this->strain2 = new clsStrain($strain_table[0]);
			}
			$this->start_date = new clsNumberDate($clones_row['start_date']);
			$this->flower_date = new clsNumberDate($clones_row['flower_date']);
			$this->harvest_date = new clsNumberDate($clones_row['harvest_date']);
			$this->end_date = new clsNumberDate($clones_row['time_of_death']);
			if(is_null($this->end_date->raw_value)){
				if(is_null($this->harvest_date->raw_value))
					$this->end_date = new clsNumberDate(date("Y-m-d H:i:s",time()));
				else  {
					$this->end_date = $this->harvest_date;
				}
			}
			
			
			// load pot
			$pot_table = $db->select("SELECT * FROM `clone_pot` WHERE `clone_id` = '$this->id' ORDER BY `date` DESC LIMIT 1");
			$pots_t = $db->select("SELECT * FROM `pots` WHERE `id` = '".$pot_table[0]['pot_id']."'");
			$this->pot = new clsPot($pots_t[0]);
			$this->pot->date = new clsNumberDate($pot_table[0]['date']);
/*			$this->pot->id = $pots_t[0]['id'];
			$this->pot->name = $pots_t[0]['name'];
			$this->pot->width = new clsNumberLength($pots_t[0]['width'],$_SESSION['metric']);
			$this->pot->height = new clsNumberLength($pots_t[0]['height'],$_SESSION['metric']);
			$this->pot->volume = new clsNumberVolume($pots_t[0]['volume']);*/
			// load ph
			$ph_table = $db->select("SELECT * FROM `soil_ph_level` WHERE `clone_id` = '$this->id' ORDER BY `date` DESC LIMIT 1");
			$this->phLevels = new clspHlevel($ph_table[0]);
			// load grow medium
			if($this->pot->grow_medium == 0)
				$medium_table = $db->select("SELECT * FROM `grow_medium` WHERE `id` = '".$clones_row['grow_medium_id']."' LIMIT 1");
			else
				$medium_table = $db->select("SELECT * FROM `grow_medium` WHERE `id` = '".$this->pot->grow_medium."' LIMIT 1");
			$this->grow_medium = new clsGrowMedium($medium_table[0]);
			// load waterings
			$this->waterings = clsWaterings::recentWaterings($db,$this->id);
			// load location history
			$this->location_history = clsCloneLocation::getCloneLocations($this->id);
			/*
			list($harvest_schedule) = clsDB::$db_g->select("SELECT `harvest_schedule`.`id`, `harvest_schedule`.`timeline`, `harvest_schedule`.`date`, `harvest_timelines`.`name` FROM `harvest_schedule` INNER JOIN `harvest_timelines` ON `harvest_schedule`.`timeline` = `harvest_timelines`.`id` WHERE `harvest_schedule`.`clone_id` = '$this->id' ");
			if(count($harvest_schedule)){
				$harvest_timelines = clsDB::$db_g->select("SELECT * FROM `harvest_schedule` WHERE `timeline` = '".$harvest_schedule['timeline']."' ORDER BY `date` ASC");
				$this->flower_schedule = new clsNumberDate(date("Y-m-d",strtotime($harvest_schedule['date'])-($this->strain->flowering_days*60*60*24)));
				$this->harvest_schedule = new clsNumberDate($harvest_schedule['date']);
				$this->harvest_schedule_id = $harvest_schedule['id'];
				$this->harvest_timeline = $harvest_schedule['name'];
				$this->harvest_timeline_index = 0;
				foreach($harvest_timelines as $timelines){
					$this->harvest_timeline_index++;
					if($timelines['clone_id'] == $this->id){
						break;
					}
				}
			}
			*/
			// flower_schedule_date
			if($clones_row['flower_schedule_date'])
				$this->flower_schedule_date = $clones_row['flower_schedule_date'];
			// calculate the yield
			$yield_table = clsDB::$db_g->select("SELECT `weight`, `date` FROM `yield` WHERE `clone_id` = '$this->id' ORDER BY `date` DESC");
			if(count($yield_table) > 0){
				$weight = 0;
				foreach($yield_table as $yield_row){
					$weight += $yield_row['weight'];
				}
				$this->yield = new clsNumberMass($weight);
				if($this->garden_id == 0){
					$this->end_date = new clsNumberDate($yield_table[0]['date']);
				}
			}
			if(strtotime($this->photo_date) > strtotime($this->end_date)){
				$this->end_date = new clsNumberDate(date("Y-m-d H:i:s",strtotime($this->photo_date)));
			}
			$harvest = new clsHarvest($this);
			if($harvest->weedGone() && $harvest->weedGone() != ""){
				if(strtotime($harvest->weedGone()) > strtotime($this->end_date)){
					$this->end_date = new clsNumberDate(date("Y-m-d H:i:s",strtotime($harvest->weedGone())));
				}
			}
			return $this;
		}
		public function averageDaysBetweenTransplant(){
			$pot_table = clsDB::$db_g->select("SELECT * FROM `clone_pot` WHERE `clone_id` = '$this->id' ORDER BY `date` ASC");
			$start = strtotime($pot_table[0]['date']);
			$time = 0;
			for($i = 1; $i < count($pot_table); $i++){
				$time += strtotime($pot_table[$i]['date']) - $start;
				$start = strtotime($pot_table[$i]['date']);
			}
			
			if((count($pot_table)-1) == 0)
				$days = round($time/60/60/24);
			else
				$days = round(($time/(count($pot_table)-1))/60/60/24);
			if($days < 14) return 14;
			return $days;
		}
		public function labelFull(){
			if($this->label_full)
				return $this->label_full;
			return $this->label;
		}
		public function hasStash(){
			$stashes = clsStash::load_stash_from_clone(1,$this,false,false);
			if(count($stashes))
				return $stashes[0];
			return false;
		}
		public function strains_guid(){
			if($this->strain2)
				return $this->strain->id.".".$this->strain2->id;
			return $this->strain->id.".0";
		}
		public function children(){
			$children_table = clsDB::$db_g->select("SELECT `id`, `name`, `flower_date`, `harvest_date`, `time_of_death` FROM `clones` WHERE `parent_id` = ".$this->id." ORDER BY `time_of_death` ASC, `harvest_date` ASC, `flower_date` ASC;");
			$children = array();
			if(count($children_table)){
				foreach($children_table as $child_row)
					array_push($children,new clsCloneChild($child_row));
			}
			return $children;
		}
		public function growth_stage(){
			if($this->is_dead)
				return "dead";
			if($this->harvest_date != "")
				if($this->garden_id != 0)
					return "drying";
				else 
					return "harvest";
			if($this->days_in_flower() > 0){
				return "flowering";
			}
			if($this->days_in_veg() > 0){
				return "vegitation";
			}
			if($this->days_in_nursery() > 0){
				return "nursery";
			}
			return "new";
		}
		public function photos_json(){
			$photos_table = clsDB::$db_g->select("SELECT `path` FROM `clone_photos` WHERE `clone_id` = ".$this->id." ORDER BY `date` ASC;");
			$photos = array();
			foreach($photos_table as $photo_row){
				array_push($photos,$photo_row['path']);
			}
			return json_encode($photos);
		}
		
		public function last_scheduled_harvest(){
			
		}
		public function clone_window(){
			$day_of_time = 60*60*24;
			$next = new clsNumberDate(date("Y-m-d",strtotime($this->last_scheduled_harvest())+($day_of_time*28*2)));
			$clone_start = new clsNumberDate(date("Y-m-d",strtotime($next)-($this->flowering_days()*$day_of_time)-($this->max_days_in_nursery()*$day_of_time)-((28*3)*$day_of_time)));
			$clone_ideal = new clsNumberDate(date("Y-m-d",strtotime($next)-($this->flowering_days()*$day_of_time)-($this->average_days_in_nursery()*$day_of_time)-((28*3)*$day_of_time)));
			$clone_end = new clsNumberDate(date("Y-m-d",strtotime($next)-($this->flowering_days()*$day_of_time)-($this->min_days_in_nursery()*$day_of_time)-((28*3)*$day_of_time)));
		}
		public function cut_clones(){
			$strainInfo = $this->getStrainGrow();
			if($this->flower_schedule_index == 0 && $strainInfo->plants_in_veg() == 1)
				return true;
			return false;
		}
		
		public function plants_in_veg(){
			$strainInfo = $this->getStrainGrow();
			return $strainInfo->plants_in_veg();
		}
		public function plants_in_nursery(){
			$strainInfo = $this->getStrainGrow();
			return $strainInfo->plants_in_nursery();
		}
		public function latest_clones_cut(){
			$strainInfo = $this->getStrainGrow();

		}
		public function getStrainGrow(){
			if(clsGrow::$grow){
				$grow = clsGrow::$grow;
				foreach($grow->strains as $sI){
					if($sI->strain_id == $this->strain->id){
						if($this->strain2){
							if($sI->strain2_id == $this->strain2->id)
								return $sI;
						} else {
							if($sI->strain2_id == 0)
								return $sI;
						}
					}
				}
			}
			if($this->strain2)
				return new clsStrainGrow($this->strain->id, $this->strain2->id);
			return new clsStrainGrow($this->strain->id, 0);
			
		}
		
		public function strain_name(){
			if($this->strain2){
				return $this->strain->name." x ".$this->strain2->name;
			}
			return $this->strain->name;
		}
		public function flowering_days(){
			$days = $this->strain->flowering_days;
			if($this->strain2){
				$days = round(($this->strain->flowering_days*0.75+$this->strain2->flowering_days*0.25));
			}
			$strainInfo = $this->getStrainGrow();
			if($strainInfo->average_days_flowering() > 0)
				$days = round(($days + $strainInfo->average_days_flowering())/2);
			return $days;
		}
		public function age_weeks(){
			$start = strtotime($this->start_date->raw_value);
			$age = strtotime($this->end_date) - $start;
			if($this->harvest_date != "")
				$age = strtotime($this->harvest_date) - $start;
			return round($age/60/60/24/7);
		}
		public function age_days(){
			$start = strtotime($this->start_date->raw_value);
			$age = strtotime($this->end_date->raw_value) - $start;
			if($this->harvest_date != "")
				$age = strtotime($this->harvest_date) - $start;
			return round($age/60/60/24);
		}
		public function page_title(){
			if($this->parent_id)
				return $this->name.": ".$this->label;
			return $this->name;
		}
		public function age(){
			$start = strtotime($this->start_date->raw_value);
			$age = strtotime($this->end_date) - $start;
			if($this->harvest_date != "")
				$age = strtotime($this->harvest_date) - $start;
			$time_span = ($age)/60/60/24;
			if($time_span < 7){
				if(floor(($age)/60/60/24) == 1)
					return floor(($age)/60/60/24)." day";
				else
					return floor(($age)/60/60/24)." days";
			} elseif($time_span < 60) {
				if(round(($age)/60/60/24/7,1) == 1)
					return round(($age)/60/60/24/7,1)." week";
				else
					return round(($age)/60/60/24/7,1)." weeks";
			} elseif($time_span < 365) {
				if(round(($age)/60/60/24/30,1) == 1)
					return round(($age)/60/60/24/30,1)." month";
				else
					return round(($age)/60/60/24/30,1)." months";
			}else{
				if(round(($age)/60/60/24/365,1) == 1)
					return round(($age)/60/60/24/365,1)." year";
				else
					return round(($age)/60/60/24/365,1)." years";
			}
			return round($age/60/60/24)." days";
		}
		public function ph(){
			if(count($this->waterings) == 0)
				return 5.5;
			if($this->grow_medium->hydroponic && $this->waterings[0]->ph == 0)
				return $this->waterings[1]->ph;
			if($this->grow_medium->hydroponic)
				return $this->waterings[0]->ph;
			if(count($this->waterings) == 0 && $this->age_weeks() < 1 && $this->grow_medium->name = "Rockwool")
				return 5.5;
			if($this->phLevels->ph == 0 && $this->waterings[0]->ph >0)
				return $this->waterings[0]->ph;
			if($this->waterings[0]->ph >0)
				return ($this->waterings[0]->ph+$this->phLevels->ph)/2;
			if($this->waterings[1]->ph >0)
				return ($this->waterings[1]->ph+$this->phLevels->ph)/2;
			return ($this->waterings[0]->nutriens->pH+$this->phLevels->ph)/2;
			return $this->phLevels->ph;
		}
		public function moisture(){
			if($this->grow_medium->hydroponic)
				return 8;
			$moist = $this->phLevels->moisture;
			$h = $this->hours_since_watered();
			if($h < 3)
				$moist = $this->phLevels->moisture + 9;
			elseif($h < 6)
				$moist = $this->phLevels->moisture + 7;
			elseif($h < 12)
				$moist = $this->phLevels->moisture + 8;
			elseif($h < 18)
				$moist = $this->phLevels->moisture + 6;
			if($moist > 10)
				$moist = 10;
			return $moist;
		}
		
		public function flower_parent_offset(){
			$strainInfo = $this->getStrainGrow();
			$days = $this->average_days_in_nursery() - $strainInfo->nursery_clone_age_days();
			if($days < 0)
				$days = 0;
			return $days;
		}
		// answer some more questions
		public function average_days_in_nursery(){
			if($this->rooted_total() == 0)
				return 14;
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$days_in_nursery = 0;
			$c = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_veg() > 0 && $clone->days_in_nursery() > 1){
					$days_in_nursery += $clone->days_in_nursery();
					$c++;
				}
			}
			return round($days_in_nursery/$c);
		}
		public function max_days_in_nursery(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$days_in_nursery = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				$days_in_n = $clone->days_in_nursery();
				if($days_in_n > $days_in_nursery)
					$days_in_nursery = $clone->days_in_nursery();
			}
			return $days_in_nursery;
		}
		public function min_days_in_nursery(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$days_in_nursery = 100;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_veg() > 0 && $clone->days_in_nursery() > 1){
					$days_in_n = $clone->days_in_nursery();
					if($days_in_n > 0 && $days_in_n < $days_in_nursery)
						$days_in_nursery = $clone->days_in_nursery();
				}
			}
			return $days_in_nursery;
		}
		public function average_days_in_nursery_fail(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$days_in_nursery = 0;
			$c = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_veg() <= 7 && $clone->days_in_nursery() > 1 && $clone->is_dead){
					$din = $clone->days_in_nursery();
					$days_in_nursery += $din;
					$c++;
				}
			}
			return round($days_in_nursery/$c);
		}
		public function max_days_in_nursery_fail(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$days_in_nursery = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				$days_in_n = $clone->days_in_nursery();
				if($clone->days_in_veg() <= 7 && $days_in_n > $days_in_nursery && $clone->is_dead)
					$days_in_nursery = $clone->days_in_nursery();
			}
			return $days_in_nursery;
		}
		public function min_days_in_nursery_fail(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$days_in_nursery = 100;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_veg() <= 7 && $clone->days_in_nursery() > 1 && $clone->is_dead){
					$days_in_n = $clone->days_in_nursery();
					if($days_in_n > 0 && $days_in_n < $days_in_nursery)
						$days_in_nursery = $clone->days_in_nursery();
				}
			}
			return $days_in_nursery;
		}
		
		public function failed_to_root_total(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$failed = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_veg() <= 7 && $clone->days_in_nursery() > 1 && $clone->is_dead){
					$failed++;
				}
			}
			return $failed;
		}
		public function rooted_total(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$rooted = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_veg() > 7 && $clone->days_in_nursery() > 1){
					$rooted++;
				}
			}
			return $rooted;
		}
		public function clones_cut_total(){
			$strain_id = $this->strain->id;
			$clone_table = clsDB::$db_g->select("SELECT * FROM `clones` WHERE `strain_id` = '$strain_id'");
			$cut = 0;
			foreach($clone_table as $clone_row){
				$clone = new clsClones(clsDB::$db_g, $clone_row);
				if($clone->days_in_nursery() > 1){
					$cut++;
				}
			}
			return $cut;
		}
		public function rooting_success_rate(){
			return 100-$this->rooting_failure_rate();
		}
		public function rooting_failure_rate(){
			return round(($this->failed_to_root_total() / $this->clones_cut_total())*100,1);
		}
		public function garden_on_day($day, $use_parent_garden = false){
			$day_time = strtotime($day);
			foreach($this->location_history as $location){
				if(strtotime($location->date) <= $day_time){
					if($use_parent_garden && $location->parent_id > 0)
						return $location->parent_id;
					return $location->garden_id;
				}
			}
		}
		public function garden_date_on_day($day){
			$day_time = strtotime($day);
			foreach($this->location_history as $location){
				if(strtotime($location->date) <= $day_time){
					return $location->date;
				}
			}
		}
		public function days_in_garden_on_date($day){
			$i = 0;
			$day_time = strtotime($day);
			foreach($this->location_history as $location){
				if(strtotime($location->date) <= $day_time){
					$age = $day_time - strtotime($location->date);
					return floor($age/60/60/24);
				}
				$i++;
			}
			return $this->age_days();
		}
		
		public function days_in_nursery(){
			$i = 0;
			$days = 1;
			$day_time = strtotime($day);
			foreach($this->location_history as $location){
				if($location->stage == 1){
					if($i == 0)
						$age = strtotime($this->end_date) - strtotime($location->date);
					else
						$age = strtotime($this->location_history[$i-1]->date) - strtotime($location->date);
					$days += floor($age/60/60/24);
				}
				$i++;
			}
			return $days;
		}
		public function days_in_veg(){
			$i = 0;
			$days = 0;
			$day_time = strtotime($day);
			foreach($this->location_history as $location){
				if($location->stage == 2){
					if($i == 0)
						$age = strtotime($this->end_date) - strtotime($location->date);
					else
						$age = strtotime($this->location_history[$i-1]->date) - strtotime($location->date);
					$days += floor($age/60/60/24);
				}
				$i++;
			}
			return $days;
		}
		public function days_til_flower(){
			$time = strtotime($this->flower_schedule_date) - time();
			$days = round($time/60/60/24);
			return $days;
		}
		public function days_in_flower(){
			$i = 0;
			$days = 0;
			$day_time = strtotime($day);
			foreach($this->location_history as $location){
				if($location->stage == 3){
					if($i == 0)
						$age = strtotime($this->end_date) - strtotime($location->date);
					else
						$age = strtotime($this->location_history[$i-1]->date) - strtotime($location->date);
					$days += floor($age/60/60/24);
				}
				$i++;
			}
			return $days;
		}
		public function days_drying(){
			$days = 0;
			if($this->harvest_date == "")
				return 0;
			// still drying
			if($this->garden_id != 0){
				$age = time() - strtotime($this->harvest_date);
				$days += floor($age/60/60/24);
			} else {
				$yield_table = clsDB::$db_g->select("SELECT `date` FROM `yield` WHERE `clone_id` = '".$this->id."' ORDER BY `date` DESC LIMIT 1;");
				if(count($yield_table)){
					$yield_date = $yield_table[0]['date'];
					$age = strtotime($yield_date) - strtotime($this->harvest_date);
					$days += floor($age/60/60/24);
				}
			}
			return $days;
		}
		public function drying_on_day($date){
			if($this->harvest_date != "" &&	strtotime($this->harvest_date) <= strtotime($date)){
				if($this->garden_id != 0 && strtotime($date) <= time()){
					return true;
				} elseif(strtotime($date) <= strtotime($this->harvest_date)+($this->days_drying()*60*60*24)){
					return true;
				}
			}
			return false;
		}
		
		public function measure_plant(){
			if($this->days_in_flower() > 14)
				return false;
			if(date("G",time()) < 12 || $this->location_history[0]->stage == 1)
				return false;
			global $settings;
			if(strtotime($this->growth[0]->datetime) < strtotime(date("Y-m-d",time()-$settings->days_between_measuring*60*60*24)))
				return true;
			return false;
		}
		public function photo_plant(){
			if($this->hasStash() && date("Y-m-d",strtotime($this->photo_date)) != date("Y-m-d")){
				return true;
			}
			if(date("G",time()) < 12)
				return false;
			if($this->garden_id == 0)
				return false;
			if(isset($this->harvest_date) && $this->harvest_date != "")
				return false;
			global $settings;
			if(strtotime($this->photo_date) < strtotime(date("Y-m-d",time()-$settings->days_between_photos*60*60*24)))
				return true;
			return false;
		}
		public function measure_pH(){
			if($this->days_since_watered() < 1){
				return false;
			}
			if(isset($this->harvest_date) && $this->harvest_date != "")
				return false;
			if(date("G",time()) < 12 || $this->grow_medium->hydroponic)
				return false;
			global $settings;
			if(strtotime($this->phLevels->date) < strtotime(date("Y-m-d",time()-0*60*60*24)))
				return true;
			return false;
		}
		public function transplant_plant(){
			if(isset($this->harvest_date) && $this->harvest_date != "")
				return false;
			if($this->days_in_flower() > 0)
				return false;
			if(date("G",time()) < 12)
				return false;
			global $settings;
			if(count($this->waterings) < 4)
				return false;
			if(floor((time() - strtotime($this->pot->date))/60/60/24) < 14)
				return false;
			if($this->days_between_watering() <= $settings->days_between_watering + ($this->days_since_transplanted()/20) && $this->days_since_watered() == 1 && $this->needs_food())
				return true;
			return false;	
		}
		public function days_till_harvest(){
			if($this->days_in_flower() < 14)
				return 1000;
			$havest_date = new clsNumberDate(date("Y-m-d",time() + ($this->strain->flowering_days - $this->days_in_flower())*60*60*24));
			return round((strtotime($havest_date) - time())/60/60/24);
		}
		public function transfer_plant(){
			if(isset($this->harvest_date) && $this->harvest_date != "")
				return false;
			//if($this->height > $this->height_goal)
				//return true;
			if(date("G",time()) < 12)
				return false;
			if($this->days_in_nursery() > 14 && $this->location_history[0]->stage == 1)
				return true;
			if($this->flower_schedule_date != "" && strtotime($this->flower_schedule_date) == strtotime(date("Y-m-d",time())))
				return true;
			return false;
		}
		public function root_plant(){
			if(date("G",time()) < 12)
				return false;
			if($this->days_in_nursery() > 14 && $this->location_history[0]->stage == 1)
				return true;
			return false;
		}
		
		public function days_between_watering(){
			$dsw = $this->days_since_watered();
			if(count($this->waterings) < 4)
				return round($dsw,1);
			$i = 1;
			$days = 0;
			foreach($this->waterings as $watering){
				$date1 = strtotime(date("Y-m-d",strtotime($watering->datetime)));
				$date2 = strtotime(date("Y-m-d",strtotime($this->waterings[$i++]->datetime)));
				$days += floor(abs($date1 - $date2)/(60*60*24))-1;
				if($i > 3)
					break;
			}
			$dbw = $days/($i-1);
			if($dbw > $dsw)
				return round($dbw,1);
			$p = $dbw/$dsw;
			return round(($dbw*$p)+($dsw*(1-$p)),1);
		}
		public function needs_food(){
			if($this->waterings[0]->nutriens->food == 0 && $this->waterings[1]->nutriens->food == 0)
				return true;
			return false;
		}
		public function water_plant(){
			if($this->measure_pH())
				return false;
			if(round($this->moisture()) < 3){
				return true;
			}
			return false;
		}
		public function flower_plant(){
			if($this->flower_date == "" && $this->flower_schedule_date != "" && strtotime($this->flower_schedule_date) < time()){
				return true;
			}
			return false;
		}
		public function harvest_plant(){
			if($this->harvest_date == "" && $this->days_in_flower() >= $this->flowering_days()){
				return true;
			}
			return false;
		}
		public function measure_yield(){
			if($this->harvest_date != ""){
				return true;
			}
			return false;
		}
		public function days_since_watered(){
			if(count($this->waterings) == 0)
				return 100000;
			$now = strtotime(date("Y-m-d",time()));
			$date = strtotime(date("Y-m-d",strtotime($this->waterings[0]->datetime)));
			return floor(($now - $date)/60/60/24);
		}
		public function days_since_transplanted(){
			if(count($this->waterings) == 0)
				return 100000;
			$now = strtotime(date("Y-m-d",time()));
			$date = strtotime(date("Y-m-d",strtotime($this->pot->date)));
			return floor(($now - $date)/60/60/24);
		}
		public function hours_since_watered(){
			if(count($this->waterings) == 0)
				return 100000;
			$now = time();
			$date = strtotime($this->waterings[0]->datetime);
			return floor(($now - $date)/60/60);
		}
		public function water_today(){
			if(isset($this->harvest_date) && $this->harvest_date != "")
				return false;
			global $settings;
			if($this->days_since_watered() < 1 || $this->grow_medium->hydroponic){
				return false;
			}
			if($this->days_since_watered() > $settings->max_water_days){
				return true;
			}
			if($this->moisture() < $settings->min_moisture){
				return true;
			}
			return false;
		}
		public function water_tomorrow(){
			global $settings;
			if($this->days_since_watered() + 1 > $settings->max_water_days){
				return true;
			}
			$now = time();
			$date = strtotime($this->phLevels->date);
			return floor(($now - $date)/60/60/24);
			if($this->phLevels->moisture < $settings->min_moisture && floor(($now - $date)/60/60/24) == 1){
				
			}
			return false;
		}
		
		public function harvest_date_projected(){
			if($this->harvest_date != ""){
				return $this->harvest_date;
			}
			if($this->flower_date != ""){
				return date("Y-m-d",strtotime($this->flower_date) + $this->flowering_days()* 60*60*24);
			}
			if($this->flower_schedule_date != ""){
				return date("Y-m-d",strtotime($this->flower_schedule_date) + $this->flowering_days()* 60*60*24);
			}
			return NULL;
		}
		
		public function type(){
			if($this->strain2){
				$type = "";
				if(($this->strain->indica*0.75 + $this->strain2->indica*0.25) == ($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)) {
						$type = "hybrid";
				} else {
					if(($this->strain->indica*0.75 + $this->strain2->indica*0.25) > ($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)){
						$type = "indica";
						if(($this->strain->indica*0.75 + $this->strain2->indica*0.25)/2 < 0.7){
							if(($this->strain->indica*0.75 + $this->strain2->indica*0.25)/2 > 0.60)
								$type .= " dominant";
							$type .= " hybrid";
						}
					}else{
						$type = "sativa";
						if(($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)/2 < 0.7){
							if(($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)/2 > 0.60)
								$type .= " dominant";
							$type .= " hybrid";
						}
					}
				}
				return $type;
			}
			return $this->strain->type();
		}
		public function type_short(){
			if($this->strain2){
				$type = "";
				if(($this->strain->indica*0.75 + $this->strain2->indica*0.25) == ($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)) {
						$type = "hybrid";
				} else {
					if(($this->strain->indica*0.75 + $this->strain2->indica*0.25) > ($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)){
						$type = "indica";
						if(($this->strain->indica*0.75 + $this->strain2->indica*0.25)/2 < 0.7){
							if(($this->strain->indica*0.75 + $this->strain2->indica*0.25)/2 > 0.60)
								$type .= " dom";
							$type .= " hybrid";
						}
					} else {
						$type = "sativa";
						if(($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)/2 < 0.7){
							if(($this->strain->sativa*0.75 + $this->strain2->sativa*0.25)/2 > 0.60)
								$type .= " dom";
							$type .= " hybrid";
						}
					}
				}
				return $type;
			}
			return $this->strain->type_short();
		}
		public function growthRate(){
			return clsGrowth::growthRate($this->growth);
		}
		public function averageWeeklyGrowthRate(){
			
		}
		public function averageWeeklyGrowthRateForWeek($week){
			
		}
		public function weeksToGoal(){
			$gr = $this->growthRate();
			$h = $this->height;
			$weeks = 1;
			for($weeks = 1; $weeks < 100; $weeks++){
//				$h = $this->height * pow(1+$gr,$weeks);				
				$h = $h * (1+$gr);				
				if($h >= $this->height_goal)
					break;
			}
			return $weeks;
		}
		
		
		public function projected_yield(){
			//round(($clone->width/100)*($clone->height/100)*$clone->strain->yield_rate)
			if($this->depth)
				return new clsNumberMass((($this->depth->raw_value/100 * $this->width->raw_value/100)-($this->variation/100))*$this->average_yield_rate());
			return new clsNumberMass(($this->height->raw_value/100 * $this->width->raw_value/100)*$this->average_yield_rate());
		}
		public function projected_yield_low(){
			//round(($clone->width/100)*($clone->height/100)*$clone->strain->yield_rate)
			if($this->depth)
				return new clsNumberMass((($this->depth->raw_value/100 * $this->width->raw_value/100)-($this->variation/100))*($this->average_yield_rate()/2));
			return new clsNumberMass(($this->height->raw_value/100 * $this->width->raw_value/100)*($this->average_yield_rate()/2));
		}
		public function projected_yield_goal(){
			//round(($clone->width/100)*($clone->height/100)*$clone->strain->yield_rate)
			if($this->depth)
				return new clsNumberMass((($this->depth->raw_value/100 * $this->width->raw_value/100)-($this->variation/100))*$this->yield_rate());
			return new clsNumberMass(($this->height->raw_value/100 * $this->width->raw_value/100)*$this->yield_rate());
		}
		
		public function actual_yield_rate(){
			return $this->yield->raw_value / (($this->depth->raw_value/100 * $this->width->raw_value/100)-($this->variation/100)); 
		}
		
		public function average_yield_rate(){
			if(!$this->fam)
				$this->fam = clsClones::loadFamilyFinishedHarvests($this);
			
			if(count($this->fam) == 0)
				return $this->yield_rate()*0.01;
			
			$yr = 0;
			foreach($this->fam as $fc){
				$yr += $fc->actual_yield_rate();
			}
			return round($yr/count($this->fam),2);
		}
		
		public function yield_rate(){
			$iyr = $this->ideal_yield_rate();
			if(!$this->fam)
				$this->fam = clsClones::loadFamilyFinishedHarvests($this);
			if(count($this->fam)>0){
				$big = 0;
				foreach($this->fam as $fc){
					if($fc->actual_yield_rate() > $big){
						$big = $fc->actual_yield_rate();
					}
				}
				if(count($this->fam)>1)
					return ($big * 0.75 + $big * 0.25);
				return ($big + $iyr) / 2;
			}
			return $iyr;
		}
		public function ideal_yield_rate(){
			if($this->depth){
				if(!isset($this->strain2))
					return $this->strain->yield_rate;
				return ($this->strain->yield_rate+$this->strain2->yield_rate)/2;
			}
			if(!isset($this->strain2))
				return $this->strain->yield_rate/4;
			return ($this->strain->yield_rate+$this->strain2->yield_rate)/2/4;
		}
		
		public static function loadCloneAt($db,$id){
			$clones_table = $db->select("SELECT * FROM `clones` WHERE `id` = '$id'");
			return new clsClones($db,$clones_table[0]);
		}
		
		public static function loadClone($db){
			$clones = array();
			$clones_table = $db->select("SELECT * FROM `clones` WHERE `garden_id` > 0 ORDER BY `strain_id`");
			////print_r($clones_table);
			foreach($clones_table as $clones_row){
				$clone = new clsClones($db,$clones_row);
				array_push($clones,$clone);
			}
			return $clones;
		}
		public static function loadCloneInGarden($db,$garden_id){
			$clones = array();
			$clones_table = $db->select("SELECT * FROM `clones` WHERE `garden_id` = '$garden_id' ORDER BY `strain_id`");
			////print_r($clones_table);
			foreach($clones_table as $clones_row){
				$clone = new clsClones($db,$clones_row);
				array_push($clones,$clone);
			}
			usort($clones,'sort_clones_by_pot');
			return $clones;
		}
		public static function loadFamily($clone){
			$db = clsDB::$db_g;
			$clones = array();
			$clones_table = $db->select("SELECT * FROM `clones` WHERE `strain_id` = '$clone->strain->id' AND `second_strain_id` = '$clone->strain2->id' ORDER BY `strain_id`");
			////print_r($clones_table);
			foreach($clones_table as $clones_row){
				$clone = new clsClones($db,$clones_row);
				array_push($clones,$clone);
			}
			return $clones;
		}
		public static function loadFamilyFinishedHarvests($clone){
			$db = clsDB::$db_g;
			$clones = array();
			$clones_table = $db->select("SELECT * FROM `clones` WHERE `strain_id` = '".$clone->strain->id."' AND `second_strain_id` = '".$clone->strain2->id."' AND `garden_id` = '0' AND `harvest_date` IS NOT NULL");
			////print_r($clones_table);
			foreach($clones_table as $clones_row){
				$clone = new clsClones($db,$clones_row);
				array_push($clones,$clone);
			}
			return $clones;
		}
		public static $finishedHarvestsCache;
		public static function loadFinishedHarvests(){
			if(clsClones::$finishedHarvestsCache){
				return clsClones::$finishedHarvestsCache;
			}
			$db = clsDB::$db_g;
			$clones = array();
			$clones_table = $db->select("SELECT * FROM `clones` WHERE `garden_id` = '0' AND `harvest_date` IS NOT NULL");
			////print_r($clones_table);
			foreach($clones_table as $clones_row){
				$clone = new clsClones($db,$clones_row);
				array_push($clones,$clone);
			}
			clsClones::$finishedHarvestsCache = $clones;
			return $clones;
		}
	}
	
	class clsStrain {
		public $id;
		public $name;
		public $short_name;
		public $indica;
		public $sativa;
		public $flowering_days;
		public $url;
		public $yield_rate;
		public function clsStrain($row){
			$this->id = $row['id'];
			$this->name = $row['name'];
			$this->short_name = $row['short_name'];
			$this->indica = $row['indica'];
			$this->sativa = $row['sativa'];
			$this->flowering_days = $row['flowering_days'];
			$this->url = $row['leafly_url'];
			$this->yield_rate = $row['yield_rate'];
		}
		public function type(){
			$type = "";
			if($this->indica >= 0.9){
				return "indica";	
			}
			if($this->sativa >= 0.9){
				return "sativa";	
			}
			if($this->indica == 1){
				return "indica";	
			}
			if($this->indica == $this->sativa) {
					$type = "hybrid";
			} else {
				if($this->indica > $this->sativa){
					$type = "indica";
					if($this->indica < 0.8){
						if($this->indica > 0.60)
							$type .= " dominant";
						$type .= " hybrid";
					}
				} else {
					$type = "sativa";
					if($this->sativa < 0.8){
						if($this->sativa > 0.60)
							$type .= " dominant";
						$type .= " hybrid";
					}
				}
			}
			return $type;
		}
		public function type_short(){
			$type = "";
			if($this->indica >= 0.9){
				return "indica";	
			}
			if($this->sativa >= 0.9){
				return "sativa";	
			}
			if($this->indica == $this->sativa) {
					$type = "hybrid";
			} else {
				if($this->indica > $this->sativa){
					$type = "indica";
					if($this->indica < 0.8){
						if($this->indica > 0.60)
							$type .= " dom";
						$type .= " hybrid";
					}
				} else {
					$type = "sativa";
					if($this->sativa < 0.8){
						if($this->sativa > 0.60)
							$type .= " dom";
						$type .= " hybrid";
					}
				}
			}
			return $type;
		}

		
		public static function strains($db){
			$strain_table = $db->select("SELECT * FROM `strains` ORDER BY `indica` DESC");
			$strains = array();
			foreach($strain_table as $strain_row){
				$strain = new clsStrain();
				$strain->id = $strain_row['id'];
				$strain->name = $strain_row['name'];
				$strain->short_name = $strain_row['short_name'];
				$strain->indica = $strain_row['indica'];
				$strain->sativa = $strain_row['sativa'];
				$strain->flowering_days = $strain_row['flowering_days'];
				$strain->url = $strain_row['leafly_url'];
				$strain->yield_rate = $strain_row['yield_rate'];
				array_push($strains,$strain);
			}
			return $strains;
		}
	}
	
	class clsPot {
		public $id;
		public $name;
		public $width;
		public $height;
		public $volume;
		public $grow_medium;
		public $drain_bowl;
		public $date;
		
		public function clsPot($pot_row){
			$this->id = $pot_row['id'];
			$this->name = $pot_row['name'];
			$this->width = new clsNumberLength($pot_row['width'],$_SESSION['metric']);
			$this->height = new clsNumberLength($pot_row['height'],$_SESSION['metric']);
			$this->volume = new clsNumberVolume($pot_row['volume']);
			$this->grow_medium = $pot_row['grow_medium'];
			if($pot_row['drain_bowl'] > 0){
				$drain_table = clsDB::$db_g->select("SELECT * FROM `drainage_bowls` WHERE `id` = '".$pot_row['drain_bowl']."';");
				$this->drain_bowl = new clsDrainBowl($drain_table[0]);
			}
		}
		
		public function size_text(){
			if($this->volume->raw_value >= 1){
				return round($this->volume->raw_value)."gal";
			} 
			if($this->volume->raw_value > 0.05) {
				return "cup";
			}
			return "cube";
		}
		
		public static function loadPots($db){
			$pots_table = $db->select("SELECT * FROM `pots`");
			$pots = array();
			foreach($pots_table as $pot_row){
				$pot = new clsPot($pot_row);
				array_push($pots,$pot);	
			}
			return $pots;
		}
	}
	class clsDrainBowl {
		public $id;
		public $name;
		public $width;
		
		public function clsDrainBowl($pot_row){
			$this->id = $pot_row['id'];
			$this->name = $pot_row['name'];
			$this->width = new clsNumberLength($pot_row['width'],$_SESSION['metric']);
		}
	}
	class clsGrowMedium {
		public $id;
		public $name;
		public $hydroponic;
		public $notes;
		public $pHmin;
		public $pHmax;
		public function clsGrowMedium($row){
			$this->id = $row['id'];
			$this->name = $row['name'];
			$this->hydroponic = $row['hydroponic'];
			$this->notes = $row['notes'];
			$this->pHmax = $row['max_pH'];
			$this->pHmin = $row['min_pH'];
			return $this;
		}
		public static function loatGrowMedium($db){
			$mediums = array();
			$medium_table = $db->select("SELECT * FROM `grow_medium`");
			foreach($medium_table as $row){
				array_push($mediums,new clsGrowMedium($row));
			}
			return $mediums;
		}
	}
	
	class clsClonePhoto {
		public $id;
		public $clone_id;
		public $path;
		public $datetime;
		
		public static function loadClonePhotos($db,$id){
			$photos = array();
			$photo_table = $db->select("SELECT * FROM `clone_photos` WHERE `clone_id` = '$id' ORDER BY `date` DESC");
			foreach($photo_table as $photo_row){
				$photo = new clsClonePhoto;
				$photo->id = $photo_row['id'];
				$photo->clone_id = $photo_row['clone_id'];
				$photo->path = $photo_row['path'];
				$photo->datetime = new clsNumberDate($photo_row['date']);
				array_push($photos,$photo);
			}
			return $photos;
		}
		public static function getHightInPhoto($db,$photo){
			$height_table = $db->select("SELECT * FROM `clone_growth` WHERE `clone_id` = '$photo->clone_id' ORDER BY `date` DESC");
			foreach($height_table as $height_row){
				if(strtotime($photo->datetime) > strtotime($height_row['date'])){
					
					return $height_row['height'];
				}
			}
			return $height_table[0]['height'];
		}
		public static function getTransplantInPhoto($db,$photo){
			$pot_table = $db->select("SELECT * FROM `clone_pot` WHERE `clone_id` = '$photo->clone_id' ORDER BY `date` DESC");
			foreach($pot_table as $pot_row){
				if(strtotime($photo->datetime) >= strtotime($pot_row['date']) && strtotime($photo->datetime)-(24 * 60 * 60) <= strtotime($pot_row['date'])){
					$pots_t = $db->select("SELECT * FROM `pots` WHERE `id` = '".$pot_row['pot_id']."'");
					$pot_row['name'] = $pots_t[0]['name'];
					return $pot_row;
				}
			}
			return NULL;//$pot_table[0];
		}
		public static function getWateringInPhoto($db,$photo){
			$waterings_table = $db->select("SELECT * FROM `waterings` WHERE `clone_id` = '$photo->clone_id' ORDER BY `date`");
			foreach($waterings_table as $waterings_row){
				if(strtotime($photo->datetime) >= strtotime(date("Y-m-d",strtotime($waterings_row['date']))) && strtotime($photo->datetime)-(24 * 60 * 60) <= strtotime(date("Y-m-d",strtotime($waterings_row['date'])))){
					$watering = new clsWaterings();
					$watering->id = $waterings_row['id'];
					$watering->clone_id = $waterings_row['clone_id'];
					$watering->nutriens = new clsNutrients();
					$nutrients_table = $db->select("SELECT * FROM `nutrients` WHERE `id` = '".$waterings_row['nutrients_id']."'");
					$watering->nutriens->id = $nutrients_table[0]['id'];
					$watering->nutriens->name = $nutrients_table[0]['name'];
					$watering->nutriens->ingredients = $nutrients_table[0]['ingredients'];
					$watering->datetime = new clsNumberDate($waterings_row['date']);
					return $watering;
				}
			}
			return NULL;
		}
	}
	
	class clspHlevel {
		public $id;
		public $clone_id;
		public $ph;
		public $moisture;
		public $light;
		public $date;
		public function clspHlevel($ph_row){
			//print_r($ph_row);
			$this->id = $ph_row['id'];
			$this->clone_id = $ph_row['clone_id'];
			$this->ph = $ph_row['ph'];
			$this->moisture = $ph_row['moisture'];
			$this->light = $ph_row['light'];
			$this->date = new clsNumberDate($ph_row['date']);
			return $this;
		}
		public static function phLevelsForClone($db, $clone_id){
			$ph_table = $db->select("SELECT * FROM `soil_ph_level` WHERE `clone_id` = '$clone_id' ORDER BY `date`");
			$phLevels = array();
			foreach($ph_table as $ph_row){
				array_push($phLevels,new clspHlevel($ph_row));
			}
			return $phLevels;
		}
	}
	
	class clsCutting {
		public $id;
		public $parent_id;
		public $name;
		public $height;
		public $width;
		public $photo;
		public function clsCutting($clone_row){
			$db = clsDB::$db_g;
			$this->id = $clone_row['id'];
			$this->parent_id = $clone_row['parent_id'];
			$label_table = clsDB::$db_g->select("SELECT `labels`.`id`, `labels`.`name` FROM `labels` INNER JOIN `label_clone` on `labels`.`id` = `label_clone`.`label_id` WHERE `label_clone`.`clone_id` = '$this->id'");
			$this->name = $label_table[0]['name'];
			
			$growth_table = $db->select("SELECT `height`, `width` FROM `clone_growth` WHERE `clone_id` = '$this->id' ORDER BY `date` ASC LIMIT 1");
			$this->height = new clsNumberLength($growth_table[0]['height'],$_SESSION['metric']);
			$this->width = new clsNumberLength($growth_table[0]['width'],$_SESSION['metric']);
			$photo_table = $db->select("SELECT `path` FROM `clone_photos` WHERE `clone_id` = '$this->id' ORDER BY `date` ASC LIMIT 1");
			$this->photo = $photo_table[0]['path'];
		}
	}
	class clsCloneLocation {
		public $id;
		public $garden_id;
		public $parent_id;
		public $name;
		public $stage;
		public $date;
		public function clsCloneLocation($location_row) {
			$this->id = $location_row['id'];
			$this->garden_id = $location_row['garden_id'];
			$garden_table = clsDB::$db_g->select("SELECT * FROM `gardens` WHERE `id` = '$this->garden_id' LIMIT 1");
			$this->parent_id = $garden_table[0]['parent_id'];
			$this->name = $garden_table[0]['name'];
			$this->stage = $garden_table[0]['stage'];
			$this->date = new clsNumberDate($location_row['date']);
		}
		public static function getCloneLocations($clone_id){
			$location_table = clsDB::$db_g->select("SELECT * FROM `clone_location` WHERE `clone_id` = '$clone_id' ORDER BY `date` DESC");
			$locations = array();
			foreach($location_table as $location_row){
				array_push($locations, new clsCloneLocation($location_row));
			}
			return $locations;
		}
	}
	class clsCloneTraining {
		public $id;
		public $user_id;
		public $stress_level;
		public $branches;
		public $date;
		
		public function clsCloneTraining($training_row){
			$this->user_id = $training_row['user_id'];
			$this->stress_level = $training_row['stress_level'];
			$this->branches = $training_row['branches'];
			$this->date = new clsNumberDate($training_row['date']);
		}
		public static function getTraining($clone_id){
			$traning_table = clsDB::$db_g->select("SELECT * FROM `training` WHERE `clone_id` = '$clone_id' ORDER BY `date` DESC");
			$tranings = array();
			foreach($training_table as $training_row){
				array_push($trainings, new clsCloneTraining($training_row));
			}
			return $trainings;
		}
	}
	class clsCloneChild {
		public $id;
		public $name;
		public $status;
		
		public function clsCloneChild($clone_row){
			$this->id = $clone_row['id'];
			$this->name = $clone_row['name'];
			if($clone_row['time_of_death'] != ""){
				$this->status = "dead";
			} elseif($clone_row['harvest_date'] != ""){
				$this->status = "harvest";
			} elseif($clone_row['flower_date'] != ""){
				$this->status = "flower";
			} else {
				$this->status = "";
			}
		}
	}
}


function sort_clones_by_pot($a, $b) {
	if($a->pot->volume->raw_value == $b->pot->volume->raw_value){ 
		if($a->canopy->raw_value == $b->canopy->raw_value){ return 0 ; }
		return ($a->canopy->raw_value < $b->canopy->raw_value) ? 1 : -1;
	}
	return ($a->pot->volume->raw_value < $b->pot->volume->raw_value) ? 1 : -1;
}
function sort_clones_by_canopy($a, $b) {
	if($a->canopy->raw_value == $b->canopy->raw_value){ return 0 ; }
	return ($a->canopy->raw_value < $b->canopy->raw_value) ? 1 : -1;
}
function sort_clones_by_harvest_date($a, $b) {
	if(strtotime($a->harvest_date_projected()) == strtotime($b->harvest_date_projected())){ return 0 ; }
	return (strtotime($a->harvest_date_projected()) > strtotime($b->harvest_date_projected())) ? 1 : -1;
}
function sort_clones_by_flower_schedule($a, $b) {
	if(strtotime($a->flower_schedule_date) == strtotime($b->flower_schedule_date)){ return 0 ; }
	return (strtotime($a->flower_schedule_date) > strtotime($b->flower_schedule_date)) ? 1 : -1;
}
?>