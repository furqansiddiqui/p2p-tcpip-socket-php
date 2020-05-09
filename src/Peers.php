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

namespace FurqanSiddiqui\P2PSocket;

use FurqanSiddiqui\P2PSocket\Exception\PeerConnectException;
use FurqanSiddiqui\P2PSocket\Exception\PeerReadException;
use FurqanSiddiqui\P2PSocket\Exception\PeerWriteException;
use FurqanSiddiqui\P2PSocket\Peers\Peer;
use FurqanSiddiqui\P2PSocket\Peers\PeersMessages;
use FurqanSiddiqui\P2PSocket\Peers\PeersReadMessage;
use FurqanSiddiqui\P2PSocket\Socket\SocketResource;

/**
 * Class Peers
 * @package FurqanSiddiqui\P2PSocket
 */
class Peers
{
    /** @var P2PSocket */
    private $p2pSocket;
    /** @var int */
    private $count;
    /** @var array */
    private $peers;
    /** @var array */
    private $ip2PeerMap;

    /**
     * Peers constructor.
     * @param P2PSocket $p2pSocket
     */
    public function __construct(P2PSocket $p2pSocket)
    {
        $this->p2pSocket = $p2pSocket;
        $this->count = 0;
        $this->peers = [];
        $this->ip2PeerMap = [];
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->peers;
    }

    /**
     * @param string $ip
     * @return array
     */
    public function ip2Peers(string $ip): array
    {
        return $this->ip2PeerMap[$ip] ?? [];
    }

    /**
     * @throws PeerConnectException
     */
    public function accept(): void
    {
        if (!$this->p2pSocket->socket()) {
            throw new PeerConnectException('Cannot use Peers::accept method, socket server was never created');
        }

        $num = $this->count + 1;
        $peerSocket = @socket_accept($this->p2pSocket->socket()->resource());
        if ($peerSocket) {
            $peer = new Peer($this->p2pSocket, new SocketResource($this->p2pSocket, $peerSocket), $num);
            $this->peerIsConnected($peer);
        }
    }

    /**
     * @param string $remotePeerAddr
     * @param int $port
     * @param int $timeOut
     * @throws Exception\P2PSocketException
     * @throws PeerConnectException
     */
    public function connect(string $remotePeerAddr, int $port, ?int $timeOut = null): void
    {
        if (!filter_var($remotePeerAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new PeerConnectException('Invalid remote IPv4 peer address');
        }

        if ($port < 0x3e8 || $port > 0xffff) {
            throw new PeerConnectException('Invalid remote peer port');
        }

        $socket = SocketResource::Create($this->p2pSocket);

        try {
            if (!$timeOut) {
                if (!@socket_connect($socket->resource(), $remotePeerAddr, $port)) {
                    throw new PeerConnectException(
                        $socket->lastError()->error2String(
                            sprintf('Peer connection to "%s" on port %d failed', $remotePeerAddr, $port)
                        )
                    );
                }

                $peer = new Peer($this->p2pSocket, $socket, ($this->count + 1));
                $this->peerIsConnected($peer);
                return;
            }

            // With timeout
            $socket->setNonBlockMode();
            $connected = false;
            $maxAttempts = $timeOut * 100;
            $attempt = 0;
            while (true) {
                if ($attempt >= $maxAttempts) {
                    break;
                }

                $attempt++;
                $connected = @socket_connect($socket->resource(), $remotePeerAddr, $port);
                if (!$connected) {
                    $lastError = socket_last_error($socket->resource());
                    if ($lastError !== SOCKET_EINPROGRESS && $lastError !== SOCKET_EALREADY) {
                        throw new PeerConnectException(
                            $socket->lastError()->error2String(
                                sprintf('Peer connection to "%s" on port %d failed (timeOut: %d)', $remotePeerAddr, $port, $timeOut)
                            )
                        );
                    }

                    usleep(10000); // sleep 1/100 of a second
                }
            }

            $socket->setBlockMode();
            if (!$connected) {
                throw new PeerConnectException(
                    sprintf('Connection timed out at %ds to peer "%s" on port %d', $timeOut, $remotePeerAddr, $port)
                );
            }

            $peer = new Peer($this->p2pSocket, $socket, ($this->count + 1));
            $this->peerIsConnected($peer);
            return;
        } catch (PeerConnectException $e) {
            @socket_close($socket->resource());
            throw $e;
        }
    }

    /**
     * @param string $message
     * @param callable|null $failPeerCallback
     * @return int
     * @throws PeerWriteException
     */
    public function broadcast(string $message, ?callable $failPeerCallback = null): int
    {
        $sent = 0;
        /** @var Peer $peer */
        foreach ($this->peers as $peerName => $peer) {
            try {
                $peer->send($message);
                $sent++;
            } catch (PeerWriteException $e) {
                if ($failPeerCallback) {
                    call_user_func_array($failPeerCallback, [$peer]);
                    continue;
                }

                throw $e;
            }
        }

        return $sent;
    }

    /**
     * @param int $length
     * @param callable|null $failPeerCallback
     * @return PeersMessages
     * @throws PeerReadException
     */
    public function read(int $length = 1024, ?callable $failPeerCallback = null): PeersMessages
    {
        $messages = new PeersMessages();

        /** @var Peer $peer */
        foreach ($this->peers as $peerName => $peer) {
            try {
                $peerMsgs = $peer->read($length);
                if ($peerMsgs) {
                    foreach ($peerMsgs as $peerMsg) {
                        if ($peerMsg) {
                            $messages->append(new PeersReadMessage($peer, $peerMsg));
                        }
                    }
                }
            } catch (PeerReadException $e) {
                if ($failPeerCallback) {
                    call_user_func_array($failPeerCallback, [$peer]);
                    continue;
                }

                throw $e;
            }
        }

        return $messages;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->peers);
    }

    /**
     * Removes a peer from register WITHOUT DISCONNECTING
     * @param Peer $peer
     */
    public function remove(Peer $peer): void
    {
        if (array_key_exists($peer->name(), $this->peers)) {
            unset($this->peers[$peer->name()]);
            $this->count--;

            $ipPeers = $this->ip2Peers($peer->ip());
            unset($ipPeers[array_search($peer->port(), $ipPeers)]);
            $this->ip2PeerMap[$peer->ip()] = array_unique($ipPeers, SORT_NUMERIC);
        }
    }

    /**
     * @param Peer $peer
     */
    private function peerIsConnected(Peer $peer): void
    {
        $this->peers[$peer->name()] = $peer;
        $this->count++;

        // Map multiple ports from same IP
        $ipPeers = $this->ip2Peers($peer->ip());
        $ipPeers[] = $peer->port();
        $this->ip2PeerMap[$peer->ip()] = array_unique($ipPeers, SORT_NUMERIC);

        // Call event
        $this->p2pSocket->events()->onPeerConnect()->trigger([$peer]);
    }
}
