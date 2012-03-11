
<?php

class Delta extends HTTPRequest {

  public function get($response){
    $response->setTemplate('echo "delta says your time is: " . time();', false); 
    $response->setContentType('text/javascript');
    $response->setCache(200);
    return $response;
  }

}

return new Delta();

?>
