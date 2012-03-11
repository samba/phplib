<?php

class Gamma extends HTTPRequest {

  public function get(){
    print 'hello, gamma.';
  }

  public function post(){
    print "oh, hi... this handler doesn't support HTTP POST";
  } 

}

return new Gamma();

?>
