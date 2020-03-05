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

use FurqanSiddiqui\P2PSocket\Exception\PeerConnectException;
use FurqanSiddiqui\P2PSocket\Exception\PeerException;
use FurqanSiddiqui\P2PSocket\Exception\PeerReadException;
use FurqanSiddiqui\P2PSocket\Exception\PeerWriteException;
use FurqanSiddiqui\P2PSocket\P2PSocket;
use FurqanSiddiqui\P2PSocket\Socket\SocketResource;

/**
 * Class Peer
 * @package FurqanSiddiqui\P2PSocket\Peers
 */
class Peer
{
    /** @var P2PSocket */
    private $master;
    /** @var SocketResource */
    private $socket;
    /** @var string */
    private $name;
    /** @var string */
    private $ip;
    /** @var int */
    private $port;

    /**
     * Peer constructor.
     * @param P2PSocket $p2pSocket
     * @param SocketResource $peer
     * @param int $num
     * @throws PeerConnectException
     */
    public function __construct(P2PSocket $p2pSocket, SocketResource $peer, int $num)
    {
        if (!@socket_getpeername($peer->resource(), $ip, $port)) {
            throw new PeerConnectException(
                $peer->lastError()->error2String(sprintf('A new peer connection (#%d) failed', $num))
            );
        }

        $this->master = $p2pSocket;
        $this->ip = $ip;
        $this->port = $port;
        $this->name = sprintf('%s:%d', $this->ip, $this->port);
        $this->socket = $peer;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function ip(): string
    {
        return $this->ip;
    }

    /**
     * @return int
     */
    public function port(): int
    {
        return $this->port;
    }

    /**
     * @param int $length
     * @return string
     * @throws PeerReadException
     */
    public function read(int $length = 1024): string
    {
        $buffer = "";
        $recv = @socket_recv($this->socket->resource(), $buffer, $length, MSG_DONTWAIT);
        if ($recv === false) {
            $socketLastErr = $this->socket()->lastError();
            if ($socketLastErr->code === 11) {
                return ""; // Check if its temporarily unavailable (still connected but no data to read!)
            }

            throw new PeerReadException(
                $this,
                $socketLastErr->error2String(sprintf('Failed to read peer "%s"', $this->name))
            );
        } elseif ($recv === 0) {
            // Connection is no longer valid, remote has been disconnected
            $this->master->events()->onPeerDisconnect()->trigger([$this]);
        }

        return $buffer;
    }

    /**
     * @param string $message
     * @throws PeerWriteException
     */
    public function send(string $message): void
    {
        $send = @socket_write($this->socket->resource(), $message);
        if ($send === false) {
            throw new PeerWriteException(
                $this,
                $this->socket->lastError()->error2String(sprintf('Failed to write to peer "%s"', $this->name))
            );
        }
    }

    /**
     * @param bool $suppressExceptions
     * @throws PeerException
     */
    public function disconnect(bool $suppressExceptions = false): void
    {
        // Shutdown socket
        $shutdown = @socket_shutdown($this->socket->resource(), 2);
        if (!$shutdown) {
            if (!$suppressExceptions) {
                throw new PeerException($this->socket->lastError()->error2String(
                    sprintf('Failed to shutdown socket to peer "%s"', $this->name)
                ));
            }
        }

        @socket_close($this->socket->resource());
    }

    /**
     * @return SocketResource
     */
    public function socket(): SocketResource
    {
        return $this->socket;
    }
}
