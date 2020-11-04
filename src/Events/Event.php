<?php
/**
 * This file is a part of "furqansiddiqui/p2p-tcpip-socket-php" package.
 * https://github.com/furqansiddiqui/p2p-tcpip-socket-php
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/p2p-tcpip-socket-php/blob/master/LICENSE
 */

declare(strict_types=1);

namespace FurqanSiddiqui\P2PSocket\Events;

/**
 * Class Event
 * @package FurqanSiddiqui\P2PSocket\Events
 */
class Event
{
    /** @var EventRegister */
    private EventRegister $register;
    /** @var string */
    private string $name;
    /** @var array */
    private array $listeners;

    /**
     * Event constructor.
     * @param EventRegister $register
     * @param string $name
     */
    public function __construct(EventRegister $register, string $name)
    {
        if (!preg_match('/^[\w\-.]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid event name');
        }

        $this->register = $register;
        $this->name = $name;
        $this->listeners = [];
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return EventRegister
     */
    public function register(): EventRegister
    {
        return $this->register;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function listen(callable $callback): self
    {
        $this->listeners[] = $callback;
        return $this;
    }

    /**
     * @param array|null $params
     * @return int
     */
    public function trigger(?array $params = null): int
    {
        if (!$this->listeners) {
            return 0;
        }

        $params = $params ?? [];
        array_push($params, $this);
        $count = 0;
        foreach ($this->listeners as $listener) {
            call_user_func_array($listener, $params);
            $count++;
        }

        return $count;
    }
}
