<?php
function SettingsStamps(){
    $settings = GetSettings();
    $settings_obj = [];
    foreach($settings as $setting){
        $settings_obj[$setting['name']] = ["value" => $setting['value'], "modified"=>$setting['modified']];
    }
    return $settings_obj;
}
function SettingStamp($name){
    $setting = GetSetting($name);
    if(count($setting)){
        return [$setting[0]['name'] => ["value" => $setting[0]['value'],"modified" => $setting[0]['modified']]];
    }
    return ["error"=>"404 setting not found"];
}
function SimpleSettingsStamps(){
    $settings = GetSettings();
    $settings_obj = [];
    foreach($settings as $setting){
        $settings_obj[$setting['name']] = $setting['value'];
    }
    return $settings_obj;
}
function SimpleSettingStamp($name){
    $setting = GetSetting($name);
    if(count($setting)){
        return [$setting[0]['name'] => $setting[0]['value']];
    }
    return ["error"=>"404 setting not found"];
}
?>