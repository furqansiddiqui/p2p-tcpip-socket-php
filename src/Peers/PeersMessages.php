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

namespace FurqanSiddiqui\P2PSocket\Peers;

/**
 * Class PeersMessages
 * @package FurqanSiddiqui\P2PSocket\Peers
 */
class PeersMessages implements \Iterator, \Countable
{
    /** @var array */
    private array $messages;
    /** @var int */
    private int $count;
    /** @var int */
    private int $pos;

    /**
     * PeersMessages constructor.
     */
    public function __construct()
    {
        $this->messages = [];
        $this->count = 0;
        $this->pos = 0;
    }

    /**
     * @param PeersReadMessage $msg
     */
    public function append(PeersReadMessage $msg): void
    {
        $this->messages[] = $msg;
        $this->count++;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->pos = 0;
    }

    /**
     * @return PeersReadMessage
     */
    public function current(): PeersReadMessage
    {
        return $this->messages[$this->pos];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->pos;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->pos;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->messages[$this->pos]);
    }
}
