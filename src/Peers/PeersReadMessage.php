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
 * Class PeersReadMessage
 * @package FurqanSiddiqui\P2PSocket\Peers
 */
class PeersReadMessage
{
    /** @var Peer */
    private Peer $peer;
    /** @var string */
    private string $message;

    /**
     * PeersReadMessage constructor.
     * @param Peer $peer
     * @param string $message
     */
    public function __construct(Peer $peer, string $message)
    {
        $this->peer = $peer;
        $this->message = $message;
    }

    /**
     * @return Peer
     */
    public function peer(): Peer
    {
        return $this->peer;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
