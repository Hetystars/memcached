<?php declare(strict_types=1);


namespace SwoftTest\Redis\Unit\Command;


use Swoft\Memcached\Memcached;
use SwoftTest\Memcached\Unit\TestCase;

/**
 * Class StringTest
 *
 * @since 2.0
 */
class StringTest extends TestCase
{
    public function testSet()
    {
        $key    = \uniqid();
        $result = Memcached::set($key, \uniqid());
        $this->assertTrue($result);

        $ttl    = 100;
        $ttlKey = \uniqid();
        Memcached::set($ttlKey, uniqid(), $ttl);

        Memcached::set($key, json_encode(['a']), 111);
        Memcached::get($key);
    }

    public function testGet()
    {
        $value = \uniqid();
        $key   = $this->setKey($value);

        $getValue = Memcached::get($key);

        $this->assertEquals($value, $getValue);
    }

    public function testArray()
    {
        $key = \uniqid();

        $setData = [
            'goods' => ['goods_id' => 1, 'goods_name' => 'iPhone xx']
        ];
        Memcached::set($key, $setData);

        $this->assertEquals($setData, Memcached::get($key));
    }

    public function testMsetAndMget()
    {
        $key    = ':mset:' . \uniqid();
        $value  = [\uniqid()];
        $key2   = ':mset:' . \uniqid();
        $value2 = [\uniqid()];

        $keys = [
            $key  => $value,
            $key2 => $value2,
        ];

        $result = Memcached::setMulti($keys);
        $this->assertTrue($result);


        $resultVlue  = Memcached::get($key);
        $resultVlue2 = Memcached::get($key2);

        $this->assertEquals($value, $resultVlue);
        $this->assertEquals($value2, $resultVlue2);

        $values = Memcached::getMulti([$key, $key2, 'key3']);

        $this->assertEquals(count($values), 2);
        $this->assertEquals($values, $keys);
    }
}
