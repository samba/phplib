<?php

# Where is this library loading?
define('LIB_DIR', dirname(__FILE__));

# Require that the app has configured the basics properly
defined('PROJECT_DIR') || define('PROJECT_DIR', '../');

require(constant('LIB_DIR') . '/urlmap.php');
require(constant('LIB_DIR') . '/request.php');
require(constant('LIB_DIR') . '/response.php');


?>
