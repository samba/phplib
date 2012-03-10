<?php

# Should we load HTTP-method handlers automatically?
defined('AUTO_METHOD') || define('AUTO_METHOD', false);

class URLMapping {
  private $map = null;
  private $url = null;
  private $method = null;

  public function __construct($url_config, $url_current, $request_method){
    $this->map = $url_config;
    $this->url = $url_current;
    $this->method = $request_method;
  }

  public function go($path = ''){
    return self::each($this->map, $this->url, array($this, 'import'), $this->method, $path);
  }

  private function get_handler(& $url, & $expression, & $handler){
    $exp = sprintf('#%s#', $expression);
    if(preg_match($exp, $url, $m)){
      return array($m, preg_replace($exp, $handler, $url));
    } else return array(null, null);
  }
  
  private static function & import($exp, $target, $url, $method, $path){
    list($match, $file) = self::get_handler($url, $exp, $target);
    if($match && $file){
      print_r($match, $file);
      $r = require_once(($path ? $path . '/' : '') . $file);
      $c = array($r, $method);
      if(is_object($r) && is_callable($c) && constant('AUTO_METHOD')){
        return call_user_func($c, $method, $url);
      } else return $r;
    } else return $match;
  }

  private static function each($map, $url, $callback, $method, $path){
    return array_map(
      $callback,
      array_keys($map),
      array_values($map),
      array_fill(0, count($map), $url),
      array_fill(0, count($map), $method),
      array_fill(0, count($map), $path)
    );
  }

  public static function init($url_config, $method, $request_uri, $query_string = '', $path = ''){
    $current = str_replace($query_string, '', $request_uri);
    $u = new URLMapping($url_config, $current, $method);
    return $u->go($path); 
  }
}


?>
