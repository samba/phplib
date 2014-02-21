<?php


class Template {

  public static $properties = array();

  public static function define($property, $value = null){
    self::$properties[ $property ] = $value;
  }

}


?>
