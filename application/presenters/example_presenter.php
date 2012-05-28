<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Example_Presenter extends Presenter {

	public function transform_price($price)
	{
		return $price ? number_format($price, 2, '.', ',').' &euro;' : 'N/A';
	}

	public function transform_name($name)
	{
		return $name ? $this->key('T2').' '.$name : 'N/A';
	}

	public function transform_key($key)
	{
		return $key ? '<b>'.$key.'</b>' : 'N/A';
	}

	public function do_something($var)
	{
		return $var;
	}

}


/* End of file example_presenter.php */
/* Location: ./application/presenters/example_presenter.php */