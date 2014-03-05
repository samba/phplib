<?php

# retrieve the request body, optionally decoding it (e.g. json)
function & request_body ($decode = null, $args = array()) {
  $b = @file_get_contents('php://input');
  if(is_callable($decode)){
    array_unshift($args, $b);
    $b = call_user_func_array($decode, $args);
  }
  return $b;
}


function report_timer($start_time, $name = null, $send_html = true){
	$diff = microtime() - $start_time;
	if($send_html && headers_sent()){
		printf("<!-- time elapsed (%s): %f -->", (empty($name) ? 'none' : $name), $diff);
	} else {
		header(sprintf('X-Time-Elapsed-%s: %f', (empty($name) ? 'none' : $name), $diff));
	}
}



function startswith($string, $prefix){
  return strpos($string, $prefix) === 0;
}

function endswith($string, $prefix){
  return strpos($string, $prefix) === strlen($string) - strlen($prefix);
}

?>
