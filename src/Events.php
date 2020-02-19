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

use FurqanSiddiqui\P2PSocket\Events\Event;
use FurqanSiddiqui\P2PSocket\Events\EventRegister;

/**
 * Class Events
 * @package FurqanSiddiqui\P2PSocket
 */
class Events extends EventRegister
{
    /**
     * @return Event
     */
    public function onPeerConnect(): Event
    {
        return $this->on("onPeerConnect");
    }
}