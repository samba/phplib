<?php

interface IDatabase {
  public static function connection($host, $port, $user, $pass, $dbname);
  public function query($str, $mode = null);
  public function escape($str);
  public function columns($table);
  public function prepare($stmt);
}

interface IQuery extends Iterator {
  public function id();
  public function length();
}

interface IStatement extends IQuery {
  public function execute($data, $callback = null);
}


# This behaves as a global interface, simulating a singleton.
class Database {
  public static $handlerclass = null;
  public static $connection = null;
  
  public static function init($host, $port, $user, $pass, $dbname){
    $c = get_supporting_class('IDatabase');
    if($c){ 
      self::$connection = $c::connection($host, $port, $user, $pass, $dbname);
      self::$handlerclass = $c;
      return self::$connection;
    }
  }

  public static function query($string, $mode = null){
    return self::$connection->query($string, $mode);
  }

  public static function columns($table){
    return self::$connection->coluns($table);
  }

  public static function escape($string){
    return self::$connection->escape($string);
  }

}



?>
