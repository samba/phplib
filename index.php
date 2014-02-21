<?php

# This is a base configuration. It's provided primarily as an example, but may
# also be useful in some actual sites.


require('config.php');
require('lib/init.php');

# Define some template variables
template('hostname', $_SERVER['HTTP_HOST']);
template('basepath', constant('URL_PREFIX'));




// Route these (in order) 

redirect('/(index\.html?)', '/');

URL('/static/(.*)', 'site/static/$1', true, null);
URL('/$', 'site/route/index.php');

URL('/blog/$', 'site/route/blog-index.php');
URL('/blog/([a-z0-9\_\-]+/)$', 'site/route/blog-category.php');
URL('/blog/(?:(\d+)/)?(.*/)', 'site/route/blog-entry.php');



if(!request_handled()){
  fail(404, readfile('site/notfound.html'));
}


?>
