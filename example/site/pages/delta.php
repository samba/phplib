
<?php

class Delta extends HTTPRequest {

  public function get($response, $match, $uri){
    $response->setTemplate(sprintf('echo "%s (%s) says your time is: " . time();', $match[1], $uri), false); 
    $response->setContentType('text/javascript');
    $response->setCache(3600);
    return $response;
  }

}

return new Delta();

?>
