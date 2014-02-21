<?php

# Where is this library loading?
define('LIB_DIR', dirname(__FILE__));

# Template support paths
defined('TEMPLATE') || define('TEMPLATE', 'undefined');
defined('TEMPLATE_DIR') || define('TEMPLATE_DIR', 'site/template/' . constant('TEMPLATE'));

# Should we load HTTP-method handlers automatically?
defined('AUTO_METHOD') || define('AUTO_METHOD', false);

# Environment defaults for URL parsers
defined('REQUEST_METHOD') || define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
defined('REQUEST_URI') || define('REQUEST_URI', $_SERVER['REQUEST_URI']);
defined('QUERY_STRING') || define('QUERY_STRING', $_SERVER['QUERY_STRING']);
defined('REQUEST_PATH') || define('REQUEST_PATH', str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));



###########################################################
# Real action begins here.

require_once(constant('LIB_DIR') . '/util.php'); # common utilities (e.g. string processing)
require_once(constant('LIB_DIR') . '/urlmap.php'); # URL mapping automation
require_once(constant('LIB_DIR') . '/site.php'); # Site configuration and base templating
require_once(constant('LIB_DIR') . '/template.php'); # Template loading & support features
require_once(constant('LIB_DIR') . '/cache.php'); # Cache features (placeholder)


?>
