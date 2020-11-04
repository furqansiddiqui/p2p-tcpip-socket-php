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

namespace FurqanSiddiqui\P2PSocket\Exception;

use FurqanSiddiqui\P2PSocket\Peers\Peer;
use Throwable;

/**
 * Class PeerCommunicateException
 * @package FurqanSiddiqui\P2PSocket\Exception
 */
class PeerCommunicateException extends P2PSocketException
{
    /** @var Peer */
    private Peer $peer;

    /**
     * PeerCommunicateException constructor.
     * @param Peer $peer
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(Peer $peer, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->peer = $peer;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Peer
     */
    public function peer(): Peer
    {
        return $this->peer;
    }
}

