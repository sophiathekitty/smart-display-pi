<?php
require_once("../../includes/main.php");
$data = [];
if(isset($_GET['var'], $_GET['value'])){
    SetSetting($_GET['var'],$_GET['value']);
    $data = SettingStamp($_GET['var']);
} elseif(isset($_GET['var'])){
    $data = SettingStamp($_GET['var']);
} else {
    $data = SimpleSettingsStamps();
}
OutputJson($data);
?>