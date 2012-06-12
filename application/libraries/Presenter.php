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
 * @link		https://github.com/druu/ci-presenter/
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
	 * Our class constructor
	 *
	 * @param mixed $result_set The actual data which we're working on.
	 */
	public function __construct($result_set = null)
	{
		//Do your magic here
		$this->_result_set = $result_set;
		$this->is_multi =  is_array($result_set);
		$this->ci =& get_instance();
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
	public function create($presenter, $data)
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
		if (is_null($data))
		{
			return $classname;
		}

		return new $classname($data);
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
					if (is_object($value))
					{
						var_dump($value);
						die();
					}
					$tmp = str_replace("#$key#", $value, $tmp);
				}
				$out .= $tmp;
			}
			//$this->active_item = null;
		}
		return $out;
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
		if (property_exists($this->_result_set, $property))
		{
			if (method_exists($this, 'transform_'.$property))
			{
				$method = array($this, 'transform_'.$property);
				$arguments = array($this->_result_set->$property);
				return call_user_func_array($method, $arguments);
			}
			else
			{
				return $this->_result_set->$property;
			}
		}
		else
		{
			log_message('error', "Presenter: Property '$property' does not exist.");
			return 'N/A';
		}
	}


	/**
	 * MAGIC CALL!
	 *
	 * Allows overriding the data while using the shorter call way
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
			if (property_exists($this->_result_set, $property))
			{
				if (method_exists($this, 'transform_'.$property))
				{
					$method = array($this, 'transform_'.$property);
					return call_user_func_array($method, $value);
				}
			}

			log_message('error', "Presenter: Method '$property' does not exist.");
			return 'N/A';
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


}


/* End of file presenter.php */
/* Location: ./application/libraries/presenter.php */