<?php 
// exit if stand alone
require_once("clsDB.php");
require_once("clsClones.php");
require_once("clsGear.php");
require_once("clsNumber.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('STASH_CLASS')){
	define('STASH_CLASS',true);
	class clsStash{
		public $id;
		public $clone_id;
		public $strain_id;
		public $parent_id;
		public $user_id;
		public $weight;
		public $type;
		public $date_got;
		public $date_gone;
		
		private $stash_name;
		private $stash_strain;
		private $stash_clone;
		private $_effects;
		private $_sessions;
		private $_bowls;
		
		public function strain(){
			//if($this->stash_strain)
			//	return $this->stash_strain;
			$strain_table = clsDB::$db_g->select("SELECT * FROM `strains` WHERE `id` = '".$this->strain_id."'");
			
			$this->stash_strain = new clsStrain();
			$this->stash_strain->id = $strain_table[0]['id'];
			$this->stash_strain->name = $strain_table[0]['name'];
			$this->stash_strain->short_name = $strain_table[0]['short_name'];
			$this->stash_strain->indica = $strain_table[0]['indica'];
			$this->stash_strain->sativa = $strain_table[0]['sativa'];
			$this->stash_strain->flowering_days = $strain_table[0]['flowering_days'];
			$this->stash_strain->url = $strain_table[0]['leafly_url'];
			$this->stash_strain->yield_rate = $strain_table[0]['yield_rate'];
			return $this->stash_strain;
		}
		
		public function name(){
			if($this->stash_name)
				return $this->stash_name;
			
			if($this->clone_id){
				if(!$this->stash_clone)
					$this->stash_clone = clsClones::loadCloneAt(clsDB::$db_g,$this->clone_id);
				if($clone->label != $this->stash_clone->name && strlen($this->stash_clone->label) < 8)
					$this->stash_name = $this->stash_clone->label.":".$this->stash_clone->name;
				else
					$this->stash_name = $this->stash_clone->label;
			}
			
			if($this->strain_id){
				list($strain_row) = clsDB::$db_g->select("SELECT `name` FROM `strains` WHERE `id` = '$this->strain_id' ");
				$this->stash_name = $strain_row['name'];
			}
			
			return $this->stash_name;
		}
		
		public function type(){
			if($this->stash_strain){
				return $this->stash_strain->type();
			}
			if($this->clone_id){
				if(!$this->stash_clone)
					$this->stash_clone = clsClones::loadCloneAt(clsDB::$db_g,$this->clone_id);
				return $this->stash_clone->type();
			}
			$strain_table = clsDB::$db_g->select("SELECT * FROM `strains` WHERE `id` = '$this->strain_id'");
			$this->stash_strain = new clsStrain();
			$this->stash_strain->id = $strain_table[0]['id'];
			$this->stash_strain->name = $strain_table[0]['name'];
			$this->stash_strain->short_name = $strain_table[0]['short_name'];
			$this->stash_strain->indica = $strain_table[0]['indica'];
			$this->stash_strain->sativa = $strain_table[0]['sativa'];
			$this->stash_strain->flowering_days = $strain_table[0]['flowering_days'];
			$this->stash_strain->url = $strain_table[0]['leafly_url'];
			$this->stash_strain->yield_rate = $strain_table[0]['yield_rate'];
			return $this->stash_strain->type();
		}

		public function sessions(){
			if($this->_sessions)
				return $this->_sessions;
			$sessions_table = clsDB::$db_g->select("SELECT * FROM `smoke_session` WHERE `stash_id` = '$this->id';");
			$this->_sessions = array();
			foreach($sessions_table as $row){
				array_push($this->_sessions, new smokeSession($row));
			}
			return $this->_sessions;
		}
		public function sessions_day($date){
			$smokes = $this->sessions();
			$day_smokes = array();
			foreach($smokes as $smoke){
				if(strtotime($smoke->started) > strtotime(date("Y-m-d",strtotime($date))) && strtotime($smoke->started) < strtotime(date("Y-m-d",strtotime($date)+60*60*24+1))){
					array_push($day_smokes,$smoke);
				}
			}
			if(count($day_smokes))
				echo "(".count($day_smokes).")[".$day_smokes[0]->weed_smoked()."]";
			return $day_smokes;
		}
		public function weed_smoked($date = ""){
			if($date == ""){
				$sessions = $this->sessions();
				$weed = 0;
				foreach($sessions as $smoke){
					$weed += $smoke->weed_smoked();
				}
				return new clsNumberMass($weed);
			} else {
				$bowls = $this->bowls($date);
				$weed = 0;
				foreach($bowls as $bowl){
					if($bowl->bowl_size)
						$weed += $bowl->bowl_size;
				}
				return new clsNumberMass($weed);
			}
		}
		public function effects(){
			if($this->_effects)
				return $this->_effects;
			$this->_effects = array();
			$smoke_session_table = $this->sessions();
			foreach($smoke_session_table as $smoke_session_row){
				$id = $smoke_session_row->id;
				$effects_table = clsDB::$db_g->select("SELECT * FROM `effects` JOIN (`smoke_effects`) ON (`smoke_effects`.`effect_id` = `effects`.`id`) WHERE `smoke_effects`.`session_id` = '$id';");
				if(count($effects_table)){
					foreach($effects_table as $effects_row){
						$has = false;
						if(count($this->_effects)){
							// go through existing effects and add if needed
							foreach($this->_effects as $effect){
								if($effect->effect == $effects_row['effect']){
									$has = true;
									$effect->total++;
								}
							}
						}
						if(!$has){
							$effect = new smokeEffect($effects_row);
							array_push($this->_effects,$effect);
						}
					}
				}
			}
			return $this->_effects;
		}
		public function bowls($date){
			if($this->_bowls && $date == "")
				return $this->_bowls;
			if(!$this->_bowls) {
				$this->_bowls = array();
				$smoke_session_table = $this->sessions();
				foreach($smoke_session_table as $smoke_session_row){
					foreach($smoke_session_row->bowls as $bowl){
						array_push($this->_bowls,$bowl);
					}
				}
			}
			if($date == "")
				return $this->_bowls;
			$bowls_on_day = array();
			foreach($this->_bowls as $bowl){
				if(strtotime($bowl->date_used) >= strtotime($date) && strtotime($bowl->date_used) < strtotime($date)+60*60*24)
					array_push($bowls_on_day,$bowl);
			}
			usort($bowls_on_day,'sort_bowls_date');
			return $bowls_on_day;
		}
		
		public function hadWeed(){
			$st = date("M jS",strtotime($this->date_got));
			if($this->date_gone && $this->date_gone != ""){
				if(date("M",strtotime($this->date_got)) == date("M",strtotime($this->date_gone)))
					$st .= " - ".date("jS",strtotime($this->date_gone));
				else
					$st .= " - ".date("M jS",strtotime($this->date_gone));
				$st .= ", ".date("Y",strtotime($this->date_gone));
			}
			return $st;
		}
		public function hadWeedDays(){
			if($this->date_gone && $this->date_gone != "")
				$time = strtotime($this->date_gone)+60*60*24 - strtotime($this->date_gone);
			else 
				$time = time() - strtotime($this->date_got);
			return floor($time/60/60/24)+1;
		}
		public function weed_per_day(){
			$time = $this->hadWeedDays();
			$weight = $this->weight;
			return new clsNumberMass(round($weight/$time,2));
		}
		public function effects_html($limit = 5){
			$effects = $this->effects();
			usort($effects,'sort_smoke_effects_totals');
			$html = ""; $i = 0;
			foreach($effects as $effect){
				$good = '';
				if($effect->good)
					$good = 'good ';
				elseif(!is_null($effect->good) && $effect->good == 0)
					$good = 'bad ';
				$activity = '';
				if($effect->activity)
					$activity = 'activity ';
				$html .= "<span class='".$good.$activity."total_".$effect->totalFlattened()."'>";
				$html .= $effect->effect;
				if($i++ < count($effects)-1 && $i < $limit)
					$html .= ", ";
				$html .= "</span>";
				if($i == $limit)
					return $html;
			}
			return $html;
			print_r($effects);
		}
		
		//
		// innit
		//
		public function clsStash($stash_row){
			$this->id = $stash_row['id'];
			$this->clone_id = $stash_row['clone_id'];
			$this->strain_id = $stash_row['strain_id'];
			$this->parent_id = $stash_row['parent_id'];
			$this->user_id = $stash_row['user_id'];
			$this->weight = new clsNumberMass($stash_row['weight']);
			$this->type = $stash_row['type'];
			$this->date_got = new clsNumberDate($stash_row['date_got']);
			$this->date_gone = new clsNumberDate($stash_row['date_gone']);
		}
		public function stashType(){
			switch($this->type){
				case 1:
					return "flower";
				case 2:
					return "kief";
				case 3:
					return "vaped";
				case 4:
					return "resin";
				case 5:
					return "hash";
				case 6:
					return "oil";
				case 7:
					return "wax";
				case 8:
					return "shatter";
				case 9:
					return "blend";
			}
		}
		public function rating(){
			$ratings = clsDB::$db_g->select("SELECT `rating` FROM `smoke_session` WHERE `stash_id` = $this->id");
			if(count($ratings)){
				$total = 0;
				foreach($ratings as $r){
					$total += $r['rating'];
				}
				return round($total/count($ratings),1);
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
		
		//
		// static add cunctions (returns a stash object)
		//
		//
		// add stash from a clone
		//
		public static function add_stash_from_clone($clone_id, $user_id, $weight, $parent_id = NULL){
			$stash_array = array();
			$stash = new clsStash();
			$stash_array['clone_id'] = $stash->clone_id = $clone_id;
			$stash_array['user_id'] = $stash->user_id = $user_id;
			$stash_array['weight'] = $stash->weight = $weight;
			if($parent_id)
				$stash_array['parent_id'] = $stash->parent_id = $parent_id;
			$stash_array['date_got'] = date("Y-m-d H:i:s",time());
			$stash->date_got = new clsNumberDate($stash_array['date_got']);
			$stash->id = clsDB::$db_g->safe_insert("stash",$stash_array);
			return $stash;
		}
		//
		// add stash from a strain
		//
		public static function add_stash_from_strain($srain_id, $user_id, $weight, $parent_id = NULL){
			$stash_array = array();
			$stash = new clsStash();
			$stash_array['strain_id'] = $stash->strain_id = $strain_id;
			$stash_array['user_id'] = $stash->user_id = $user_id;
			$stash_array['weight'] = $stash->weight = $weight;
			if($parent_id)
				$stash_array['parent_id'] = $stash->parent_id = $parent_id;
			$stash_array['date_got'] = date("Y-m-d H:i:s",time());
			$stash->date_got = new clsNumberDate($stash_array['date_got']);
			$stash->id = clsDB::$db_g->safe_insert("stash",$stash_array);
			return $stash;
		}
		//
		// add stash from a parent
		//
		public static function add_stash_from_parent($user_id, $weight, $parent_id){
			list($stash_row) = clsDB::$db_g->select("SELECT `clone_id`, `strain_id` FROM `stash` WHERE `id` = '$parent_id';");
			if($stash_row['clone_id'])
				return clsStash::add_stash_from_clone($stash_row['clone_id'],$user_id,$weight,$parent_id);

			if($stash_row['strain_id'])
				return clsStash::add_stash_from_strain($stash_row['strain_id'],$user_id,$weight,$parent_id);
		}
		//
		// find the current ammount user has of harvest
		//
		public static function userShareOfHarvest($user_id,$clone_id){
			$stash_table = clsDB::$db_g->select("SELECT `weight` FROM `stash` WHERE `user_id` = '$user_id' AND `clone_id` = '$clone_id' AND `parent_id` IS NULL AND `gear_id` IS NULL;");
			$weight = 0;
			foreach($stash_table as $stash_row){
				$weight += $stash_row['weight'];
			}
			return new clsNumberMass($weight);
		}
		public static function userShareOfHarvestHasGone($user_id,$clone_id){
			$stash_table = clsDB::$db_g->select("SELECT `weight` FROM `stash` WHERE `user_id` = '$user_id' AND `clone_id` = '$clone_id' AND `parent_id` IS NULL AND `gear_id` IS NULL;");
			$weight = 0;
			foreach($stash_table as $stash_row){
				$weight += $stash_row['weight'];
			}
			if($weight == 0) return "gone";
			return "has";
		}
		//
		// load stash by user_id
		//
		public static function load_stash($user_id, $show_all = false, $group_by_gone = false){
			if($show_all)
				$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `user_id` = '$user_id' ORDER BY `date_got` DESC;");
			else 
				$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `user_id` = '$user_id' AND `date_gone` is NULL;");
			$stash_array = array();
			foreach($stash_tabel as $stash_row){
				array_push($stash_array,new clsStash($stash_row));
			}
			if($show_all && $group_by_gone){
				$stash_groups = array();
				$stash_groups['current'] = array();
				$stash_groups['gone'] = array();
				$stash_groups['all'] = $stash_array;
				foreach($stash_array as $stash){
					if($stash->date_gone != ""){
						array_push($stash_groups['gone'],$stash);
					} else {
						array_push($stash_groups['current'],$stash);
					}
				}
				return $stash_groups;
			}
			return $stash_array;
		}
		//
		// load stash by user_id
		//
		public static function load_stash_at($stash_id){
			$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `id` = '$stash_id';");
			if(count($stash_tabel)){
				return new clsStash($stash_tabel[0]);
			}
			return NULL;
		}
		public static function load_stash_from_clone($user_id,$clone, $show_all = false, $group_by_gone = false){
			$c = new clsClones();
			$c->id;
			if($show_all)
				$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `user_id` = '$user_id' AND `clone_id` = '".$clone->id."' ORDER BY `date_got` DESC;");
			else 
				$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `user_id` = '$user_id' AND `clone_id` = '".$clone->id."' AND `date_gone` is NULL;");
			$stash_array = array();
			foreach($stash_tabel as $stash_row){
				array_push($stash_array,new clsStash($stash_row));
			}
			if($show_all && $group_by_gone){
				$stash_groups = array();
				$stash_groups['current'] = array();
				$stash_groups['gone'] = array();
				$stash_groups['all'] = $stash_array;
				foreach($stash_array as $stash){
					if($stash->date_gone != ""){
						array_push($stash_groups['gone'],$stash);
					} else {
						array_push($stash_groups['current'],$stash);
					}
				}
				return $stash_groups;
			}
			return $stash_array;
		}
		//
		// load stash by clone_id
		//
		public static function load_stash_harvest_date($clone_id, $date){
			$show_all = true; $group_by_gone = false;
			$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `clone_id` = '$clone_id' AND `parent_id` is NULL AND `date_got` <= '$date' ORDER BY `date_got` DESC;");
			$stashes = clsStash::load_stash_harvest_parse($stash_tabel, $clone_id, $show_all, $group_by_gone);
			foreach($stashes as $stash){
				if($stash->gone_date && $stash->gone_date != ""){
					if(strtotime($stash->gone_date) >= strtotime($date))
						return $stash;
				} else
					return $stash;
			}
			return NULL;
		}
		public static function load_stashes_harvest_date($clone_id, $date){
			$show_all = true; $group_by_gone = false;
			$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `clone_id` = '$clone_id' AND `parent_id` is NULL AND `date_got` <= '$date' ORDER BY `date_got` DESC;");
			$stashes = clsStash::load_stash_harvest_parse($stash_tabel, $clone_id, $show_all, $group_by_gone);
			$stashes_on_day = array();
			foreach($stashes as $stash){
				if($stash->gone_date && $stash->gone_date != ""){
					if(strtotime($stash->gone_date) >= strtotime($date))
						array_push($stashes_on_day,$stash);
				} else
						array_push($stashes_on_day,$stash);
			}
			return $stashes_on_day;
		}
		//
		// load stash by clone_id
		//
		public static function load_stash_harvest($clone_id, $show_all = false, $group_by_gone = false){
			if($show_all)
				$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `clone_id` = '$clone_id' AND `parent_id` is NULL ORDER BY `date_got` DESC;");
			else 
				$stash_tabel = clsDB::$db_g->select("SELECT * FROM `stash` WHERE `clone_id` = '$clone_id' AND `parent_id` is NULL AND `date_gone` is NULL;");
			return clsStash::load_stash_harvest_parse($stash_tabel, $clone_id, $show_all, $group_by_gone);
		}
		public static function load_stash_harvest_parse($stash_tabel, $clone_id, $show_all = false, $group_by_gone = false){
			$stash_array = array();
			foreach($stash_tabel as $stash_row){
				array_push($stash_array,new clsStash($stash_row));
			}
			
			if($show_all && $group_by_gone){
				$stash_groups = array();
				$stash_groups['current'] = array();
				$stash_groups['gone'] = array();
				$stash_groups['all'] = $stash_array;
				foreach($stash_array as $stash){
					if($stash->date_gone != ""){
						array_push($stash_groups['gone'],$stash);
					} else {
						array_push($stash_groups['current'],$stash);
					}
				}
				return $stash_groups;
			}
			return $stash_array;
		}
	}// end of class
	
	
	
	
	
	class clsHarvest{
		public $plant;
		public $stashes;
		// iniit
		public function clsHarvest($clone){
			$this->plant = $clone;
			$this->stashes = clsStash::load_stash_harvest($this->plant->id,true);
		}
		public function my_stashes($user_id){
			$stashes = array();
			foreach($this->stashes as $stash){
				if($user_id == $stash->user_id){
					array_push($stashes,$stash);
				}
			}
			return $stashes;
		}
		// list effects
		public function effects($stashType = "flower"){
			if(count($this->stashes)){
				$effects = array();
				foreach($this->stashes as $stash){
					if($stash->stashType() == $stashType){
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
				}
				return $effects;
			}
			return NULL;
		}
		public function bowls($date, $stashType = "flower"){
			$bowls = array();
			foreach($this->stashes as $stash){
				if($stash->stashType() == $stashType){
					foreach($stash->bowls($date) as $bowl){
						array_push($bowls,$bowl);
					}
				}
			}
			usort($bowls,'sort_bowls_date');
			return $bowls;
		}
		public function weed_smoked($date = "", $stashType = "flower"){
			foreach($this->stashes as $stash){
				if($stash->stashType() == $stashType){
					if($date == ""){
						$sessions = $stash->sessions();
					} else {
						$sessions = $stash->sessions_day($date);
					}
					foreach($sessions as $smoke){
						$smoked = $smoke->weed_smoked()->raw_value;
						$weed += $smoked;
					}
				}
			}
			return new clsNumberMass($weed);
		}
		
		public function gotWeed(){
			if($this->plant){
				$yield_table = clsDB::$db_g->select("SELECT `date` FROM `yield` WHERE `clone_id` = '".$this->plant->id."' ORDER BY `date` ASC LIMIT 1;");
				if(count($yield_table)){
					return $yield_table[0]['date'];
				}
			}
			if(count($this->stashes)){
				$date = $this->stashes[0]->date_got;
				foreach($this->stashes as $stash){
					if(strtotime($date) < strtotime($stash->date_got)){
						$date = $stash->date_got;
					}
				}
				return $date;
			}
			return NULL;
		}
		public function weedGone(){
			if(count($this->stashes)){
				$date = NULL;
				if($this->stashes[0]->date_gone && $this->stashes[0]->date_gone != "")
					$date = $this->stashes[0]->date_gone;
					
				foreach($this->stashes as $stash){
					if($stash->date_gone && $stash->date_gone != "" && $stash->stashType() == "flower"){
						if($date){
							if(strtotime($date) < strtotime($stash->date_gone))
								$date = $stash->date_gone;
						} else {
							$date = $stash->date_gone;
						}
					}
				}
				return $date;
			}
			return NULL;
		}
		public function hadWeed(){
			$st = date("M jS",strtotime($this->gotWeed()));
			if($this->weedGone()){
				if(date("M",strtotime($this->gotWeed())) == date("M",strtotime($this->weedGone())))
					$st .= " - ".date("jS",strtotime($this->weedGone()));
				else
					$st .= " - ".date("M jS",strtotime($this->weedGone()));
				$st .= ", ".date("Y",strtotime($this->weedGone()));
			}
			return $st;
		}
		public function hadWeedDays(){
			if($this->weedGone())
				$time = strtotime($this->weedGone())+60*60*24 - strtotime($this->gotWeed());
			else 
				$time = time() - strtotime($this->gotWeed());
			return floor($time/60/60/24)+1;
		}
		public function weed_per_day(){
			$time = $this->hadWeedDays();
			$weight = $this->yield_raw();
			return new clsNumberMass(round($weight/$time,2));
		}
		// list effects html
		public function effects_html($limit = 5){
			$effects = $this->effects();
			usort($effects,'sort_smoke_effects_totals');
			$html = ""; $i = 0;
			foreach($effects as $effect){
				$good = '';
				if($effect->good)
					$good = 'good ';
				elseif(!is_null($effect->good) && $effect->good == 0)
					$good = 'bad ';
				$activity = '';
				if($effect->activity)
					$activity = 'activity ';
				$html .= "<span class='".$good.$activity."total_".$effect->totalFlattened()."'>";
				$html .= $effect->effect;
				if($i++ < count($effects)-1 && $i < $limit)
					$html .= ", ";
				$html .= "</span>";
				if($i == $limit)
					return $html;
			}
			return $html;
			print_r($effects);
		}
		// available weight
		public function weight(){
			$weight = $this->plant->yield->raw_value;
			foreach($this->stashes as $stash){
				if($stash->stashType() == "flower")
					$weight -= $stash->weight->raw_value;
			}
			if($weight < 0 || !$weight) $weight = 0;
			return round($weight,2);
		}
		/*
		// total weight
		public function yield(){
			return $this->plant->yield;
		}
		*/
		// total weight
		public function yield_raw(){
			return $this->plant->yield->raw_value;
		}
		
		public function rating($stashType = "flower"){
			if(count($this->stashes)){
				$total = 0; $count = 0;
				foreach($this->stashes as $s){
					if($s->rating() && $s->stashType() == $stashType){
						$total += $s->rating();
						$count++;
					}
				}
				return round($total/$count,1);
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
		// load harvests
		public static function loadHarvests(){
			$clones = clsClones::loadFinishedHarvests();
			$harvests = array();
			foreach($clones as $clone){
				$harvest = new clsHarvest($clone);
				if($harvest->weight() > 0)
					array_push($harvests, $harvest);
			}
			return $harvests;
		}
		public static function loadHarvestsWeight(){
			$clones = clsClones::loadFinishedHarvests();
			$weight = 0;
			foreach($clones as $clone){
				$harvest = new clsHarvest($clone);
				if($harvest->weight() > 0)
					$weight += $harvest->weight();
			}
			return $weight;
		}
		public static function loadHarvestID(){
			$clones = clsClones::loadFinishedHarvests();
			foreach($clones as $clone){
				$harvest = new clsHarvest($clone);
				if($harvest->weight() > 0)
					return $harvest->plant->id;
			}
			return 0;
		}
		public static function loadHarvestDate(){
			$clones = clsClones::loadFinishedHarvests();
			foreach($clones as $clone){
				$harvest = new clsHarvest($clone);
				if($harvest->weight() > 0)
					return $harvest->plant->end_date;
			}
			return 0;
		}
		public static function loadHarvestCloneID($clone_id){
			$clone = clsClones::loadCloneAt(clsDB::$db_g,$clone_id);
			return new clsHarvest($clone);
		}
	}
	class smokeEffect {
		public $id;
		public $effect;
		public $good;
		public $activity;
		public $total;
		public function smokeEffect($row){
			$this->id = $row['id'];
			$this->effect = $row['effect'];
			if(!is_null($row['good']))
				$this->good = $row['good'];
			$this->activity = $row['activity'];
			if(substr($this->effect,strlen($this->effect)-3) == "ing")
				$this->activity = 1;
			$this->total = 1;
		}
		public function totalFlattened(){
			if($this->total < 10)
				return $this->total;
			$num = floor($this->total/10)*10;
			if($num < 100)
				return $num;
			$num = floor($num/100)*100;
			if($num < 1000)
				return $num;
			if($num > 1000)
				$num = 1000;
			return $num;
		}
	}
	class smokeSession {
		public $id;
		public $bowls;
		public $rating;
		public $started;
		public $last_event;
		private $_effects;
		public function smokeSession($row){
			$this->id = $row['id'];
			$this->rating = $row['rating'];
			//$this->last_event = $row['date'];
			$this->started = $row['date'];
			$bowl_table = clsDB::$db_g->select("SELECT `smoke_bowl`.`date`, `smoke_bowl`.`gear_id`, `smoke_gear`.* FROM `smoke_bowl` JOIN (`smoke_gear`) ON (`smoke_bowl`.`gear_id` = `smoke_gear`.`id`) WHERE `smoke_bowl`.`session_id` = '$this->id' ORDER BY `smoke_bowl`.`date` DESC;");
			$this->bowls = array();
			foreach($bowl_table as $bowl_row){
				$bowl = new clsGear($bowl_row);
				if(is_null($bowl_row['date']) || $bowl_row['date'] == ""){
					$bowl->date_used = $this->started;
				} else 
					$bowl->date_used = $bowl_row['date'];
				if(strtotime($bowl_row['date']) > strtotime($this->last_event)){
					$this->last_event = $bowl_row['date'];
				}
				if(strtotime($bowl_row['date']) < strtotime($this->started)){
					$this->started = $bowl_row['date'];
				}
				array_push($this->bowls,$bowl);
			}
			if(!$this->last_event || $this->last_event == ""){
				$this->last_event = $this->started;
			}
			
		}
		public function stillActive(){
			if(time()-strtotime($this->started) > 8*60*60)
				return false;
			if(time()-strtotime($this->last_event) < 3*60*60)
				return true;
			return false;
		}
		public function last_bowl_time(){
			$minutes = round((time() - strtotime($this->last_event))/60);
			if($minutes < 120)
				return $minutes." min";
			$hours = round($minutes/60);
			return $hours." hrs";
		}
		public function session_time(){
			$minutes = round((time() - strtotime($this->started))/60);
			if($minutes < 120)
				return $minutes." min";
			$hours = round($minutes/60);
			return $hours." hrs";
		}
		public function weed_smoked(){
			$weed = 0;
			foreach($this->bowls as $bowl){
				if($bowl->bowl_size){
					$weed += $bowl->bowl_size;
				}
			}
			return new clsNumberMass($weed);
		}
		
		public function effects(){
			if($this->_effects)
				return $this->_effects;
			$this->_effects = array();
			$id = $this->id;
			$effects_table = clsDB::$db_g->select("SELECT * FROM `effects` JOIN (`smoke_effects`) ON (`smoke_effects`.`effect_id` = `effects`.`id`) WHERE `smoke_effects`.`session_id` = '$id';");
			if(count($effects_table)){
				foreach($effects_table as $effects_row){
					$has = false;
					if(count($this->_effects)){
						// go through existing effects and add if needed
						foreach($this->_effects as $effect){
							if($effect->effect == $effects_row['effect']){
								$has = true;
								$effect->total++;
							}
						}
					}
					if(!$has){
						$effect = new smokeEffect($effects_row);
						array_push($this->_effects,$effect);
					}
				}
			}
			return $this->_effects;
		}

		// list effects html
		public function effects_html($limit = 5){
			$effects = $this->effects();
			usort($effects,'sort_smoke_effects_totals');
			$html = ""; $i = 0;
			foreach($effects as $effect){
				$good = '';
				if($effect->good)
					$good = 'good ';
				elseif(!is_null($effect->good) && $effect->good == 0)
					$good = 'bad ';
				$activity = '';
				if($effect->activity)
					$activity = 'activity ';
				$html .= "<span class='".$good.$activity."total_".$effect->totalFlattened()."'>";
				$html .= $effect->effect;
				if($i++ < count($effects)-1 && $i < $limit)
					$html .= ", ";
				$html .= "</span>";
				if($i == $limit)
					return $html;
			}
			return $html;
			print_r($effects);
		}
		
		
		public static function loadLastSession($stash_id){
			$table = clsDB::$db_g->select("SELECT * FROM `smoke_session` WHERE `stash_id` = '$stash_id' ORDER BY `date` DESC LIMIT 1;");
			if(count($table)){
				return new smokeSession($table[0]);
			}
			return NULL;
		}
		public static function loadSession($stash_id){
			$table = clsDB::$db_g->select("SELECT * FROM `smoke_session` WHERE `stash_id` = '$stash_id' ORDER BY `date` DESC;");
			$sesions = array();
			foreach($table as $row){
				array_push($sessions,new smokeSession($row));
			}
			return $sesions;
		}
	}
	
	function sort_smoke_effects_totals($a, $b) {
		if($a->total == $b->total){ return 0 ; }
		return ($a->total < $b->total) ? 1 : -1;
	}
	function sort_bowls_date($a, $b) {
		if(strtotime($a->date_used) == strtotime($b->date_used)){ return 0 ; }
		return (strtotime($a->date_used) < strtotime($b->date_used)) ? 1 : -1;
	}
	
} // end of defined

?>