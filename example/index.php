<?php
/*
 *
 *
 */

header('Content-Type: text/plain');

define('PROJECT_DIR', dirname(__FILE__));

# If defined, this will replace a leading path with '/' for handler resolution
define('URL_PREFIX', '/~samba/phplib/example/');

define('AUTO_METHOD', 'true');

# Pull in standard utilities
require('lib/init.php');

# Apply this site's configuration
require('config.php');




?>
