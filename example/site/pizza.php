<?php

class PizzaProcessor extends HTTPRequest {

	public function get($cheese, $flavor){
		$this->response->write(template_render("Your pizza has [[cheese]] and [[flavor]] on ", get_defined_vars()) . '[[hostname]]');
	}


}

return "PizzaProcessor";

?>