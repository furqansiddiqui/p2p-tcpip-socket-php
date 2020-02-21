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

/**
 * Class SocketResource
 * @package FurqanSiddiqui\P2PSocket\Socket
 */
class SocketResource
{
    /** @var resource */
    private $resource;

    /**
     * SocketResource constructor.
     * @param $socket
     */
    public function __construct($socket)
    {
        if (!is_resource($socket)) {
            throw new \InvalidArgumentException('Argument is not a valid resource');
        }

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
        return new SocketLastError($this);
    }

    /**
     * @param bool $debug
     * @return static
     * @throws P2PSocketException
     */
    public static function Create(bool $debug = false): self
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) {
            throw new P2PSocketException(
                (new SocketLastError())->error2String('Failed to create socket', $debug)
            );
        }

        return new self($socket);
    }
}
