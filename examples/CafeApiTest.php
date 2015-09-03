<?php

use DesiCochrane\ApiTesting\ValidatesApi;

class CafeApiTest extends TestCase
{

    use ValidatesApi;

    /** @test */
    public function it_shows_a_paginated_index_of_all_cafes()
    {
        // new endpoint for backwards compatibility
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
                'photos' => ['not_present'], // No photos for the index page!
            ]);
    }

    /** @test */
    public function it_shows_photos_in_cafe_index_if_specified()
    {
        // query string to include extra output in response
        $this->get('/api/v2/cafes?include=photos')
            ->seeStatusCode(200)
            ->seeCollection([
                'photos' => ['required'], // photos now required!
            ])
        ->seeCollection([
            'id' => ['required', 'integer'],
            'path' => ['required', 'url']
        ], 'data.0.photos.data'); // Only check the first one for near-enough testing
    }

    /** @test */
    public function it_shows_a_single_cafe_page()
    {
        $this->get('/api/v2/cafes/1')
            ->seeStatusCode(200)
            ->seeItem([
                'id' => ['required', 'integer'],
                'name' => ['required', 'string'],
                'description' => ['required', 'string'],
                'address' => ['required', 'string'],
                'food_options.vegan' => ['required', 'strict_boolean'],
                'food_options.vegetarian' => ['required', 'strict_boolean'],
                'created_at' => ['not_present'],
                'updated_at' => ['not_present'],
            ])

            // Validate the api nested collection, with second argument indicating the key
            ->seeCollection([
                'id' => ['required', 'integer'],
                'path' => ['required', 'url']
            ], 'photos.data');
    }
}