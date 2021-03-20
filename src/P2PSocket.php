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

use FurqanSiddiqui\P2PSocket\Exception\P2PSocketException;
use FurqanSiddiqui\P2PSocket\Exception\PeerConnectException;
use FurqanSiddiqui\P2PSocket\Socket\SocketResource;

/**
 * Class P2PSocket
 * @package FurqanSiddiqui\P2PSocket
 * @property-read bool $debug
 * @property-read string $delimiter
 */
class P2PSocket
{
    /** @var int Peer connection was accepted */
    public const INBOUND_PEER = 0x80;
    /** @var int Connection was established "to" peer */
    public const OUTBOUND_PEER = 0xff;

    /** @var SocketResource|null */
    private ?SocketResource $socket = null;
    /** @var Peers */
    private Peers $peers;
    /** @var int */
    private int $maxPeers;
    /** @var Events */
    private Events $events;
    /** @var bool */
    private bool $debug;
    /** @var string */
    private string $delimiter;
    /** @var bool */
    private bool $allowPrivateIPs;

    /**
     * P2PSocket constructor.
     * @param int $maxPeers
     * @param bool $debug
     * @throws P2PSocketException
     */
    public function __construct(int $maxPeers, bool $debug = false)
    {
        // Set debugging mode?
        $this->debug = $debug;

        // Maximum Peers
        if ($maxPeers < 0x01 || $maxPeers > 0xff) {
            throw new P2PSocketException('Max peers argument must be a valid single byte unsigned integer');
        }

        // Init other props
        $this->maxPeers = $maxPeers;
        $this->peers = new Peers($this);
        $this->events = new Events();
        $this->delimiter = "\n";
        $this->allowPrivateIPs = true;
    }

    /**
     * @param bool $allow
     * @return $this
     */
    public function allowPrivateIPRange(bool $allow): self
    {
        $this->allowPrivateIPs = $allow;
        return $this;
    }

    /**
     * @param string $validIP
     * @throws PeerConnectException
     */
    public function privateIPRangeCheck(string $validIP): void
    {
        if (!$this->allowPrivateIPs) {
            if (!filter_var($validIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new PeerConnectException('Connections to/from private IP ranges are disabled');
            }
        }
    }

    /**
     * @param string $bindIpAddress
     * @param int $port
     * @throws P2PSocketException
     */
    public function createServer(string $bindIpAddress, int $port): void
    {
        if ($this->socket) {
            throw new P2PSocketException('Socket server was already created');
        }

        // Validate arguments
        if (!filter_var($bindIpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new P2PSocketException('Invalid IPv4 host address');
        }

        if ($port < 0x3e8 || $port > 0xffff) {
            throw new P2PSocketException('Invalid socket listen port');
        }

        // Save socket resource
        $this->socket = SocketResource::Create($this);
        if (!@socket_bind($this->socket->resource(), $bindIpAddress, $port)) {
            throw new P2PSocketException(
                $this->socket->lastError()->error2String('Failed to bind listen IP and port')
            );
        }

        if (!@socket_listen($this->socket->resource(), $this->maxPeers)) {
            throw new P2PSocketException(
                $this->socket->lastError()->error2String('Failed to start listener')
            );
        }
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "debug":
                return $this->debug ?? false;
            case "delimiter":
                return $this->delimiter;
        }

        throw new \OutOfBoundsException('Cannot get value of inaccessible property');
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return Events
     */
    public function events(): Events
    {
        return $this->events;
    }

    /**
     * @return Peers
     */
    public function peers(): Peers
    {
        return $this->peers;
    }

    /**
     * @param string $remotePeerAddr
     * @param int $port
     * @throws Exception\PeerConnectException
     * @throws P2PSocketException
     */
    public function connect(string $remotePeerAddr, int $port): void
    {
        $this->peers->connect($remotePeerAddr, $port);
    }

    /**
     * @param int|null $queue
     * @param callable|null $callbackOnEachFail
     * @throws P2PSocketException
     * @throws PeerConnectException
     */
    public function listen(?int $queue = null, ?callable $callbackOnEachFail = null): void
    {
        if (!$this->socket) {
            throw new P2PSocketException('Cannot use listen method, socket server was never created');
        }

        $this->socket->setNonBlockMode(); // Set non-block mode

        $remain = $queue ? $queue : $this->maxPeers - $this->peers->count();
        if ($remain > 0) {
            for ($i = 0; $i <= $remain; $i++) {
                try {
                    $this->peers->accept();
                } catch (PeerConnectException $e) {
                    if (!$callbackOnEachFail) {
                        throw $e;
                    }

                    call_user_func_array($callbackOnEachFail, [$i, $e]);
                }
            }
        }

        $this->socket->setBlockMode(); // Revert back to blocking mode
    }

    /**
     * @return SocketResource|null
     */
    public function socket(): ?SocketResource
    {
        return $this->socket;
    }
}
