<?php declare(strict_types=1);


namespace Swoft\Memcached;

/**
 * Class MemcachedEvent
 *
 * @since 2.0
 */
class MemcachedEvent
{
    /**
     * Before command
     */
    const BEFORE_COMMAND = 'swoft.memcached.command.before';

    /**
     * After command
     */
    const AFTER_COMMAND = 'swoft.memcached.command.after';
}
