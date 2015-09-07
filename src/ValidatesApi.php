<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Support\Facades\Validator;

/**
 * Class ValidatesApi
 * @package DesiCochrane\ApiTesting
 */
trait ValidatesApi
{

    /**
     * @var string
     */
    protected $paginationNamespaceKey = '';

    /**
     *
     */
    public function setup()
    {
        parent::setUp();

        Validator::extendImplicit('not_present', function ($attribute, $value, $parameters) {
            return is_null($value);
        });
        Validator::extend('strict_boolean', function ($attribute, $value, $parameters) {
            return is_bool($value);
        });
    }

    /**
     * @param array $rules
     * @param string $namespace
     * @return $this
     */
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

    /**
     * @param array $rules
     * @param null $key
     * @return $this
     */
    protected function seeItem(array $rules, $key = null)
    {
        static::assertValidItem($this->getValueAtKey($key), $rules);

        return $this;
    }

    /**
     * @return array
     */
    protected function jsonResponseAsArray()
    {
        return json_decode($this->response->getContent(), true);
    }

    /**
     * @param $namespace
     * @return $this
     */
    protected function underPaginationNamespace($namespace)
    {
        $this->paginationNamespaceKey = $namespace;

        return $this;
    }

    /**
     * @param $total
     * @param string $key
     * @return ValidatesApi
     */
    protected function seeTotalResults($total, $key = 'total')
    {
        return $this->seePagination($key, $total);
    }

    /**
     * @param $page
     * @param string $key
     * @return ValidatesApi
     */
    protected function seeCurrentPage($page, $key = 'current_page')
    {
        return $this->seePagination($key, $page);
    }

    /**
     * @param $perPage
     * @param string $key
     * @return ValidatesApi
     */
    protected function seeResultsPerPage($perPage, $key = 'per_page')
    {
        return $this->seePagination($key, $perPage);
    }

    /**
     * @param $perPage
     * @param string $key
     * @return ValidatesApi
     */
    protected function seeTotalPages($perPage, $key = 'last_page')
    {
        return $this->seePagination($key, $perPage);
    }

    /**
     * @param $key
     * @param $value
     * @return ValidatesApi
     */
    protected function seePagination($key, $value)
    {
        $resolvedKey = $this->resolvePaginationKey($key);

        return $this->seeValueAtKey($resolvedKey, $value);
    }

    /**
     * @param $key
     * @return string
     */
    protected function resolvePaginationKey($key)
    {
        return implode('.', array_filter([$this->paginationNamespaceKey, $key]));
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    protected function seeValueAtKey($key, $value)
    {
        $valueAtKey = $this->getValueAtKey($key);
        PHPUnit::assertEquals($valueAtKey, $value, "Failed asserting that key `$key` equals `$value`");

        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getValueAtKey($key = null)
    {
        if (is_null($key)) return $this->jsonResponseAsArray();

        static::assertArrayKeyExists($this->jsonResponseAsArray(), $key);

        return array_get($this->jsonResponseAsArray(), $key);
    }

    /**
     * @param $key
     * @param $array
     */
    protected static function assertArrayKeyExists($array, $key)
    {
        return PHPUnit::assertTrue(array_has($array, $key), "Failed asserting that key `$key` exists");
    }

    /**
     * @param array $rules
     * @param array $item
     */
    protected static function assertValidItem($item, array $rules)
    {
        $validator = Validator::make($item, $rules);

        PHPUnit::assertTrue($validator->passes(), $validator->errors());
    }
}