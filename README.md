ci-presenter
============

CodeIgniter Presenter Library is a library that will add another layer of abstraction between the Controller and the views.

***LOADING THE LIBRARY***

`$this->load->library('presenter');`

Although probably you want to auto-load this library since you're going to use it all the time.

***Using a object with the Presenter***

	public function index()
	{	
		/*
		Creating your object
		Normally this would be a result from a model
		*/
		$obj = new stdClass();
		$obj->name = 'Testproduct';
		$obj->key = 'T1';
		$obj->price = 1000;

		$this->load->library('presenter');
		$data['presenter'] = $this->presenter->create('example', $obj);

		$this->load->view('welcome_message', $data);
	}

- The 1st param for the create method is the name of your presenter without the _presenter.php

- All presenters must be stored at: ./application/presenters/

- Your second param is the object with all the data that you need.


***CREATING YOUR PRESENTER*** 

Your presenter must always extend to the presenter library.


	<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
	class Example_Presenter extends Presenter {

	}
	
	/* End of file example_presenter.php */
	/* Location: ./application/presenters/example_presenter.php */
	