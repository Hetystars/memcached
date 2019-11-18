<?php declare(strict_types=1);


namespace Swoft\Memcached\Connection;


use memcached;
use ReflectionException;
use Swoft;
use Swoft\Bean\BeanFactory;
use Swoft\Bean\Exception\ContainerException;
use Swoft\Connection\Pool\AbstractConnection;
use Swoft\Log\Helper\Log;
use Swoft\Memcached\Contract\ConnectionInterface;
use Swoft\Memcached\Exception\MemcachedException;
use Swoft\Memcached\MemcachedDb;
use Swoft\Memcached\MemcachedEvent;
use Swoft\Memcached\Pool;
use Throwable;
use function sprintf;

/**
 * Class Connection
 *
 * @since 2.0
 * @method static bool append(string $key, string $value)
 * @method static bool add(string $key, string $value, int $expiration)
 * @method static bool addByKey(string $server_key, string $key, mixed $value, int $expiration)
 * @method static bool addServer(string $host, int $port, int $weight)
 * @method static bool addServers(array $servers)
 * @method static bool appendByKey(string $server_key, string $key, string $value)
 * @method static bool cas(float $cas_token, string $key, mixed $value, int $expiration)
 * @method static array casByKey (float $cas_token, string $server_key, string $key, mixed $value, int $expiration)
 * @method static array decrement (string $key, int $offset = 1)
 * @method static array decrementByKey (string $server_key, string $key, int $offset = 1, int $initial_value = 0, int $expiry = 0)
 * @method static bool delete (string $key, int $time = 0)
 * @method static bool deleteByKey (string $server_key, string $key, int $time = 0)
 * @method static array deleteMulti(array $keys, int $time = 0)
 * @method static bool deleteMultiByKey(string $server_key, array $keys, int $time = 0)
 * @method static array fetch()
 * @method static array fetchAll()
 * @method static bool flush(int $delay = 0)
 * @method static array getAllKeys()
 * @method static array getByKey(string $server_key, string $key, callback $cache_cb, float &$cas_token)
 * @method static array getDelayed(array $keys, bool $with_cas, callback $value_cb)
 * @method static array getDelayedByKey (string $server_key, array $keys, bool $with_cas, callback $value_cb)
 * @method static mixed getMulti (array $keys, int $flags)
 * @method static mixed getMultiByKey (string $server_key, array $keys, string &$cas_tokens, int $flags)
 * @method static mixed getOption (int $option)
 * @method static int getResultCode()
 * @method static string getResultMessage()
 * @method static array getServerByKey(string $server_key)
 * @method static array getServerList()
 * @method static array getStats()
 * @method static array getVersion()
 * @method static int increment(string $key, int $offset = 1)
 * @method static int incrementByKey(string $server_key, string $key, int $offset = 1, int $initial_value = 0, int $expiry = 0)
 * @method static bool isPersistent()
 * @method static bool isPristine()
 * @method static bool prepend(string $key, string $value)
 * @method static bool prependByKey(string $server_key, string $key, string $value)
 * @method static bool quit()
 * @method static bool replace(string $key, mixed $value, int $expiration = null)
 * @method static bool replaceByKey(string $server_key, string $key, mixed $value, int $expiration = null)
 * @method static bool resetServerList()
 * @method static bool setByKey(string $server_key, string $key, mixed $value, int $expiration = null)
 * @method static bool setMulti(array $items, int $expiration = null)
 * @method static bool setMultiByKey(string $server_key, array $items, int $expiration = null)
 * @method static bool setOption(int $option, mixed $value)
 * @method static bool setOptions(int $options)
 * @method static bool setSaslAuthData(string $username, string $password)
 * @method static bool touch(string $key, int $expiration)
 * @method static bool touchByKey(string $server_key, string $key, int $expiration)
 */
abstract class Connection extends AbstractConnection implements ConnectionInterface
{
    /**
     * Supported methods
     *
     * @var array
     */
    protected $supportedMethods = [
        'get',
        'set',
        'delete',
        'append',
        'add',
        'addByKey',
        'addServer',
        'appendByKey',
        'cas',
        'casByKey',
        'decrement',
        'decrementByKey',
        'delete',
        'deleteByKey',
        'deleteMulti',
        'deleteMultiByKey',
        'fetch',
        'fetchAll',
        'flush',
        'getAllKeys',
        'getByKey',
        'getDelayed',
        'getDelayedByKey',
        'getMultiByKey',
        'getOption',
        'getResultCode',
        'getResultMessage',
        'getServerByKey',
        'getServerList',
        'getStats',
        'getVersion',
        'increment',
        'incrementByKey',
        'isPersistent',
        'isPristine',
        'prepend',
        'prependByKey',
        'quit',
        'replace',
        'replaceByKey',
        'resetServerList',
        'setByKey',
        'setMulti',
        'setMultiByKey',
        'setOption',
        'setOptions',
        'setSaslAuthData',
        'touch',
        'touchByKey'
    ];

    /**
     * @var Memcached
     */
    protected $client;

    /**
     * @var MemcachedDb
     */
    protected $memcachedDb;

