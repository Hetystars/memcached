<?php declare(strict_types=1);


namespace Swoft\Memcached;

use Swoft\Bean\BeanFactory;
use Swoft\Memcached\Connection\Connection;
use Swoft\Memcached\Connection\ConnectionManager;
use Swoft\Memcached\Exception\MemcachedException;
use Throwable;

/**
 * Class Memcached
 *
 * @since 2.0
 *
 * @method static bool append(string $key, string $value)
 * @method static bool add(string $key, string $value, int $expiration = null)
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
 * @method static mixed get(string $key, callback $cache_cb = null, float &$cas_token = null)
 * @method static array getAllKeys()
 * @method static array getByKey(string $server_key, string $key, callback $cache_cb, float &$cas_token)
 * @method static array getDelayed(array $keys, bool $with_cas, callback $value_cb)
 * @method static array getDelayedByKey (string $server_key, array $keys, bool $with_cas, callback $value_cb)
 * @method static mixed getMulti (array $keys, int $flags = null)
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
 * @method static bool set(string $key, mixed $value, int $expiration = null)
 * @method static bool setByKey(string $server_key, string $key, mixed $value, int $expiration = null)
 * @method static bool setMulti(array $items, int $expiration = null)
 * @method static bool setMultiByKey(string $server_key, array $items, int $expiration = null)
 * @method static bool setOption(int $option, mixed $value)
 * @method static bool setOptions(int $options)
 * @method static bool setSaslAuthData(string $username, string $password)
 * @method static bool touch(string $key, int $expiration)
 * @method static bool touchByKey(string $server_key, string $key, int $expiration)
 */
class Memcached
{
    
    /**
     * @param string $pool
     *
     * @return Connection
     * @throws MemcachedException
     */
    public static function connection(string $pool = Pool::DEFAULT_POOL): Connection
    {
        try {
            /* @var ConnectionManager $conManager */
            $conManager = BeanFactory::getBean(ConnectionManager::class);

            /* @var Pool $memcachedPool */
            $memcachedPool = BeanFactory::getBean($pool);
            $connection = $memcachedPool->getConnection();

            $connection->setRelease(true);
            $conManager->setConnection($connection);
        } catch (Throwable $e) {
            throw new MemcachedException(
                sprintf('Pool error is %s file=%s line=%d', $e->getMessage(), $e->getFile(), $e->getLine())
            );
        }

        // Not instanceof Connection
        if (!$connection instanceof Connection) {
            throw new MemcachedException(
                sprintf('%s is not instanceof %s', get_class($connection), Connection::class)
            );
        }
        return $connection;
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws MemcachedException
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $connection = self::connection();
        return $connection->{$method}(...$arguments);
    }
}
