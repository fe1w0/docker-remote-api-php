<?php 
@include_once('config.php');
@include_once('class.php');

$time=$_POST['TTL'];
$image_name=$_POST['image_name'];

 
/* $image_name="dvwa";
$time="00:01:00";
 */

/*
if(isset($_SERVER['HTTP_REFERER'])) {
    if($_SERVER['HTTP_REFERER']!== $refer_url )
    {   
        $string = sprintf("<script>location.href='%s?error=1';</script>",$error_location); // 错误类型 1 $_SERVER['HTTP_REFERER'])错误 ,跳转到login.php
        echo($string);
    }
}
else{
    $string = sprintf("<script>location.href='%s?error=2';</script>",$error_location);  // 错误类型 2  $_SERVER['HTTP_REFERER'])缺少 ,跳转到login.php
}
*/

function check_images($data,$images_array)
{
    if($images_array[$data]){
        return false;
    }
    else{
        return true;
    }
}
function check_time($time)
{  
    $parsed = strtotime($time);
    return empty($parsed);
}

## docker

if( empty($image_name) or empty($time) or check_images($image_name,$images_array) or check_time($time)){  
    $string = sprintf("<script>location.href='%s?error=4';</script>",$error_location);
    echo($string);       //错误类型 4 docker 页面参数缺少或错误,跳转到靶机请求页面
}
else{
    $image_name = $images_array[$image_name];
    $docker = new docker($ip_url,$image_name,$container_name="",$container_port="",$time,$max_number,$port_pool,$error_location);
    $BOOL = $docker->ready();
    if($BOOL)
    {
        // 跳转到成功页面
        $string = sprintf("<script>location.href='http://192.168.21.131:%s';</script>",$docker->container_port);
        echo $string;
        //image_name   container_name  container_port time
        $shell = sprintf("php docker.php %s %s %s %s >/dev/null &",$image_name,$docker->container_name,$docker->container_port,$time);
        var_dump($shell);
        shell_exec($shell);
    }
    else{
        $string = sprintf("<script>location.href='%s?error=3';</script>",$docker->error_location);
        echo($string);    //错误类型 3 容器达到上限,跳转到同一的错误页面
    }
}

?>
