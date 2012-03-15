<?php

class MySQLDatabase extends mysqli implements IDatabase {

  private static $internal_connection = null;

  public static function & connection($host, $port, $user, $pass, $dbname){
    if(is_null(self::$internal_connection))
      self::$internal_connection = new MySQLDatabase($host, $user, $pass, $dbname, $port);
    return self::$internal_connection;
  }

  public function prepare($stmt){
    return new MySQLStatement($this, parent::prepare($stmt));
  }

  public function query($string, $mode = MYSQLI_STORE_RESULT){
    return new MySQLQuery($this, parent::query($string, $mode));
  }
  
  public function escape($string){
    return $this->real_escape_string($string);
  }

  public function table($table){
    return new MySQLTableMeta($table, parent::query(sprintf('SELECT * FROM %s WHERE 1=0;', $table), MYSQLI_STORE_RESULT)->fetch_fields());
  }

}

class MySQLTableMeta extends TableMeta {
  private $structure = null;
  private $tablename = null;
  private $keys = array();

  public function __construct($tablename, $fields){
    $this->tablename = $tablename;
    $this->structure = self::field_keys($fields);
    $this->keys = array_keys($this->structure);
    ## TODO: cache prepred statements for operations on this table
  }

  private static function & field_keys($ar){
    $result = array();
    foreach($ar as $k => & $v){
      $result[ $v->name ] = & $v;
    }
    return $result;
  }

  public function name(){
    return $this->tablename;
  }

  public function columns(){
    return $this->keys;
  }

  public function is_primary($field){
    return (bool) ($this->structure[$field]->flags & MYSQLI_PRI_KEY_FLAG);
  }

  public function is_foreign($field){
    return false; // TODO: how does MySQL describe foreign keys?
  }

  public function is_unique($field){
    return (bool) ($this->structure[$field]->flags & MYSQLI_UNIQUE_KEY_FLAG);
  } 

  public function is_automatic($field){
    return (bool) ($this->structure[$field]->flags & MYSQLI_AUTO_INCREMENT_FLAG);
  }

  public function is_timestamp($field){
    return (bool) ($this->structure[$field]->flags & MYSQLI_TIMESTAMP_FLAG);
  }

  public function is_numeric($field){
    return (bool) ($this->structure[$field]->flags & MYSQLI_NUM_FLAG);
  }

  public function allow_null($field){
    return (bool) ($this->structure[$field]->flags & MYSQLI_NOT_NULL_FLAG);
  }

  public function get_default($name){
    $d = $this->structure[$name]->def;
    return (empty($d) && $this->allow_null($name)) ? null : $d;
  }


  public function query($verb, $attribs){
    // TODO: build prepared statement ?
  } 

}



class MySQLQuery implements IQuery {
  protected $database = null;
  protected $result = null;
  protected $cursor = null;
  protected $counter = -1;

  public function __construct($database, $result){
    $this->database = $database;
    $this->result = $result;
  }

  public function id(){
    return (is_object($this->result)) ? $this->result->insert_id : null;
  }

  public function length(){
    return ($this->result === true) ? $this->database->affected_rows : $this->result->num_rows;
  }

  public function & current(){
    return $this->cursor;
  }
  public function valid(){
    return (bool) $this->cursor;
  }
  public function key(){
    return $this->counter;
  }
  public function rewind(){
    $this->counter = 0;
    if($this->result->data_seek(0))
      $this->cursor = $this->result->fetch_assoc();
   
  }
  public function & next(){
    $this->counter++;
    $this->cursor = $this->result->fetch_assoc();
    return $this->cursor;
  }

}

class MySQLStatement extends MySQLQuery implements IStatement {
  protected $database = null;
  protected $statement = null;
  protected $result = null;

  public function __construct($database, $statement){
    $this->database = $database;
    $this->statement = $statement;
  }

  public function execute($data, $callback = null){
    // TODO

    return $this;
  }

}

?>
