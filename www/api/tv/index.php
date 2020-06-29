<?php 
require_once("../../includes/main.php");
if($_GET['power']){
    if($_GET['power'] == "on"){
        TurnTVOn();
    } elseif($_GET['power'] == "off" || $_GET['power'] == "standby") {
        TurnTVOff();
        TurnChromecastOff();
    }
}
$osd_name = GetSetting("osd_name");
$data = ['tv'=>GetTVPow(),'osd_name'=>$osd_name['value']];
SetMyName($osd_name['value']);
OutputJson($data);
?>