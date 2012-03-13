<?php

class HTTPRequest {
  protected $match = null;
  protected $uri = null;
  protected $body = null;
  protected $path = null;
  protected $method = null;
  protected $user = null;

  public function __construct(){
    $this->user = isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : null;
    $this->path = constant('REQUEST_URI');
    $this->method = constant('REQUEST_METHOD');
    if(class_exists('HTTPResponse'))
      $this->response = new HTTPResponse($this);
  }

  public function render($method, $match, $uri, $auto = false){
    $this->match = $match;
    $this->uri = $uri;
    $c = array($this, $method);
    if($auto && is_callable($c)){
      $this->body = request_body();
      $r = call_user_func($c, $match, $uri);
      if(is_string($r) || (class_exists('HTTPResponse') && $r instanceOf HTTPResponse)) print $r;
      return $r;
    }
  }

  public function head($response){
    // handle HTTP HEAD queries
  }

  public function get($response){
    // handle HTTP GET
  }

  public function put($response){
    // handle HTTP PUT
  }

  public function post($response){
    // handle HTTP POST
  }

  public function delete($response){
    // handle HTTP DELETE
  }

  public function options($response){
    // handle HTTP OPTIONS
  }


  public static function param($name, $validate, $regex = false, $arr = null){
    $arr = ($arr === null) ? $_REQUEST : $arr;
    if(!array_key_exists($name, $arr)) return null;
    if($validate == '*' || (!$regex && $arr[$name] == $validate)) return $arr[$name];
    if($regex){
      $c = preg_match($validate, $arr[$name], $m);
      return $m;
    }
    return null;
  }


}


?>
