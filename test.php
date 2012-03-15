<?php

header('Content-Type: text/plain');

define('USE_MODELS', true);
define('USE_DATABASE', 'mysql');
define('DATABASE_HOST', '127.0.0.1');
define('DATABASE_PORT', '6229');
define('DATABASE_NAME', 'mydb');
define('DATABASE_USER', 'devuser');
define('DATABASE_PASS', '8f3blocku7');

require('lib/init.php');

require_database('mysql');

# print_r(get_class_vars('Database'));
# print_r(get_class_vars('Model'));


# $records = Database::query('select * from config');
# print_r(array($records->length(), $records));

$x = (Database::table('config'));

var_dump(array(
  $x->is_primary('id'),
  $x->is_automatic('id'),
  $x->is_numeric('name')
));

class User extends Model {
  protected static $source_table = 'user';
}

Model::assign('User');


print_r(User::get(1));


?>
