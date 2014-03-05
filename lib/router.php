<?php

# Depends on `lib/template.php`

# Whether to use our templating engine on the output buffer automatically
defined('USE_LIGHT_TEMPLATES') || define('USE_LIGHT_TEMPLATES', true);


# Prefix of the URL to replace (i.e. a project directory, to which all evaluation is relative)
defined('URL_PREFIX') || define('URL_PREFIX', null);

# Make URL matching case-insensitive
defined('URL_MATCH_FLAGS') || define('URL_MATCH_FLAGS', 'i');

# Environment defaults for URL parsers
defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
defined('REQUEST_URI') || define('REQUEST_URI', $_SERVER['REQUEST_URI']);
defined('QUERY_STRING') || define('QUERY_STRING', $_SERVER['QUERY_STRING']);

function __default_requestpath(){
	return str_replace(($_SERVER['QUERY_STRING'] ? '?' : '') . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
}

defined('REQUEST_PATH') || define('REQUEST_PATH', __default_requestpath());


# Start buffering
LIGHTRouter::start(constant('USE_LIGHT_TEMPLATES'));

# Statics used as singleton case.
class LIGHTRouter {

	public static $has_handled = false;

	# Standard context for template processing
	public static $base_template_context = array();

	public static function render_headers($status, $message, $headers = array()){
		$proto = $_SERVER['SERVER_PROTOCOL'];
		header(sprintf('%s %u %s', $proto, (int) $status, $message), true, (int) $status);
		foreach($headers as $h => $v){
			header(sprintf('%s: %s', $h, $v));
		}
	}

	public static function flush(){
		while(@ob_end_flush()); # Dump the buffer & run any filters (e.g. our templates)
	}

	public static function start($use_template = true){
		# Start buffering, and possibly add our templating engine to the output queue
		ob_start($use_template ? array('LIGHTRouter', 'template') : null);
	}

	public static function template($body_content){
		# Filter using our simplified template syntax, support all known variables (globals)
		return template_render($body_content, array_merge(self::$base_template_context, get_defined_vars()));
	}

	public static function map($pattern, $output, $current_url){
		# Drop the (environment) prefix before evaluating application URLs
		if(($prefix = constant('URL_PREFIX')) && ($l = strlen($prefix)) && startswith($current_url, $prefix)){
			$current_url = substr($current_url, $l);
		}
		# Evaluate the URL against this pattern & translate any regular expression maps
		if(1 === ($c = preg_match(sprintf('#^%s#%s', $pattern, constant('URL_MATCH_FLAGS')), $current_url, $match))){
			$result = preg_replace('#\$(\d)#e', '\$match["$1"]', $output);
			return array($c, $result, $match); # Yield the translated result
		} else {
			return array(0, false, false); # Yield a mismatch
		}
	}

	public static function convey($handler, $method, $match){
		$method = strtolower($method);
		if(is_callable($handler)){
			# Support direct callback
			array_shift($match);
			array_unshift($match, $method);
			call_user_func_array($handler, $match);

		} elseif(is_object($handler) && is_callable($callback = array(&$handler, $method))){
			# Support class instances that have matching method names
			array_shift($match);
			call_user_func_array($callback, $match); # this is expected to dump to the output buffer

		} elseif(is_string($handler) && class_exists($handler)){
			# Support classes (non-instantiated)
			return self::convey(new $handler(), $method, $match);
		}
	}

	public static $type_extensions = array(
		'png' => 'image/png',
		'jpg' => 'image/jpg',
		'gif' => 'image/gif',
		'css' => 'text/css',
		'json' => 'application/json',
		'js' => 'text/javascript'
	);

	public static function guess_type($filename){
		# $finfo = new finfo(FILEINFO_MIME | FILEINFO_SYMLINK);
		# return $finfo->file($filename);
		$ext = substr($filename, strrpos($filename, '.') + 1, strlen($filename));
		return array_key_exists($ext, self::$type_extensions) 
			? self::$type_extensions[ $ext ]
			: 'application/octet-stream';
	}

}

# Provide a couple sensible defaults in the automatic templates
LIGHTRouter::$base_template_context['request_time'] = time();
LIGHTRouter::$base_template_context['hostname'] = $_SERVER['HTTP_HOST'];
LIGHTRouter::$base_template_context['basepath'] = constant('URL_PREFIX');

# Declare a route, i.e. map a URL to a request handler (either static or dynamic)
function route($pattern, $handler, $as_static = false, $type_override = null){
	if(!LIGHTRouter::$has_handled){
		list($c, $result, $match) = LIGHTRouter::map($pattern, $handler, constant('REQUEST_PATH')); 
		if($c && $match && $result){

			if($as_static && is_file($result)){
				header('Content-Type: ' . (is_string($type_override) ? $type_override : LIGHTRouter::guess_type($result)));
				if(false !== readfile($result)){
					LIGHTRouter::$has_handled = true;
					return true;
				}
			} elseif(!$as_static && is_file($result)){
				try {
					$result = require_once($result);
					LIGHTRouter::convey($result, constant('REQUEST_METHOD'), $match);
					LIGHTRouter::$has_handled = true;
					return true;
				} catch (Exception $e) {
					LIGHTRouter::render_headers(500, 'Server Error', array('Content-Type' => 'text/plain'));
					LIGHTRouter::$has_handled = true;
					return true;
				}
			}

		}
	}
	return false;
}

# Declare a redirect mapping
function redirect($pattern, $destination, $status = 302){
	if(!LIGHTRouter::$has_handled){
		list($c, $result, $match) = LIGHTRouter::map($pattern, $destination, constant('REQUEST_PATH')); 
		if($c){
			LIGHTRouter::render_headers($status, 'Redirect', array('Location' => $result));
			LIGHTRouter::$has_handled = true;
			return true;
		}
	}
	return false;
}

/** Provide a clear failure message if the request hasn't already been handled when this directive runs.
 * @param {integer} response status
 * @param {string} response message 
 */
function fail($status = 404, $message = 'Not Found', $callback = null){
	if(!LIGHTRouter::$has_handled){
		LIGHTRouter::render_headers($status, $message);
		if(is_callable($callback)){
			call_user_func($callback, $status, $message);
		} elseif(is_string($callback) && is_file($callback)){
			readfile($callback);
		} else {
			print $message;
		}
		LIGHTRouter::flush();
		LIGHTRouter::$has_handled = true;
		return true;
	}
	return false;
}




?>