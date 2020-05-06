<?php
if(!isset($root_path)){
    $root_path = "";
    $i = 0;
    while(!is_file($root_path."includes/main.php") && $i < 10){
        $root_path .= "../"; $i++;
    }
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set("America/Denver");
require_once($root_path."php/clsDB.php");
require_once($root_path."php/clsNumber.php");
require_once($root_path."settings.php");
$db = new clsDB($db_info['database'], $db_info['username'], $db_info['password']);

IncludeFolder($root_path."models/");
IncludeFolder($root_path."modules/");
IncludeFolder($root_path."views/");

function IncludeFolder($path){
    //echo "<br><b>$path</b><br>";
    $shared_models_dir = opendir($path);
    // LOOP OVER ALL OF THE  FILES
    
    while ($file = readdir($shared_models_dir)) { 
        //echo "<br><i>$file</i> ".is_dir($path.$file)."  ".is_dir($file."/")." <br>";
        // IF IT IS NOT A FOLDER, AND ONLY IF IT IS A .php WE ACCESS IT
        if(!is_dir($file) && strpos($file, '.php')>0 && is_file($path.$file)) { 
            //echo "<br>$path$file<br>";
            require_once($path.$file);
        } elseif(is_dir($path.$file) && $file != ".." && $file != "."){
            IncludeFolder($path.$file."/");
        }
    }
    
    // CLOSE THE DIRECTORY
    closedir($shared_models_dir);
    
}


//require_once($root_path."includes/models.php");
//require_once($root_path."includes/modules.php");
//require_once($root_path."includes/views.php");

?>