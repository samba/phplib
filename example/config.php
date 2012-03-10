<?php

define('URL_PREFIX', '/~samba/phplib/example/');

URL('^/$', 'site/page/alpha.php');
URL('^/beta$', 'site/page/beta.php');
URL('^/(gamma|delta)/', 'site/page/$1.php');
URL('^(.*)$', 'site/pages/default.php');

?>
