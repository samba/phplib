<?php

# require_once(constant('LIB_DIR') . '/util.php');

class HTTPRequest {
  protected $match = null;
  protected $uri = null;

  public function __construct(){

  }

  public function render($method, $match, $uri, $auto = false){
    $this->match = $match;
    $this->uri = $uri;
    $c = array($this, $method);
    if($auto && is_callable($c)){
      $r = call_user_func($c, $match, $uri);
      if($r instanceOf HTTPResponse){
        print $r->render();
      }
      return $r;
    }
  }

  public function head(){
    // handle HTTP HEAD queries
  }

  public function get(){
    // handle HTTP GET
  }

  public function put(){
    // handle HTTP PUT
  }

  public function post(){
    // handle HTTP POST
  }

  public function delete(){
    // handle HTTP DELETE
  }

  public function options(){
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
