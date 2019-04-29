<?php

namespace hiqdev\recon\core\Service;

use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Class AmqpBindingsProvider
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
final class AmqpBindingsProvider
{
    /**
     * @var \Closure[] array of closures, that bind routing keys to a right queue.
     * Expected closure signature is:
     *
     * ```php
     * function (PhpAmqpLib\Channel\AMQPChannel $channel): void {
     *    // Bind here, e.g.
     *    $channel->queue_declare('recon.queue', false, true, false, false);
     *    $channel->exchange_declare('dbms.updates', 'topic', false, true, true, false, false);
     *    $channel->queue_bind('recon.queue', 'dbms.updates', 'bot.dns.*');
     * }
     * ```
     */
    public $bindings = [];

    public function bind(AMQPChannel $channel): void
    {
        foreach ($this->bindings as $bindTo) {
            $bindTo($channel);
        }
    }
}
