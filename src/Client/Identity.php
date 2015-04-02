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

/**
 * A Simple client credentials Identity implementation that loads entries from a PHP file.
 *
 * @package Tacit\Client
 */
class Identity
{
    /**
     * @var array An array of all loaded identities.
     */
    protected static $identities = [];

    /**
     * Get the secretKey defined for an Identity.
     *
     * @param string      $identifier The client identifier.
     * @param null|string $location An optional file path to load identities from.
     *
     * @return string|bool The secretKey or false if the identity is invalid or does not have a secretKey defined.
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
     * Load identities from a PHP file.
     *
     * @param null|string $location The file location to load the identities from.
     *
     * @throws \RuntimeException If the file does not exist or is not readable.
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
