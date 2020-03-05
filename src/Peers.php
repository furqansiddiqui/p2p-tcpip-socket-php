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
     * @throws Exception\P2PSocketException
     * @throws PeerConnectException
     */
    public function connect(string $remotePeerAddr, int $port): void
    {
        if (!filter_var($remotePeerAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new PeerConnectException('Invalid remote IPv4 peer address');
        }

        if ($port < 0x3e8 || $port > 0xffff) {
            throw new PeerConnectException('Invalid remote peer port');
        }

        $socket = SocketResource::Create($this->p2pSocket);
        if (!@socket_connect($socket->resource(), $remotePeerAddr, $port)) {
            throw new PeerConnectException(
                $socket->lastError()->error2String(
                    sprintf('Peer connection to "%s" on port %d failed', $remotePeerAddr, $port)
                )
            );
        }

        $peer = new Peer($this->p2pSocket, $socket, ($this->count + 1));
        $this->peerIsConnected($peer);
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
                $peerMsg = $peer->read($length);
                if (is_string($peerMsg) && strlen($peerMsg) > 0) {
                    $messages->append(new PeersReadMessage($peer, $peerMsg));
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
