<?php

defined('URL_REGEXP_FLAGS') || define('URL_REGEXP_FLAGS', 'i');
defined('URL_INCLUDE_PREFIX') || define('URL_INCLUDE_PREFIX', '');

# Mostly just a shell to hold context
class _URLMapping {

  public static $debug = false;
  public static $loaded = false;

  private static function get_handler(& $url, & $expression, & $handler){
    $exp = sprintf('#%s#%s', $expression, constant('URL_REGEXP_FLAGS'));
    if(preg_match($exp, $url, $m)){
      if(self::$debug) print_r(array($exp, $url, $handler));
      return array($m, preg_replace($exp, $handler, $url));
    } else return array(null, null);
  }

  public static function import(& $uri, & $expression, & $handler, & $method, $prefix){
    list($match, $file) = self::get_handler($uri, $expression, $handler);
    if($match && $file){
      self::$loaded = true;
      if(self::$debug) print_r(array($match, $file));
      $r = require_once($prefix . $file);
      if($r instanceOf HTTPRequest)
        return $r->render($method, $match, $uri, constant('AUTO_METHOD'));
      return $r;
    } else return null;
  }

}


function URL($regex, $handler, $uri = null){
  $uri = (empty($uri) ? constant('REQUEST_PATH') : $uri);
  if(_URLMapping::$loaded) # Only load once.
    return null;
  else return _URLMapping::import(
    preg_replace(sprintf('#^%s#%s', constant('URL_PREFIX'), constant('URL_REGEXP_FLAGS')), '/', $uri), 
    $regex, 
    $handler,
    strtolower(constant('REQUEST_METHOD')),
    constant('URL_INCLUDE_PREFIX')
  );
} 

?>
