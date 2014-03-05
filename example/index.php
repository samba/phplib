<?php

# This is a base configuration. It's provided primarily as an example, but may
# also be useful in some actual sites.

require('config.php');
require('lib/init.php');


// Route these (in order) 
redirect('/(index\.html?)', '/');

route('/pizza/((?:no|extra)?cheese)/(pepperoni|hawaiian)', 'site/pizza.php');

route('/static/(.*)', 'site/static/$1', true, null);
route('/$', 'site/route/index.php');

route('/blog/$', 'site/route/blog-index.php');
route('/blog/([a-z0-9\_\-]+/)$', 'site/route/blog-category.php');
route('/blog/(?:(\d+)/)?(.*/)', 'site/route/blog-entry.php');


# Finally...
fail(404, 'Not Found', 'site/notfound.html');

report_timer(constant('INIT_START'), 'all');

?>
