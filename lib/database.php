<?php

interface IDatabase {
  public static function connection($host, $port, $user, $pass, $dbname);
  public function query($str, $mode = null);
  public function escape($str);
  public function table($table);
  public function prepare($stmt);
}

interface ITableMeta {
  public function name();
  public function columns();
  public function get_primary();
  public function get_default($name);
  public function allow_null($name);
  public function is_primary($name);
  public function is_foreign($name);
  public function is_unique($name);
  public function is_numeric($name);
  public function is_automatic($name);
  public function is_timestamp($name);
  public function validate($name, $val);
  public function sanitize($type, $val);
  public function query($verb, $attribs);
}

interface IQuery extends Iterator {
  public function id();
  public function length();
}

interface IStatement extends IQuery {
  public function execute($data, $callback = null);
}

abstract class TableMeta implements ITableMeta {
  const NUMERIC = 1;
  const NULLIFY = 2;
  const USEDEFAULT = 3;

  public function get_primary(){
    $c = array_filter($this->columns(), array($this, 'is_primary'));
    if(is_array($c) && count($c) === 1) return $c[0];
    return $c;
  }

  public function validate($name, $val){
    if($this->is_numeric($name) && is_numeric($val)) return self::NUMERIC;
    if(is_null($val) && $this->allow_null($name)) return self::NULLIFY;
    elseif(is_null($val)) return self::USEDEFAULT;
    return false;
  }

  public function sanitize($val, $type, $name = null){
    if(is_string($name) && $type == self::USEDEFAULT) return $this->get_default($name);
    if($type == self::NUMERIC) return (float) $val;
    return $val;
  }

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

  public static function table($table){
    return self::$connection->table($table);
  }

  public static function escape($string){
    return self::$connection->escape($string);
  }

}



?>
