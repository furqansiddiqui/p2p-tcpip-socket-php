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

namespace FurqanSiddiqui\P2PSocket\Socket;

use FurqanSiddiqui\P2PSocket\Exception\P2PSocketException;
use FurqanSiddiqui\P2PSocket\P2PSocket;

/**
 * Class SocketResource
 * @package FurqanSiddiqui\P2PSocket\Socket
 */
class SocketResource
{
    /** @var P2PSocket */
    private P2PSocket $p2pSocket;
    /** @var resource */
    private $resource;

    /**
     * SocketResource constructor.
     * @param P2PSocket $p2pSocket
     * @param $socket
     */
    public function __construct(P2PSocket $p2pSocket, $socket)
    {
        if (!is_resource($socket)) {
            throw new \InvalidArgumentException('Argument is not a valid resource');
        }

        $this->p2pSocket = $p2pSocket;
        $this->resource = $socket;
    }

    /**
     * @return bool
     */
    public function setNonBlockMode(): bool
    {
        return @socket_set_nonblock($this->resource);
    }

    /**
     * @return bool
     */
    public function setBlockMode(): bool
    {
        return @socket_set_block($this->resource);
    }

    /**
     * @return resource
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * @return SocketLastError
     */
    public function lastError(): SocketLastError
    {
        return new SocketLastError($this->p2pSocket, $this);
    }

    /**
     * @param P2PSocket $p2pSocket
     * @return static
     * @throws P2PSocketException
     */
    public static function Create(P2PSocket $p2pSocket): self
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new P2PSocketException(
                (new SocketLastError($p2pSocket))->error2String('Failed to create socket')
            );
        }

        return new self($p2pSocket, $socket);
    }
}
