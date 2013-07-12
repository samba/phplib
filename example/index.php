<?php

require('./config.php');
require('../urlmap.php');
require('../site.php');

template('hostname', $_SERVER['HTTP_HOST']);

// Serve a stylesheet with template processing
URL('(.*)/style.css', 'route/stylesheet-template.css', true);

// Route these ...
URL('(.*)/test.php$', 'route/test.php');
URL('(.*)/base.php$', 'route/base.php');


if(!request_handled()){
  fail(404, 'Not found');
}

?>
