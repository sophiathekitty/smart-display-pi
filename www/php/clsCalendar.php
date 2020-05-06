<?php
// exit if stand alone
require_once("clsDB.php");
require_once("clsNumber.php");
require_once("clsClones.php");
require_once("clsGrowth.php");
require_once("clsGrow.php");
require_once("clsUser.php");
require_once("clsStash.php");
require_once("clsWaterings.php");
require_once("clsTemperature.php");
require_once("clsStash.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('CALENDAR_CLASS')){
	define('CALENDAR_CLASS',true);
	
	class clsCalendar{
		private $clones;
		
		public function clsCalendar($grow){
			$this->clones = $grow->allClones();
		}
		
		public function days($date){
			$days = array();
			$first_day_date = date("Y-m-01",strtotime($date));
			$first_day = date("N",strtotime($first_day_date));
			$days_in_month = date("t",strtotime($date));
			if($first_day >= 7) $first_day = 0;
			$first_date = date("Y-m-d",strtotime($first_day_date) - $first_day*60*60*24);
			$last_day = date("N",strtotime($first_day_date)+$days_in_month*60*60*24);
			if($last_day >= 7) $last_day = 0;
			$next_month = 7 - ($last_day);
			// last month
			for($offset = $first_day; $offset > 0; $offset--){
				$d = date("Y-m-d",strtotime($first_day_date) - $offset*60*60*24);
				array_push($days,new clsCalendarDay($d,$this->days_events($d),"last_month"));
			}
			// this month
			$last_date = "";
			$dc = 0;
			for($di = 0; $di <= $days_in_month && $dc <= $days_in_month; $di++){
				$d = date("Y-m-d",strtotime($first_day_date) + $di*60*60*24);
				if($d != $last_date && date("F",strtotime($date)) == date("F",strtotime($d))){
					array_push($days,new clsCalendarDay($d,$this->days_events($d),"this_month"));
					$last_date = $d;
					$dc++;
				}
			}
			// next month
			if($last_day != 0){
				$next_month_date = date("Y-m-01",strtotime($first_day_date)+35*60*60*24);
				$dc = 0;
				if($last_day == 3 & date("Y-m",strtotime($date)) == "2016-11")
					$next_month = 3;
				for($di = 0; $di < $next_month; $di++){
					$d = date("Y-m-d",strtotime($next_month_date) + $di*60*60*24);
					array_push($days,new clsCalendarDay($d,$this->days_events($d),"next_month"));
				}
			}
			return $days;
		}
		
		public function days_till_next_harvest($date){
			$days = 40;
			foreach($this->clones as $clone){
				if($clone->harvest_date_projected() && strtotime($date) < strtotime($clone->harvest_date_projected())){
					$t = strtotime($clone->harvest_date_projected()) - strtotime($date);
					$d = round($t/60/60/24);
					if($days > $d)
						$days = $d;
				}
			}
			return $days;
		}
		
		public function drying_on_day($date){
			foreach($this->clones as $clone){
				if($clone->drying_on_day($date))
					return true;
			}
			return false;
		}
		
		public function has_weed_on_day($date){
			$harvest_weight = clsHarvest::loadHarvestsWeight();
			if(count($this->yield_report($date)) || ($date == date("Y-m-d") && $harvest_weight > 0) || ($harvest_weight > 0 && strtotime(clsHarvest::loadHarvestDate()) < strtotime($date) && strtotime($date) < time())){
				return true;
			}
			$stashes = clsStash::load_stash(clsUser::$current_user->id,true);
			foreach($stashes as $stash){
				if($stash->stashType() == "flower"){
					if($stash->date_gone == ""){
						if(strtotime($stash->date_got) < strtotime($date) && strtotime($date) < time())
							return true;
					} else {
						if(strtotime($stash->date_got) < strtotime($date) && strtotime($date) <= strtotime($stash->date_gone))
							return true;
					}
				}
			}
			
			return false;
		}
		public function has_vaped_weed_on_day($date){
			$harvest_weight = clsHarvest::loadHarvestsWeight();
			if(count($this->yield_report($date)) || ($date == date("Y-m-d") && $harvest_weight > 0) || ($harvest_weight > 0 && strtotime(clsHarvest::loadHarvestDate()) < strtotime($date) && strtotime($date) < time())){
				return true;
			}
			$stashes = clsStash::load_stash(clsUser::$current_user->id,true);
			foreach($stashes as $stash){
				if($stash->stashType() == "vaped"){
					if($stash->date_gone == ""){
						if(strtotime($stash->date_got) < strtotime($date) && strtotime($date) < time())
							return true;
					} else {
						if(strtotime($stash->date_got) < strtotime($date) && strtotime($date) <= strtotime($stash->date_gone))
							return true;
					}
				}
			}
			
			return false;
		}
		
		public function dayCell($day){
			$past = "";
			if(strtotime($day->date) < time()){
				$past = " past";
			}
			if($day->date == date("Y-m-d")){
				$past = " today";
			}
			$flower = ""; $harvest = "";
			foreach($day->events as $event){
				if(strpos($event->type,'harvest') !== false){
					$harvest = " harvest";
				}
				if(strpos($event->type,'flower') !== false){
					$harvest = " flower";
				}
			}
			$flush = "";
			if($this->days_till_next_harvest($day->date) <= 14){
				if($this->days_till_next_harvest($day->date) > 7){
					$flush = " pre_harvest flush";
				} else {
					$flush = " flush";
				}
			}
			$drying = "";
			if($this->drying_on_day($day->date))
				$drying = " drying";
			$has_weed = "";
			if($this->has_weed_on_day($day->date))
				$has_weed = " has_weed";
			if($has_weed == "" && $this->has_vaped_weed_on_day($day->date))
				$has_weed = " has_vaped";
			$html = "<td class=\"$day->month$past$harvest$flower$flush$drying$has_weed\">".date("j",strtotime($day->date));
			foreach($day->events as $event){
				$html .= $event->div();
			}
			
			$html .= "</td>";
			return $html;
		}
		
		public function days_events($date){
			$events = array();
			// harvists
			$harvests = $this->harvests($date);
			if($harvests)
				$events = array_merge($events,$harvests);
			// flowering
			$flowering = $this->flowering($date);
			if($flowering)
				$events = array_merge($events,$flowering);
			// spray
			$spray = $this->spray($date);
			if($spray)
				$events = array_merge($events,$spray);
			// rooting clones
			// yield report
			$yield = $this->yield_report($date);
			if($yield)
				$events = array_merge($events,$yield);
			// transplants
			$transplants = $this->transplants($date);
			if($transplants)
				$events = array_merge($events,$transplants);
			// waterings
			$waterings = $this->waterings($date);
			if($waterings)
				$events = array_merge($events,$waterings);
			// trainings
			$trainings = $this->trainings($date);
			if($trainings)
				$events = array_merge($events,$trainings);
			return $events;
		}
		
		private function harvests($date){
			$events = array();
			foreach($this->clones as $clone){
				// is this clone flowering?
				if($clone->flower_date != "" || $clone->flower_schedule_date != "" && !$clone->is_dead){
					// if the clone is harvested on this date
					if(($clone->harvest_date != "" && $clone->harvest_date == $date) || ($clone->harvest_date_projected() == $date)){
						array_push($events,new clsCalendarEvent("harvest ".$clone->type(),$clone->label,$clone->strain->short_name));
					}
				}
			}
			if(count($events))
				return $events;
			return NULL;
		}
		
		private function flowering($date){
			$events = array();
			foreach($this->clones as $clone){
				// is this clone flowering?
				if($clone->flower_date != "" && $clone->flower_date == $date){
					// if the clone is harvested on this date
					array_push($events,new clsCalendarEvent("flower ".$clone->type(),$clone->label,$clone->strain->short_name));
				}
				if($clone->flower_schedule_date != "" && $clone->flower_schedule_date == $date && !$clone->is_dead){
					// if the clone is harvested on this date
					array_push($events,new clsCalendarEvent("flower ".$clone->type(),$clone->label,$clone->strain->short_name));
				}
				/*
				if($clone->flower_schedule_date != "" && strtotime($clone->flower_schedule_date) < time()){
					array_push($events,new clsCalendarEvent("flower ".$clone->type(),$clone->label,$clone->strain->short_name));
				}
				*/
			}
			if(count($events)){
				$event = array();
				array_push($event,$events[0]);
				return $event;
			}
			return NULL;
		}
		
		private function spray($date){
			$events = array();
			$sprays = clsDB::$db_g->select("SELECT * FROM `spray_schedule`");
			foreach($sprays as $s){
				$sp = new clsSpray($s);
				if($sp->spray($date)){
					if($sp->name == "Water")
						array_push($events,new clsCalendarEvent("spray water",$sp->name,$sp->name));
					else
						array_push($events,new clsCalendarEvent("spray pesticide",$sp->name,$sp->name));
				}
			}
			if(count($events))
				return $events;
			return NULL;
		}
		
		private function transplants($date){
			$events = array();
			// select transplants
			$table = clsDB::$db_g->select("SELECT `pots`.`volume`, `clone_pot`.`clone_id` FROM `pots` JOIN (`clone_pot`) ON (`pots`.`id` = `clone_pot`.`pot_id`) WHERE `clone_pot`.`date` = '$date' ");
			foreach($table as $row){
				$clone = $this->clone_id($row['clone_id']);
				if($clone)
					array_push($events, new clsCalendarEvent("transplant pot_".$this->transplant_size_text($row['volume'])." ".$clone->type(),$clone->label,$clone->strain->short_name));
			}
			if(count($events))
				return $events;
			return NULL;
		}
		private function transplant_size_text($volume){
			if($volume >= 1){
				return round($volume)."gal";
			} elseif($volume > 0.05) {
				return "cup";
			} else {
				return "cube";
			}
		}
		
		private function waterings($date){
			$events = array();
			// select waterings
			$table = clsDB::$db_g->select("SELECT * FROM `waterings` WHERE `date` LIKE '%$date%%'");
			foreach($table as $row){
				$clone = $this->clone_id($row['clone_id']);
				$waterings = new clsWaterings(clsDB::$db_g,$row);
				$food = "";
				if($waterings->is_food())
					$food = "food";
				if($clone)
					array_push($events, new clsCalendarEvent("water_plant $food ".$clone->type(),$clone->label,$clone->strain->short_name));
			}
			if(count($events))
				return $events;
			return NULL;
		}
		
		private function trainings($date){
			$events = array();
			// select transplants
			$table = clsDB::$db_g->select("SELECT `clone_id`, `stress_level` FROM `training` WHERE `date` LIKE '%$date%%'");
			foreach($table as $row){
				$clone = $this->clone_id($row['clone_id']);
				array_push($events, new clsCalendarEvent("train stress_".$row['stress_level']." ".$clone->type(),$clone->label,$clone->strain->short_name));
			}
			if(count($events))
				return $events;
			return NULL;
		}

		private function yield_report($date){
			$events = array();
			$yield = 0;
			// select yields
			$table = clsDB::$db_g->select("SELECT `clone_id`, `weight` FROM `yield` WHERE `date` LIKE '%$date%%'");
			foreach($table as $row){
				$yield += $row['weight'];
			}
			if($yield > 0){
				$clone = $this->clone_id($table[0]['clone_id']);
				$weight = new clsNumberMass($yield);
				array_push($events, new clsCalendarEvent("yield ".$clone->type(),"-".$weight->text_formated(),"+".$weight->text_short_formated()));
			}
			if(count($events))
				return $events;
			return NULL;
		}
		
		private function clone_id($id){
			foreach($this->clones as $clone){
				if($clone->id == $id)
					return $clone;
			}
			return NULL;
		}
		
	}
	
	class clsCalendarDay{
		public $date;
		public $month;
		public $events;
		
		public function clsCalendarDay($date,$events,$month){
			$this->date = $date;
			$this->events = $events;
			$this->month = $month;
		}
	}
	
	class clsCalendarEvent{
		public $type;
		public $label;
		public $strain;
		
		public function clsCalendarEvent($type,$label,$strain = ""){
			$this->type = $type;
			$this->label = $label;
			$this->strain = $strain;
		}
		
		public function div(){
			if(strlen($this->label) > 6 && $this->strain != "")
				return "<div class=\"$this->type\">$this->strain</div>";
			return "<div class=\"$this->type\">$this->label</div>";
		}
	}
	
	
	class clsSpray{
		public $name;
		public $start_date;
		public $repeat_days;
		
		public function clsSpray($row){
			$nutrients_id = $row['nutrients_id'];
			list($nut) = clsDB::$db_g->select("SELECT `name` FROM `nutrients` WHERE `id` = $nutrients_id ");
			$this->name = $nut['name'];
			$this->start_date = $row['start_date'];
			$this->repeat_days = $row['repeat_days'];
		}
		
		public function spray($date){
			if($date == $this->start_date)
				return true;
			/*
			for($i = strtotime($this->start_date); $i <= strtotime($date); $i += 60*60*24*$this->repeat_days){
				if(date("Y-m-d",strtotime($date)) == date("Y-m-d",$i))
					return true;
			}
			*/
			// try a new method for when the other one fails....
			if(floor((strtotime($date) - strtotime($this->start_date))/($this->repeat_days*60*60*24)) ==
				(strtotime($date) - strtotime($this->start_date))/($this->repeat_days*60*60*24))
					return true;
			
			
			return false;	
		}
	}
	
}
?>