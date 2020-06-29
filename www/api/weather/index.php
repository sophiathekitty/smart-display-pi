<?php
require_once("../../includes/main.php");
$server = HubServer();

if(isset($_GET['current'])){
    $info = file_get_contents("http://".$server['url']."/api/weather/?current=".$_GET['current']);
    $data = json_decode($info);
    SetSetting('sunrise',RoundHour($data->current->sys->sunrise));
    SetSetting('sunset',RoundHour($data->current->sys->sunset));
} elseif(isset($_GET['forecast'])){
    $info = file_get_contents("http://".$server['url']."/api/weather/?forecast=".$_GET['forecast']);
    $data = json_decode($info);
} else {
    $info = file_get_contents("http://".$server['url']."/api/weather/");
    $data = json_decode($info);
}

OutputJson($data);
?>