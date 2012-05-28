<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{	

		$obj = new stdClass();
		$obj->name = 'Testproduct';
		$obj->key = 'T1';
		$obj->price = 1000;

		$this->load->library('presenter');
		$data['presenter'] = $this->presenter->create('example', $obj);


		$this->load->view('welcome_message', $data);


		//$this->load->helper('inflector');
		//$word = "this is the string_with_underscores"; die (camelize($word));
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
