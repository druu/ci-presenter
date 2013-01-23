<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter Presenter Class
 *
 * This Library to eases up loading, creation and usage of object-presenters
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		David Wosnitza (@_druu)
 * @copyright   Copyright (c) 2012, David Wosnitza
 * @link		https://github.com/druu/ci-presenter/
 * @license     MIT http://opensource.org/licenses/MIT
 */
class Presenter {

	/**
	 * The actual data-object
	 * @var object / array of objects
	 */
	protected $_result_set;

	/**
	 * The CI super object
	 * @var object
	 */
	protected $ci;

	/**
	 * The active item of a multiple $_result_set
	 * @var object
	 */
	protected $active_item = null;

	/**
	 * Are we working on multiple objects?
	 * @var [type]
	 */
	protected $is_multi;

	/**
	 * Properties that should not be processed
	 * @var array
	 */
	protected $ignore = array();

	/**
	 * Where to look for our partials?
	 * @var string
	 */
	protected $partial_path = NULL;

	/**
	 * Storage for Virtual/Composed Properties
	 * @var array
	 */
	protected $v_map = array();

	/**
	 * Left delimiter
	 * @var string
	 */
	protected $l_delimiter = '#';

	/**
	 * Right delimiter
	 * @var string
	 */
	protected $r_delimiter = '#';

	/**
	 * Our class constructor
	 *
	 * @param mixed $result_set The actual data which we're working on.
	 */
	public function __construct($result_set = NULL)
	{
		//Do your magic here
		$this->_result_set = $result_set;
		$this->is_multi =  is_array($result_set);
		$this->ci =& get_instance();
		$this->partial_path = $this->_fetch_partial();
		log_message('debug', "Presenter Class Initialized");
	}


	/**
	 * Creation Method
	 *
	 * This method loads and creates the actual Presenters
	 *
	 * @param  string $presenter The Presenters name without the post-fix
	 * @param  string $data      The actual data to work on
	 * @return Presenter         The actual Presenter object
	 */
	public function create($presenter, $data = NULL)
	{
		return $this->_load($presenter, $data);
	}

	/**
	 * Internal loader
	 *
	 * See create()
	 *
	 */
	private function _load( $presenter, $data = null )
	{
		$classname = $presenter.'_presenter';
		if (!class_exists($classname))
		{
			$this->ci->load->file(APPPATH.'/presenters/'.$classname.'.php');
			log_message('debug', "Presenter: Loaded '$classname'.");
		}

		$classname = new $classname($data);
		$classname->set_delimiters($this->l_delimiter, $this->r_delimiter);
		return $classname;
	}

	/**
	 * Ignore
	 *
	 * This function sets the properties to be ignored
	 *
	 * @param  mixed $ignore The properties to ignore
	 * @return Presenter The presenter (let's keep it chainable ;) )
	 */
	protected function ignore($ignore)
	{
		$this->ignore = (array) $ignore;
		return $this;
	}


	/**
	 * Multi-Partial Renderer
	 *
	 * Allows you to map different partials to each object in a set
	 *
	 *
	 * @param  string $map Name of the mapping function to be loaded
	 * @return string          Processed output
	 */

	public function multi_partial($map)
	{

		$out = '';
		if ($this->is_multi)
		{
			foreach ($this->_result_set as $item)
			{
				if (method_exists($this, 'map_'.$map))
				{
					$partial = call_user_func_array(array($this,'map_'.$map), array($item));
				}
				if (empty($partial)) return $out;

				$html = $this->ci->load->view($this->partial_path.'/'.$partial, NULL, TRUE);
				$out .= $this->_parse_html($item, $html);
			}

			$out = preg_replace("~{$this->l_delimiter}\w*{$this->r_delimiter}~", '', $out);
		}
		return $out;
	}

	/**
	 * Partial Renderer
	 *
	 * No more HTML in your Presenter!
	 * Just pass the name of the partial to be loaded and let the magic happen!
	 *
	 *
	 * @param  string $partial Name of the partial to be loaded
	 * @return string          Processed output
	 */
	public function partial($partial)
	{
		$html = $this->ci->load->view($this->partial_path.'/'.$partial, NULL, TRUE);
		return $this->_generate_output($html);
	}


	/**
	 * Output creator
	 *
	 * For working on multiple Objects:
	 * Pass this function a bit of html, with replacement markers like #propertyname#, and this function will iterate over our $_result_set, replacing the markers with the output of the transformation callbacks.
	 * It returns the concatenated result of each and every item
	 *
	 * @param  string $html HTML-Snippet to perform transformation callbacks on
	 * @return string       Concatenated output
	 */
	protected function _generate_output($html)
	{
		$out = '';
		if ($this->is_multi)
		{
			foreach ($this->_result_set as $item)
			{
				$out .= $this->_parse_html($item, $html);
			}
			$out = preg_replace("~{$this->l_delimiter}\w*{$this->r_delimiter}~", '', $out);
		}
		return $out;
	}


