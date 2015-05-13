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
use Tacit\Client\Response;
use Tacit\Client\RestfulException;

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

            $client = new \GuzzleHttp\Client(['base_url' => rtrim($endPoint, '/') . '/']);
            try {
                $response = $client->post($app->config('api.route.token') ?: 'security/token', [
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
                $app->getLog()->error($e->getMessage(), $e->hasResponse() ? $e->getResponse()->json() : $e->getTrace());
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
        $entryPoint = rtrim($entryPoint, '/') . '/';
        $this->endPoint = $entryPoint;
        $this->httpClient = new \GuzzleHttp\Client(['base_url' => $entryPoint]);
        $this->httpClient->getEmitter()->on('before', function (BeforeEvent $event) use ($app, $entryPoint) {
            $accessToken = Client::getAccessToken($app, $entryPoint);
            $event->getRequest()->setHeader('Authorization', 'Bearer ' . $accessToken);
        });
    }

    public function get($uri = null, array $headers = [], $options = [])
    {
        try {
            return new Response($this->httpClient->get($uri, array_merge(['headers' => $headers], $options)));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }

    public function head($uri = null, array $headers = [], array $options = [])
    {
        try {
            return new Response($this->httpClient->head($uri, array_merge(['headers' => $headers], $options)));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }

    public function delete($uri = null, array $headers = [], $body = null, array $options = [])
    {
        try {
            return new Response($this->httpClient->delete($uri,
                array_merge(['headers' => $headers, 'body' => $body], $options)));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }

    public function put($uri = null, array $headers = [], $body = null, array $options = [])
    {
        try {
            return new Response($this->httpClient->put($uri,
                array_merge(['headers' => $headers, 'body' => $body], $options)));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }

    public function patch($uri = null, array $headers = [], $body = null, array $options = [])
    {
        try {
            return new Response($this->httpClient->patch($uri,
                array_merge(['headers' => $headers, 'body' => $body], $options)));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }

    public function post($uri = null, array $headers = [], $body = null, array $options = [])
    {
        try {
            return new Response($this->httpClient->post($uri,
                array_merge(['headers' => $headers, 'body' => $body], $options)));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }

    public function options($uri = null, array $options = [])
    {
        try {
            return new Response($this->httpClient->options($uri, $options));
        } catch (RequestException $e) {
            throw new RestfulException(
                $e->hasResponse() ? $e->getResponse()->json() : ['message' => 'Bad Request', 'description' => $e->getMessage()],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 400
            );
        }
    }
}
