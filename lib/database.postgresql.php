<?php


### NOTE: This module is completely untested at this point.
# I'm merely filling it out in a fashion compatible with the interfaces.
# ... also, rename it "database.postgresql.php"

class PostgreSQLDatabase implements IDatabase {

  private static $internal_connection = null;
  private $prepared_counter = 0;
  
  public static function connection($host, $port, $user, $pass, $dbname){
    if(is_null(self::$internal_connection))
      self::$internal_connection = pg_pconnect(sprintf(
        "host=%s port=%u user=%s password='%s' dbname=%s",
        $host, $port, $user, $pass, $dbname
      ));
    return self::$internal_connection;
  }

  public function prepare($stmt){
    $name = 'prepared' . (++ $this->prepared_counter);
    return new PostgreSQLStatement($name, pg_prepare($name, $stmt));
  }

  public function escape($string){
    return pg_escape_string(self::$internal_connection, $string);
  }

  public function query($str, $mode = null){
    return new PostgreSQLQuery(pg_query(self::$internal_connection, $str));
  }

  public function table($name){
    return new PostgreSQLTableMeta($name, pg_query(
      self::$internal_connection,
      sprintf('SELECT * FROM %s WHERE 1=0;', $name)
    ));
  }

}

class PostgreSQLTableMeta extends TableMeta {

  public function __construct($name, $result){
    // TODO: collect table metadata 
  }  

  public function columns(){}
  public function get_primary(){}
  public function get_default($name){}
  public function allow_null($name){}
  public function is_primary($name){}
  public function is_foreign($name){}
  public function is_unique($name){}
  public function is_numeric($name){}
  public function is_automatic($name){}
  public function is_timestamp($name){}

}


class PostgreSQLQuery implements IQuery {
  private $result = null;
  private $cursor = null;
  private $counter = -1;

  public function __construct($result){
    $this->result = $result;
  }

  public function id(){
    // TODO: last insert id
    // this MUST use the "RETURNING" syntax for inserts
  }

  public function length(){
    return pg_num_rows($this->result);
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
    if(pg_result_seek($this->result, 0))
      $this->cursor = pg_fetch_assoc($this->result, 0);
  }
  public function & next(){
    $this->counter++;
    $this->cursor = pg_fetch_assoc($this->result);
    return $this->cursor;
  }

}

class PostgreSQLStatement extends IQuery {

  public function __construct($name, $res){
    // TODO
  }

}


?>