	/**
	* the engine that actually parses the  html
	*
	* @param  string $item - the actual data object
	* @param  string $html HTML-Snippet to perform transformation callbacks on
	* @return string       Concatenated output
	*/
	protected function _parse_html($item, $html)
	{

		$this->active_item = $item;
		$tmp = $html;

		foreach ($item as $key => $value)
		{
			// skip ignored keys
			if (in_array($key, $this->ignore))
			{
				continue;
			}
			// run transformation callbacks on fields
			if (method_exists($this, 'transform_'.$key))
			{
				$value = call_user_func_array(array($this,'transform_'.$key), array($value));
			}
			$tmp = str_replace("{$this->l_delimiter}{$key}{$this->r_delimiter}", $value, $tmp);
		}

		// run transformation callbacks on v_map
		foreach ($this->v_map as $v_key => $v_values)
		{
			if (method_exists($this, 'transform_'.$v_key))
			{
				$v_values = (array) $v_values;
				foreach ($v_values as & $v_value)
				{
					if (method_exists($this, 'transform_'.$v_value))
					{
						$v_value = call_user_func_array(array($this,'transform_'.$v_value), array($item->$v_value));
					}
					else
					{
						$v_value = $item->$v_value;
					}
				}

				$value = call_user_func_array(array($this,'transform_'.$v_key), $v_values);
			}
			$tmp = str_replace("{$this->l_delimiter}{$v_key}{$this->r_delimiter}", $value, $tmp);
		}

		return $tmp;
	}


	/**
	 * Set the active item
	 * @param mixed $id The key of and item in $_result_set
	 */
	public function set_active($id)
	{
		if ($this->is_multi)
		{
			foreach ($this->_result_set as $item)
			{
				if ($id === $item->id)
				{
					$this->active_item = $id;
					log_message('debug', "Presenter: Active Presenter Item: $id");
					return $id;
				}
			}
		}

		log_message('debug', "Presenter: Nothing set.");
		return null;
	}


	/**
	 * Get the active Item
	 * @return object Active item
	 */
	public function get_active()
	{
		return $this->active_item;
	}

	/**
	 * MAGIC GET!
	 *
	 * Call the transformation function automatically
	 *
	 * @param  string $property The property's name
	 * @return mixed
	 */
	public function __get($property)
	{
		if (is_object($this->_result_set))
		{
			// Pre-set some common checks and vars
			$is_raw         = strtolower(substr($property,-4)) === '_raw';
			$property       = $is_raw ? substr($property, 0, -4) : $property;
			$trans_method   = 'transform_'.$property;
			$prop_exists    = property_exists($this->_result_set, $property);
			$arr_key_exists = array_key_exists($property, $this->v_map);
			$method_exists  = method_exists($this, 'transform_'.$property);


			// Do we want it RAW?
			if ( $is_raw && $prop_exists)
			{
				return $this->_result_set->$property;
			}

			// Let's get cracking: Do we have a matching transformation method?
			if ($method_exists)
			{
				$method = array($this, $trans_method);
				$arguments = array();

				// Is it a real property ?
				if($prop_exists)
				{
					$arguments = array($this->_result_set->$property);
				}

				// Or do we have some virtual mapping going on?
				elseif ($arr_key_exists)
				{
					foreach($this->v_map[$property] as $arg)
					{
						$arguments[] = $this->_result_set->$arg;
					}
				}

				// GIMME DAT!
				return call_user_func_array($method, $arguments);
			}
			// Seems like theres no transformation method... Let's try to get at least the raw stuff
			else {
				// Again: real property?
				if($prop_exists)
				{
					return $this->_result_set->$property;
				}
				// or our beloved V-Map ?
				elseif ($arr_key_exists)
				{
					$out = array();
					foreach ($this->v_map[$property] as $arg)
					{
						$out[] = $this->result_set->$arg;
					}
					return implode(' ', $out);
				}
			}
		}

		// Nope... YOU SCREWED UP!!! FIX THAT! :P
		log_message('error', "Presenter: Property '$property' does not exist.");
		return '';

	}


	/**
	 * MAGIC CALL!
	 *
	 * Allows overriding the data while using the shorter call way
	 * Allows allows magic partial loading
	 *
	 *
	 * @param  string $property The name of the Property
	 * @param  array $value    Overrides the data set
	 * @return string          Whatever the output is
	 */
	public function __call($property, $value = null)
	{
		if (is_null($value) || empty($value))
		{
			return $this->__get($property);
		}
		else
		{
			if (
				is_object($this->_resultset)
				&& property_exists($this->_result_set, $property)
			)
			{
				if (method_exists($this, 'transform_'.$property))
				{
					$method = array($this, 'transform_'.$property);
					return call_user_func_array($method, $value);
				}
			}

			log_message('error', "Presenter: Method '$property' does not exist.");
			return '';
		}

	}


	/**
	 * Use all the transformations on all the properties
	 *
	 *
	 * @param  string $property_separator
	 * @param  string $line_separator
	 * @return string
	 */
	public function to_string($property_separator = ' ', $line_separator = PHP_EOL)
	{
		$output = '';
		if ($this->is_multi) {
			foreach ($this->_result_set as $result)
			{
				$classname  = get_class($this);
				$presenter  = new $classname($result);
				$output    .= $presenter->to_string().$line_separator;
			}
		}
		else {
			foreach ($this->_result_set as $key => $value)
			{
				$output .= $this->$key().$property_separator;
			}
		}

		return $output;
	}

	private function _fetch_partial()
    {
        if ($this->partial_path == NULL)
        {
        	return 'partials/'.preg_replace('/(_p|_presenter|_Presenter)?$/', '', strtolower(get_class($this)));
        }
    }
    
	/**
	 * Use delimiters of string replace
	 *
	 *
	 * @param  string $l_delimiter
	 * @param  string $r_delimiter
	 */
    public function set_delimiters($l_delimiter, $r_delimiter)
    {
        $this->l_delimiter = $l_delimiter;
        $this->r_delimiter = $r_delimiter;
    }


}


/* End of file presenter.php */
/* Location: ./application/libraries/presenter.php */