    /**
     * @param Pool $pool
     * @param MemcachedDb $memcachedDb
     */
    public function initialize(Pool $pool, MemcachedDb $memcachedDb)
    {
        $this->pool = $pool;
        $this->memcachedDb = $memcachedDb;
        $this->lastTime = time();

        $this->id = $this->pool->getConnectionId();
    }

    /**
     * @throws MemcachedException
     */
    public function create(): void
    {
        $this->createClient();
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        $this->client->quit();
    }

    /**
     * @throws MemcachedException
     */
    public function createClient(): void
    {
        $config = [
            'persistent_id' => $this->memcachedDb->getPersistentId(),
            'receive_timeout' => $this->memcachedDb->getReceiveTimeout(),
            'connect_timeout' => $this->memcachedDb->getConnectTimeout(),
            'send_timeout' => $this->memcachedDb->getSendTimeout(),
            'retry_timeout' => $this->memcachedDb->getRetryTimeout(),
            'cache_cluster' => $this->memcachedDb->getCacheCluster(),
            'cache_dynamic' => $this->memcachedDb->getCacheDynamic(),
            'distribution' => $this->memcachedDb->getDistribution(),
            'optLibketamaCompatible' => $this->memcachedDb->getOptLibketamaCompatible(),
        ];

        $option = $this->memcachedDb->getOption();

        $this->client = $this->memcachedDb->getConnector()->connect($config, $option);
    }


    /**
     * Run a command against the memcached database. Auto retry once
     *
     * @param string $method
     * @param array $parameters
     * @param bool $reconnect
     *
     * @return mixed
     * @throws MemcachedException
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function command(string $method, array $parameters = [], bool $reconnect = false)
    {
        try {
            $lowerMethod = strtolower($method);
            if (!in_array($lowerMethod, $this->supportedMethods, true)) {
                throw new MemcachedException(
                    sprintf('Method(%s) is not supported!', $method)
                );
            }

            // Before event
            Swoft::trigger(MemcachedEvent::BEFORE_COMMAND, null, $method, $parameters);

            Log::profileStart('memcached.%s', $method);
            $result = $this->client->{$method}(...$parameters);
            Log::profileEnd('memcached.%s', $method);

            // After event
            Swoft::trigger(MemcachedEvent::AFTER_COMMAND, null, $method, $parameters, $result);

            // Release Connection
            $this->release();
        } catch (Throwable $e) {
            if (!$reconnect && $this->reconnect()) {
                return $this->command($method, $parameters, true);
            }

            throw new MemcachedException(
                sprintf('memcached command reconnect error(%s)', $e->getMessage())
            );
        }

        return $result;
    }

    /**
     * Run a command callback against the memcached database. Auto retry once
     *
     * @param callable $callback
     * @param bool $reconnect
     *
     * @return mixed
     * @throws ContainerException
     * @throws Throwable
     * @throws ReflectionException
     *
     * @example
     *         Uses eval script
     *         memcached::call(function(\memcached $memcached) {
     *              $memcached->eval("return {1,2,3,memcached.call('lrange','mylist',0,-1)}");*
     *              return $memcached->getLastError();
     *         });
     *
     */
    public function call(callable $callback, bool $reconnect = false)
    {
        try {
            Log::profileStart('memcached.%s', __FUNCTION__);
            $result = $callback($this->client);
            Log::profileEnd('memcached.%s', __FUNCTION__);
            // Release Connection
            $this->release();
        } catch (Throwable $e) {
            if (!$reconnect && $this->reconnect()) {
                return $this->call($callback, true);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * @param bool $force
     *
     */
    public function release(bool $force = false): void
    {
        /* @var ConnectionManager $conManager */
        $conManager = BeanFactory::getBean(ConnectionManager::class);
        $conManager->releaseConnection($this->id);

        parent::release($force);
    }

    /**
     * @param string $key
     *
     * @return bool|mixed If key didn't exist, FALSE is returned. Otherwise, the value
     *
     * @throws ContainerException
     * @throws MemcachedException
     * @throws ReflectionException
     */
    public function get(string $key)
    {
        $result = $this->command('get', [$key]);

        $hit = 0;
        if ($result !== false) {
            $hit = 1;
        }

        $name = $this->getCountingKey(__FUNCTION__);

        Log::counting($name, $hit, 1);
        return $result;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $timeout
     *
     * @return bool
     * @throws ContainerException
     * @throws MemcachedException
     * @throws ReflectionException
     */
    public function set(string $key, $value, int $timeout = null): bool
    {
        return $this->command('set', [$key, $value, $timeout]);
    }

    /**
     * @return bool
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function reconnect(): bool
    {
        try {
            $this->create();
        } catch (Throwable $e) {
            Log::error('memcached reconnect error(%s)', $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Pass other method calls down to the underlying client.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     * @throws MemcachedException
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function __call(string $method, array $parameters)
    {
        return $this->command($method, $parameters);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getCountingKey(string $name): string
    {
        return sprintf('memcached.hit/req.%s', $name);
    }

}
