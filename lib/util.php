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


function startswith($string, $prefix){
  return strpos($string, $prefix) === 0;
}

function endswith($string, $prefix){
  return strpos($string, $prefix) === strlen($string) - strlen($prefix);
}

function _template_eval($values, $key, $regex = null){
  if(empty($regex) || preg_match($regex, $values[$key])) return $values[$key];
  return null;
}

# replace {{ ... }} values in the string, filtering them by regular expressions (in #...# suffix)
function template($string, $values){
  return preg_replace('/{{\s*([a-z0-9_\-]+)(#(?:.*)#)?\s*}}/ie', '_template_eval($values, "$1", "$2")', $string);
}

?>
