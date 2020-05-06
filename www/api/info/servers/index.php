<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("America/Denver");

require_once("../../../php/clsDB.php");
require_once("../../../settings.php");
$db = new clsDB($db_info['database'], $db_info['username'], $db_info['password']);

if(isset($_GET['name'],$_GET['url'],$_GET['type'],$_GET['main'],$_GET['last_ping'],$_GET['online'])){
	
	setHub($_GET['name'],$_GET['url'],$_GET['type'],$_GET['main'],$_GET['last_ping'],$_GET['online'],$_GET['mac_address']);

	$data = [
		'get' => $_GET,
		];
		
} else {
	$server = loadHubURL();

	$data = [
		'hub' => [
			'url' => $server
			],
		];
	
}

function setHub($name,$url,$type,$main,$last_ping,$online,$mac_address){
	$server = clsDB::$db_g->select("SELECT * FROM `servers` WHERE `mac_address` = '$mac_address';");
	$data = ["name"=>$name,"url"=>$url,"type"=>$type,"main"=>$main,"last_ping"=>$last_ping,"online"=>$online,"mac_address"=>$mac_address];
	if(count($server)){
		clsDB::$db_g->safe_update("servers",$data);
	} else {
		clsDB::$db_g->safe_insert("servers",$data);
	}
}
function loadHubUrl(){
	$server = clsDB::$db_g->select("SELECT * FROM `servers` WHERE `main` = '1';");
	if(count($server)){
		return $server[0]['url'];
	}
	return "not found";
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
?>