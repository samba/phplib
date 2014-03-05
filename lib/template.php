<?php
# Template implementation logic.
# Our aim here is to maintain a _minimal_ template support for simple cases, 
# and leaves much more advanced logic to other template engines.
# Recommendation: https://github.com/fabpot/Twig.git
#


class LIGHTTemplateParser {

	# Extracts syntax fields, e.g...
	#	[[ variable ]]
	# 	[[ variable.subattribute ]]  (value of sub-attribute)
	# 	[[ variable.subattribute|filter(options) ]] (evaluate through a known/registered filter)
	
	const EXTRACT = "@\[\[\s*([a-z0-9_\-\.]+)(?:\|(.+?))?\s*\]\]@ie";

	# Matches filter expressions in the extraction
	const EXTRACT_FILTER = "@[\|]?([a-z_]+)(?:\(((?:[^\)]|\\\)*)\))?@";

	# Passes all matched components and the instance for evaluation
	const EVALUATE = 'LIGHTTemplateParser::template_evaluate($instance, "$1", "$2")';

	# Shared filters
	protected static $standard_filters = array();

	protected $template_values = array();
	protected $template_filters = array();

	public function __construct($values = array()){
		if(is_array($values)){
			$this->template_values = array_merge($this->template_values, $values);
		}
	}

	public function set($name, $value){
		$this->template_values[ $name ] = $value;
	}

	public function render($template_string){
		$instance = & $this;
		return preg_replace(self::EXTRACT, self::EVALUATE, $template_string);
	}

	public function addFilter($name, $callback){
		if(is_callable($callback)){
			$this->template_filters[ $name ] = $callback;
		}
	}

	public static function filter_upper($input, $args = null){
		return strtoupper($input);
	}

	public static function filter_lower($input, $args = null){
		return strtolower($input);
	}

	public static function filter_date($input, $args = null){
		if(empty($args)){
			$args = ['Y-m-d'];
		}
		return date($args[0], $input);
	}


	public static function slice_commas($args){
		return preg_split('@(?<!\\\),@', $args);
	}

	public static function template_resolve(& $instance, $name){
		$current = $instance->template_values;

		# Parse the dot-separated path through our known values
		$key = explode('.', $name);

		# Evaluate the variable path
		while($term = array_shift($key)){
			if(is_array($current) && array_key_exists($term, $current)){
				$current = $current[ $term ];
			} elseif(is_object($current) && property_exists($current, $term)){
				$current = $current->{$term};
			} else {
				return null;
			}
		}

		return $current;
	}


	public static function template_evaluate(& $instance, $key, $filter_text = null){

		# Resolve the name in present scope
		$current = self::template_resolve($instance, $key);

		if(!empty($filter_text)){
			if($m = preg_match_all(self::EXTRACT_FILTER, $filter_text, $matches)){

				for($i = 0; $i < $m; $i++){

					$filter = $matches[1][$i];
					$args = $matches[2][$i];

					if(!empty($args)){
						$args = self::slice_commas($args);
					}

					# Try filters from this instance
					if(array_key_exists($filter, $instance->template_filters)){
						$current = call_user_func($instance->teamplate_filters[ $filter ], $current, $args);
						continue;
					} 

					# Try the shared filters
					if(array_key_exists($filter, TemplateParser::$standard_filters)){
						$current = call_user_func(TemplateParser::$standard_filters[ $filter ], $current, $args);
						continue;
					}
				}
			}
		}

		return $current;
	}

	public static function setStandardFilter($name, $function){
		if(is_callable($function)){
			self::$standard_filters[ $name ] = $function;
		}
	}
}

# Register standard filters 
LIGHTTemplateParser::setStandardFilter('upper', array('LIGHTTemplateParser', 'filter_upper'));
LIGHTTemplateParser::setStandardFilter('lower', array('LIGHTTemplateParser', 'filter_lower'));
LIGHTTemplateParser::setStandardFilter('date', array('LIGHTTemplateParser', 'filter_date'));


function template_render_file($filename, $template_values){
	$template = new LIGHTTemplateParser($template_values);
	return $template->render(file_get_contents($filename));
}

function template_render($content, $template_values = null){
	$template = new LIGHTTemplateParser($template_values);
	return $template->render($content);
}


?>
