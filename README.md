ci-presenter
============

CodeIgniter Presenter Library is a library that will add another layer of abstraction between the Controller and the views.

---

### LOADING THE LIBRARY

`$this->load->library('presenter');`

Although probably you want to auto-load this library since you're going to use it all the time.

---
### Using a object with the Presenter

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

---

### CREATING YOUR PRESENTER

Your presenter must always extend to the presenter library.


	<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
	class Example_Presenter extends Presenter {

	}
	
	/* End of file example_presenter.php */
	/* Location: ./application/presenters/example_presenter.php */
	
---

### AUTOMATICALLY CALLING YOUR METHODS

If you have your object with 3 properties, and you want to present each one in a different way. For that you just have to create your methods in your presenter with the prefix of **transform_**.

Example:

**Your object:**

	$obj = new stdClass();
	$obj->name = 'Testproduct';
	$obj->key = 'T1';
	$obj->price = 1000;

**The 3 methods in your presenter:**
	
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
	
### Looping your data and rendering html

At some point we all had a bunch of data that we needed to loop from begining to end and show it in a specific way. That can be achieved with the presenters.

**Imagine you have this object:**

	$objs = array();

	$obj = new stdClass();
	$obj->key = 'T1';
	$obj->name = 'Testproduct';
	$obj->price = 1000;
	array_push($objs, $obj);

	$obj = new stdClass();
	$obj->name = 'Second Testproduct';
	$obj->key = 'T2';
	$obj->price = 12345;
	array_push($objs, $obj);

	$obj = new stdClass();
	$obj->name = 'Third Testproduct';
	$obj->key = 'T3';
	$obj->price = 500;
	array_push($objs, $obj);
	
**You load your presenter with that object**

	$this->load->library('presenter');
	$data['presenter'] = $this->presenter->create('example', $objs);
	
**In your presenter you just have to do this:**

	<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Example_Presenter extends Presenter {

		public function list_items()
		{
			$html = '<p>#key# #name#<br> This item costs: #price#<p>';
			return $this->_generate_output($html);
		}
	}

You just have to wrap your items with a # and your html. Then you just need to return your data with the method called **_generate_output();**

Like any other presenter method this should use used in your view like this:

	<?= $presenter->list_items() ?>
	
---

### Special thanks go to:

* [Jamie Rumbelow](https://github.com/jamierumbelow) for giving the concept of presenters!
* [Marco Monteiro](https://github.com/mpmont) for doing all documentation / corrections! (yes, I'm a lazy ass ;) )
	
