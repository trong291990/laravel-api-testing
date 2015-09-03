<?php
namespace DesiCochrane\ApiTesting;

use PHPUnit_Framework_Assert as Assertion;
use Illuminate\Support\Facades\Validator;

trait ValidatesApi
{

    public function setup()
    {
        parent::setUp();

        Validator::extendImplicit('not_present', function ($attribute, $value, $parameters) {
            return is_null($value);
        });
        Validator::extendImplicit('strict_boolean', function ($attribute, $value, $parameters) {
            return is_bool($value);
        });
    }

    /**
     * @param array $rules
     * @param string $jsonKey
     * @return $this
     */
    protected function seeCollection(array $rules, $jsonKey = 'data')
    {
        foreach ($this->getValueAtKey($jsonKey) as $item) {
            Assertion::assertTrue(is_array($item), "Valid collection not found at key `$jsonKey`");
            static::assertValid($rules, $item);
        }

        return $this;
    }

    /**
     * @param array $rules
     * @param null $jsonKey
     * @return $this
     */
    protected function seeItem(array $rules, $jsonKey = null)
    {
        static::assertValid($rules, $this->getValueAtKey($jsonKey));

        return $this;
    }

    /**
     * @return array
     */
    protected function responseJson()
    {
        return json_decode($this->response->getContent(), true);
    }


    protected function seeCurrentPage($page, $key = 'current_page')
    {
        $this->seeValueAtKey($key, (int) $page, "Failed asserting current page `$key` equals `$page`");

        return $this;
    }

    protected function seeTotalItems($total, $key = 'total')
    {
        $this->seeValueAtKey($key, (int) $total, "Failed asserting total items `$key` equals `$total`");

        return $this;
    }

    protected function seePerPage($perPage, $key = 'per_page')
    {
        $this->seeValueAtKey($key, (int) $perPage, "Failed asserting items per page `$key` equals `$perPage`");

        return $this;
    }

    protected function seeValueAtKey($key, $value, $message = '')
    {
        Assertion::assertEquals($this->getValueAtKey($key), $value, $message);

        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getValueAtKey($key = null)
    {
        if (is_null($key)) return $this->responseJson();

        static::assertArrayHasFlatKey($key, $this->responseJson());

        return array_get($this->responseJson(), $key);
    }

    /**
     * @param $key
     * @param $array
     */
    protected static function assertArrayHasFlatKey($key, $array)
    {
        return Assertion::assertTrue(array_has($array, $key), "Failed asserting that key `$key` exists");
    }

    /**
     * @param array $rules
     * @param array $resourceData
     */
    protected function assertValid(array $rules, array $resourceData)
    {
        $validator = Validator::make($resourceData, $rules);

        Assertion::assertTrue($validator->passes(), $validator->errors());
    }
}