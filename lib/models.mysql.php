<?php

define('DATABASE_CLASS', 'MySQLDatabase');

class MySQLDatabase extends mysqli implements ModelDatabase {

  public function __construct($host, $port, $user, $pass, $dbname){
    parent::__construct($host, $user, $pass, $dbname, $port);
  } 

  # THIS MUST RETURN AN ITERATOR
  public function retrieve($table, $params = '*'){
    
  }

  public function update($reference, $values){

  } 
  
  public function delete($reference){

  }

  public function properties($table){

  }

} 
 

?>
