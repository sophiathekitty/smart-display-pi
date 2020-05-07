<?php
function OnlineServers(){
    return clsDB::$db_g->select("SELECT * FROM `servers` WHERE `online` = '1'");
}
function OfflineServers(){
    return clsDB::$db_g->select("SELECT * FROM `servers` WHERE `online` = '0'");
}
function HubServer(){
    $servers = clsDB::$db_g->select("SELECT * FROM `servers` WHERE `online` = '1' AND `main` = '1' LIMIT 1;");
    if(count($servers)){
        return $servers[0];
    }
    return null;
}
?>