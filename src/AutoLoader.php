<?php declare(strict_types=1);


namespace Swoft\Memcached;


use Swoft\SwoftComponent;
use function bean;

/**
 * Class AutoLoader
 *
 * @since 2.0
 */
class AutoLoader extends SwoftComponent
{
    /**
     * @return array
     */
    public function getPrefixDirs(): array
    {
        return [
            __NAMESPACE__ => __DIR__,
        ];
    }

    /**
     * @return array
     */
    public function metadata(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function beans(): array
    {
        return [
            'memcached' => [
                'class' => MemcachedDb::class,
                'option' => [
                ],
            ],
            'memcached.pool' => [
                'class' => Pool::class,
                'memcachedDb' => bean('memcached'),
            ]
        ];
    }
}
