<?php
/*
 * This file is part of the Tacit Client package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tacit\Client;


class Identity
{
    /**
     * @var array An array of all loaded identities.
     */
    protected static $identities = [];

    /**
     * @param string $identifier
     * @param null   $location
     *
     * @return string|bool
     */
    public static function getSecretKey($identifier, $location = null)
    {
        if (!isset(self::$identities[$identifier])) {
            self::loadIdentities($location);
        }
        if (!isset(self::$identities[$identifier])) {
            return false;
        }
        if (!isset(self::$identities[$identifier]['secretKey'])) {
            return false;
        }
        return self::$identities[$identifier]['secretKey'];
    }

    /**
     * @param null|string $location
     *
     * @throws \RuntimeException
     */
    private static function loadIdentities($location = null)
    {
        if (null === $location) {
            $location = __DIR__ . '/../../../identities.php';
        }
        if (!is_readable($location)) {
            throw new \RuntimeException("Invalid Identities location: {$location} does not exist or is not readable");
        }
        self::$identities = include $location;
    }
}
