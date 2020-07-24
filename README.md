# docker-remote-api-php
> 官方文档🔗:[https://docs.docker.com/engine/api/](https://docs.docker.com/engine/api/)
> 可以通过 `docker version`查看 Engine: API version
> 个人当前 服务器和客户端都是1.4版本
> 需注意版本不同,手册也不同，基本上一个小版本一个手册
> 我宣布`curl`就是我大哥 👍
> 前端来着xpp

## search the containers

```
curl http://192.168.21.131:2375/containers/json
```

## create one container

* create.json

> [https://docs.docker.com/engine/api/v1.40/#operation/ContainerCreate](https://docs.docker.com/engine/api/v1.40/#operation/ContainerCreate)

```bash
curl -v -X POST -H "Content-Type: application/json"  http://192.168.21.131:2375/containers/create?name=dvwa -d '{"Image": "astronaut1712/dvwa:latest", "HostConfig": {"NetworkMode": "bridge","PortBindings": {"80/tcp": [{"HostPort": "3080"}]}}}'
```

## start the container

```bash
curl -v -X POST http://192.168.21.131:2375/containers/dvwa/start
```

## stop the container

```bash 
curl -v --raw -X POST http://192.168.21.131:2375/containers/dvwa/stop
```

## delete the container

```
 curl -v -X DELETE http://192.168.21.131:2375/containers/dvwa
```

# php  与  docker remote api

```bash
└─fe1w0
        class.php
        config.php
        curl.php
        docker.php
        error.php
        error1.html
        error2.html
        error3.html
        error4.html
        index.css
        index.html
```

其中重要的是`class.php、curl.php、config.php、docker.php`

* `class.php`

```php
<?php
class docker{
    public $ip_url;    # http://192.168.21.131:2375 config.php
    public $image_name;
    public $container_name;
    public $container_port;
    public $time;
    public $search_url;
    public $create_url;
    public $start_url;
    public $stop_url;   
    public $delete_url;
    public $data_string;
    public $max_number;  # 容器的最大数量 config.php
    public $number;
    public $arr_container;
    public $port_pool;
    public $current_ports;
    public $error_location;

    function __construct($ip_url="",$image_name="",$container_name="",$container_port="",$time="",$max_number=30,$port_pool,$error_location)
    {
        $this->ip_url =$ip_url;
        $this->image_name = $image_name;
        $this->container_name = $container_name;
        $this->container_port = $container_port;
        $this->max_number = $max_number;
        $this->arr_container=array();
        $this->current_ports=array();
        $this->port_pool=$port_pool; //端口池由外部设置
        $this->number=0;
        $this->error_location = $error_location;

        $parsed = date_parse($time);
        $this->time= $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

        $this->search_url = sprintf('%s/containers/json?all=1',$this->ip_url);# get
    }

    function geturl($url){
        # 初始化
        $headerArray =array("Content-type:application/json;","Accept:application/json");
        $ch = curl_init();
        
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_TIMEOUT,500);
        curl_setopt($ch,CURLOPT_URL,$url); # url 为api的url
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        #curl_setopt($ch, CURLOPT_HEADER, true);
        $output = curl_exec($ch);
        curl_close($ch);
    
        # json 转为数组 $res
        $output = json_decode($output,true);
        return $output;
    }

    
    function posturl($url,$data_string=""){
        #$data_string = json_encode($requestData);
        #var_dump($data_string);
        $ch = curl_init();
        $headerArray = array('Content-Type: application/json', 'Content-Length: '.strlen($data_string));
    
        curl_setopt($ch,CURLOPT_URL,$url);
        #curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
    
        #$output = json_decode($output,true);
        return $output;
    }


    function deleteurl($url){
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $url);
        #curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $output = curl_exec($ch);
        curl_close($ch);
        
        #$output = json_decode($output,true);
        return $output;
    }



    function number_container()
    {
        $this->arr_container = $this->geturl($this->search_url);
        $this->number = count($this->arr_container,0);
    }

    # 得到一个相对随机的端口
    function Random_port()
    {
        # 暂时使用垃圾一点的 伪随机端口
        $max = end($this->port_pool);
        $min = reset($this->port_pool);
        $port = mt_rand($min,$max);
        return $port;
    }
    # 获取当前容器的端口数组
    function get_port_array()
    {
        $this->arr_container = $this->geturl($this->search_url);
        #var_dump($this->arr_container);
        $this->number = count($this->arr_container,0);
        $i = range(0,$this->number-1);
        if($this->number != 0)
        {
            foreach($i as $nn)
            {
                $this->current_ports[$nn] = $this->arr_container[$nn]["Ports"][1]["PublicPort"];
            }
        }

    }

    # 迭代确定最终端口
    function get_port()
    {   
        $booL = true;
        $Random_port =$this->Random_port();
        $this->get_port_array();
        while(true){
            foreach($this->current_ports as $port)
            {
                if($port == $Random_port)
                {
                    $booL= false;
                }
            }
            if(!$booL){
                $this->get_port();
            }
            else
            {
                $this->container_port = $Random_port;
                #echo("[*] Container_port: ".$this->container_port."\n");
                break;
            }
        }

    }
    # 重新加载参数



    function create_container(){
        $res = $this->posturl($this->create_url,$this->data_string);
        echo("[*] create container:");
        echo('Request: curl -v -X POST -H "Content-Type: application/json" '.$this->create_url.' -d \''. $this->data_string.'\''."<br>");
        echo("Response: ".$res."<br>");
    }

    function start_container(){
        $res =  $this->posturl($this->start_url);
        echo("[*] start container:");
        echo('Request: curl -v -X POST '.$this->start_url."<br>");
        echo("Response: ".$res."<br>");
    }

    function stop_container(){
        $res = $this->posturl($this->stop_url);
        echo("[*] stop container:");
        echo('Request: curl -v -X POST '.$this->stop_url."<br>");
        echo("Response: ".$res."<br>");
    }

    function delete_container(){
        $res = $this->deleteurl($this->delete_url);
        echo("[*] delete container:");
        echo('Request: curl -v -X DELETE '.$this->delete_url."<br>");
        echo("Response: ".$res."<br>");
    }

    function ready()
    {
        $this->number_container();
        $this->reset();
        if($this->number < $this->max_number)
        {
            return true;
        }
        else{
            return false;
        }


    }

    function reset()
    {
        if(!$this->container_name  and  !$this->container_port)
        {
            $this->get_port(); // container_port
            //container_name
            $pre_container_name=time();
            $this->container_name = $pre_container_name.$this->container_port; 
        }
        $this->create_url = sprintf('%s/containers/create?name=%s',$this->ip_url,$this->container_name); #post
        $this->start_url = sprintf('%s/containers/%s/start',$this->ip_url,$this->container_name); #post
        $this->stop_url = sprintf('%s/containers/%s/stop?t=%s',$this->ip_url,$this->container_name,$this->time); #post
        $this->delete_url = sprintf('%s/containers/%s',$this->ip_url,$this->container_name); #delete
        $this->data_string = sprintf( '{"Image": "%s","HostConfig": {"NetworkMode": "bridge","PortBindings": {"80/tcp": [{"HostPort": "%s"}]}}}',$this->image_name,$this->container_port);
    }

    function docker(){
        $this->create_container($this->create_url,$this->data_string);
        $this->start_container($this->start_url);
        $this->stop_container($this->stop_url);
        //$this->delete_container($this->delete_url);
    }

}

?>
```

* `curl.php`

```php
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
```



* `config.php`

```php
<?php
$ip_url = 'http://192.168.21.131:2375'; // docker remote api 
$max_number = 20;   // 最大靶机数量
$port_pool = range(4000,4300);  //端口池
$error_location = "http://localhost/docker/fe1w0/error.php"; //报错页面  http://202.119.201.197:2000/docker/error.php
$refer_url='http://localhost/docker/fe1w0'; // 允许的refer_url
$login_url='http://localhost';  // 登录页面
$images_array=array("dvwa"=>"astronaut1712/dvwa:latest","sqlilabs"=>"c0ny1/sqli-labs:0.1");
?>
```

* `docker.php`

```php
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
```



# php与定时 

> 目前遇到的最大的难点
>
> 两种思路
>
> * php 能控制docker stop  and delete
> * crontab 看别人说,可能不太合适

### docker 远程api  控制  stop 、delete

> github上的相关讨论
>
> https://github.com/moby/moby/issues/1905

在1.4的文档中我们可以看到api有`StopTimeout`这个参数,但奇怪的是在实际使用中,并没有效果(也可能我设置错了)

![image-20200722125449814](http://img.xzaslxr.xyz/img/image-20200722125449814.png)

以下是我尝试的命令:

```bash
curl -v -X POST -H "Content-Type: application/json http://192.168.21.131:2375/containers/create?name=dvwa -d '{"Image": "astronaut1712/dvwa:latest","HostConfig": {"NetworkMode": "bridge","StopSignal": "SIGTERM","StopTimeout":30,"PortBindings": {"80/tcp": [{"HostPort": "3080"}]}}}'
```

但在`wait`功能中有一个起到延时功能的参数,说明如下:

> 参考链接:https://docker-php.readthedocs.io/en/latest/cookbook/container-run/#waiting-for-the-container-to-end

![image-20200722141740320](http://img.xzaslxr.xyz/img/image-20200722141740320.png)

修改php代码

![image-20200722141912689](http://img.xzaslxr.xyz/img/image-20200722141912689.png)

#### 执行结果

```bash
[*] create container:
Request: curl -v -X POST -H "Content-Type: application/json" http://192.168.21.131:2375/containers/create?name=dvwa -d '{"Image": "astronaut1712/dvwa:latest","HostConfig": {"NetworkMode": "bridge","PortBindings": {"80/tcp": [{"HostPort": "3080"}]}}}'
Response: HTTP/1.1 201 Created
Api-Version: 1.40
Content-Type: application/json
Docker-Experimental: false
Ostype: linux
Server: Docker/19.03.12 (linux)
Date: Wed, 22 Jul 2020 06:15:18 GMT
Content-Length: 88

{"Id":"9a65141c4191505094838d91c5f537744e18a795494c480199f6598951102fa8","Warnings":[]}

[*] start container:
Request: curl -v -X POST http://192.168.21.131:2375/containers/dvwa/start
Response: HTTP/1.1 204 No Content
Api-Version: 1.40
Docker-Experimental: false
Ostype: linux
Server: Docker/19.03.12 (linux)
Date: Wed, 22 Jul 2020 06:15:19 GMT

[*] stop container:
Request: curl -v -X POST http://192.168.21.131:2375/containers/dvwa/stop?t=30
Response: HTTP/1.1 204 No Content
Api-Version: 1.40
Docker-Experimental: false
Ostype: linux
Server: Docker/19.03.12 (linux)
Date: Wed, 22 Jul 2020 06:15:50 GMT

[*] delete container:
Request: curl -v -X DELETE http://192.168.21.131:2375/containers/dvwa
Response: HTTP/1.1 204 No Content
Api-Version: 1.40
Docker-Experimental: false
Ostype: linux
Server: Docker/19.03.12 (linux)
Date: Wed, 22 Jul 2020 06:15:50 GMT

[Finished in 32.2s]
```

# php与容器数量控制

## 利用查询到的容器信息来解决该问题

```php
    # 
    function number_container()
    {
        $res = $this->geturl($this->search_url);
        $number = count($res,0);//count()中的二个参数为0时,统计第一维度的所有元素,为1时循环统计遍历所有元素
        return $number;
    }
/*

省略中间的代码

*/

    function docker(){
        $number = $this->number_container();
        if($number < $this->max_number)
        {
            $this->create_container($this->create_url,$this->data_string);
            $this->start_container($this->start_url);
            $this->stop_container($this->stop_url);
            $this->delete_container($this->delete_url);
        }
        else{
            die("[*] 容器数量已经达到最大值,请稍后尝试");
        }
    }
```

# php 与端口转发

## 端口池

端口池大小为`[5000-6000]`，这个由配置文件控制

如:

* `config.php`

```php
<?php
$ip_url = 'http://192.168.21.131:2375';
$max_number = 3;
$port_pool = range(4000,4300);
?>
```

* `docker.php`节选

```php
class docker{
    /*
    省略
    */
    public $port_pool;
    
    function __construct($ip_url="",$image_name="",$container_name="",$container_port="",$time="",$max_number=30,$port_pool)
    {
      	 /*
   		 省略
    	 */
        $this->port_pool=$port_pool; //端口池由外部设置
        /*
   		 省略
    	 */
    }
 /*
省略
*/ 
}        
```



## 统计处于开启状态容器的端口

* 思路

1. 从`5000~6000`之间随机挑选一个端口 port_A
2. 读取container的端口信息,并用数组保存下来 port_B
3. 二者比较, 若port_A 不在port_B 中,则port_A作为正常的端口;反之,退回到第一步,重新执行,直到有正常的端口出现。

* 核心代码

```php
    # 得到一个相对随机的端口
    function Random_port()
    {
        # 暂时使用垃圾一点的 伪随机端口
        $max = end($this->port_pool);
        $min = reset($this->port_pool);
        $port = mt_rand($min,$max);
        return $port;
    }
    # 获取当前容器的端口数组
    function get_port_array()
    {
        $this->arr_container = $this->geturl($this->search_url);
        $this->number = count($this->arr_container,0);
        $i = range(0,$this->number-1);
        foreach($i as $nn)
        {
            $this->current_ports[$nn] = $this->arr_container[$nn]["Ports"][1]["PublicPort"];
        }

    }



    # 迭代确定最终端口
    function get_port()
    {   
        $booL = true;
        $Random_port =$this->Random_port();
        $this->get_port_array();
        while(true){
            foreach($this->current_ports as $port)
            {
                if($port == $Random_port)
                {
                    $booL= false;
                }
            }
            if(!$booL){
                $this->get_port();
            }
            else
            {
                $this->container_port = $Random_port;
                echo("[*] Container_port: ".$this->container_port."\n");
                break;
            }
        }

    }
    # 重新加载参数
    function reset()
    {
        $this->data_string = sprintf( '{"Image": "%s","HostConfig": {"NetworkMode": "bridge","PortBindings": {"80/tcp": [{"HostPort": "%s"}]}}}',$this->image_name,$this->container_port);
    }

```



# 多线程 或 多进程

Q：api客户端为什么需要多线程或多进程。

A：以下是个人理解：

当php脚本没有设置多线程或没有使用进程方式启动时,出于性能考虑,中间器会有响应时间设定,但响应超时时,则认为目的服务器有问题,即状态为504错误。而多线程的设定或多进程的方式,不会考虑响应时间问题,直达结束。此外,在前一种情况中,是全部执行才可以输出,而在第二种情况中,随着进展一步步输出。而我的代码(起初的代码),也遇到这个问题,当运行到docker()函数时,需要等待较长的等待时间,比如30分钟，那毫不犹豫就是504报错。所以需要多线程或多进程。

Q：如何实现？

A: 

![image-20200723184832220](http://img.xzaslxr.xyz/img/image-20200723184832220.png)

> 此回答参考此链接  https://www.zhihu.com/question/31893506/answer/53797159  作者：徐汉彬

当正如前辈,在这么长的时间段里,如何确保父进程()还在进行工作,这是比较难确定的,但对于我而言依旧有学习价值。

## 多线程 尝试

> 学习链接:
>
> 【Multi-Threading in PHP with pthreads】
>
> https://gist.github.com/krakjoe/6437782 原文
>
> https://download.csdn.net/download/tianhuimin/6407035 为上一篇的翻译 但可惜没csdn币￥
>
> https://blog.csdn.net/u010433704/article/details/92795346  free 译文
>
> https://www.cnblogs.com/zhenbianshu/p/7978835.html 实例
>
> https://www.php.net/manual/zh/book.pthreads.php  PHP手册
>
> http://www.netkiller.cn/journal/php.thread.html 推荐

## 多进程 尝试

实话实说,开启一个新的shell进程,并在里面运行php脚本的方式, 个人比较推荐,就是要控制好参数,以免被直接构造出webshell。

大致思路是利用`shell_exec()`来实现异步执行,同时将输出导向[`/dev/null`](https://zh.wikipedia.org/wiki//dev/null),并在将当前作业(`docker.php`)放到当前的shell的后台执行。

大致代码

* `curl.php`

```php
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
```

* `docker.php`

```php
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

```

# 后计与想法

😓一开始,打算远程控制zstack来创建和销毁主机,虽然官网上有这一部分资料,但可行性的方案实现起来难度还是高。和老师商量之后,采用在zstack开启一个docker云主机,并用remote api来实现容器的创建和销毁,目前来看实现性要高很多,但安全性也是个燃眉之急[可见 [Docker 2375 端口入侵服务器](http://www.dockerinfo.net/1416.html) ]，这还不算脚本中的问题(暂时在docker的方法中有一个`check`函数,以备后续研究)。此外,有空可以着手学习`swoole`。

Orz,丢人的我。









