<?php

/* Demonstrates _basic_ use of the Request/Response framework */

class TestingHandler extends HTTPRequest {

  // This meethod will respond to HTTP GET requests
  function get($full, $lead){
    // NOTE: this is a PARTIAL document. 
    // The framework should clean this up, decorate with header/footer appropriately.
    $this->response->define('testing', '########');
    $this->response->template("This is a test! <ul><li><i>{$lead}</i></li><li>{{ testing }}</li></ul>");
    return true;
  }

}

// REQUIRED:
return new TestingHandler;

?>
