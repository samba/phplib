<?php

/* This module provides the bulk of the RESTful API handling.
 *  Major features:
 *    - Mapping URL patterns (regular expressions) to routed paths
 *      > e.g. URL('^/my(path)/', 'route/target.php');
 *    - Preparing HTTP Requests for processing by routed paths
 *      > Parsing ACCEPT headers 
 *    - Mapping HTTP Requests to matching methods 
 *      > Routed paths will have: 
 *          class Handler extends HTTPRequest {
 *            // methods map to HTTP verbs
 *            // arguments map captured groups of the regular expression
 *            protected function get($match_0, $match_1, ...){ ... }
 *            protected function post($match_0, $match_1, ...){ ... }
 *          }
 *    - Processing HTTP Responses
 *      > Simple cache configuration
 *      > Simple Template processing
 *        * Process '{{name}}' format directly from data context
 *        * Process PHP logic in template files
 *      > Automatic buffer integration
 */

# Prefix of the URL to replace (i.e. a project directory, to which all evaluation is relative)
defined('URL_PREFIX') || define('URL_PREFIX', null);
defined('URL_MATCH_FLAGS') || define('URL_MATCH_FLAGS', 'i');

# Environment defaults for URL parsers
defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
defined('REQUEST_URI') || define('REQUEST_URI', $_SERVER['REQUEST_URI']);
defined('QUERY_STRING') || define('QUERYS_STRING', $_SERVER['QUERY_STRING']);
defined('REQUEST_PATH') || define('REQUEST_PATH', str_replace(($_SERVER['QUERY_STRING'] ? '?' : '') . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));

# Resulting request path for URL handler evaluation
define('EVAL_REQUEST_PATH', URLMap::this_url(constant('REQUEST_PATH')));

if(defined('APP_TIMEZONE'))
  date_default_timezone_set(constant('APP_TIMEZONE'));


HTTPResponseBody::add_filter(); # Begin buffering

function fail($status = 404, $message = null){
  HTTPResponse::render_headers($status, $message, array('Content-Type' => 'text/plain'));  
  print $message;
}

class URLMap {
  public static $loaded = false;
  
  public static function this_url($url){
    return constant('URL_PREFIX')
      ? preg_replace(sprintf('#^%s#%s', constant('URL_PREFIX'), constant('URL_MATCH_FLAGS')), '', $url)
      : $url;
  }

  public static function match($pattern, $include_file){
    $pattern = sprintf('#%s#%s', $pattern, constant('URL_MATCH_FLAGS'));
    if(is_file($include_file)){
      $c = preg_match($pattern, constant('EVAL_REQUEST_PATH'), $match);
      return array($c, $match, $include_file);
    } else {
      return array(0, false, false);  
    }
  }

  public static function import($match, $handler, $static_mode = false, $type = null){
    if($match && $handler){
      try {
        if($static_mode && is_string($type)){
          header('Content-Type: ' . $type);
        }
        $result = $static_mode 
          ? (readfile($handler)) 
          : require_once($handler);
        if($result instanceOf HTTPRequest){
          $result->render(constant('EVAL_REQUEST_PATH'), $match, constant('REQUEST_METHOD'), $type);
        } 
      } catch (Exception $e) {
        fail(500, $e->getMessage());
        return false;
      } 
      return true;
    } else {
      return false;
    }
  }

}

function request_handled(){
  return URLMap::$loaded;
}

function URL($pattern, $include_file, $static_mode = false, $type_override = null){
  if(request_handled()) return false;
  list($count, $result, $file) = URLMap::match($pattern, $include_file);
  if($count){
    URLMap::$loaded = (bool) URLMap::import($result, $file, $static_mode, $type_override);
  }
}


class RegExpIterator implements Iterator {
  private $pattern = null;
  private $results = null;
  private $counter = -1;
 
  public function __construct($regexp, $input = null){
    $this->pattern = $regexp;
    $this->setInput($input);  
  }
  
