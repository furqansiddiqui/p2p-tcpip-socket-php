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
    private P2PSocket $master;
    /** @var SocketResource */
    private SocketResource $socket;
    /** @var bool */
    private bool $connected;
    /** @var string */
    private string $name;
    /** @var string */
    private string $ip;
    /** @var int */
    private int $port;
    /** @var PeerFlags Arbitrary bitwise flags */
    private PeerFlags $flags;
    /** @var PeerData Arbitrary data */
    private PeerData $data;
    /** @var string */
    private string $recvBuffer;

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
        $this->connected = true;
        $this->ip = $ip;
        $this->port = $port;
        $this->name = sprintf('%s:%d', $this->ip, $this->port);
        $this->socket = $peer;
        $this->flags = new PeerFlags();
        $this->data = new PeerData();
        $this->recvBuffer = "";
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
     * @return bool
     */
    public function status(): bool
    {
        return $this->connected;
    }

    /**
     * @param int $length
     * @return array|null
     * @throws PeerReadException
     */
    public function read(int $length = 1024): ?array
    {
        $buffer = "";
        $recv = @socket_recv($this->socket->resource(), $buffer, $length, MSG_DONTWAIT);
        if ($recv === false) {
            $socketLastErr = $this->socket()->lastError();
            if ($socketLastErr->code === 11) {
                return []; // Check if its temporarily unavailable (still connected but no data to read!)
            }

            throw new PeerReadException(
                $this,
                $socketLastErr->error2String(sprintf('Failed to read peer "%s"', $this->name))
            );
        } elseif ($recv === 0) {
            // Connection is no longer valid, remote has been disconnected
            $this->connected = false;
            $this->master->peers()->remove($this);
            $this->master->events()->onPeerDisconnect()->trigger([$this]);
            return null;
        }

        // Append to existing buffer
        $this->recvBuffer .= $buffer;

        // Check if delimiter char exists
        if (strpos($this->recvBuffer, $this->master->delimiter) === false) { // No delimiter char?
            return []; // Keep buffer intact, no complete messages to return yet!
        }

        $recvBuffer = explode($this->master->delimiter, $this->recvBuffer);
        $this->recvBuffer = array_pop($recvBuffer); // Last incomplete message now stays in recvBuffer

        return $recvBuffer; // Return complete messages (delimited by delimiter char)
    }

    /**
     * Get incomplete message in recv buffer
     * @return string|null
     */
    public function bufferPendingMsg(): ?string
    {
        if ($this->recvBuffer) {
            return $this->recvBuffer;
        }

        return null;
    }

    /**
     * Cleans pending recv buffer
     */
    public function bufferClean(): void
    {
        $this->recvBuffer = "";
    }

    /**
     * @param string $message
     * @throws PeerWriteException
     */
    public function send(string $message): void
    {
        $send = @socket_write($this->socket->resource(), $message . $this->master->delimiter);
        if ($send === false) {
            // Note: cannot use "$this->socket()->lastError()" here because "resource" is already gone
            throw new PeerWriteException($this, sprintf('Failed to write to peer "%s"', $this->name));
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
        $this->connected = false;
        $this->master->peers()->remove($this); // Remove from peers list

        // Trigger to Event Listeners
        $this->master->events()->onPeerDisconnect()->trigger([$this]);
    }

    /**
     * @return SocketResource
     */
    public function socket(): SocketResource
    {
        return $this->socket;
    }

    /**
     * @return PeerFlags
     */
    public function flags(): PeerFlags
    {
        return $this->flags;
    }

    /**
     * @return PeerData
     */
    public function data(): PeerData
    {
        return $this->data;
    }
}
