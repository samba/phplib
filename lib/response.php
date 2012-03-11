<?php

if(defined('APP_TIMEZONE'))
  date_default_timezone_set(constant('APP_TIMEZONE'));

class HTTPResponse {
  private $status = 200;
  private $message = 'OK'; 
  private $headers = array();
  private $template = null;
  private $is_file = false;
  private $content_type = null;

  public function __construct(){

  }

  public function __toString(){
    $q = $this->render();
    if(is_string($q)) return $q;
    return '';
  }

  private function send_headers(){
   $stat = sprintf("%s %u %s", $_SERVER['SERVER_PROTOCOL'], $this->status, $this->message);
    header($stat, true, $this->status);
    header('Content-Type: ' . $this->content_type);

    foreach($this->headers as $h => $v){
      header(sprintf('%s: %s', $h, $v));
    }
  }

  public function redirect($destination, $type = 302, $message = 'Found'){
    $this->headers['Location'] = $destination;
    $this->setStatus($type, $message);
  }

  public function render($context = null, $content_type = null){
    if($content_type) $this->content_type = $content_type;
    $this->send_headers(); 
    if(empty($tpl) && !empty($this->template)){
      if($this->is_file) return require($this->template);
      else return eval($this->template);
    }
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
    $this->message = $message;
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
