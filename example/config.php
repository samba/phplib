<?php

# Evalute path mapping as sub-components the current directory (i.e. ignore this path as the prefix)
define('CONFIG_PATH', dirname($_SERVER['PHP_SELF']));
define('URL_PREFIX', constant('CONFIG_PATH'));

define('CACHE_ENABLE', false);
define('TEMPLATE', 'prelim');
define('APP_TIMEZONE', 'America/Los_Angeles');

?>
