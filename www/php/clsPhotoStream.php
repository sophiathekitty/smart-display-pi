<?php 
// image class

class clsPhotoStream {
	// private vars
	var $im;
	var $max_size;
	var $type;
	var $width;
	var $height;
	var $size;
	var $error;
	var $imageTypes;
	var $loaded;
	var $files;
	
	// constructor
	function clsPhotoStream(){
		$this->loaded = false;
		$this->max_size = 100000000;
		$this->imageTypes = array(
					IMG_GIF=>'GIF',
					IMG_JPG=>'JPG',
					IMG_PNG=>'PNG',
					IMG_WBMP=>'WBMP'
				);
	}
	function setFiles($files){
		$this->files = $files;
	}
	function stream(){
		if($this->loaded){
			switch($this->type){
				case IMG_GIF:
					header('Content-Type: image/gif');
					imagegif($this->im);
				break;
				case IMG_JPG:
					header('Content-Type: image/jpeg');
					imagejpeg($this->im);
				break;
				case IMG_PNG:
					header('Content-Type: image/png');
					imagepng($this->im);
				break;
				case IMG_WBMP:
					header('Content-Type: image/wbmp');
					imagewbmp($this->im);
				break;
				default:
					$this->error = "Unknown Image Type. ($this->type)";
					return false;
				break;
			}
		}
	}
}
?>