<?php 
@include_once("config.php");
@include_once("class.php");

$docker_parameter = $argv;
var_dump($docker_parameter);

$image_name = $docker_parameter[1];
$container_name = $docker_parameter[2];
$container_port = $docker_parameter[3];
$time = $docker_parameter[4];

$Docker = new docker($ip_url,$image_name,$container_name,$container_port,$time,$max_number,$port_pool,$error_location);
$Docker->reset();
$Docker->docker();
?>
