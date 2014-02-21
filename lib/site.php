<?php

/* Simplified CMS features
 *  - Enforces global headers, footers, etc, if present.
 */

defined('SITE_HEADER') || define('SITE_HEADER', 'site/global/header.php');
defined('SITE_FOOTER') || define('SITE_FOOTER', 'site/global/footer.php');


class Site {
  public static $context = array();

  public static function define($name, $value){
    self::$context[ $name ] = $value;
  }

  public static function get($name, $allow_const = true){
    if(isset(self::$context[ $name ])) return self::$context[ $name ];
    if($allow_const && defined($name)) return constant($name);
    return null;
  }

  public static function value($name){
    print (string) self::get($name, true); 
  }

  public static function header(){
    if(is_file(constant('SITE_HEADER'))){
      require_once(constant('SITE_HEADER'));
    }
  }

  public static function footer(){
    if(is_file(constant('SITE_FOOTER'))){
      require_once(constant('SITE_FOOTER'));
    }
  }

}


/* Some really common shortcuts... */
Site::define('hostname', $_SERVER['HTTP_HOST']);


?>
