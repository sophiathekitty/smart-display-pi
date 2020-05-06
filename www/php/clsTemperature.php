<?php
// temperature module
if(!defined('TEMPERATURE_CLASS')){
	define('TEMPERATURE_CLASS',true);
	class clsTemperature{
		// class properties	
		public $id;
		public $garden_id;
		public $temp;
		public $maxtemp;
		public $mintemp;
		public $humidity;
		public $datetime;
		
		// class static stuff
		public static $temps_cache;
		public static function averageTemp($db, $garden_id = 1){
			$temps_table = $db->select("SELECT * FROM `temperature` WHERE `garden_id` = '$garden_id' AND `datetime` > '".date("Y-m-d H:i:s",time()-(60*60*24*7*3))."'");
			$temp = 0;
			foreach($temps_table as $temp_row){
				$temp += $temp_row['temp'];
			}
			return round($temp/count($temps_table),1);
		}
		public static function averageHumidity($db, $garden_id = 1){
			$temps_table = $db->select("SELECT * FROM `temperature` WHERE `garden_id` = '$garden_id' AND `datetime` > '".date("Y-m-d H:i:s",time()-(60*60*24*7*3))."'");
			$humidity = 0;
			foreach($temps_table as $temp_row){
				$humidity += $temp_row['humidity'];
			}
			return round($humidity/count($temps_table),1);
		}
		public static function latestTemp($db, $garden_id = 1){
			$temps_table = $db->select("SELECT * FROM `temperature` WHERE `garden_id` = '$garden_id' AND `datetime` > '".date("Y-m-d H:i:s",time()-(60*60*24*7*3))."' ORDER BY `datetime` DESC LIMIT 1");
			$temp = new clsTemperature();
			$temp->id = $temps_table[0]['id'];
			$temp->garden_id = $temps_table[0]['garden_id'];
			$temp->temp = $temps_table[0]['temp'];
			$temp->maxtemp = $temps_table[0]['max'];
			$temp->mintemp = $temps_table[0]['min'];
			$temp->humidity = $temps_table[0]['humidity'];
			$temp->datetime = $temps_table[0]['datetime'];
			return $temp;
		}
		public static function getTemps($db, $garden_id = 1){
			$temps_table = $db->select("SELECT * FROM `temperature` WHERE `garden_id` = '$garden_id' AND `datetime` > '".date("Y-m-d H:i:s",time()-(60*60*24*7*3))."'");
			$temps = array();
			foreach($temps_table as $temps_row){
				$temp = new clsTemperature();
				$temp->id = $temps_row['id'];
				$temp->garden_id = $temps_row['garden_id'];
				$temp->temp = $temps_row['temp'];
				$temp->maxtemp = $temps_row['max'];
				$temp->mintemp = $temps_row['min'];
				$temp->humidity = $temps_row['humidity'];
				$temp->datetime = $temps_row['datetime'];
				array_push($temps,$temp);
			}
			clsTemperature::$temps_cache = $temps;
			return $temps;
		}
		public static function getAverageTempAtHour($h){
			$temps = clsTemperature::$temps_cache;
			$temp_count = 0;
			$temp_total = 0;
			$min_total = 0;
			$max_total = 0;
			foreach($temps as $temp){
				if(date("G",strtotime($temp->datetime)) == $h){
					$temp_count++;
					$temp_total += $temp->temp;
					$min_total += $temp->mintemp;
					$max_total += $temp->maxtemp;
				}
			}
			$avTemp = new clsTemperature();
			$avTemp->temp = $temp_total/$temp_count;
			$avTemp->mintemp = $min_total/$temp_count;
			$avTemp->maxtemp = $max_total/$temp_count;
			return $avTemp;
		}
		
		public static function getAverageTempAtHourToday($h){
			$temps = clsTemperature::$temps_cache;
			$temp_count = 0;
			$temp_total = 0;
			$min_total = 0;
			$max_total = 0;
			foreach($temps as $temp){
				if(strtotime($temp->datetime) > time()-(20 * 60 * 60))
				if(date("G",strtotime($temp->datetime)) == $h){
					$temp_count++;
					$temp_total += $temp->temp;
					$min_total += $temp->mintemp;
					$max_total += $temp->maxtemp;
				}
			}
			$avTemp = new clsTemperature();
			$avTemp->temp = $temp_total/$temp_count;
			$avTemp->mintemp = $min_total/$temp_count;
			$avTemp->maxtemp = $max_total/$temp_count;
			return $avTemp;
		}
		public static function getDailyMax(){
			$temps = clsTemperature::$temps_cache;
			$maxTemp = 0;
			foreach($temps as $temp){
				if($temp->maxtemp > $maxTemp)
					$maxTemp = $temp->maxtemp;
			}
			return $maxTemp;
		}
		public static function getDailyMin(){
			$temps = clsTemperature::$temps_cache;
			$minTemp = 10000;
			foreach($temps as $temp){
				if($temp->mintemp < $minTemp)
					$minTemp = $temp->mintemp;
			}
			return $minTemp;
		}
	}
	
	class clsDailyTemperature extends clsTemperature {
		public $dayTime;
		public $nightTime;
		public $dayTimeCount = 0;
		public $nightTimeCount = 0;

		public function clsDailyTemperature($temps_table,$lights_on,$lights_off){
			$this->dayTime = new clsTemperature();
			$this->nightTime = new clsTemperature();
			$this->dayTimeCount = 0;
			$this->nightTimeCount = 0;
			$this->nightTime->maxtemp = $this->dayTime->maxtemp = $this->maxtemp = 0;
			$this->nightTime->mintemp = $this->dayTime->mintemp = $this->mintemp = 1000;
			// go through all the temps for the day
			foreach($temps_table as $temp_row) {
				// calculate the overall daily averages
				$this->temp += $temp_row['temp'];
				if($this->maxtemp < $temp_row['max'])
					$this->maxtemp = $temp_row['max'];
				if($this->mintemp > $temp_row['min'])
					$this->mintemp = $temp_row['min'];
				$this->humidity += $temp_row['humidity'];
				$h = date("G",strtotime($temp_row['datetime']));
				// figure out if this is a daytime or a nightime temp
				if($lights_on > $lights_off){
					// the lights are turned off before they are turned on
					if($h >= $lights_off && $h < $lights_on){
						$this->nightTime->temp += $temp_row['temp'];
						if($this->nightTime->maxtemp < $temp_row['max'])
							$this->nightTime->maxtemp = $temp_row['max'];
						if($this->nightTime->mintemp > $temp_row['min'])
							$this->nightTime->mintemp = $temp_row['min'];
						$this->nightTime->humidity += $temp_row['humidity'];
						$this->nightTimeCount++;
					} else {
						$this->dayTime->temp += $temp_row['temp'];
						if($this->dayTime->maxtemp < $temp_row['max'])
							$this->dayTime->maxtemp = $temp_row['max'];
						if($this->dayTime->mintemp > $temp_row['min'])
							$this->dayTime->mintemp = $temp_row['min'];
						$this->dayTime->humidity += $temp_row['humidity'];
						$this->dayTimeCount++;
					}
				}
			}
			$this->temp = round($this->temp/count($temps_table),1);
			$this->humidity = round($this->humidity/count($temps_table),0);
			
			$this->dayTime->temp = round($this->dayTime->temp/$this->dayTimeCount,1);
			$this->dayTime->humidity = round($this->dayTime->humidity/$this->dayTimeCount,0);
			
			$this->nightTime->temp = round($this->nightTime->temp/$this->nightTimeCount,1);
			$this->nightTime->humidity = round($this->nightTime->humidity/$this->nightTimeCount,0);
			
		}
	}
	
}
?>