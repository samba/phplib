<?php

# Setup defaults and load configured libraries

# Where is this library loading?
define('LIB_DIR', dirname(__FILE__));

# Require that the app has configured the basics properly
defined('PROJECT_DIR') || define('PROJECT_DIR', '../');

# Should we load HTTP-method handlers automatically?
defined('AUTO_BUFFER_OUTPUT') || define('AUTO_BUFFER_OUTPUT', true);
defined('LOAD_REQUEST_HANDLERS') || define('LOAD_REQUEST_HANDLERS', true);
defined('LOAD_RESPONSE_HANDLERS') || define('LOAD_RESPONSE_HANDLERS', true);

# Environment defaults for URL parsers
defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
defined('REQUEST_URI') || define('REQUEST_URI', $_SERVER['REQUEST_URI']);
defined('QUERY_STRING') || define('QUERYS_STRING', $_SERVER['QUERY_STRING']);
defined('REQUEST_PATH') || define('REQUEST_PATH', str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));



###########################################################
# Real action begins here.

require_once(constant('LIB_DIR') . '/util.php');

# Buffer output from the start?
if(constant('AUTO_BUFFER_OUTPUT')) ob_start();

# Load request handler automation class (map HTTP method to class method)
if(constant('LOAD_REQUEST_HANDLERS'))
  require_once(constant('LIB_DIR') . '/request.php');

# Load response handler automation
if(constant('LOAD_RESPONSE_HANDLERS'))
  require_once(constant('LIB_DIR') . '/response.php');

# Load data model abstractions
if(constant('LOAD_MODELS'))
  require_once(constant('LIB_DIR') . '/models.php');

# URL mapping automation
require_once(constant('LIB_DIR') . '/urlmap.php');



?>
