<?php


/* Iterates over matches in a string */
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


/* Request processing base handler */
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
		$this->path = constant('REQUEST_PATH');
		$this->method = constant('REQUEST_METHOD');
		$this->content_type = new AcceptHeader('Accept');
		$this->language = new AcceptHeader('Accept-Language');
		$this->encoding = new AcceptHeader('Accept-Encoding');
		$this->charset = new AcceptHeader('Accept-Charset');
		$this->response = new HTTPResponse($this->content_type->preferred());
	}

	public function __destruct(){
		if(($resp = ($this->response)) instanceOf HTTPResponse){
			print $resp->render();
		}
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

	public static function render_headers($status, $message, $headers = array()){
		$proto = $_SERVER['SERVER_PROTOCOL'];
		header(sprintf('%s %u %s', $proto, (int) $status, $message), true, (int) $status);
		foreach($headers as $h => $v){
			header(sprintf('%s: %s', $h, $v));
		}
	}

	public static function UnsupportedMethod(){
		self::render_headers(405, "Method Not Allowed (this handler doesn't support it)");
	}

}

/* Response processing base handler */
class HTTPResponse {
	protected $headers = array();
	protected $status = 200;
	protected $message = 'OK';

	public $response_body = null;

	public function __construct($content_type = 'text/html', $request = null){
		$this->response_body = new HTTPResponseBody($content_type);
	}

	public function	__toString() {
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
	public function set_cache($seconds, $private = false, $reval = null){
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


/* Response body processing */
class HTTPResponseBody {
	
	public $body_content = null;
	public $content_type = 'text/html';

	public function __construct($content_type = 'text/html'){
		if(is_string($content_type)) $this->content_type = $content_type;
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

	

	public static function is_raw_format($content_type){
		return (bool) preg_match('#^(text/[.*]|application/(json|x(ht)?ml(\+xml)?))$#', $content_type);
	}

	/* Massages output for valid HTML */
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

?>