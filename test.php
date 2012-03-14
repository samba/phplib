<?php

header('Content-Type: text/plain');

define('USE_DATABASE', 'mysql');
define('DATABASE_HOST', '127.0.0.1');
define('DATABASE_PORT', '6229');
define('DATABASE_NAME', 'mydb');
define('DATABASE_USER', 'devuser');
define('DATABASE_PASS', '8f3blocku7');

require('lib/init.php');


class User extends Model {
  public static $table = 'user';
}

class Configuration extends Model {
  public static $table = 'config';
}



print_r(get_class_vars('User'));
print_r(get_class_vars('Configuration'));


?>
