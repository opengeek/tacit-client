<?php
/*
 * This file is part of the Tacit Client package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tacit;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Exception\RequestException;
use Slim\Slim;

/**
 * The Tacit API Client.
 *
 * @package Tacit
 */
class Client
{
    protected static $instances = [];

    protected $endPoint;

    protected $httpClient;

    /**
     * Get a unique client by the specified endpoint.
     *
     * @param string $endPoint
     *
     * @return self
     */
    public static function instance($endPoint)
    {
        $key = "{$endPoint}";
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new static($endPoint);
        }
        return self::$instances[$key];
    }

    /**
     * Get an OAuth2 Access Token using the client_credentials grant type.
     *
     * @param string $endPoint The URL of the API endpoint.
     *
     * @return string|bool A valid access token or false if one cannot be retrieved.
     */
    public static function getAccessToken($endPoint)
    {
        if (!isset($_SESSION[Client\Principal::SESSION_KEY_PRINCIPAL])) {
            $clientKey = Slim::getInstance()->config('identity');
            $clientSecret = Client\Identity::getSecretKey($clientKey);
            $client = new \GuzzleHttp\Client(['base_url' => rtrim($endPoint, '/')]);
            try {
                $response = $client->post('/security/token', [
                        'auth' => [$clientKey, $clientSecret],
                        'body' => json_encode([
                            'grant_type' => 'client_credentials',
                            'scope' => 'public'
                        ]),
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ]
                    ]);
            } catch (RequestException $e) {
                return false;
            }

            $parsed = json_decode($response->getBody(true), true);
            if (!isset($parsed['access_token'])) {
                return false;
            }

            $_SESSION[Client\Principal::SESSION_KEY_PRINCIPAL] = $parsed;
        }
        return $_SESSION[Client\Principal::SESSION_KEY_PRINCIPAL]['access_token'];
    }

    public function __construct($entryPoint)
    {
        $entryPoint = rtrim($entryPoint, '/');
        $this->endPoint = $entryPoint;
        $this->httpClient = new \GuzzleHttp\Client(['base_url' => $entryPoint]);
        $this->httpClient->getEmitter()->on('before', function (BeforeEvent $event) use ($entryPoint) {
            $accessToken = Client::getAccessToken($entryPoint);
            $event->getRequest()->addHeader('Authorization', 'Bearer ' . $accessToken);
        });
    }

    public function get($uri = null, $headers = null, $options = array())
    {
        return $this->httpClient->get($uri, $headers, $options);
    }

    public function head($uri = null, $headers = null, array $options = array())
    {
        return $this->httpClient->head($uri, $headers, $options);
    }

    public function delete($uri = null, $headers = null, $body = null, array $options = array())
    {
        return $this->httpClient->delete($uri, $headers, $body, $options);
    }

    public function put($uri = null, $headers = null, $body = null, array $options = array())
    {
        return $this->httpClient->put($uri, $headers, $body, $options);
    }

    public function patch($uri = null, $headers = null, $body = null, array $options = array())
    {
        return $this->httpClient->patch($uri, $headers, $body, $options);
    }

    public function post($uri = null, $headers = null, $postBody = null, array $options = array())
    {
        return $this->httpClient->post($uri, $headers, $postBody, $options);
    }

    public function options($uri = null, array $options = array())
    {
        return $this->httpClient->options($uri, $options);
    }
}
