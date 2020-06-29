<?php
function RoundHour($time){
    $h = date("H",$time);
    $m = date("i",$time);
    if($m > 30) $h++;
    return $h;
}
?>