  public function setInput($input, $flags = PREG_SET_ORDER, $offset = 0){
    if(is_string($input)){
      $c = preg_match_all($this->pattern, $input, $m, $flags, $offset);
      if($c && $m){
        $this->results = $m;
      }
    }
  }

  public function current(){
    return $this->results[$this->counter];
  } 

  public function key(){
    $this->counter;
  }

  public function next(){
    ++ $this->counter; 
  }

  public function rewind(){
    $this->counter = 0;  
  }
  
  public function valid(){
    return (is_array($this->results) && $this->counter >=0 && $this->counter < count($this->results));
  }

}

/* Parser for HTTP Accept and related headers */
class AcceptHeader {
  public $type = null;
  private $value = null;
  
  public function __construct($type){
    $this->type = $type;
    $this->value = self::parser($type);   
  }
  
  public static function parser($header_name){
    $header_key = 'HTTP_' . str_replace('-', '_', strtoupper($header_name));
    if(array_key_exists($header_key, $_SERVER)){
      $parser = new RegExpIterator("#([^,;=]+)(?:;([^,])(?:=([^;,]+))?)*#i", $_SERVER[$header_key]);  
    } else {
      $parser = null;
    }
    return $parser;
  }

  public function __toString(){
    return $this->preferred();
  }

  public function accepts($value){
    foreach($this->value as $match){
      if($match[1] == $value) return true;
    }
    return false;
  }

  public static function preference_sort($a, $b){ # descending
    return $a[1] == $b[1] ? 0 : ($a[1] < $b[1] ? 1 : -1);
  }

  public function preference(){ # yield the values in order of their given weights
    $weighted = array();
    foreach($this->value as $match){
      $weight = 1;
      for($i = 1; $i < count($match); $i++){
        if($match[$i] == 'q'){
          $weight = (float) $match[$i +1];
          break;
        }
      }
      array_push($weighted, array($match[1], $weight * 100));
    }
    usort($weighted, array($this, 'preference_sort'));
    return $weighted;
  }

  public function preferred(){ # yield the strongest preference
    $pref = $this->preference();
    return $pref[0][0];
  }

  public function prefers($values){ # yield the first matching preference
    if(is_array($values)){
      foreach($this->preference() as $p){
        if(in_array($p[0], $values)) return $p[0];
        if($p[0] == '*/*' || $p[0] == '*') return $values[0];
      }
      return null;
    } else {
      return $this->preferred() == $values;
    }
  }

}



abstract class HTTPRequest {
  protected $match = null;
  protected $uri = null;
  protected $path = null;
  protected $query = null;
  protected $method = null;
  protected $user = null;
  public $response = null;

  public function __construct(){
    $this->user = isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : null;
    $this->path = constant('EVAL_REQUEST_PATH');
    $this->method = constant('REQUEST_METHOD');
    $this->content_type = new AcceptHeader('Accept');
    $this->language = new AcceptHeader('Accept-Language');
    $this->encoding = new AcceptHeader('Accept-Encoding');
    $this->charset = new AcceptHeader('Accept-Charset');
    $this->response = new HTTPResponse($this->content_type->preferred());
  }

  /* @return {boolean} whether the client will accept a common data format
   */
  public function accepts_data_format(){
    return count($this->content_type->prefers(array(
      'application/json', 'application/xml', 'text/xml'
    )));
  }


  /* Serve a request by the matching method (i.e. HTTP GET -> $this->get(...) */ 
  public function render($path, $match, $method, $type = null){
    $callback = array($this, strtolower($method));
    if(is_callable($callback)){
      $result = call_user_func_array($callback, $match);
      if(is_string($result) || $result instanceOf HTTPResponse){
        print ($result);
      } elseif ($result === true) {
        print ($this->response);
      }
    } else {
      self::UnsupportedMethod();
    }
  }

  /* Indicate whether the client requested a non-cached version */
  public static function request_nocache(){
    return (isset($_SERVER['HTTP_PRAGMA']) && strpos($_SERVER['HTTP_PRAGMA'], 'no-cache') !== false);
  }

