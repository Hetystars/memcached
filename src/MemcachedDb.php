<?php declare(strict_types=1);


namespace Swoft\Memcached;

use Swoft\Memcached\Connection\Connection;
use Swoft\Memcached\Connection\MemcachedConnection;
use Swoft\Memcached\Connector\MemcachedConnector;
use Swoft\Memcached\Contract\ConnectorInterface;
use Swoft\Memcached\Exception\MemcachedException;
use Swoft\Stdlib\Helper\Arr;
use function bean;

/**
 * Class MemcachedDb
 *
 * @since 2.0
 */
class MemcachedDb
{
    /**
     * Memcached
     */
    const MEMCACHED = 'memcached';

    /**
     * Memcache
     */
    const MEMCACHE = 'memcache';

    /**
     * @var string
     */
    private $driver = self::MEMCACHED;

    /**
     * @var string
     */
    private $persistentId;

    /**
     * @var int
     */
    private $receiveTimeout = 0;

    /**
     * @var int
     */
    private $sendTimeout = 0;

    /**
     * @var int
     */
    private $connectTimeout = 0;

    /**
     * @var int
     */
    private $retryTimeout = 10;

    /**
     * @var array
     */
    private $cacheDynamic;


    /**
     * @var int
     */
    private $distribution;

    /**
     * @var bool
     */
    private $optLibketamaCompatible = true;


    /**
     * Set client option.
     *
     * @var array
     *
     * @example
     * [
     *     'serializer ' => Memcached::OPT_SERIALIZER,
     *     'prefix' => 'xxx',
     * ]
     */
    private $option = [];

    /**
     * Set client option.
     *
     * @var array
     *
     * @example
     * [
     * ['127.0.0.1', 11211],
     *     ...
     * ]
     */
    private $cacheCluster = [];

    /**
     * @var array
     */
    private $connectors = [];

    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @param Pool $pool
     *
     * @return Connection
     * @throws MemcachedException
     */
    public function createConnection(Pool $pool): Connection
    {
        $connection = $this->getConnection();
        $connection->initialize($pool, $this);
        $connection->create();

        return $connection;
    }

    /**
     * @return ConnectorInterface
     * @throws MemcachedException
     */
    public function getConnector(): ConnectorInterface
    {
        $connectors = Arr::merge($this->defaultConnectors(), $this->connectors);
        $connector = $connectors[$this->driver] ?? null;

        if (!$connector instanceof ConnectorInterface) {
            throw new MemcachedException(sprintf('Connector(dirver=%s) is not exist', $this->driver));
        }

        return $connector;
    }

    /**
     * @return Connection
     * @throws MemcachedException
     */
    public function getConnection(): Connection
    {
        $connections = Arr::merge($this->defaultConnections(), $this->connections);
        $connection = $connections[$this->driver] ?? null;

        if (!$connection instanceof Connection) {
            throw new MemcachedException(sprintf('Connection(dirver=%s) is not exist', $this->driver));
        }

        return $connection;
    }

    /**
     * @return array
     */
    public function defaultConnectors(): array
    {
        return [
            self::MEMCACHED => bean(MemcachedConnector::class)
        ];
    }

    /**
     * @return array
     */
    public function defaultConnections(): array
    {
        return [
            self::MEMCACHED => bean(MemcachedConnection::class)
        ];
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return (string)$this->driver;
    }

    /**
     * @return int
     */
    public function getRetryTimeout(): int
    {
        return (int)$this->retryTimeout;
    }

    /**
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return (int)$this->connectTimeout;
    }

    /**
     * @return int
     */
    public function getReceiveTimeout(): int
    {
        return (int)$this->receiveTimeout;
    }

    /**
     * @return array
     */
    public function getCacheDynamic(): array
    {
        return (array)$this->cacheDynamic;
    }

    /**
     * @return int
     */
    public function getDistribution(): int
    {
        return (int)$this->distribution;
    }

    /**
     * @return bool
     */
    public function getOptLibketamaCompatible(): bool
    {
        return (bool)$this->optLibketamaCompatible;
    }

    /**
     * @return int
     */
    public function getSendTimeout(): int
    {
        return (int)$this->sendTimeout;
    }

    /**
     * @return array
     */
    public function getOption(): array
    {
        return (array)$this->option;
    }

    /**
     * @return array
     */
    public function getCacheCluster(): array
    {
        return (array)$this->cacheCluster;
    }

    /**
     * @return string
     */
    public function getPersistentId(): string
    {
        return (string)$this->persistentId;
    }
}
