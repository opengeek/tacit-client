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


class RestfulException extends \Exception
{
    protected $resource;
    protected $statusCode;
    protected $description;

    public function __construct($resource, $status = 400, \Exception $previous = null)
    {
        $message = isset($resource['message']) ? $resource['message'] : (isset($resource['error'])
            ? $resource['error'] : 'Unknown Error');
        $code    = isset($resource['code']) ? $resource['code'] : 5000;
        parent::__construct($message, $code, $previous);
        $this->description = isset($resource['description']) ? $resource['description'] : (isset($resource['error_description'])
            ? $resource['error_description'] : 'An unknown error has occurred');
        $this->resource = $resource;
        if (isset($resource['status'])) {
            $this->statusCode = (int)$resource['status'];
        }
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