  # retrieve the request body, optionally decoding it (e.g. json)
  # TODO: automatically decode when content-type indicates a standard method
  public static function & body ($decode = null, $args = array()) {
    $b = @file_get_contents('php://input');
    if(is_callable($decode)){
      array_unshift($args, $b);
      $b = call_user_func_array($decode, $args);
    }
    return $b;
  }

  /* Extract a query parameter after validation */
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

  public static function UnsupportedMethod(){
    fail(405, "Method Not Allowed (this handler doesn't support it)");
  }

}


class HTTPResponse {
  protected $headers = array();
  protected $status = 200;
  protected $message = 'OK';

  public $response_body = null;



  public function __construct($content_type = 'text/html', $request = null){
    $this->response_body = new HTTPResponseBody($content_type);
  }

  public function  __toString() {
    return (string) $this->response_body;
  }

  /* Send headers and render the response body */
  public function render($content_type = null, $callback = null, $pass_headers = true){
    if($pass_headers) self::render_headers($this->status, $this->message, $this->headers);
    if(!is_null($this->response_body)){
      if(is_callable($callback)){
        $body = call_user_func($callback, $this->response_body);
      } else $body = $this->response_body->render($content_type, $pass_headers);
    } 
    return (string) $body;
  }

  /* Shortcut for standards-compliant redirection */
  public function redirect($destination, $type = 302, $message = null){
    $this->headers['Location'] = $destination;
    $this->status = (int) $type;
    if(!is_null($message)){
      $this->message = (string) $message;
    }
  }

  /* Cache header configuration shortcut */
  public function setCache($seconds, $private = false, $reval = null){
    $reval = ($reval === null) ? (!$private && $seconds) : $reval;
    if(HTTPRequest::request_nocache()) return false; # Don't set caching headers if the client said not to.
    $this->headers['Expires'] = self::expirestime($seconds); 
    $this->headers['Cache-Control'] = implode(', ', array_filter(array(
      (($private && !$seconds) ? 'no-store' : ''),
      (!$seconds ? 'no-cache' : ''),
      ($private ? 'private' : ''),
      (!$private ? 'public' : ''),
      ($reval ? ($private ? 'proxy-revalidate' : 'must-revalidate') : ''),
      ($private ? '' : sprintf('max-age=%u', $seconds))
    )));
    return true;
  }

  /* Render a timestamp compatible with the HTTP Expires header (RFC 1123)
    See also: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.21
  */
  public static function expirestime($seconds){
    if($seconds) $seconds += time();
    return strftime('%a, %d %b %Y %H:%M:%S %z', (int) $seconds);
  }

  public static function render_headers($status, $message, $headers){
    $proto = $_SERVER['SERVER_PROTOCOL'];
    header(sprintf('%s %u %s', $proto, (int) $status, $message), true, (int) $status);
    foreach($headers as $h => $v){
      header(sprintf('%s: %s', $h, $v));
    }
  }

  public function write($string){
    $this->response_body->append($string);
  }

  public function set($value){ # passthrough for body content
    return $this->response_body->set($value);
  }

  public function define($name, $value){ # passthrough for template context
    return $this->response_body->define($name, $value, false);
  }

  # Load response-body from a template (file or string)
  public function template($string, $from_file = false){
    $context = & $this->response_body->template_context;
    return $this->response_body->set(
      $this->response_body->template($string, $from_file, $context)
    );
  }


