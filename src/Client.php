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
use Tacit\Client\Identity;
use Tacit\Client\Principal;

/**
 * The base Tacit API Client.
 *
 * @package Tacit
 */
class Client
{
    /**
     * @var array Unique client instances by endpoint.
     */
    protected static $instances = [];

    /**
     * @var string The service endpoint to be used by the Client.
     */
    protected $endPoint;

    /**
     * @var \GuzzleHttp\Client The HTTP client handling the requests.
     */
    protected $httpClient;

    /**
     * Get a unique client instance by the specified endpoint.
     *
     * @param Slim   &$app
     * @param string $endPoint
     *
     * @return Client
     */
    public static function instance(Slim &$app, $endPoint)
    {
        $key = "{$endPoint}";
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new static($app, $endPoint);
        }

        return self::$instances[$key];
    }

    /**
     * Get an OAuth2 Access Token using the client_credentials grant type.
     *
     * @param Slim   &$app A reference to the Slim app.
     * @param string $endPoint The URL of the API endpoint.
     *
     * @return bool|string A valid access token or false if one cannot be retrieved.
     */
    public static function getAccessToken(Slim &$app, $endPoint)
    {
        if (!isset($_SESSION[Principal::SESSION_KEY_PRINCIPAL])) {
            $clientKey = $app->config('api.identity');
            /** @var Identity $identities */
            $identities = $app->container->get('identities');
            $clientSecret = $identities->getSecretKey($clientKey);

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

            $parsed = $response->json();
            if (!isset($parsed['access_token'])) {
                return false;
            }

            $_SESSION[Principal::SESSION_KEY_PRINCIPAL] = $parsed;
        }

        return $_SESSION[Principal::SESSION_KEY_PRINCIPAL]['access_token'];
    }

    /**
     * Construct a new API Client for a provided app and entryPoint.
     *
     * @param Slim   &$app
     * @param string $entryPoint
     */
    public function __construct(Slim &$app, $entryPoint)
    {
        $entryPoint = rtrim($entryPoint, '/');
        $this->endPoint = $entryPoint;
        $this->httpClient = new \GuzzleHttp\Client(['base_url' => $entryPoint]);
        $this->httpClient->getEmitter()->on('before', function (BeforeEvent $event) use ($app, $entryPoint) {
            $accessToken = Client::getAccessToken($app, $entryPoint);
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
