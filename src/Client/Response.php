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

use GuzzleHttp\Message\ResponseInterface;

/**
 * A GuzzleHttp\Message\Response wrapper for Tacit responses.
 *
 * @package Tacit\Client
 */
class Response
{
    /**
     * @var \GuzzleHttp\Message\Response
     */
    protected $httpResponse;

    protected $resource;
    protected $links;
    protected $embedded;

    /**
     * Wrap a Guzzle HTTP response.
     *
     * @param \GuzzleHttp\Message\ResponseInterface $original
     *
     * @throws RestfulException
     */
    public function __construct(ResponseInterface $original)
    {
        $this->httpResponse = $original;

        $contentType = $this->httpResponse->getHeader('Content-Type');
        switch ($contentType) {
            case 'text/html':
                $this->resource = $this->httpResponse->getBody();
                break;
            case 'application/json':
            default:
                $this->resource = $this->httpResponse->json();
                $this->links = isset($this->resource['_links']) ? $this->resource['_links'] : [];
                $this->embedded = isset($this->resource['_embedded']) ? $this->resource['_embedded'] : [];
                break;
        }

        if ($this->isError()) {
            if (!isset($this->resource['status'])) {
                $this->resource['status'] = $this->httpResponse->getStatusCode();
                if (isset($this->resource['error'])) {
                    $this->resource['message'] = $this->resource['error'];
                } else {
                    $this->resource['message'] = 'Bad Request';
                }
                if (isset($this->resource['error_description'])) {
                    $this->resource['description'] = $this->resource['error_description'];
                } else {
                    $this->resource['description'] = 'An unknown error occurred.';
                }
                if (isset($this->resource['error_uri'])) {
                    $this->resource['property'] = ['uri' => $this->resource['error_uri']];
                } else {
                    $this->resource['property'] = null;
                }
            }
            if ($this->isClientError()) {
                throw new RestfulException($this->resource);
            } elseif ($this->isServerError()) {
                throw new RestfulException($this->resource);
            } else {
                throw new RestfulException($this->resource);
            }
        }
    }

    /**
     * Get the _embedded data from the Resource.
     *
     * @param null|string $key An optional key to limit the embedded data element(s) to return.
     *
     * @return array|bool An array of embedded data elements or FALSE if not set.
     */
    public function getEmbedded($key = null)
    {
        if (is_string($key) && $key !== '') {
            return isset($this->embedded[$key]) ? $this->embedded[$key] : false;
        }

        return $this->embedded;
    }

    /**
     * Get the _links metadata from the Resource.
     *
     * @return array An array of link relations defined for the Resource.
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Get the data of the Resource represented by the Response.
     *
     * @param bool $includeEmbedded Indicate if _embedded data should be included.
     * @param bool $includeLinks Indicate if _links metadata should be included.
     *
     * @return array An array of data representing the Resource from the Response.
     */
    public function getResource($includeEmbedded = false, $includeLinks = false)
    {
        $resource = $this->resource;
        if (false === $includeEmbedded) {
            unset($resource['_embedded']);
        }
        if (false === $includeLinks) {
            unset($resource['_links']);
        }

        return $resource;
    }

    /**
     * Determine if the Response is a client-side error (4xx).
     *
     * @return bool TRUE if the Response has a 4xx status code, FALSE otherwise.
     */
    public function isClientError()
    {
        return $this->isError() && $this->httpResponse->getStatusCode() < 500;
    }

    /**
     * Determine if the Response is an error (4xx or 5xx).
     *
     * @return bool TRUE if the Response has a 4xx or 5xx status code, FALSE otherwise.
     */
    public function isError()
    {
        return $this->httpResponse->getStatusCode() >= 400;
    }

    /**
     * Determine if the Response is a server-side error (5xx).
     *
     * @return bool TRUE if the Response has a 5xx status code, FALSE otherwise.
     */
    public function isServerError()
    {
        return $this->isError() && $this->httpResponse->getStatusCode() >= 500;
    }

    /**
     * Return a string representation of the Response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->httpResponse->__toString();
    }
}
