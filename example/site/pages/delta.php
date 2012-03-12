
<?php

class Delta extends HTTPRequest {

  public function get($match, $uri){
    $this->response->setTemplate(sprintf('echo "%s (%s) says your time is: " . time();', $match[1], $uri), false);
    $this->response->setStatus(HTTPResponse::IM_A_TEAPOT); 
    $this->response->setContentType('text/javascript');
    $this->response->setCache(3600);
    return $this->response;
  }

}

return new Delta();

?>
