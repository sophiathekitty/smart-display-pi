<?php
function GetSettings(){
    return clsDB::$db_g->select("SELECT * FROM `settings`");
}
function GetSetting($name){
    $setting = clsDB::$db_g->select("SELECT * FROM `settings` WHERE `name` = '$name'");
    if(count($setting)){
        return $setting[0];
    }
    return ["error"=>"404 setting not found"];
}
function SetSetting($name,$value){
    clsDB::$db_g->safe_update("settings",["value",$value],["name"=>$name]);
}
?>