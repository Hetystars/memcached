<?php declare(strict_types=1);


namespace Swoft\Memcached\Contract;

/**
 * Class ConnectorInterface
 *
 * @since 2.0
 */
interface ConnectorInterface
{
    /**
     * @param array $config
     * @param array $option
     *
     * @return Object
     */
    public function connect(array $config, array $option);

}
