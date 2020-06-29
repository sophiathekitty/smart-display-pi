<?php
function GetSettings(){
    return clsDB::$db_g->select("SELECT * FROM `settings`");
}
function GetSetting($name){
    $setting = clsDB::$db_g->select("SELECT * FROM `settings` WHERE `name` = '$name'");
    if(count($setting)){
        return $setting[0];
    }
    return null;
}
function SetSetting($name,$value){
    $setting = GetSetting($name);
    if(is_null($setting))
        clsDB::$db_g->safe_insert("settings",["value"=>$value,"name"=>$name]);
    else
        clsDB::$db_g->safe_update("settings",["value"=>$value],["name"=>$name]);
}
?>