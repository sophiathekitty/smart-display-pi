<?php
require_once("../../../includes/main.php");

$server = HubServer();
$info = file_get_contents("http://".$server['url']."/api/weather/log?verbose=true");
$data = json_decode($info);

OutputJson($data);
?>