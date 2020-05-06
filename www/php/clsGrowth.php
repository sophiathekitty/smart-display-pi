<?php
// clone model
if(!defined('GROWTH_CLASS')){
	define('GROWTH_CLASS',true);
	require_once("clsNumber.php");
	class clsGrowth{
		// class properties
		public $id;
		public $clone_id;
		public $height;
		public $width;
		public $depth;
		public $variation;
		public $datetime;
		public function clsGrowth($growh_row){
			$this->id = $growh_row['id'];
			$this->clone_id = $growh_row['clone_id'];
			$this->height =  new clsNumberLength($growh_row['height'],$_SESSION['metric']);
			$this->width =  new clsNumberLength($growh_row['width'],$_SESSION['metric']);
			if($growh_row['depth']){
				$this->depth =  new clsNumberLength($growh_row['depth'],$_SESSION['metric']);
			}
			if($growh_row['variation']){
				$this->variation =  new clsNumberLength($growh_row['variation'],$_SESSION['metric']);
			}
			$this->datetime = new clsNumberDate($growh_row['date']);
			return $this;
		}
		public function area(){
			if($this->depth){
				if($this->variation){
					$area = new clsNumberArea($this->width->raw_value,$this->depth->raw_value,$_SESSION['metric']);
					$area->subtractArea_raw($this->variation);
					return $area;
				}
				return new clsNumberArea($this->width->raw_value,$this->depth->raw_value,$_SESSION['metric']);
			}
			return new clsNumberArea($this->width->raw_value,$this->height->raw_value,$_SESSION['metric']);
		}
		public static function loadGrowth($db,$clone_id){
			$grwoth_table = $db->select("SELECT * FROM `clone_growth` WHERE `clone_id` = '$clone_id' ORDER BY `date` DESC");
			$growth = array();
			foreach($grwoth_table as $growth_row){
				$g = new clsGrowth($growth_row);
				array_push($growth,$g);
			}
//			//print_r($growth);
			return $growth;
		}
		public static function growthRate($growth){
			$total_gr = 0;
			$tm = 0;
			for($i = 1; $i < count($growth); $i++){
				$gr = clsGrowth::dailyGrowthRate($growth[$i-1],$growth[$i]);
				$total_gr += $gr;
				$tm++;
			}
			return ($total_gr/(count($growth)-1))*7;
		}
		
		// (present - past) / past
		public static function dailyGrowthRate($g1, $g2){
			if(!isset($g2))
				return NULL;
			if(strtotime($g2->datetime) > strtotime($g1->datetime)){
				// g2 is the present
				$gr = ($g2->height->value-$g1->height->value)/$g1->height->value;
				$days = (strtotime($g2->datetime) - strtotime($g1->datetime))/24/60/60;
			} else {
				// g1 is the present
				$gr = ($g1->height->value-$g2->height->value)/$g2->height->value;
				$days = (strtotime($g1->datetime) - strtotime($g2->datetime))/24/60/60;
			}
			return $gr / $days;
		}
	}
}
?>