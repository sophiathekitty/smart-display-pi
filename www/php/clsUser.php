<?php 
// exit if stand alone
require_once("clsDB.php");
error_reporting(E_ERROR);
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
	exit;
if(!defined('USER_CLASS')){
	define('USER_CLASS',true);
	class clsUser{
		public $id;
		public $name;
		private $user_key;
		
		public static $current_user;
		
		public function login_id($id){
			list($user_row) = clsDB::$db_g->select("SELECT * FROM `gardeners` WHERE `id` = '$id'");
			$this->load_user($user_row);
			$this->set_cookies();
			clsUser::$current_user = $this;
		}
		public function login($name){
			list($user_row) = clsDB::$db_g->select("SELECT * FROM `gardeners` WHERE `name` LIKE '$name'");
			$this->load_user($user_row);
			$this->set_cookies();
			clsUser::$current_user = $this;
		}
		public function login_key($user_key){
			list($user_row) = clsDB::$db_g->select("SELECT * FROM `gardeners` WHERE `user_key` LIKE '$user_key'");
			$this->load_user($user_row);
			$this->set_cookies();
			clsUser::$current_user = $this;
		}
		public function login_cookie(){
			$this->login_key($_COOKIE['user_key']);
		}
		
		public function load_user_id($id){
			list($user_row) = clsDB::$db_g->select("SELECT * FROM `gardeners` WHERE `id` = '$id'");
			$this->load_user($user_row);
		}
		private function load_user($user_row){
			$this->id = $user_row['id'];
			$this->name = $user_row['name'];
			$this->user_key = $user_row['user_key'];
		}
		
		public function set_cookies(){
			$_SESSION['name'] = $this->name;
			$_SESSION['user_key'] = $this->user_key;
			$_COOKIE['user_key'] =  $this->user_key;
			setcookie('user_key',$this->user_key,time()+(60*60*24*30));
		}
		
		public function add_user($name,$pass){
			$user = array();
			$user['name'] = $this->name = $name;
			$user['user_key'] = $this->user_key = md5($pass);
			$this->id = clsDB::$db_g->safe_insert("gardeners",$user);
			
		}
	}// end of class
} // end of defined

?>