<?php
/*
 *
 *
 */


define('PROJECT_DIR', dirname(__FILE__));

# If defined, this will replace a leading path with '/' for handler resolution
define('URL_PREFIX', '/~samba/phplib/example/');

# Will the URL destinations create HTTPRequest handlers with corresponding methods? (e.g. get(), post()...)
define('AUTO_METHOD', 'true');

define('APP_TIMEZONE', 'America/Los_Angeles');


# Pull in standard utilities
require('lib/init.php');

# Apply this site's configuration
require('config.php');




?>
