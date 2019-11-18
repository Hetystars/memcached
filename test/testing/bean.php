<?php

return [
    'config' => [
        'path' => __DIR__ . '/../config',
    ],
    'memcached' => [
        'class' => \Swoft\Memcached\MemcachedDb::class,
        'cacheCluster' => [['127.0.0.1', 11211]],
        'connectTimeout' => 10,
        'sendTimeout' => 10,
        'retryTimeout' => 10,
        'receiveTimeout' => 10,
        'distribution' => Memcached::OPT_DISTRIBUTION,
        'optLibketamaCompatible' => true,
        'option' => [
            'prefix' => 'swoft-t_x',
        ],
    ],
    'memcached.pool' => [
        'class' => \Swoft\Memcached\Pool::class,
        'minActive' => 10,
        'maxActive' => 20,
        'maxWait' => 0,
        'maxWaitTime' => 0,
        'maxIdleTime' => 60,
    ],
];
