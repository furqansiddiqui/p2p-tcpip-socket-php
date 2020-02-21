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
use FurqanSiddiqui\P2PSocket\Socket\SocketResource;

/**
 * Class P2PSocket
 * @package FurqanSiddiqui\P2PSocket
 * @property-read bool $debug
 */
class P2PSocket
{
    /** @var SocketResource */
    private $socket;
    /** @var Peers */
    private $peers;
    /** @var int */
    private $maxPeers;
    /** @var Events */
    private $events;
    /** @var bool */
    private $debug;

    /**
     * P2PSocket constructor.
     * @param string $bindIpAddress
     * @param int $port
     * @param int $maxPeers
     * @param bool $debug
     * @throws P2PSocketException
     */
    public function __construct(string $bindIpAddress, int $port, int $maxPeers, bool $debug = false)
    {
        // Set debugging mode?
        $this->debug = $debug;

        // Maximum Peers
        if ($maxPeers < 0x01 || $maxPeers > 0xff) {
            throw new P2PSocketException('Max peers argument must be a valid single byte unsigned integer');
        }

        // Validate arguments
        if (!filter_var($bindIpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new P2PSocketException('Invalid IPv4 host address');
        }

        if ($port < 0x3e8 || $port > 0xffff) {
            throw new P2PSocketException('Invalid socket listen port');
        }

        // Save socket resource
        $this->socket = SocketResource::Create($this->debug);
        if (!@socket_bind($this->socket->resource(), $bindIpAddress, $port)) {
            throw new P2PSocketException(
                $this->socket->lastError()->error2String('Failed to bind listen IP and port', $this->debug)
            );
        }

        if (!@socket_listen($this->socket->resource(), $this->maxPeers)) {
            throw new P2PSocketException(
                $this->socket->lastError()->error2String('Failed to start listener', $this->debug)
            );
        }

        // Init other props
        $this->maxPeers = $maxPeers;
        $this->peers = new Peers($this);
        $this->events = new Events();
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
        }

        throw new \OutOfBoundsException('Cannot get value of inaccessible property');
    }

    /**
     * @return Events
     */
    public function events(): Events
    {
        return $this->events;
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
     * @throws Exception\PeerConnectException
     */
    public function listen(): void
    {
        $this->socket->setNonBlockMode(); // Set non-block mode

        $remain = $this->maxPeers - $this->peers->count();
        if ($remain > 0) {
            for ($i = 0; $i <= $remain; $i++) {
                $this->peers->accept();
            }
        }

        $this->socket->setBlockMode(); // Revert back to blocking mode
    }

    /**
     * @return SocketResource
     */
    public function socket(): SocketResource
    {
        return $this->socket;
    }
}
