<?php declare(strict_types=1);


namespace Swoft\Memcached\Contract;

/**
 * Class ConnectionInterface
 *
 * @since 2.0
 */
interface ConnectionInterface
{
    /**
     * Create client
     */
    public function createClient(): void;


    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * @param string   $key
     * @param mixed    $value
     * @param int|null $timeout
     *
     * @return bool
     */
    public function set(string $key, $value, int $timeout = null): bool;


}
