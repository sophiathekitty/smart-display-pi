<?php
// exit if stand alone
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('NUMBERS_CLASS')){
	define('NUMBERS_CLASS',true);
	class clsNumber {
		public $raw_value;
		public $value;
		public $scaled_value;
		public $unit;
		public $units;
		public function text(){
			if(round($this->value,1) <= 1)
				return round($this->value,1)." $this->unit";
			else
				return round($this->value,1)." $this->units";
		}
		public function text_long(){
			if(round($this->value,2) <= 1)
				return round($this->value,2)." $this->unit";
			else
				return round($this->value,2)." $this->units";
		}
		public function text_short(){
			if(round($this->value,0) <= 1)
				return round($this->value,0)." $this->unit";
			else
				return round($this->value,0)." $this->units";
		}
		public function text_formated(){
			if(round($this->value,1) <= 1)
				return "<b>".round($this->value,1)."</b><i>$this->unit</i>";
			else
				return "<b>".round($this->value,1)."</b><i>$this->units</i>";
		}
		public function text_long_formated(){
			if(round($this->value,2) <= 1)
				return "<b>".round($this->value,2)."</b><i>$this->unit</i>";
			else
				return "<b>".round($this->value,2)."</b><i>$this->units</i>";
		}
		public function text_short_formated(){
			if(round($this->value,0) <= 1)
				return "<b>".round($this->value,0)."</b><i>$this->unit</i>";
			else
				return "<b>".round($this->value,0)."</b><i>$this->units</i>";
		}
		public function __toString(){
			return (string)$this->value;
		}
	}
	class clsNumberDate extends clsNumber {
		public function clsNumberDate($datetime){
			$this->raw_value = $this->value = $datetime;
			$now = time();
			$date = strtotime($datetime);
			$time_span = ($now - $date)/60/60/24;
			$this->scaled_value = floor(($now - $date)/60/60/24/7);
			if($time_span < 7){
				$this->unit = "day";
				$this->units = "days";
			} elseif($time_span < 60) {
				$this->unit = "week";
				$this->units = "weeks";
			} elseif($time_span < 365) {
				$this->unit = "month";
				$this->units = "months";
			}else{
				$this->unit = "year";
				$this->units = "years";
			}
		}
		public function text(){
			return date("D, M jS",strtotime($this->value));
		}
		public function text_long(){
			return date("D, M jS Y",strtotime($this->value));
		}
		public function text_short(){
			$now = time();
			$date = strtotime($this->value);
			switch($this->units){
				case "days":
					if(floor(($now - $date)/60/60/24) == 1)
						return floor(($now - $date)/60/60/24)." $this->unit";
					else
						return floor(($now - $date)/60/60/24)." $this->units";
				case "weeks":
					if(floor(($now - $date)/60/60/24/7) == 1)
						return floor(($now - $date)/60/60/24/7)." $this->unit";
					else
						return floor(($now - $date)/60/60/24/7)." $this->units";
				case "months":
					if(floor(($now - $date)/60/60/24/30) == 1)
						return floor(($now - $date)/60/60/24/30)." $this->unit";
					else
						return floor(($now - $date)/60/60/24/30)." $this->units";
				case "years":
					return date("Y",strtotime($this->value));
					if(floor(($now - $date)/60/60/24/365) == 1)
						return floor(($now - $date)/60/60/24/365)." $this->unit";
					else
						return floor(($now - $date)/60/60/24/365)." $this->units";
			}
		}
	}
	class clsNumberLength extends clsNumber {
		private $metric;
		public function clsNumberLength($value,$metric = true){
			$this->metric = $metric;
			$this->raw_value = $value;
			if($metric){
				$this->scaled_value = $value;
				if($value < 100){
					$this->value = $value;
					$this->unit = "cm";
					$this->units = "cm";
				} else {
					$this->value = $value/100;
					$this->unit = "m";
					$this->units = "m";
				}
			} else {
				$this->scaled_value = $inches = $value * (1/2.54);
				if($inches < 12){
					$this->value = $value * (1/2.54);
					$this->unit = "in";
					$this->units = "in";
				} elseif($inches < 36){
					$this->value = $value * (1/30.48);
					$this->unit = "ft";
					$this->units = "ft";
				} else {
					$this->value = $value * (1/91.44);
					$this->unit = "yd";
					$this->units = "yd";
				}
			}
		}
		public function maxLengthRaw($value){
			if($value > $this->raw_value){
				$this->raw_value = $value;
				if($metric){
					$this->scaled_value = $value;
					if($value < 100){
						$this->value = $value;
						$this->unit = "cm";
						$this->units = "cm";
					} else {
						$this->value = $value/100;
						$this->unit = "m";
						$this->units = "m";
					}
				} else {
					$this->scaled_value = $inches = $value * (1/2.54);
					if($inches < 12){
						$this->value = $value * (1/2.54);
						$this->unit = "in";
						$this->units = "in";
					} elseif($inches < 36){
						$this->value = $value * (1/30.48);
						$this->unit = "ft";
						$this->units = "ft";
					} else {
						$this->value = $value * (1/91.44);
						$this->unit = "yd";
						$this->units = "yd";
					}
				}
			}
		}
	}
	class clsNumberVolume extends clsNumber {
		public function clsNumberVolume($value){
			$this->scaled_value = $this->raw_value = $value;
			if($value < 0.00390625){
				$this->value = $value * 768;
				$this->unit = "tsp";
				$this->units = "tsp";
			}elseif($value < 0.0078125){
				$this->value = $value * 256;
				$this->unit = "tbs";
				$this->units = "tbs";
			}elseif($value < 0.264172){
				$this->value = $value * 128;
				$this->unit = "ounce";
				$this->units = "ounces";
			}elseif($value < 1){
				$this->value = $value * 3.78541;
				$this->unit = "liter";
				$this->units = "liters";
			} else {
				$this->value = $value;
				$this->unit = "gallon";
				$this->units = "gallons";
			}
		}
		
		public function get_tsp(){
			return $this->raw_value * 768;
		}
		public function get_ml(){
			return $this->raw_value * 3785.41;
		}
		
	}
	class clsNumberMass extends clsNumber {
		public function clsNumberMass($value){
			$this->scaled_value = $this->raw_value = $value;
			if($value == 0){
				$this->value = $value;
				$this->unit = "g";
				$this->units = "g";
				//echo "valu?".$this->value."raw?".$this->raw_value;
			} else {
				if($value < 0.1){
					$this->value = $value*1000;
					$this->unit = "mg";
					$this->units = "mg";
				}elseif($value < 3.5436904){
					$this->value = $value;
					$this->unit = "g";
					$this->units = "g";
				}elseif($value < 7.08738){
					$this->value = 1;
					$this->unit = "/8oz";
					$this->units = " eigth";
				}elseif($value < 14.1748){
					$this->value = 1;
					$this->unit = "/4oz";
					$this->units = " quarter";
				}elseif($value < 20){
					$this->value = 1;
					$this->unit = "/2oz";
					$this->units = " half";
				}elseif($value < 400){
					$this->value = round($value * 0.035274,2);
					$this->unit = "oz";
					$this->units = "oz";
				} else {
					$this->value = round($value * 0.00220462,2);
					$this->unit = "lb";
					$this->units = "lbs";
				}
			}
		}
		public function text_long(){
			if($this->value == 0){
				return "0g";
			}
			if(round($this->value,2) <= 1)
				return round($this->value,2)."$this->units";
			else
				return round($this->value,2)."$this->units";
		}
	}
	class clsNumberArea extends clsNumber {
		public $width_raw;
		public $height_raw;
		public $width_scaled;
		public $height_scaled;
		private $metric;
		public function clsNumberArea($width,$height,$metric){
			$this->metric = $metric;
			$this->width_raw = $width;
			$this->height_raw = $height;
			$this->raw_value = $this->width_raw * $this->height_raw;
			if($metric){
				$this->width_scaled = $this->width_raw;
				$this->height_scaled = $this->height_raw;
				$this->scaled_value = $this->width_scaled * $this->height_scaled;
				if($this->scaled_value < 1000){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "cm";
				} else {
					$this->value = $this->scaled_value/10000;
					$this->unit = $this->units = "meter";
				}
			} else {
				$this->width_scaled = $this->width_raw * (1/2.54);
				$this->height_scaled = $this->height_raw * (1/2.54);
				$this->scaled_value = $this->width_scaled * $this->height_scaled;
				if($this->scaled_value < 144){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "in";
				} elseif($this->scaled_value < 1296) {
					$this->value = $this->scaled_value/144;
					$this->unit = $this->units = "ft";
				} else {
					$this->value = $this->scaled_value/1296;
					$this->unit = $this->units = "yd";
				}
			}
		}
		public function subtractArea($area){
			$this->scaled_value -= $area;
			if($metric){
				if($this->scaled_value < 1000){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "cm";
				} else {
					$this->value = $this->scaled_value/10000;
					$this->unit = $this->units = "meter";
				}
			} else {
				if($this->scaled_value < 144){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "in";
				} elseif($this->scaled_value < 1296) {
					$this->value = $this->scaled_value/144;
					$this->unit = $this->units = "ft";
				} else {
					$this->value = $this->scaled_value/1296;
					$this->unit = $this->units = "yd";
				}
			}
		}
		public function subtractArea_raw($area){
			$this->raw_value -= $area;
			if($metric){
				$this->scaled_value = $this->raw_value;
				if($this->scaled_value < 1000){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "cm";
				} else {
					$this->value = $this->scaled_value/10000;
					$this->unit = $this->units = "meter";
				}
			} else {
				$this->scaled_value = $this->raw_value * (1/2.54);
				if($this->scaled_value < 144){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "in";
				} elseif($this->scaled_value < 1296) {
					$this->value = $this->scaled_value/144;
					$this->unit = $this->units = "ft";
				} else {
					$this->value = $this->scaled_value/1296;
					$this->unit = $this->units = "yd";
				}
			}
		}
		public function addArea($area){
			$this->scaled_value += $area;
			if($metric){
				if($this->scaled_value < 1000){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "cm";
				} else {
					$this->value = $this->scaled_value/10000;
					$this->unit = $this->units = "meter";
				}
			} else {
				if($this->scaled_value < 144){
					$this->value = $this->scaled_value;
					$this->unit = $this->units = "in";
				} elseif($this->scaled_value < 1296) {
					$this->value = $this->scaled_value/144;
					$this->unit = $this->units = "ft";
				} else {
					$this->value = $this->scaled_value/1296;
					$this->unit = $this->units = "yd";
				}
			}
		}
	}

}
?>