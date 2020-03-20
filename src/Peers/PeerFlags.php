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

namespace FurqanSiddiqui\P2PSocket\Peers;

/**
 * Class PeerFlags
 * @package FurqanSiddiqui\P2PSocket\Peers
 */
class PeerFlags
{
    /** @var int */
    private $flags;

    /**
     * PeerFlags constructor.
     */
    public function __construct()
    {
        $this->flags = 0;
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function set(int $flag): self
    {
        $this->flags = $this->flags | $flag;
        return $this;
    }

    /**
     * @param int $flag
     * @return bool
     */
    public function has(int $flag): bool
    {
        return ($this->flags & $flag) ? true : false;
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function remove(int $flag): self
    {
        $this->flags &= ~$flag;
        return $this;
    }
}
