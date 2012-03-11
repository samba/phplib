<?php

# Where is this library loading?
define('LIB_DIR', dirname(__FILE__));

# Require that the app has configured the basics properly
defined('PROJECT_DIR') || define('PROJECT_DIR', '../');

# Should we load HTTP-method handlers automatically?
defined('AUTO_METHOD') || define('AUTO_METHOD', false);

# Environment defaults for URL parsers
defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
defined('REQUEST_URI') || define('REQUEST_URI', $_SERVER['REQUEST_URI']);
defined('QUERY_STRING') || define('QUERYS_STRING', $_SERVER['QUERY_STRING']);
defined('REQUEST_PATH') || define('REQUEST_PATH', str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));



###########################################################
# Real action begins here.

require_once(constant('LIB_DIR') . '/util.php');

# Automation classes for HTTP methods
if(constant('AUTO_METHOD')){
  ob_start();
  require_once(constant('LIB_DIR') . '/request.php');
  require_once(constant('LIB_DIR') . '/response.php');
}

# URL mapping automation
require_once(constant('LIB_DIR') . '/urlmap.php');



?>
