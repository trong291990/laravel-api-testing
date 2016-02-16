---
layout: post
title: "Better Api Testing in Laravel"
subtitle: "Harnessing Laravel's Validation and Array Helpers for Faster REST Api Workflow"
date: 2015-09-05
author: "Desi Cochrane"
---
So you're working on your REST api and you kind of feel like you _should_ be testing your api, but it's just not
working for you in a way that really flows.

This article is for you if you identify with any of the following:

- You're not sure how to test your REST api well, or you don't really see the benefit.
- Your REST api test workflow feels awkward and you don't feel like you're getting enough value out of your tests for
the time you are spending on them.
- You want more flexibility and specificity in your REST api testing than Laravel offers out of the box.
- You're at work and you need to have a full screen of beautiful looking code so you can look like your busy.

<strong>tl;dr - Laravel's array and validation helpers can be a powerful aid to test your JSON api. I've put all the
  code
  here into a package - the repository can be found [on github here](https://github.com/desicochrane/laravel-api-testing).</strong>

### Why End-to-End Test Your REST Api?

The goal of any testing framework should be to:

1. Flexibly test the resources and collections in your RESTful api with uber specifity;
2. Speed up your workflow a billion-fold with a terse, expressive syntax and a fast, meaningful feedback loop;
3. Guide the design of your architecture, interface, and implementation
4. Ultimately help you to achieve code zen.

Importantly, your api responses are different to your html/markup responses because it is likely that the json api
response will be directly consumed as the input for some client service. As such it is important that it behaves
predictably to a much higher granularity than your html views, and thus should be backed up by tests just as any other
module or component you would write.

Also, a good test suite for your api is a really nice piece of document for your team to refer to as you build out
your app/service. Often when doing "backend" work, I will sit down with the person doing the "frontend" work and before
anything else we will write out the tests and design the api messaging. As the project develops and features are
added, having the test suite up-to-date acts as "live documentation", and in my opinion trumps (or at
least acts as a solid compliment to) services like [Swagger](http://swagger.io/) etc.


### The Scenario

The imaginary scenario we will be assuming for this article is building up a RESTful api for a cafe app. To
focus on the testing helpers being discussed, we will stick to an over-simplified example where we are
concerning ourselves with only two endpoints, namely:

1. <strong>GET /cafes</strong> - the _index_ endpoint for fetching a paginated list representation of the state of all
cafes; and
2. <strong>GET /cafes/{id}</strong> - the _show_ endpoint for fetching a single, that is, getting a representation of
the state of a
single cafe with the matching {id}.

We'll be assuming Laravel 5.1 and we'll be testing with Laravel's <code>TestCase</code> class, which gives us a nice
layer on top of PHPUnit. That said, I have personally used the principles in this article with in older versions of
Laravel, Symfony, vanilla php and with other testing systems - from [Behat](http://docs.behat.org/) to
[TestFrameInATweet]
(https://gist
.github.com/mathiasverraes/9046427).

<strong>Disclaimer:</strong> the example api implementation shown in this article is deliberately minimalistic.
Also, the tests here are "end-to-end" and would probably compliment a richer underlying suite of unit/integration tests
for your
domain.

With all of that out of the way - consider the following:

{% highlight php %}
<?php // app/Http/routes.php

get('/cafes', function() {
    return App\Cafe::paginate();
});
{% endhighlight %}

Jumping to the browser, we will see an output like this:

{% highlight javascript %}
// http://laravel.app/cafes
{
  "total": 100,
  "per_page": 15,
  "current_page": 1,
  "last_page": 7,
  "next_page_url": "http://laravel.app/cafes/",
  "prev_page_url": null,
  "from": 1,
  "to": 15,
  "data": [
  {
    "id": 1,
    "name": "Eichmann, Hartmann and Collins",
    "rating": 2,
    "vegan_options": 1,
    "vegetarian_options": 0,
    "created_at": "2015-09-01 22:18:00",
    "updated_at": "2015-09-01 22:18:00"
  },
  // ...
}
{% endhighlight %}

So with very few lines of code we get a reasonably nice REST api basically out of the box with Laravel. But at least
for me, there are some immediate things I can see with the current implementation that are bothersome:

1. A lot of the output is not really necessary. For example `from`, `to`, and `last page`.
These values can be derived from the data itself, and unless I find a convincing reason otherwise I try not to break
the [Rule of Silence](http://www.linfo.org/rule_of_silence.html) wherever possible.
2. I feel like I might want to output a more appropriately namespaced (nested) json, for example I would like to keep
 `vegan_options` and `vegetarian_options` under a `food_options` namespace. Also I prefer to keep my pagination
 metadata nested under a `meta` namespace.
3. I am not happy with `0` and `1` being used as truthy/falsey substitutes for <code>true</code> and <code>false</code>.
4. The output is tightly coupled to my database structure with the `updated_at` and `created_at` fields revealing too
much for my taste.

And there are probably a whole lot of other criticisms that could be made regarding this output - the point is that
we *do* want to change it.

Whenever you are about to change something you should cringe at the thought of having to refresh a browser to check if
things are working every time a change is made. That kind of monkey work just slows
us
down, and if I can automate the "checking to see if things are working" workflow then you can bet that I will, and
that is what the whole point of this article is really about.

<div class="quote">
  "You should cringe at the thought of refreshing a browser to check things work every
  time a change
  is made."
</div>

### Testing Specific Key:Values

Out of the box, Laravel offers [some really nice testing features](http://laravel.com/docs/master/testing) that make
it really straightforward to test your app. There are a even couple of [built in helpers to to test your JSON Apis](http://laravel.com/docs/master/testing#testing-json-apis),
however for me, they just don't provide me enough power and flexibility.

Let's say that for the cafe index endpoint <strong>GET /cafes</strong>, I want to make an assertion that a specific key
has a
specific value - for example I want to assert that the `total` is 100. Let's start by writing the code
as though we were living in an ideal world:

{% highlight php %}
<?php // tests/CafesApiTest.php

class CafesApiTest extends TestCase
{

     /** @test */
     public function it_shows_a_paginated_index_of_all_cafes()
     {
         $this->get('/cafes')

        // Assert the specific key:value { "total": 100 } exists
        // in JSON response - this method does not exist yet, we will
        // need to create it
        ->seeValueAtKey('total', 100);
     }
}
{% endhighlight %}

That is probably how I would like it to be coded. Of course the `seeValueAtKey()` method doesn't actually
exist yet - we will need to create it - but a good workflow to getting your api "feeling right" is to write your code
in a way that you might guess it would work in an ideal world and then go ahead and make the world a little more ideal
by writing the implementation. This is what those unix guys are preaching when they talk about the [Rule of Least
Surprise](https://en.wikipedia.org/wiki/Principle_of_least_astonishment).

<div class="quote">"First act as if you were working in an ideal world, and then go ahead and make the
  world a little more ideal."</div>

Here is an implementation I prepared earlier which will get things working:

{% highlight php %}
<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as PHPUnit;

trait ValidatesApi
{

    protected function seeValueAtKey($key, $value, $message = '')
    {
        $valueAtKey = $this->getValueAtKey($key);
        PHPUnit::assertEquals($valueAtKey, $value, $message);

        return $this;
    }

    protected function getValueAtKey($key = null)
    {
        // If no key is given, return the entire response JSON as array
        if (is_null($key)) return $this->responseAsArray();

        // We can leverage Laravel's handy array functions, this will
        // give us the additional benefit of querying for nested keys
        // in our api response by dot notation e.g. 'meta.pagination.total'
        static::assertArrayKeyExists($this->responseAsArray(), $key);

        return array_get($this->responseAsArray(), $key);
    }

    protected function responseAsArray()
    {
        // The protected $this->response field will be available to us
        // courtesy of the \Illuminate\Foundation\Testing\CrawlerTrait
        $responseJson = $this->response->getContent();

        return json_decode($responseJson, true);
    }

    protected static function assertArrayKeyExists($key, $array)
    {
        PHPUnit::assertTrue(
        array_has($array, $key), "Failed asserting that key `$key` exists."
        );
    }
}
{% endhighlight %}

The nice thing here is, because we are using Laravel's handy [array helper](http://laravel.com/docs/4
.2/helpers#arrays) functions we can now be very specific with testing nested key:value pairs simply by using dot
notation.
For example if I wanted
to
specifically assert
that under the `"data"` namespaced array of cafes, the first should contain a `"name"` key with the value of
`"Eichmann, Hartmann and Collins"`, then I can now do so easily as follows:

{% highlight php %}
<?php // tests/CafesApiTest.php

class CafesApiTest extends TestCase
{

     /** @test */
     public function it_shows_a_paginated_index_of_all_cafes()
     {
         $this->get('/cafes')
         ->seeStatusCode(200)
         ->seeValueAtKey('total', 100)

         // we can now make assertions on nested keys using dot notation
         ->seeValueAtKey('data.0.name', "Eichmann, Hartmann and Collins");
     }
}
{% endhighlight %}

### Pimping our Pagination Testing

Now that we have a nice way to make assertions on our specific key:values on our JSON, let's add some more helpers
that will speed up our test workflow, starting with testing our pagination.

Right now, we could use our existing helpers to test pagination like so:

{% highlight php %}
<?php // tests/CafesApiTest.php

class CafesApiTest extends TestCase
{

     /** @test */
     public function it_shows_correct_pagination_for_index_page()
     {
         $this->get('/cafes')
        ->seeValueAtKey('total', 100)
        ->seeValueAtKey('current_page', 1)
        ->seeValueAtKey('per_page');
     }
}
{% endhighlight %}

And that would do the trick - except that in the back of my mind I hear [Tim
Roughgarden](http://theory.stanford.edu/~tim/) asking me "Can we do better?".

And yes Tim, I think we can.

I am pretty picky when it comes to my workflow. Here are some criticism of this solution:

- I like to get the maximum boost I can out of my editor. Autocomplete, type hinting, error linting (etc.) all result
in me being faster and typing less. One of the best ways to maximise your editor is to help it to help you by having
an api that is more specific and abstracts details away.
- I don't like having to explicitly set the keys here when the app could potentially have smart defaults which could
be overriden.
- I prefer a more expressive api, one that does what it says. Not only does this help the editor, but it helps me to
remember or guess the api, which again saves time.
- If the pagination is nested, I don't want to have a lot of duplication with the dot notation.

Here is maybe something I would feel more comfortable with:

{% highlight php %}
<?php // tests/CafesApiTest.php

 class CafesApiTest extends TestCase
 {

      /** @test */
      public function it_shows_correct_pagination_for_index_page()
      {
          $this->get('/cafes')
          // Allow flexible pagination namespacing, in this case we
          // want pagination data under a meta: { pagination: {...} } namespace
          ->underPaginationNamespace('meta.pagination')

          // If the key isn't specified, the api should use smart defaults
          ->seeTotalResults(100)
          ->seeResultsPerPage(15)

          // Second optional argument allows to override default pagination keys
          ->seeCurrentPage(1, 'current')
          ->seeTotalPages(7, 'total_pages');
      }
}
{% endhighlight %}

That's better. As before, we haven't yet written these methods, so now extend our api to make that
work.

{% highlight php %}
<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as PHPUnit;

trait ValidatesApi
{

    // protected field to allow the pagination namespacing to be configurable
    // while defaulting to an empty value to work out of the box with
    // basic Laravel Jsonification of paginated collections
    protected $paginationNamespaceKey = '';

    // ...

    protected function underPaginationNamespace($namespace)
    {
        $this->paginationNamespaceKey = $namespace;

        return $this;
    }

    protected function seeTotalResults($total, $key = 'total')
    {
        return $this->seePagination($key, $total);
    }

    protected function seeTotalPages($perPage, $key = 'last_page')
    {
        return $this->seePagination($key, $perPage);
    }

    protected function seeCurrentPage($page, $key = 'current_page')
    {
        return $this->seePagination($key, $page);
    }

    protected function seeResultsPerPage($perPage, $key = 'per_page')
    {
        return $this->seePagination($key, $perPage);
    }
        
    protected function seePagination($key, $value)
    {
        $resolvedKey = $this->resolvePaginationKey($key);
        
        return $this->seeValueAtKey($resolvedKey, $value);
    }
        
    protected function resolvePaginationKey($key)
    {
        // some magic using built in php array functions to join the pagination
        // namespace to the key using dot notation, taking into consideration empty ''
        // pagination namespace values
        return implode('.', array_filter([$this->paginationNamespaceKey, $key]));
    }
}
{% endhighlight %}

That should do it. Now if we were to actually run this test now with our current implementation PHPUnit would
give an error with the message:

{% highlight bash %}
There was 1 failure:

1) CafeApiTest::it_shows_correct_pagination_for_index_page
Failed asserting that key `meta.pagination.current` exists.
Failed asserting that false is true.
{% endhighlight %}

Which makes sense, because our current implementation does not namespace our pagination output, and does not change
the default current page key that laravel provides.

So this is actually pretty nice, already we can see our tests are giving us
error messages that are easy to understand and act upon. So let's update our implementation to get our updated test to
green.

{% highlight php %}
<?php // app/Http/routes.php

get('/cafes', function() {
    /* @var Illuminate\Pagination\LengthAwarePaginator $paginatedCafes */
    $paginatedCafes = App\Cafe::paginate();

    return [
        "meta" => [
            "pagination" => [
                "total" => $paginatedCafes->total(),
                "current" => $paginatedCafes->currentPage(),
                "per_page" => $paginatedCafes->perPage(),
                "total_pages" => $paginatedCafes->lastPage(),
            ]
        ]
    ];
});
{% endhighlight %}

And that gets us to green! If we were to check the output in the browser it would look like this:

{% highlight javascript %}
// http://laravel.app/cafes
{
  "meta": {
    "pagination": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 7
    }
  }
}
{% endhighlight %}

Okay! That's not bad, we now have a pretty flexible and reusable way to test our api pagination, and the we have a
terse and expressive api that gives meaningful errors.

### Pimping our Resource Testing

Now, obviously we are not satisfied - our previous tests are green even though we aren't returning anything besides
pagination!

Laravel comes shipped with a pretty darn good validation functionality which is primarily used by most to validate
form input, however under the hood the validator works on the input in keu:value array form, knowing
this we can use the power of Laravel's validation to "validate" our api json.

So let's write a test using Laravel's validation api as we might expect it could be written in an ideal world:

{% highlight php %}
<?php // tests/CafesApiTest.php

 class CafesApiTest extends TestCase
 {
      // ...

      /** @test */
      public function it_shows_a_collection_of_cafes_on_index_page()
      {
          $this->get('/cafes')
          ->seeCollection([
              'id' => ['required', 'integer'],
              'name' => ['required', 'string'],
              'rating' => ['required', 'integer'],
              'vegan_options' => ['required', 'boolean'],
              'vegetarian_options' => ['required', 'boolean'],
          ]);
      }
}
{% endhighlight %}

This is what I would like to have working, it should be smart enough to work out of the box with laravels default
jsonification of an eloquent model, which by default will be an array of the serialized models namespaced under a
'data' key.

Let's write the code to make this work and configurable.

{% highlight php %}
<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Support\Facades\Validator;

trait ValidatesApi
{

    // ...

    protected function seeCollection(array $rules, $namespace = 'data')
    {
        // Get the collection array from the response by the namespace
        $collection = $this->getValueAtKey($namespace);

        // Assert an array is present at the given namespace
        PHPUnit::assertTrue(
            is_array($collection),
            "Valid collection not found at key `$namespace`"
        );

        foreach ($collection as $item) {
            static::assertValidItem($item, $rules);
        }

        return $this;
    }

    protected static function assertValidItem($item, array $rules)
    {
        $validator = Validator::make($item, $rules);

        PHPUnit::assertTrue($validator->passes(), $validator->errors());
    }
}
{% endhighlight %}

And without too much complication, we now have the full power of laravel's validation for out api testing. Of course
if we were to run this now, it will fail with our current implementation:

{% highlight bash %}
There was 1 failure:

1) CafeApiTest::it_shows_a_collection_of_cafes_on_index_page
Failed asserting that key `data` exists.
Failed asserting that false is true.
{% endhighlight %}

Again, the error message is quite useful, we should be able to get this green without much trouble:

{% highlight php %}
<?php // app/Http/routes.php

get('/cafes', function() {
    /* @var Illuminate\Pagination\LengthAwarePaginator $paginatedCafes */
    $paginatedCafes = App\Cafe::paginate();

    return [
        "meta" => [
            "pagination" => [
                "total" => $paginatedCafes->total(),
                "current" => $paginatedCafes->currentPage(),
                "per_page" => $paginatedCafes->perPage(),
                "total_pages" => $paginatedCafes->lastPage(),
            ]
        ],
        // Adding this line gets us back to green
        "data" => $paginatedCafes->items()
    ];
});
{% endhighlight %}

We run the test now, and viola, we get green! A quick check in the browser confirms things are working:

{% highlight javascript %}
{
    "meta": {
        "pagination": {
            "total": 100,
            "current": 1,
            "per_page": 15,
            "total_pages": 7
        }
    },
    "data": [
        {
            "id": 1,
            "name": "Eichmann, Hartmann and Collins",
            "rating": 2,
            "vegan_options": 1,
            "vegetarian_options": 0,
            "created_at": "2015-09-01 22:18:00",
            "updated_at": "2015-09-01 22:18:00"
        },
        // ...
    ]
}
{% endhighlight %}

To quickly illustrate how powerful our tests now are, imagine the scenario where we decide that our "rating" should
no longer be an integer, but rather a categorical value limited to "bad", "average" and "good". Well, in true TDD
nature lets update our tests first leaning on laravels validator to do the heavy lifting. Looking at the [validation
docs](http://laravel.com/docs/5.1/validation#available-validation-rules) we can find the <code>in</code> rule which
seems like it would satisfy our requirements nicely.

{% highlight php %}
<?php // tests/CafesApiTest.php

 class CafesApiTest extends TestCase
 {
      // ...

      /** @test */
      public function it_shows_a_collection_of_cafes_on_index_page()
      {
          $this->get('/cafes')
          ->seeCollection([
              'id' => ['required', 'integer'],
              'name' => ['required', 'string'],

              // use the 'in' rule to specify allowed values
              'rating' => ['required', 'in:bad,average,good'],

              'vegan_options' => ['required', 'boolean'],
              'vegetarian_options' => ['required', 'boolean'],
          ]);
      }
}
{% endhighlight %}

If we run this, as expected, we get the following error output:

{% highlight bash %}
There was 1 failure:

1) CafeApiTest::it_shows_a_collection_of_cafes_on_index_page
{"rating":["The selected rating is invalid."]}
Failed asserting that false is true.
{% endhighlight %}

Notice how powerful this is, and how useful this error message is - right out of the box. Before moving on, let's get
this green. A quick way might be to use Eloquent's `getXXXAttribute()` method to modify how the field value when it
is accessed, scaling the value to correspond to map to an array.

{% highlight php %}
<?php
namespace App;

class Cafe extends Illuminate\Database\Eloquent\Model
{
    private $ratings = ['bad', 'average', 'good'];

    public function getRatingAttribute()
    {
        return $this->ratings[$this->attributes['rating'] - 1];
    }
}
{% endhighlight %}

And with that, we are back at green. So this is nice, we can easily test our api collections. Let's now move on to
the single `show` endpoint <strong> GET /cafes/{id}</strong> starting with the test of course.

{% highlight php %}
<?php // tests/CafesApiTest.php

 class CafesApiTest extends TestCase
 {
      // ...

      /** @test */
      public function it_shows_a_single_cafe_page()
      {
          $this->get('/cafes/1')
          ->seeItem([
              'id' => ['required', 'integer'],
              'name' => ['required', 'string'],
              'rating' => ['required', 'in:bad,average,good'],
              'vegan_options' => ['required', 'boolean'],
              'vegetarian_options' => ['required', 'boolean'],
          ]);
      }
}
{% endhighlight %}

It is now trivial to implement the <code>seeItem()</code> method in our test trait:

{% highlight php %}
<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Support\Facades\Validator;

trait ValidatesApi
{

    // ...

    protected function seeItem(array $rules, $key = null)
    {
        static::assertValidItem($this->getValueAtKey($key), $rules);

        return $this;
    }
}
{% endhighlight %}

Easy. If we run this now it would fail because we haven't set up the route. But getting it to pass is as easy as:

{% highlight php %}
<?php // app/Http/routes.php

// ...

get('/cafes/{id}', function($id) {
    return App\Cafe::find($id);
});
{% endhighlight %}
And we're back to green. A quick check in the browser shows the following output:
{% highlight javascript %}
{
  "id": 1,
  "name": "Eichmann, Hartmann and Collins",
  "rating": "average",
  "vegan_options": 1,
  "vegetarian_options": 0,
  "created_at": "2015-09-01 22:18:00",
  "updated_at": "2015-09-01 22:18:00"
}
{% endhighlight %}

### Can We Do Better?

Looking at the above output, there are some obvious smells that are bothering me.

1. The `updated_at` and `created_at` fields are present, even though we are not checking for them in our test. It is
not only a bad practice to expose our database fields like this, but also, I just don't want to be showing this data in
my api as I feel it won't be relevant to the client. Unfortunately, out of the box, laravel validator does not
allow us to assert a value is not present.
2. The `vegan_options` and `vegetarian_options` should be boolean, and indeed we are specifying this in our validation,
but the laravel validator will check this as truthy/falsy and thus it is passing despite being integer values, it
would be nice if we could specify these be strict boolean in our tests.
3. I prefer to more religiously apply namespacing to my REST apis where it makes sense, and I would like to have the
`vegan_options` and `vegetarian_options` under a `food_options` namespace.

As usual, lets write our tests assuming an ideal world, and then figure out how to get it working.

 {% highlight php %}
 <?php // tests/CafesApiTest.php

 class CafesApiTest extends TestCase
 {
      // ...

      /** @test */
      public function it_shows_a_single_cafe_page()
      {
          $this->get('/cafes/1')
          ->seeItem([
              'id' => ['required', 'integer'],
              'name' => ['required', 'string'],
              'rating' => ['required', 'in:bad,average,good'],

              // Assert these nested under the `food_options` namespace
              // Assert actual boolean output and not truthy/falsy integers
              'food_options.vegan' => ['required', 'strict_boolean'],
              'food_options.vegetarian' => ['required', 'strict_boolean'],

              // These should not be visible
              'created_at' => ['not_present'],
              'updated_at' => ['not_present'],
          ]);
      }
}
{% endhighlight %}

The namespacing should already work with the code we already wrote since laravel's array helpers support dot notation.
However the `strict_boolean` and the
`not_present` validation rules are not provided by laravel out of the box, we will need to implement these ourselves
as follows.

{% highlight php %}
<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Support\Facades\Validator;

trait ValidatesApi
{

    // ...

    public function setup()
    {
        parent::setUp();

        // explain the implicit thing
        Validator::extendImplicit('not_present',
            function ($attribute, $value, $parameters) {
                return is_null($value);
            }
        );

        Validator::extend('strict_boolean',
            function ($attribute, $value, $parameters) {
                return is_bool($value);
            }
        );
    }
}
{% endhighlight %}

That should get the test to do what they should, if we run this it fails with the following.

{% highlight bash %}
There was 1 failure:

1) CafeApiTest::it_shows_a_single_cafe_page
{"food_options.vegan":["The food options.vegan field is required."],"food_options.vegetarian":["The food options.vegetarian field is required."],"created_at":["validation.not_present"],"updated_at":["validation.not_present"]}
Failed asserting that false is true.
Failed asserting that false is true.
{% endhighlight %}

Hopefully, by now you are seeing the value and power of these tests. Let's get this passing.

{% highlight php %}
<?php // app/Http/routes.php

// ...

get('/cafes/{id}', function($id) {
    $cafe = App\Cafe::find($id);

    return [
        'id' => $cafe->id,
        'name' => $cafe->name,
        'rating' => $cafe->rating,
        'food_options' => [
            'vegan' => (bool) $cafe->vegan_options,
            'vegetarian' => (bool) $cafe->vegetarian_options,
        ]
    ];
});
{% endhighlight %}

And with that, we're back to green for a final output of:
{% highlight javascript %}
// http://laravel.app/cafe/1
{
    "id": 1,
    "name": "Eichmann, Hartmann and Collins",
    "rating": "average",
    "food_options": {
        "vegan": true,
        "vegetarian": false
    }
}
{% endhighlight %}

### Conclusions

So this has been a crash course in describing on the very very beginnings of building out helpers for your testing
suite. Obviously this is very incomplete, but hopefully encourages the right attitude of incrementally improving your
testing codebase to make testing a little more accessible to your apps.



