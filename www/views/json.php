<?php
function OutputJson($data){
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    if(json_last_error())
        echo json_last_error_msg();    
}    
?>