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
}

interface ModelDatabase {
  public function table($tablename);
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

class Model implements ModelInterface {

  # All models refer to this database
  public static $database = null;
  public static function init($database){
    if($database instanceof IDatabase){
      $c = get_supporting_class('ModelDatabase');
      self::$database = new $c($database);

    }
  }

  # Model classes must be assigned to a table
  protected static $source_table = null;
  protected static $table_mapping = array();
  public static function assign($c){
    $t = self::$database->table($c::$source_table);
    self::$table_mapping[$c] = & $t;
    $c::$source_table = & $t;
    return $t;
  }

  # Create a query for this class
  public static function filter($name, $val, $op = '='){
    $c = self::$table_mapping[get_called_class()];
    $q = new ModelQuery($c, self::$database);
    return $q->filter($name, $val, $op);
  }

  # Direct retrieval is a form of filter query 
  public static function get($id){
    $c = get_called_class();
    $res = self::filter(null, $id)->acquire();
    return is_null($res) ? null : new $c(reset($res));
  }

  protected $attributes = array();

  public function __construct($values = null){
    if(is_object($values)) $values = get_object_vars($values);
    foreach($values as $k => & $v)
      $this->{$k} = & $v;
  }

  # Assign values after validating and sanitizing them.
  public function __set($name, $val){
    $type = self::$source_table->validate($name, $val);
    if($type === false)
      throw new Exception(sprintf('Type error; (%s = %s)', $name, $val));
    $this->attributes[$name] = self::$source_table->sanitize($val, $type, $name);
  }

  public function __get($name){
    return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
  }

  # Remove this record form the database
  public function delete(){
    return self::$database->delete($this->reference);    
  }

  # Add or update this record in the database
  public function save(){
    return self::$database->update($this->reference, $this->attributes);
  }
}


# Paramater aggregation class
class ModelQuery implements Iterator{
  public $table = null;
  public $params = array();
  public $database = null;

  private $iterator = null; 

  public function __construct($table, $database = null){
    $this->table = $table;
    $this->database = $database;
  }

  # Add parameters to the set
  public function filter($param_name, $param_val, $operator = '='){
    if(is_null($param_name)) $param_name = $this->table->get_primary();
    array_push($this->params, array($param_name, $param_val, $operator));
    return $this;
  }

  # Push the parameters to the database for retrieval;
  # Expect an iterator-compatible result
  public function & acquire(){
    if(($this->iterator) instanceof Iterator)
      return $this->iterator;
    $iter = $this->database->retrieve($this->table, $this->params);
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



?>
