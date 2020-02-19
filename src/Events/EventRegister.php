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
 * Class EventRegister
 * @package FurqanSiddiqui\P2PSocket\Events
 */
class EventRegister
{
    /** @var array */
    private $events;

    /**
     * EventsRegister constructor.
     */
    public function __construct()
    {
        $this->events = [];
    }

    /**
     * @param string $event
     * @return Event
     */
    public function on(string $event): Event
    {
        $event = strtolower($event);
        if (array_key_exists($event, $this->events)) {
            return $this->events[$event];
        }

        return $this->events[$event] = new Event($this, $event);
    }

    /**
     * @param string $event
     * @return bool
     */
    public function has(string $event): bool
    {
        return array_key_exists(strtolower($event), $this->events);
    }

    /**
     * @param Event $event
     */
    public function clear(Event $event): void
    {
        unset($this->events[strtolower($event->name())]);
    }
}