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

/**
 * Class PeerData
 * @package FurqanSiddiqui\P2PSocket\Peers
 */
class PeerData
{
    /** @var array */
    private $data;

    /**
     * PeerData constructor.
     */
    public function __construct()
    {
        $this->data = [];
    }

    /**
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value): void
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Cannot store a non-scalar value in PeerData obj');
        }

        $this->data[strtolower($key)] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->data[strtolower($key)] ?? null;
    }

    /**
     * @param string $key
     */
    public function remove(string $key): void
    {
        unset($this->data[strtolower($key)]);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[strtolower($key)]);
    }
}
