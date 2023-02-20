<?php

class HttpClient{

    private $timeout;
    private $cookie;

    public function __construct(){
       $this->timeout = 10;
       $this->cookie  = false;
    }

    public function setTimeout($timeout){$this->timeout = $timeout;}

    public function setCookie($cookie){$this->cookie = $cookie;}

    public function GET($url, $data = [], $header = []){
        return $this-> sendHttp($url, "GET", $header, $data);
    }

    public function PUT($url, $data, $header = []){
        return $this-> sendHttp($url, "PUT", $header, $data);
    }
    
    public function POST($url, $data, $header = []){
        return $this-> sendHttp($url, "POST", $header, $data);
    }
    
    public function DELETE($url, $data, $header = []){
        return $this-> sendHttp($url, "DELETE", $header, $data);
    }
    
    private function sendHttp($url, $method, $header, $data){

        //标准化请求头格式
        $headers = array(); 
        foreach($header as $n => $v) {
            if (is_numeric($n))
                $headers[] = $v;
            else
                $headers[] = $n.':'.$v;
        }
        
        //处理请求数据
        if ($method == "GET"){
            if(!empty($data))
                $url = $url.(strstr($url,"?")?"&":"?").(is_array($data)?http_build_query($data):$data);
        }else{
            if(!empty($data))
                $data = is_array($data)?json_encode($data):$data;
            else
                $data = "{}";
        }

        //Curl请求命令
        $curlcmd = "curl -X $method $url \\\n";
        foreach($headers as $k=>$v)
        {
            $curlcmd .= "-H \"$v\" \\\n";
        }
        if($method != "GET")
            $curlcmd .= "-d "."'$data'\n";

        $http = curl_init ($url);                               //初始化一个CURL类
        curl_setopt_array($http, [
            CURLOPT_SSL_VERIFYPEER  => false,                   //是否验证证书由CA颁发
            CURLOPT_SSL_VERIFYHOST  => false,                   //是否验证域名与证书一致
            CURLOPT_ENCODING        => 'UTF-8',                 //解析压缩格式
            CURLOPT_HTTPHEADER      => $headers,                //构造请求头
            CURLOPT_CUSTOMREQUEST   => $method,                 //定义请求方法
            CURLOPT_HEADER          => 1,                       //取得http头
            CURLOPT_RETURNTRANSFER  => 1,                       //结果保存到变量
            CURLOPT_CONNECTTIMEOUT  => 5,                       //连接前5秒未响应超时
            CURLOPT_TIMEOUT         => 5,                       //连接在5秒后超时
        ]);

        if($this->cookie)
            curl_setopt_array($http, [
                CURLOPT_COOKIEJAR       => $this->cookie,       //保存Cookie
                CURLOPT_COOKIEFILE      => $this->cookie,       //读取Cookie
            ]);
        
        if ($method != "GET")
            curl_setopt($http, CURLOPT_POSTFIELDS, $data);      //发送的数据

        $Response = curl_exec ($http);                          //执行并取得返回值
        
        if (curl_errno($http)>0){
            $error = curl_error($http);
            curl_close ($http);                                 //关闭CURL连接资源
            return array('state'=> $error, 'curl'=>$curlcmd);
        }else{
            $hSize = curl_getinfo($http, CURLINFO_HEADER_SIZE); //取得响应头大小
            $headers = substr($Response, 0, $hSize);            //取出响应头
            $Body = substr($Response, $hSize);                  //取出响应内容
            curl_close ($http);                                 //关闭CURL连接资源
            return array('state'=>'success','header'=>$headers,
                'body'=>$Body, 'curl'=>$curlcmd) ;
        }
    }
}
