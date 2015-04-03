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
    protected $identities = [];
    protected $location;

    /**
     * Get an Identity instance from the specified PHP file location.
     *
     * @param string $location
     */
    function __construct($location)
    {
        if (!is_readable($location)) {
            throw new \RuntimeException("Invalid Identities location: {$location} does not exist or is not readable");
        }
        $this->location = $location;
        $this->identities = include $this->location;
    }

    /**
     * Get a client Identity.
     *
     * @param string $identifier The client identifier.
     *
     * @return array|bool The client identity associated with the specified identifier or FALSE if no identity is found.
     */
    public function get($identifier)
    {
        if (!isset($this->identities[$identifier])) {
            return false;
        }

        return $this->identities[$identifier];
    }

    /**
     * Get the secretKey defined for an Identity.
     *
     * @param string $identifier The client identifier.
     *
     * @return string|bool The secretKey or false if the identity is invalid or does not have a secretKey defined.
     */
    public function getSecretKey($identifier)
    {
        if (!isset($this->identities[$identifier])) {
            return false;
        }
        if (!isset($this->identities[$identifier]['secretKey'])) {
            return false;
        }

        return $this->identities[$identifier]['secretKey'];
    }
}
