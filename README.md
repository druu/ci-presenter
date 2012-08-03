ci-presenter
============

CodeIgniter Presenter Library is a library that will add another layer of abstraction between the Controller and the views.
# Getting ready
### Load the library

Just load it Like any other library:

    $this->load->library('presenter');

Although probably you want to auto-load this library since you're going to use it all the time.

---

### Implementing your own Presenter Class

Your presenter must always extend the presenter library.


	<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	
	class Example_Presenter extends Presenter {

	}
	
	/* End of file example_presenter.php */
	/* Location: ./application/presenters/example_presenter.php */
	
That's the most basic presenter you can have !

Make sure all your presenters files are in `./application/presenters` and end with `_presenter.php`

---

### Creating a presenter object
**In your controller:**

	public function index()
	{	
		// Creating your object
		// Normally this would be a result from a model
		
		$obj = new stdClass();
		$obj->name = 'Testproduct';
		$obj->key = 'T1';
		$obj->price = 1000;

		// Load the library
		$this->load->library('presenter');
		
		// Let the library create a Presenter for you
		$data['presenter'] = $this->presenter->create('example', $obj);
		
		// Pass it to the view
		$this->load->view('welcome_message', $data);
	}

- The 1st param for the create method is the name of your presenter without the _presenter.php
- Your second param is the object with all the data that you need.
	
# Using the Presenter on a single object
### The plain basics

With the example above you can start ptinting data in your views like this:

    <p>Key: <?= $presenter->key ?></p>
    
This will result in

    <p>Key: T1</p>
    
That's it. Well yeah, there's no benefit in using a presenter over just outputting the actual data… But let some black magic in and you'll see the benefit pretty soon!

---

### Introducing: Transformation methods

If you have your object with 3 properties, and you want to present each one in a different way. For that you just have to create your methods in your presenter with the prefix of **transform_**.

Example:

**Your object:**

	$obj = new stdClass();
	$obj->name = 'Testproduct';
	$obj->key = 'T1';
	$obj->price = 1000;

**The 3 transformation methods in your presenter:**

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
	
Let's explain this a bit:

* To every property of our data, we have a matching transformation method
* Transformation methodnames allways look like `transform_<insert propertyname here>`
* A transformation method's only parameter is the value of the according property
* The `transform_name` method is a bit different, we'll come to that later.

**Your view hasn't changed:**

	<p>Name: <?= $presenter->key ?></p>

**But now the outcome is**
	
    <p>Key: <b>T1</b></p>

Nice, isn't it? Yes, it is. But wait. There's more!

---
### Getting the RAW data
As you might have noticed, your object's data will be allways be 'transformed'. That's totally okay most of the time. But what to do in case you really, really want to get the RAW data?  
Nothing easier than that: just append `_raw` to your property's name.

**Example:**
 
    <p>Name: <?= $presenter->key_raw ?></p>
    
**Output:**

    <p>Key: T1</p>
    
---
### Presenting custom data
So you now have your presenter which is presenting all its data nice and comfy.  
All of a sudden you want to output some custom data with the transformation of one of your properties.  

**Well then, go on:**

    <p>Name: <?= $presenter->key('Custom') ?></p>
    
**Turns out to be:**

    <p>Key: <b>Custom</b></p>
    
Now, what the hell happened?  
The Presenter Library has a nifty little feature, which allows you to use your data's properties as a function. This will call the according transformation method with the parameter you passed.

You could call `$presenter->transform_key('Custom')` too, but let's save some typing here and just call the property ;)

--- 

### Combining and transforming 
Remember the `transform_name` method from above? I told you we'll come back to it, so here it is again:

	public function transform_name($name)
	{
		return $name ? $this->key('T2').' '.$name : 'N/A';
	}

So what's happening here? Basically we concat the name of our product with a custom transformed key. That's one way to combine data. 

Let's make this a bit more sexy. With **VIRTUAL MAPPINGS**  
At first we add a little array to our presenter class:

**Example_Presenter**

    public $v_map = array(
        'key_and_name' => array('key', 'name')
    );
    
The array key `key_and_name` is the name of our new virtual property. The assigned array contains the actual properties to be used. Now we need the transformation method. Easy stuff from now on.

    public function transform_key_and_name($key, $name)
	{
		return 'This is a Virtual Transformation function returning the raw key "'.$key.'" and name "'.$name.'" of our example.';
	}

**Call it in your view**

    <?= $presenter->key_and_name ?>
    
**And out comes:**
	
    This is a Virtual Transformation function returning the raw key "T1" and name "Testproduct" of our example.'
    
Tadaaaaa :)

# Using the presenter on a collection of objects
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

### Looping your data and rendering html WITH PARTIALS

Allright, we want to get rid of that HTML within our presenter. So let's take a look at *partials*. In this example we use the same object collection as above and create the presenter object, which will be passed to the view.
But instead of creating a method in our presenter that contains the HTML-Snippet we do this:

**Create the partial**

First we create our partial file: `/application/views/partials/example/list_products.php`
There are a few rules to follow, which allow to autoload the partials:

* All partials go into `/application/views/partials`
* If our presenter's class is `Example_Presenter`, we have to create a subfolder called `example` in our partials directory
* Within this directory the partials cann be called whatever you like. Just make sure all filenames end with `.php`

Let's make an easy partial containing only this peace of code

    <p>Listing: Product #name# costs #price# </p>

Note that all properties of our object look like this. `#property_name#`
This allows the presenter to replace the keys with our properties' values.
And if we have matching transformation methods, they will be called too! Even on virtual properties.

So all we have to do now is getting our output.
In your view just put this:

    <?= $presenter->partial('list_products) ?>

Et voilà, the included magic, and white rabbits have done the rest for you, and **present** you with some nice output.

Tadaaaa.

Also, don't forget to watch the screencast on how to use the ci-presenter library. [Vimeo](https://vimeo.com/43767192)


---

### Special thanks go to:

* [Jamie Rumbelow](https://github.com/jamierumbelow) for giving the concept of presenters!
* [Marco Monteiro](https://github.com/mpmont) for doing all documentation / corrections! (yes, I'm a lazy ass ;) )

