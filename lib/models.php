<?php

# Data model automation
#  - Provides interfaces for database drivers (ModelDatabase, ModelDatabaseTable)
#  - Provides object access routines for abstracting DB operations
#  - Requires compatible database drivers to define 'DATABASE_CLASS'
#

interface ModelInterface {
  public function save();
  public function delete();
  public static function get($id);
  public static function filter($name, $val, $operator = '=');
  public static function create($attribs = null);
  public static function register($classname, $table);
  public static function properties($table = null);
}

interface ModelDatabase {
  public function properties($table);
  public function retrieve($table, $params = '*');
  public function delete($reference);
  public function update($reference, $values);
}

# Reference to a record in a table
# This will be populated by the database driver, and all
# operations on models will require a reference.
class ModelReference {
  public $table = null;
  public $ident = null;

  public function __construct($table, Array $ident){
    $this->table = $table;
    $this->ident = $ident;
  }
}

# Paramater aggregation class
class ModelQuery implements Iterator{
  public $table = null;
  public $params = array();
  public $model = null;

  private $iterator = null; 

  public function __construct($table, $model = null){
    $this->table = $table;
    $this->model = $model;
  }

  # Add parameters to the set
  public function filter($param_name, $param_val, $operator = '='){
    array_push($this->params, array($param_name, $param_val, $operator));
    return $this;
  }

  # Push the parameters to the database for retrieval;
  # Expect an iterator-compatible result
  public function & acquire(){
    if(!($this->iterator) instanceof Iterator)
      $iter = $this->model->database->retrieve($this->table, $this->params);
    if($iter instanceOf Iterator) $this->iterator = & $iter;
    return $this->iterator;
  }

  # pass through iterator handling
  public function current(){
    return $this->acquire()->current(); 
  }

  # pass through iterator handling
  public function key(){
    return $this->acquire()->key();
  }

  # pass through iterator handling
  public function next(){
    return $this->acquire()->next();
  }
  
  # pass through iterator handling
  public function rewind(){
    return $this->acquire()->rewind();
  }

  # pass through iterator handling
  public function valid(){
    $this->acquire()->valid();
  }

}


# Core model behavior
class Model implements ModelInterface {

  # Use a single database connection
  public static $database = null;
  public static function connect($host, $port, $user, $pass, $dbname){
    if(!(self::$database instanceOf ModelDatabase)){
      $c = constant('DATABASE_CLASS');
      self::$database = new $c($host, $port, $user, $pass, $dbname);
    }
    return self::$database;
  }

  # Allow classes to reference tables of different names
  public static $classmap = array(array(), array());
  public static function register($classname, $table){
    self::$classmap[0][$classname] = $table; # forward
    self::$classmap[1][$table] = $classname; # reverse
  }

  public static function get_table($class = null){
    $class = (is_string($class) ? $class : get_class(self));
    return isset(self::$classmap[0][$class]) ? self::$classmap[0][$class] : $class;
  }

  public static function properties($table = null){
    $table = is_string($table) ? $table : self::get_table();
    return self::$database->properties($table);
  }

  # Create a query for this class
  public static function filter($name, $val, $op = '='){
    $q = ModelQuery(self::get_table(), $this);
    return $q->filter($name, $val, $op);
  }

  # Direct retrieval is a form of filter query 
  public static function get($id){
    return reset(self::filter(null, $id)->acquire());
  }

  # Spawn a new record without a reference
  public static function create($attributes = null){
    $class = get_class(self);
    return new $class(null, $attributes);
  }


  ### INSTANCE BEGINS HERE
  
  private $reference = null;
  private $attributes = null;

  public function __construct($ref, $attribs){
    if($ref instanceof ModelReference) $this->reference = $ref;
    $this->attributes = $attribs;
  }

  # Remove this record form the database
  public function delete(){
    return self::$database->delete($this->reference);    
  }

  # Add or update this record in the database
  public function save(){
    return self::$database->update($this->reference, $this->attributes);
  }

  public function __set($name, $val){
    if(!is_array($this->attributes)) $this->attributes = array();
    $this->attributes[$name] = $val;
  }

  public function __get($name){
    return (is_array($this->attributes) && array_key_exists($name, $this->attributes)) ? $this->attributes[$name] : null;
  }
}



?>
