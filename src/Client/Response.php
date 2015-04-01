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

class Response
{
    /**
     * @var \Guzzle\Http\Message\Response
     */
    protected $httpResponse;

    protected $resource;
    protected $links;
    protected $embedded;

    /**
     * Wrap a Guzzle HTTP response.
     *
     * @param \Guzzle\Http\Message\Response $original
     *
     * @throws ClientException
     * @throws RestfulException
     * @throws ServerException
     */
    public function __construct($original)
    {
        $this->httpResponse = $original;

        $contentType = $this->httpResponse->getContentType();
        switch ($contentType) {
            case 'text/html':
                $this->resource = $this->httpResponse->getBody(true);
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
                throw new ClientException($this->resource);
            } elseif ($this->isServerError()) {
                throw new ServerException($this->resource);
            } else {
                throw new RestfulException($this->resource);
            }
        }
    }

    public function getEmbedded($key = null)
    {
        if (is_string($key) && $key !== '') {
            return isset($this->embedded[$key]) ? $this->embedded[$key] : false;
        }
        return $this->embedded;
    }

    public function getLinks()
    {
        return $this->links;
    }

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

    public function isClientError()
    {
        return $this->httpResponse->isClientError();
    }

    public function isError()
    {
        return $this->httpResponse->isError();
    }

    public function isServerError()
    {
        return $this->httpResponse->isServerError();
    }

    public function __toString()
    {
        return $this->httpResponse->__toString();
    }
}
