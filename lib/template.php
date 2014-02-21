<?php
/* Template implementation logic
 *
 *  
 */


class TemplateParser {

	# Extracts syntax fields, e.g...
	#	{{ variable }}
	# 	{{ variable#regex# }} (required match entire regex)
	#	{{ variable#re(gex)#[1] }}  (match subset in regex)
	# 	{{ variable.subattribute }}  (value of sub-attribute)
	# 	{{ variable.subattribute|filter(options) }} (evaluate through a known/registered filter)
	const EXTRACT = '@{{\s*([a-z0-9_\-\.]+)(?:(#(?:.*)#[a-z]*)(?:\[(\d+)\])?)?(\|([^\}\s]*))?\s*}}@ie';
	
	# Passes all matched components and the instance for evaluation
	const EVALUATE = 'TemplateParser::evaluate($instance, "$1", "$2", "$3", "$4")';
	
	# Matches filter expressions in the extraction
	const FILTER = '@^([a-z0-9_]+)(?:\((.*)\))?$@';

	protected $values = array();
	protected $filters = array();

	# Shared filters
	public static $Filters = array();

	public function __construct($values = array()){
		if(is_array($values)){
			$this->values = array_merge($this->values, $values);
		}

		# Apply standard filters
		$this->filters['upper'] = "TemplateParser::filter_upper";
		$this->filters['lower'] = "TemplateParser::filter_lower";
	}

	public function set($name, $value){
		$this->values[ $name ] = $value;
	}

	public function render($template_string){
		$instance = & $this;
		return preg_replace(self::EXTRACT, self::EVALUATE, $template_string);
	}

	public function add_filter($name, $callback){
		if(is_callable($callback)){
			$this->filters[ $name ] = $callback;
		}
	}

	public static function filter_upper($input, $args = null){
		return strtoupper($input);
	}

	public static function filter_lower($input, $args = null){
		return strtolower($input);
	}


	public static function evaluate(& $instance, $key, $regex = null, $index = 0, $filters = null){
		$current = $instance->values;

		# Parse the dot-separated path through our known values
		$key = explode('.', $key);

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

		# Evaluate any provided regular expression
		if(!empty($regex) && strlen($regex) > 0 && preg_match($regex, $current, $match)){
			$current =  empty($index) ? $match[0] : $match[$index];
		}

		# Run any available filters...
		if(!empty($filters) && strlen($filters)){
			$filters = explode('|', $filters);

			# starts at 1 since the string is guaranteed to start with a pipe...
			for($i = 1; $i < count($filters); $i++){

				# Extract options and filter name
				if(preg_match(self::FILTER, $filters[$i], $match)){
					list($all, $filter, $args) = $match;
				} else continue;

				if(empty($filter)) continue;

				# Try filters from this instance
				if(array_key_exists($filter, $instance->filters)){
					$current = call_user_func($instance->filters[ $filter ], $current, $args);
					continue;
				} 

				# Try the shared filters
				if(array_key_exists($filter, TemplateParser::$Filters)){
					$current = call_user_func(TemplateParser::$Filters[ $filter ], $current, $args);
					continue;
				}
			}

		}

		return $current;
	}
}



class BaseTemplate {
	private $filename = null;
	public $values = array();

	public function __construct($filename){
		if(is_string($filename)){
			$this->filename = $filename;
		}
	}

	public function set($name, $value){
		$this->values[ $name ] = $value;
	}

	public function render($values = array()){
		$values = array_merge($this->values, $values);
		$parser = TemplateParser($values);
		return $parser->render(readfile($this->filename));
	}

}





class Template extends BaseTemplate {


}


?>