  // HTTP response codes for automatic message selection
  const OK = 200;
  const CREATED = 201;
  const ACCEPTED = 202;
  const INFO = 203;
  const NO_CONTENT = 204;
  const RESET_CONTENT = 205;
  const PARTIAL_CONTENT = 206;
  const MOVED = 301;
  const FOUND = 302;
  const SEE_OTHER = 303;
  const NOT_MODIFIED = 304;
  const BAD_REQUEST = 400;
  const UNAUTHORIZED = 401;
  const PAYMENT_REQUIRED = 402;
  const FORBIDDEN = 403;
  const NOT_FOUND = 404;
  const NOT_ALLOWED = 405;
  const NOT_ACCEPTABLE = 406;
  const PROXY_AUTH_REQUIRED = 407;
  const REQUEST_TIMEOUT = 408;
  const CONFLICT = 409;
  const GONE = 410;
  const LENGTH_REQUIRED = 411;
  const PRECONDITION_FAILED = 412;
  const REQUEST_ENTITY_TOO_LARGE = 413;
  const REQUEST_URI_TOO_LONG = 414;
  const UNSUPPORTED_MEDIA = 415;
  const REQUEST_RANGE_INVALID = 416;
  const EXPECTATION_FAILED = 417;
  const TEAPOT = 418;
  const ENHANCE_YOUR_CALM = 420;
  const TOO_MANY_REQUESTS = 429;
  const NO_RESPONSE = 444;
  const INTERNAL_ERROR = 500;
  const NOT_IMPLEMENTED = 501;
  const BAD_GATEWAY = 502;
  const SERVICE_UNAVAILBLE = 503;
  const GATEWAY_TIMEOUT = 504;

  // Select a response message based on its status code
  public static function get_message($code){
    switch($code){
    case (self::OK): return 'OK';
    case (self::CREATED): return 'Created';
    case (self::ACCEPTED): return 'Accepted';
    case (self::INFO): return 'Non-Authoritative Information';
    case (self::NO_CONTENT): return 'No Content';
    case (self::RESET_CONTENT): return 'Reset Content';
    case (self::PARTIAL_CONTENT): return 'Partial Content';
    case (self::MOVED): return 'Moved Permanently';
    case (self::FOUND): return 'Found';
    case (self::SEE_OTHER): return 'See Other';
    case (self::NOT_MODIFIED): return 'Not Modified';
    case (self::BAD_REQUEST): return 'Bad Request';
    case (self::UNAUTHORIZED): return 'Unauthorized';
    case (self::PAYMENT_REQUIRED): return 'Payment Required';
    case (self::FORBIDDEN): return 'Forbidden';
    case (self::NOT_FOUND): return 'Not Found';
    case (self::NOT_ALLOWED): return 'Method Not Allowed';
    case (self::NOT_ACCEPTABLE): return 'Not Acceptable';
    case (self::PROXY_AUTH_REQUIRED): return 'Proxy Authentication Required';
    case (self::REQUEST_TIMEOUT): return 'Request Timeout';
    case (self::CONFLICT): return 'Conflict';
    case (self::GONE): return 'Gone';
    case (self::LENGTH_REQUIRED): return 'Length Required';
    case (self::PRECONDITION_FAILED): return 'Precondition Failed';
    case (self::REQUEST_ENTITY_TOO_LARGE): return 'Request Entity Too Large';
    case (self::REQUEST_URI_TOO_LONG): return 'Request URI Too Long';
    case (self::UNSUPPORTED_MEDIA): return 'Unsupported Media Type';
    case (self::REQUEST_RANGE_INVALID): return 'Request Range Not Satisfiable';
    case (self::EXPECTATION_FAILED): return 'Expectation Failed';
    case (self::TEAPOT): return 'I\'m a Teapot';
    case (self::ENHANCE_YOUR_CALM): return 'Enhance your calm';
    case (self::TOO_MANY_REQUESTS): return 'Too Many Requests';
    case (self::NO_RESPONSE): return 'No Response';
    case (self::INTERNAL_ERROR): return 'Internal Server Error';
    case (self::NOT_IMPLEMENTED): return 'Not Implemented';
    case (self::BAD_GATEWAY): return 'Bad Gateway';
    case (self::SERVICE_UNAVAILABLE): return 'Service Unavailable';
    case (self::GATEWAY_TIMEOUT): return 'Gateway Timeout';
    }
  }

}

function template($name, $value){
  return HTTPResponseBody::define_global($name, $value);
}


class HTTPResponseBody {
  
  public $template_context = array();
  public $body_content = null;
  public $content_type = 'text/html';

  private static $template_context_global = array();


  public function __construct($content_type = 'text/html'){
    if(is_string($content_type)) $this->content_type = $content_type;
  }

  public function __destruct(){
    self::flush(); 
  }

