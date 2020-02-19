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

/**
 * Class SocketLastError
 * @package FurqanSiddiqui\P2PSocket\Socket
 */
class SocketLastError
{
    /** @var null|int */
    public $code;
    /** @var null|string */
    public $message;

    /**
     * SocketLastError constructor.
     * @param SocketResource|null $socket
     */
    public function __construct(?SocketResource $socket = null)
    {
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
     * @param bool $debug
     * @return string
     */
    public function error2String(?string $message = null, bool $debug = false): string
    {
        $exceptionMsg = "";
        if ($message) {
            $exceptionMsg .= trim($message) . " ";
        }

        if ($debug) {
            $exceptionMsg .= sprintf("[#%d] %s", $this->code, $this->message);
        }

        return $exceptionMsg;
    }
}

