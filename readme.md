## Helpers for API testing in Laravel

### Usage
    composer require desicochrane/laravel-api-testing
    

For a demo, check out this [presentation](https://prezi.com/-bceczai8pmn/lumen-fractal/)
    
    
### Harness the power of Laravel's Validator to write tests like this:

```php
use DesiCochrane\ApiTesting\ValidatesApi;

class CafeApiTest extends TestCase
{

    use ValidatesApi;

    /** @test */
    public function it_shows_a_paginated_index_of_all_cafes()
    {
        $this->get('/api/v2/cafes')
            ->seeStatusCode(200)

            // Put the pagination data in a meta: { pagination: {...} } namespace
            ->seeCurrentPage(1, 'meta.pagination.current_page')
            ->seeTotalItems(100, 'meta.pagination.total')
            ->seePerPage(15, 'meta.pagination.per_page')
            ->seeCollection([
                'id' => ['required', 'integer'],
                'name' => ['required', 'string'],
                'description' => ['required', 'string'],
                'address' => ['required', 'string'],

                // - Make these nested in the response under the `food_options` namespace
                // - Ensure actual boolean output and not truthy/falsy integers
                'food_options.vegan' => ['required', 'strict_boolean'],
                'food_options.vegetarian' => ['required', 'strict_boolean'],

                // These should not be visible
                'created_at' => ['not_present'],
                'updated_at' => ['not_present'],
                'photos' => ['not_present'],
            ]);
    }
```

Which will test green for this:

```javascript
{
  "data": [
    {
        "id": 14,
        "name": "Durgan, Herzog and Jacobs",
        "description": "Suscipit enim. Earum facilis consectetur non sed ipsam oditaut.",
        "address": "8784 Legros Track\nWest Marion, VA 12804-8004",
        "food_options": {
            "vegan": false,
            "vegetarian": true
        },
        "photos": {
            "data": [
                {
                    "id": 87,
                    "path": "http://lorempixel.com/400/650/?16981"
                },
                {
                    "id": 88,
                    "path": "http://lorempixel.com/400/650/?15181"
                },
            ]
        }
    }
  ],
  "meta": {
      "pagination": {
          "total": 100,
          "count": 15,
          "per_page": 15,
          "current_page": 1,
          "total_pages": 7,
          "links": {
              "next": "http://laravel.app/api/v2/cafes/?page=2"
          }
      }
  }
}
```