  public function set($value, $content_type = null){
    if(is_string($content_type)) $this->content_type = $content_type;
    return ($this->body_content = $value);
  }

  public function append($string){
    if(is_null($this->body_content))
      $this->body_content = '';
    if(is_string($this->body_content)){
      $this->body_content .= $string;
    }
  }

  public static function define_global($name, $value){
    self::$template_context_global[$name] = $value;
  }

  public function define($name, $value, $global = false){
    if($global) return self::define_global($name, $value);
    else return ($this->template_context[$name] = $value);
  }

  public static function template_filter($string, $values = null){
    if(!is_array($values)) 
      $values = & self::$template_context_global;
    return preg_replace('/\{\{\s*([_\w\.]+)\s*\}\}/e', "\$values[\"$1\"]", $string);
  }

  /* Process PHP using native syntax, plus replacement formats */
  public static function template($string, $from_file = false, $values = null){
    if($from_file){
      extract(self::$template_context_global);
      if(!is_null($values))
        extract($values);
      $result = require($string);
      return self::template_filter($result, $values);
    } else {
      return self::template_filter($string, array_merge(self::$template_context_global, $values));
    }
  } 
  
  public static function add_filter(){
    ob_start(array('HTTPResponseBody', 'template_filter'));
  }


  public static function flush(){
    while(@ob_end_flush());
  }

  public static function is_raw_format($content_type){
    return (bool) preg_match('#^(text/[.*]|application/(json|x(ht)?ml(\+xml)?))$#', $content_type);
  }

  public static function cleanup_html($content){
    @libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML( $content );
    return $doc->saveHTML();
  }


  public function render($content_type = null, $pass_header = true){
    $ctype = is_null($content_type) ? $this->content_type : $content_type;
    $ctype_override = array(
      'json' => 'application/json',
      'xml' => 'application/xml'
    );
    if(array_key_exists($ctype, $ctype_override)){
      $ctype = $ctype_override[$ctype];
    }

    # print_r(array(
    #   'body' => $this->body_content,
    #   'type' => $ctype
    # ));


    if($pass_header) header('Content-Type: ' . $ctype, true);
    if(self::is_raw_format($ctype) && is_string($this->body_content)){
      $content = $this->body_content;
      if(false === strpos($ctype, 'html'))
        $content = self::cleanup_html($content);
      return (string) $content;
    } else {
      switch($ctype){
      case 'application/json':
      case 'text/javascript':
        return json_encode($this->body_content);
      case 'application/xml':
      case 'application/xhtml+xml':
        return self::to_xml($this->body_content);
      default:
        return (string) $this->body_content; # and hope it converts
      }
    }
  }

  public function __toString(){
    return $this->render(null, false); 
  }

  public static function array_numeric(& $ar){
    return array_keys($ar) === range(0, count($ar) -1);
  }

  # Render object (or array) as XML
  public static function to_xml($obj, $n = null, $as_string = true){
    if(is_object($obj) && is_null($n)) $n = get_class($obj);
    if(is_null($n)) $n = 'root';
    
    # get Root Node
    $n = $n instanceof SimpleXMLElement 
      ? $n : new SimpleXMLElement(sprintf('<%s/>', $n));

    $values = (is_object($obj) ? get_object_vars($obj) : (is_array($obj) ? $obj : null));
    $numeric = self::array_numeric($values);

    foreach($values as $k => & $v){
      if(is_scalar($v) && !$numeric) $n->addAttribute($k, (string) $v);
      elseif(is_scalar($v)){
        $x = $n->addChild('value', $v);
        $x->addAttribute('type', gettype($v));
        $x->addAttribute('index', (string) $k);
      }
      else self::to_xml($v ? $v : (string) $v, $n->addChild($numeric ? $n->getName() : $k), false);
    } 

    return ($as_string) ? $n->asXML() : $n;
  }


}



/* vim: set nowrap tabstop=2 shiftwidth=2 softtabstop=0 expandtab textwidth=0 filetype=php foldmethod=syntax foldmarker={{{,}}} foldcolumn=4*/
?>
