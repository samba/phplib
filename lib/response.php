<?php

if(defined('APP_TIMEZONE'))
  date_default_timezone_set(constant('APP_TIMEZONE'));

class HTTPResponse {
  
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
  const METHOD_NOT_ALLOWED = 405;
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
  const IM_A_TEAPOT = 418;
  const ENHANCE_YOUR_CALM = 420;
  const TOO_MANY_REQUESTS = 429;
  const NO_RESPONSE = 444;
  const INTERNAL_ERROR = 500;
  const NOT_IMPLEMENTED = 501;
  const BAD_GATEWAY = 502;
  const SERVICE_UNAVAILBLE = 503;
  const GATEWAY_TIMEOUT = 504;

  private $request = null;
  private $status = 200;
  private $message = 'OK'; 
  private $headers = array();
  private $template = null;
  private $is_file = false;
  private $content_type = 'text/html';
  private $context = array();
  private $use_template = true;

  public function __construct($req = null){
    $this->request = null;
  }

  public function __toString(){
    $q = $this->render();
    return (is_string($q)) ? $q : '';
  }

  public function redirect($destination, $type = 302, $message = 'Found'){
    $this->headers['Location'] = $destination;
    $this->setStatus($type, $message);
    $this->use_template = false; # No need to render if we're redirecting early.
  }

  public function render($content_type = null){
    if($content_type) $this->content_type = $content_type;
    $this->headers['Content-Type'] = $this->content_type;
    self::render_headers($this->status, $this->message, $this->headers);

    if(!empty($this->template) && $this->use_template){
      if(is_array($this->context)) extract($this->context); # bring context vars into scope 
      if($this->is_file) return require($this->template); # import a file
      else return eval($this->template); # process a PHP string
    }
  }
  
  public function setContext($name, $value = null){
    $this->context[$name] = $value;
  }

  public function setTemplate($string, $from_file = true){
    $this->template = $string;
    $this->is_file = (bool) $from_file;
  }

  public function setContentType($type){
    $this->content_type = $type;
  }

  public function setStatus($status, $message = null){
    $this->status = $status;
    $this->message = (is_null($message) ? self::get_message($status) : $message);
  }

  public function setCache($seconds, $private = false, $reval = null){
    $reval = ($reval === null) ? (!$private && $seconds) : $reval;
    $public = $seconds && !$private;
    $nocache = !$seconds; 
    $nostore = $private;

    $this->headers['Expires'] = self::expirestime($seconds); 
    
    $this->headers['Cache-Control'] = implode(', ', array_filter(array(
      (($private && !$seconds) ? 'no-store' : ''),
      (!$seconds ? 'no-cache' : ''),
      ($private ? 'private' : ''),
      (!$private ? 'public' : ''),
      ($reval ? ($private ? 'proxy-revalidate' : 'must-revalidate') : ''),
      ($private ? '' : sprintf('max-age=%u', $seconds))
    )));
  }

  public static function expirestime($seconds){
    if($seconds) $seconds += time();
    return strftime('%a, %d %b %Y %H:%M:%S %z', (int) $seconds);
  }

  public static function render_headers($status, $message, $headers){
    $proto = $_SERVER['SERVER_PROTOCOL'];
    header(sprintf('%s %u %s', $proto, $status, $message), true, $status);
    foreach($headers as $h => $v){
      header(sprintf('%s: %s', $h, $v));
    }
  }

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
    case (self::METHOD_NOT_ALLOWED): return 'Method Not Allowed';
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
    case (self::IM_A_TEAPOT): return 'I\'m a Teapot';
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

# From PHP docs. Not quite the way I want it.
function get_include_contents($filename) {
  if (is_file($filename)) {
    ob_start();
    include $filename;
    return ob_get_clean();
  }
  return false;
}


?>
