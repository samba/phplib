# Simple PHP framework

This project aims to provide a set of robust libraries with minimum interdependence, which would
simplify web application development in PHP, and facilitate RESTful APIs and MVC-like architecture.

The intended deployment is to treat this project as a Git submodule, to be deployed as a component
of the full-featured applications you might build.

For example:
```shell
git submodule add https://github.com/samba/phplib.git lib
```

Your application would then import the various parts of it:
```php
require_once('lib/urlmap.php');
```

This project is still quite young, so documentation is lacking. Please see the 'examples' directory.


