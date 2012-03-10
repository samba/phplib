<?php
/*
 *
 *
 */

# Get this site's configuration
require('config.php');

# Pull in standard utilities
require('lib/init.php');

print_r($urls);

# Initiate 
if(is_array($urls))
  print_r(URLMapping::init($urls, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING'], constant('PROJECT_DIR')));



?>
