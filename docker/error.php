<?php
@include_once('config.php');

$error = @$_GET['error'];
$HTTP_REFERER = @$_SERVER['HTTP_REFERER'];
$url = array(1=>"error1.html",2=>"error1.html",3=>"error1.html",4=>"error1.html");

/* 
// 错误类型 1 $_SERVER['HTTP_REFERER'])错误 ,跳转到login.php
// 错误类型 2  $_SERVER['HTTP_REFERER'])缺少 ,跳转到login.php
// 错误类型 3 容器达到上限,跳转到靶机选择的错误页面
// 错误类型 4 docker页面缺少参数,跳转到靶机请求页面
*/

if( !$HTTP_REFERER or $HTTP_REFERER !== "http://localhost/docker/xxp/docker.php"){
    $string = sprintf("<script>location.href='%s';</script>",$refer_url);
    echo($string);
}

function error()
{
   die("<p>参数错误</p>");
}

function check($data,$url)
{   
    $array = range(1,4);
    foreach($array as $i)
    {
        if($i !== $data)
        {
            error();
        }
        else
        {
            $string = sprintf("<script>location.href='%s';</script>",$url[$i]);
            echo($string);
        }       
    }
}

?>