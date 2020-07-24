<?php
$ip_url = 'http://192.168.21.131:2375'; // docker remote api 
$max_number = 20;   // 最大靶机数量
$port_pool = range(4000,4300);  //端口池
$error_location = "http://localhost/docker/fe1w0/error.php"; //报错页面  http://202.119.201.197:2000/docker/error.php
$refer_url='http://localhost/docker/fe1w0'; // 允许的refer_url
$login_url='http://localhost';  // 登录页面
$images_array=array("dvwa"=>"astronaut1712/dvwa:latest","sqlilabs"=>"c0ny1/sqli-labs:0.1");
?>