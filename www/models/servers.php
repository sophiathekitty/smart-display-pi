<?php
function OnlineServers(){
    return clsDB::$db_g->select("SELECT * FROM `servers` WHERE `online` = '1'");
}
function OfflineServers(){
    return clsDB::$db_g->select("SELECT * FROM `servers` WHERE `online` = '0'");
}
?>