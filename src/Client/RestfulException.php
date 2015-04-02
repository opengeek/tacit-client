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
 * Thrown when 4xx or 5xx error responses are encountered.
 *
 * @package Tacit\Client
 */
class RestfulException extends \Exception
{
    protected $resource;
    protected $statusCode;
    protected $description;

    /**
     * @param array $resource The resource data contained in the error response.
     * @param int $status The status code of the error response.
     * @param null|\Exception $previous A previous exception to wrap with this.
     */
    public function __construct($resource, $status = 400, \Exception $previous = null)
    {
        $message = isset($resource['message']) ? $resource['message']
            : (isset($resource['error']) ? $resource['error'] : 'Unknown Error');
        $code = isset($resource['code']) ? $resource['code'] : 5000;
        parent::__construct($message, $code, $previous);
        $this->description = isset($resource['description'])
            ? $resource['description']
            : (isset($resource['error_description']) ? $resource['error_description']
                : 'An unknown error has occurred');
        $this->resource = $resource;
        if (isset($resource['status'])) {
            $this->statusCode = (int)$resource['status'];
        }
    }

    /**
     * Get the error description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the error response resource.
     *
     * @return array
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
