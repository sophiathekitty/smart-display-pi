<?php
// exit if stand alone
require_once("clsDB.php");
require_once("clsNumber.php");
require_once("clsClones.php");
require_once("clsStash.php");
require_once("clsGrowth.php");
require_once("clsWaterings.php");
require_once("clsTemperature.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('CLONE_BLOG_CLASS')){
	define('CLONE_BLOG_CLASS',true);
	class clsCloneBlog{
		public $entries;
		public $clones;
		
		public function clsCloneBlog($clone){
			global $db;
			
			$this->entries = array();
			$this->clones = $clone;
			$clone_start_date = strtotime($clone->start_date);
			$now = strtotime($clone->end_date);
			$day_step = 60*60*24;
			for($i = $now; $i >= $clone_start_date; $i -= $day_step){
				$entry = clsCloneBlogEntry::loadEntryDay($db,date("Y-m-d",$i),$clone);
				if($entry != NULL)
					array_push($this->entries,$entry);
			}
		}
		
		public function this_week(){
			return new clsCloneBlogWeek(date("Y-m-d",strtotime($this->clones->end_date)),$this->entries);
		}
		public function previous_week($weeks_ago = 1){
			return new clsCloneBlogWeek(date("Y-m-d",strtotime($this->clones->end_date)-(60*60*24*7*$weeks_ago)),$this->entries);
		}
		
		public static function loadBlog($db,$clone){
			$blog = new clsCloneBlog($clone);
			return $blog;
		}
	}
	class clsCloneBlogWeek{
		public $date_start;
		public $date_end;
		public $entries;
		public function clsCloneBlogWeek($date,$entries){
			$now = strtotime(date("Y-m-d",strtotime($date)));
			if(date("N",$now) == 7){
				$this->date_start = new clsNumberDate(date("Y-m-d",$now));
				$this->date_end = new clsNumberDate(date("Y-m-d",$now +(60*60*24*6)));
			} else {
				$this->date_start = new clsNumberDate(date("Y-m-d",$now - date("N",$now)*60*60*23));
				$this->date_end = new clsNumberDate(date("Y-m-d",$now - date("N",$now)*60*60*24+(60*60*24*6)));
			}
			$this->entries = array();
			foreach($entries as $entry){
				if(strtotime($this->date_start) <= strtotime($entry->date) && strtotime($this->date_end) >= strtotime($entry->date)){
					array_push($this->entries, $entry);
				}
			}
		}
		public function first_measurment(){
			$day_of_week = 7;
			$measurment = NULL;
			foreach($this->entries as $day){
				if(date("N",strtotime($day->date)) == 7 && $day->measurment){
					return $day->measurment;
				}
				if(date("N",strtotime($day->date)) < $day_of_week && $day->measurment){
					$measurment = $day->measurment;
					$day_of_week = date("N",strtotime($day->date));
				}
			}
			return $measurment;
		}
		public function last_measurment(){
			$day_of_week = -7;
			$measurment = NULL;
			foreach($this->entries as $day){
				if(date("N",strtotime($day->date)) == 7 && $day_of_week < 0 && $day->measurment){
					$measurment = $day->measurment;
					$day_of_week = 0;
				}
				if(date("N",strtotime($day->date)) > $day_of_week && date("N",strtotime($day->date)) != 7 && $day->measurment){
					$measurment = $day->measurment;
					$day_of_week = date("N",strtotime($day->date));
				}
			}
			return $measurment;
		}
		public function growth(){
			$first = $this->first_measurment();
			$last = $this->last_measurment();
			$height = $last->height->raw_value - $first->height->raw_value;
			$width = $last->width->raw_value - $first->width->raw_value;
			$growth = new clsGrowth(array(
						'id' => 0,
						'clone_id' => 0,
						'height' => $height,
						'width' => $width, 
						'date' => date("Y-m-d",time())

				));
			//print_r($growth);
			return $growth;
		}
		public function transplanted(){
			
		}
		public function transfered(){
			
		}
	}
	class clsCloneBlogEntry{
		public $date;
		public $temperature;
		public $measurment;
		public $watering;
		public $ph_level;
		public $photo;
		public $transplant;
		public $cuttings;
		public $garden_name;
		public $garden_id;
		public $garden_type;
		public $garden_date;
		public $garden_days;
		public $stash;
		public $stashes;
		public $smoke_sessions;
		public $notes;
		public $yield;
		
		public function clsCloneBlogEntry($db,$date,$clone){
			$this->date = new clsNumberDate($date);
			// stash 
			$this->stash = clsStash::load_stash_harvest_date($clone->id,$date);
//			$this->stashes = clsStash::load_stashes_harvest_date($clone->id,$date);
			$yield_table = clsDB::$db_g->select("SELECT `weight` FROM `yield` WHERE `clone_id` = '$clone->id' AND `date` LIKE '%$date%%'");
			if(count($yield_table)){
				$yt = 0;
				foreach($yield_table as $yield_row){
					$yt += $yield_row['weight'];
				}
				$this->yield = new clsNumberMass($yt);
			}

			// measurment
			$growth_table = $db->select("SELECT * FROM `clone_growth` WHERE `clone_id` = '$clone->id' AND `date` = '$date' ");
			if(count($growth_table))
				$this->measurment = new clsGrowth($growth_table[0]);
			// watering
			$watering_table = $db->select("SELECT * FROM `waterings` WHERE `clone_id` = '$clone->id' AND `date` BETWEEN '$date 00:00:00' AND '$date 23:59:59' ");
			if(count($watering_table))
				$this->watering = new clsWaterings($db,$watering_table[0]);
			// ph_level
			$ph_table = $db->select("SELECT * FROM `soil_ph_level` WHERE `clone_id` = '$clone->id' AND `date` BETWEEN '$date 00:00:00' AND '$date 23:59:59' ");
			if(count($ph_table))
				$this->ph_level = new clspHlevel($ph_table[0]);
			// photo
			$photo_table = $db->select("SELECT * FROM `clone_photos` WHERE `clone_id` = '$clone->id' AND `date` BETWEEN '$date 00:00:00' AND '$date 23:59:59' ");
			if(count($photo_table))
				$this->photo = $photo_table[0]['path'];
			// transplant
			$transplant_table = $db->select("SELECT * FROM `clone_pot` WHERE `clone_id` = '$clone->id' AND `date` = '$date' ");
			if(count($transplant_table)){
				$pot_table = $db->select("SELECT * FROM `pots` WHERE `id` = '".$transplant_table[0]['pot_id']."' ");
				$this->transplant = new clsPot($pot_table[0]);
			}
			// transplant
			$cuttings_table = $db->select("SELECT * FROM `clones` WHERE `parent_id` = '$clone->id' AND `start_date` = '$date' ");
			if(count($cuttings_table)){
				$this->cuttings = array();
				foreach($cuttings_table as $cuttings_row){
					array_push($this->cuttings,new clsCutting($cuttings_row));
				}
			}
			// temperatures
			$temperature_table = $db->select("SELECT * FROM `temperature` WHERE `garden_id` = '".$clone->garden_on_day($date,true)."' AND `datetime` BETWEEN '$date 00:00:00' AND '$date 23:59:59' ");
			if(count($temperature_table)){
				$this->temperature = new clsDailyTemperature($temperature_table,9,3);
				/*
				$temp = 0; $max = 0; $min = 10000; $humidity = 0; $i = 0;
				foreach($temperature_table as $temps){
					$i++;
					$temp += $temps['temp'];
					$humidity+= $temps['humidity'];
					if($max < $temps['max'])
						$max = $temps['max'];
					if($min > $temps['min'])
						$min = $temps['min'];
				}
				$this->temperature = new clsTemperature();
				$this->temperature->temp = round($temp/$i,1);
				$this->temperature->humidity = round($humidity/$i);
				$this->temperature->maxtemp = $max;
				$this->temperature->mintemp = $min;
				*/
			}
			
			$this->garden_id = $clone->garden_on_day($date);
			$garden_table = $db->select("SELECT * FROM `gardens` WHERE `id` = '$this->garden_id'");
			$this->garden_name = $garden_table[0]['name'];
			$this->garden_date = new clsNumberDate($clone->garden_date_on_day($date));
			$this->garden_days = $clone->days_in_garden_on_date($date);
			switch($garden_table[0]['stage']){
				case 1:
				case "1":
					$this->garden_type = "nursery";
					break;
				case 2:
				case "2":
					$this->garden_type = "vegitation";
					break;
				case 3:
				case "3":
					$this->garden_type = "flowering";
					break;
				case 4:
				case "4":
					$this->garden_type = "drying";
					break;
			}
			$notes_table = $db->select("SELECT `note` FROM `notes` WHERE `clone_id` = '$clone->id' AND `date` BETWEEN '$date 00:00:00' AND '$date 23:59:59' ORDER BY `date` ASC");
			if(count($notes_table)){
				$this->notes = array();
				foreach($notes_table as $notes_row){
					array_push($this->notes, $notes_row['note']);
				}
			}
		}
		public function nothingHappened(){
			if(
				$this->measurment == NULL &&
				$this->watering == NULL &&
				$this->ph_level == NULL &&
				$this->photo == NULL &&
				$this->temperature == NULL &&
				$this->transplant == NULL &&
				$this->stash == NULL &&
				$this->yield == NULL &&
				$this->notes == NULL
				)
				return true;
			return false;
		}
		public static function loadEntryDay($db,$date,$clone){
			$entry = new clsCloneBlogEntry($db,$date,$clone);
			if($entry->nothingHappened())
				return NULL;
			return $entry;
		}
	}
}
?>