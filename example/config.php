<?php


URL('^/$', 'site/pages/alpha.php');
URL('^/beta$', 'site/pages/beta.php');
URL('^/(gamma|delta)/', 'site/pages/$1.php');
URL('^(.*)$', 'site/pages/default.php');

?>
