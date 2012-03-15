<?php

# Setup defaults and load configured libraries

# Where is this library loading?
define('LIBDIR', dirname(__FILE__));

# Require that the app has configured the basics properly
defined('PROJECT_DIR') || define('PROJECT_DIR', '../');

# Should we load HTTP-method handlers automatically?
defined('USE_MODELS') || define('USE_MODELS', true);
defined('USE_REQUEST_HANDLER') || define('USE_REQUEST_HANDLER', true);
defined('USE_RESPONSE_HANDLER') || define('USE_RESPONSE_HANDLER', true);
defined('AUTO_BUFFER_OUTPUT') || define('AUTO_BUFFER_OUTPUT', true);

# Environment defaults for URL parsers
defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
defined('REQUEST_URI') || define('REQUEST_URI', $_SERVER['REQUEST_URI']);
defined('QUERY_STRING') || define('QUERYS_STRING', $_SERVER['QUERY_STRING']);
defined('REQUEST_PATH') || define('REQUEST_PATH', str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));

# Database configuration
defined('DATABASE_HOST') || define('DATABASE_HOST', null);
defined('DATABASE_PORT') || define('DATABASE_PORT', null);
defined('DATABASE_USER') || define('DATABASE_USER', null);
defined('DATABASE_PASS') || define('DATABASE_PASS', null);
defined('DATABASE_NAME') || define('DATABASE_NAME', null);
defined('DATABASE_SOCK') || define('DATABASE_SOCK', null);

###########################################################
# Real action begins here.

function quiet_include($filename, $context = null){
  if(is_array($context)) extract($context);
  if(file_exists($filename)) require_once($filename);
}

require_once(constant('LIBDIR') . '/util.php');

# Buffer output from the start?
if(constant('AUTO_BUFFER_OUTPUT')) ob_start();

# Load request handler automation class (map HTTP method to class method)
if(constant('USE_REQUEST_HANDLER'))
  require_once(constant('LIBDIR') . '/request.php');

# Load response handler automation
if(constant('USE_RESPONSE_HANDLER'))
  require_once(constant('LIBDIR') . '/response.php');

# Load database and data model abstractions
function require_database($name, $models = true){
  # Database
  require_once(constant('LIBDIR') . '/database.php');
  quiet_include(sprintf('%s/database.%s.php', constant('LIBDIR'), $name));
  quiet_include(sprintf('%s/queries.%s.php', constant('PROJECT_DIR'), $name));

  $x = Database::init(
    constant('DATABASE_HOST'),
    constant('DATABASE_PORT'),
    constant('DATABASE_USER'),
    constant('DATABASE_PASS'),
    constant('DATABASE_NAME')
  );

  # Data Models
  if($models && constant('USE_MODELS')){
    require_once(constant('LIBDIR') . '/models.php');
    quiet_include(sprintf('%s/models.%s.php', constant('LIBDIR'), $name));
    quiet_include(sprintf('%s/models.php', constant('PROJECT_DIR')));
    Model::init($x);
  }

}


# URL mapping automation
require_once(constant('LIBDIR') . '/urlmap.php');



?>
