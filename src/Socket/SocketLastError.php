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

use FurqanSiddiqui\P2PSocket\P2PSocket;

/**
 * Class SocketLastError
 * @package FurqanSiddiqui\P2PSocket\Socket
 */
class SocketLastError
{
    /** @var P2PSocket */
    private $p2pSocket;
    /** @var null|int */
    public $code;
    /** @var null|string */
    public $message;

    /**
     * SocketLastError constructor.
     * @param P2PSocket $p2pSocket
     * @param SocketResource|null $socket
     */
    public function __construct(P2PSocket $p2pSocket, ?SocketResource $socket = null)
    {
        $this->p2pSocket = $p2pSocket;
        $resource = $socket ? $socket->resource() : null;
        $errCode = socket_last_error($resource);
        if ($errCode) {
            $errString = socket_strerror($errCode);
            $this->code = $errCode;
            $this->message = $errString;
        }
    }

    /**
     * @param string|null $message
     * @return string
     */
    public function error2String(?string $message = null): string
    {
        $exceptionMsg = "";
        if ($message) {
            $exceptionMsg .= trim($message) . " ";
        }

        if ($this->p2pSocket->debug) {
            $exceptionMsg .= sprintf("[#%d] %s", $this->code, $this->message);
        }

        return $exceptionMsg;
    }
}

