<?php
function TurnTVOn(){
    shell_exec("echo 'on 0.0.0.0' | cec-client -s -d 1");
}
function TurnTVOff(){
    shell_exec("echo 'standby 0.0.0.0' | cec-client -s -d 1");
}
function TurnChromecastOff(){
    shell_exec("echo 'standby 4.0.0.0' | cec-client -s -d 1");
}
function GetTVPow(){
    $output = shell_exec("echo 'pow 0.0.0.0' | cec-client -s -d 1");
    $pos = strpos($output,"power status:") + 14;
    $length = strlen($output) - $pos - 1;
    return substr($output,$pos,$length);
}
function GetChromecastPow(){
    $output = shell_exec("echo 'pow 4.0.0.0' | cec-client -s -d 1");
    $pos = strpos($output,"power status:") + 14;
    $length = strlen($output) - $pos - 1;
    return substr($output,$pos,$length);
}
function SetMyName($name){
    $cec = strtoCEC($name);
    shell_exec("echo 'txn 10:47$cec' | cec-client -s -d 1");
}
function strtoCEC($string){
    $cec = "";
    for($i = 0; $i < strlen($string) && $i <= 14; $i++){
        $cec .= ":".bin2hex(substr($string,$i,1));
    }
    return $cec;
}
function BecomeActiveSource(){
    shell_exec("echo 'as' | cec-client -s -d 1");
}
?>