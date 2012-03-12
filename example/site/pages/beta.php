<?php

class Beta extends HTTPRequest {

  public function get($match, $uri){
    $this->response->setEncoder('xml');
    $this->response->setCache(600);
    $this->response->setResult((object) array(
      'one' => 'two',
      'three' => 'four',
      'five' => array(
        'six', 'seven', 'eight'
      )
    ));

    return $this->response;
  }

}

return new Beta();

?>
