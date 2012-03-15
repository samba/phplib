<?php

class MySQLModelDB implements ModelDatabase {
  private $database = null;

  public function __construct($db){
    if($db instanceof MySQLDatabase) $this->database = $db;
  }


  public function table($nm){
    return $this->database->table($nm);
  }

  public function retrieve($table, $params = '*'){
    return $this->database->query($table->compile('select', $params));
  }

  public function delete($reference){

  }

  public function update($reference, $values){

  } 

}
 

?>
