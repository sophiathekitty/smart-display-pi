<?php
require_once("../../includes/main.php");
$server = HubServer();
$info = file_get_contents("http://".$server['url']."/api/photos/");
$photos = json_decode($info);
$data = ["photos"=>[]];
$hour = date("H");
if($hour > 6 && $hour < 18){
    foreach($photos->photos as $photo){
        $isDay = false;
        foreach($photo->tags as $tag){
            if($tag == "day"){
                $photo->url = "http://".$server['url'].$photo->filepath;
                array_push($data['photos'],$photo);
            }
        }
    }
} else {
    foreach($photos->photos as $photo){
        //echo $photo->filepath."<br>";
        $isDay = false;
        foreach($photo->tags as $tag){
            if($tag == "night"){
                $photo->url = "http://".$server['url'].$photo->filepath;
                array_push($data['photos'],$photo);
            }
        }
    }
}
$data['random'] = $data['photos'][rand(0,count($data['photos']))];
OutputJson($data);
?>