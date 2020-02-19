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

/**
 * Class SocketLastError
 * @package FurqanSiddiqui\P2PSocket
 */
class SocketLastError
{
    /** @var null|int */
    public $code;
    /** @var null|string */
    public $message;

    /**
     * SocketLastError constructor.
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $errCode = socket_last_error($socket);
        if ($errCode) {
            $errString = socket_strerror($errCode);
            $this->code = $errCode;
            $this->message = $errString;
        }
    }
}

