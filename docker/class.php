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