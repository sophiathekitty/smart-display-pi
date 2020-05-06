<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("America/Denver");

require_once("../../php/clsDB.php");
require_once("../../settings.php");
$db = new clsDB($db_info['database'], $db_info['username'], $db_info['password']);

$ifconfig = shell_exec("ifconfig wlan0");
if(strpos($ifconfig,"inet6") > 0){
	
	$mac_address = substr($ifconfig,strpos($ifconfig,"inet6")+6,strpos($ifconfig,"prefixlen") -( strpos($ifconfig,"inet6")+6) - 1);
}
$data = [
	'info' => [
		'url' => $_SERVER['HTTP_HOST'],
		'type' => "display",
		'main' => 0,
		'server' => "pi3ap",
		'mac_address' => $mac_address,
		'name' => "raspberry display"
		]
	];
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
echo json_encode($data);
?>