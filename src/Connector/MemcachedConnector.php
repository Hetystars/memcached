<?php declare(strict_types=1);


namespace Swoft\Memcached\Connector;

use Memcached;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Memcached\Contract\ConnectorInterface;
use Swoft\Memcached\Exception\MemcachedException;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * Class MemcachedConnector
 *
 * @since 2.0
 *
 * @Bean()
 */
class MemcachedConnector implements ConnectorInterface
{
    /**
     * @var array
     */
    public $optionMap = [
        'receive_timeout' => Memcached::OPT_RECV_TIMEOUT,
        'connect_timeout' => Memcached::OPT_CONNECT_TIMEOUT,
        'send_timeout' => Memcached::OPT_SEND_TIMEOUT,
        'retry_timeout' => Memcached::OPT_RETRY_TIMEOUT,
        'distribution' => Memcached::OPT_DISTRIBUTION,
        'optLibketamaCompatible' => Memcached::OPT_LIBKETAMA_COMPATIBLE,
    ];

    /**
     * @param array $config
     * @param array $option
     * @return Memcached
     * @throws MemcachedException
     */
    public function connect(array $config, array $option): Memcached
    {
        $client = $config['persistent_id'] ? new Memcached($config['persistent_id']) : new Memcached();
        $this->establishConnection($client, $config, $option);

        return $client;
    }

    /**
     * @param Memcached $client
     * @param array $config
     * @param array $option
     * @throws MemcachedException
     */
    protected function establishConnection(Memcached $client, array $config, array $option): void
    {
        $serverList = $client->getServerList();
        if (empty($serverList)) {
            if ($this->setOption($client, $config, $option)) {
                return;
            }
            $servers = $config['cache_cluster'];
            $rs = $client->addServers($servers);
            if ($rs) {
                $versions = $client->getVersion();
                $validNodes = 0;
                foreach ($versions as $node => $version) {
                    if (!empty($version) && $version !== '255.255.255') {
                        ++$validNodes;
                    }
                }
                if ($validNodes >= \count($servers)) {
                    return;
                } else if ($validNodes > 0) {
                    return;
                } else if ($validNodes <= 0) {
                    throw new MemcachedException(
                        sprintf('memcached connect error(%s)', JsonHelper::encode($config, JSON_UNESCAPED_UNICODE))
                    );
                }
            }
        }
    }

    /**
     * @param Memcached $client
     * @param array $config
     * @param array $option
     * @return bool
     */
    private function setOption(Memcached $client, array $config, array $option): bool
    {
        array_walk($this->optionMap, function ($item, $key) use ($client, $config) {
            if (!empty($config[$key])) {
                $client->setOption($item, $config[$key]);
            }
        });

        if (!empty($option['prefix'])) {
            $client->setOption(Memcached::OPT_PREFIX_KEY, $option['prefix']);
        }

        if (!empty($option['serializer'])) {
            $client->setOption(Memcached::OPT_SERIALIZER, (string)$option['serializer']);
        }

        if (defined('\Memcached::OPT_CLIENT_MODE')) {
            if (isset($config['cache_dynamic'])) {
                //AWS Auto Discovery
                $cache_dynamic = $config['cache_dynamic'];
                if (!empty($cache_dynamic)) {
                    $client->setOption(Memcached::OPT_CLIENT_MODE, Memcached::DYNAMIC_CLIENT_MODE);
                    $rs = $client->addServer($cache_dynamic[0], $cache_dynamic[1]);
                    if ($rs) {
                        return true;
                    }
                }
            }
            //dynamic client failed, switch to static mode
            $client->setOption(Memcached::OPT_CLIENT_MODE, Memcached::STATIC_CLIENT_MODE);
        }
        return false;
    }
}
