<?php

define('INIT_START', microtime());

# Where is this library loading?
define('LIB_DIR', dirname(__FILE__));

define('CACHE_DIR', dirname(__FILE__) . '/cache');

# Template support paths
defined('TEMPLATE') || define('TEMPLATE', 'undefined');
defined('TEMPLATE_DIR') || define('TEMPLATE_DIR', 'site/template/' . constant('TEMPLATE'));


###########################################################
# Prepare the working environment

if(defined('APP_TIMEZONE'))
  date_default_timezone_set(constant('APP_TIMEZONE'));


###########################################################
# Load supporting libraries

require_once(constant('LIB_DIR') . '/util.php'); # Small utilities used elsewhere...
#require_once(constant('LIB_DIR') . '/defaults.php'); # Configure defaults if not already set
require_once(constant('LIB_DIR') . '/template.php'); # Template language features (i.e. output filtering)
require_once(constant('LIB_DIR') . '/router.php'); # Request routing support
require_once(constant('LIB_DIR') . '/objectify.php'); # HTTP Request & Response object models
#require_once(constant('LIB_DIR') . '/site.php'); # Site configuration and base theming
#require_once(constant('LIB_DIR') . '/cache.php'); # Cache features (placeholder)


?>
