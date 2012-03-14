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
    return $this->real_escape($string);
  }

  public function columns($table){

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
    $this->counter = -1;
    if($this->result->data_seek(0))
      $this->cursor = $this->result->fetch_assoc();
   
  }
  public function & next(){
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

  }

}

?>
