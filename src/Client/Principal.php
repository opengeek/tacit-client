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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Slim\Slim;

/**
 * A session Principal that uses the Client.
 *
 * @package Tacit\Client
 */
class Principal
{
    const SESSION_KEY_PRINCIPAL = '__principal';

    protected $accessToken;
    protected $authorized;
    protected $expires;
    protected $refreshToken;
    protected $scope;
    protected $user;

    /**
     * Authenticate a user by username and password.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @param string $scope A space-delimited list of scopes to request.
     *
     * @throws RestfulException If an error occurs retrieving an accessToken.
     * @return bool TRUE if a valid accessToken is retrieved, FALSE otherwise.
     */
    public static function authenticate($username, $password, $scope = 'public user')
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            return false;
        }

        $resource = static::accessToken($username, $password, $scope);
        $resource['expires'] = time() + (integer)$resource['expires_in'];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        } else {
            session_regenerate_id(true);
        }
        $_SESSION[static::SESSION_KEY_PRINCIPAL] = $resource;

        return true;
    }

    /**
     * Get an instance of a Principal, requesting a specific scope.
     *
     * @param string $scope A space-delimited string of scopes to request.
     *
     * @throws RestfulException If a problem occurs getting an authorized Principal.
     * @return null|static A Principal or null.
     */
    public static function instance($scope = 'public user')
    {
        if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
            session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (isset($_SESSION[static::SESSION_KEY_PRINCIPAL])) {
                $data = $_SESSION[static::SESSION_KEY_PRINCIPAL];
                if (is_array($data)) {
                    if (!isset($data['expires'])) {
                        $data['expires'] = time() - 3600;
                    }
                    if (isset($data['refresh_token']) && (time() > $data['expires'])) {
                        $data = array_merge($data, static::refreshToken($data['refresh_token'], $scope));
                    }
                    $principal = new static($data);

                    return $principal;
                }
            }
            static::endSession();
        }

        return null;
    }

    /**
     * End a session for the current Principal.
     */
    public static function endSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path']);
            session_destroy();
        }
    }

    /**
     * Get a valid user credentials accessToken.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @param string $scope A space-delimited list of scopes to request a token for.
     *
     * @throws RestfulException If an error occurs getting the accessToken.
     * @return array The response body from the request containing the accessToken details.
     */
    protected static function accessToken($username, $password, $scope = 'public user')
    {
        $app = Slim::getInstance();

        /** @var Identity $identity */
        $identity = $app->container->get('identities');

        $clientKey = $app->config('api.identity');
        $clientSecret = $identity->getSecretKey($clientKey);

        try {
            $response = (new Client([
                'base_url' => rtrim($app->config('api.endpoint'), '/') . '/'
            ]))->post($app->config('api.route.token') ?: 'security/token', [
                'auth' => [$clientKey, $clientSecret],
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'grant_type' => 'password',
                    'username' => $username,
                    'password' => $password,
                    'scope' => $scope
                ])
            ]);
        } catch (RequestException $e) {
            throw new RestfulException($e->hasResponse() ? $e->getResponse()->json() : [],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500, $e);
        }

        return $response->json();
    }

    protected static function refreshToken($refreshToken, $scope = 'public user')
    {
        $app = Slim::getInstance();

        /** @var Identity $identity */
        $identity = $app->container->get('identities');

        $clientKey = $app->config('api.identity');
        $clientSecret = $identity->getSecretKey($clientKey);

        try {
            $response = (new Client([
                'base_url' => rtrim($app->config('api.endpoint'), '/') . '/'
            ]))->post($app->config('api.route.token') ?: 'security/token', [
                'auth' => [$clientKey, $clientSecret],
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'scope' => $scope
                ])
            ]);
        } catch (RequestException $e) {
            throw new RestfulException($e->hasResponse() ? $e->getResponse()->json() : [],
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500, $e);
        }

        return $response->json();
    }

    /**
     * Get or set data about the user represented by the Principal.
     *
     * @param array $data An array of Principal data from a Response or existing session.
     *
     * @return array|null An array of data representing the user or null.
     */
    protected static function user(array $data)
    {
        if (isset($data['user'])) {
            return $data['user'];
        }

        $app = Slim::getInstance();

        /** @var \Tacit\Client $api */
        $api = $app->container->get('api');

        try {
            /** @var \Tacit\Client\Response $response */
            $response = $api->get($app->config('api.route.identity') ?: 'security/token/ident');

            if (!$response->isError()) {
                $data['user'] = $response->getResource();
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION[static::SESSION_KEY_PRINCIPAL] = $data;
                }

                return $data['user'];
            }
        } catch (\Exception $e) {
            /* TODO: attempt to refresh expired OAuth2 tokens */
        }
        static::endSession();

        return null;
    }

    /**
     * Determine if the Principal is authorized for a specific scope.
     *
     * @param string $scope A space-delimited string of scopes to test.
     *
     * @return bool TRUE if the user is authorized, FALSE otherwise.
     */
    public function isAuthorized($scope = 'public user')
    {
        $authorizedScopes = explode(' ', $this->scope);
        $requestedScopes = explode(' ', $scope);
        $intersection = array_intersect($requestedScopes, $authorizedScopes);
        if (count($intersection) === count($requestedScopes)) {
            if (in_array('user', $requestedScopes) || in_array('admin', $requestedScopes)) {
                return $this->authorized;
            }

            return true;
        }

        return false;
    }

    /**
     * Return an OAuth2 access_token assigned to the Principal.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Return an OAuth2 refresh_token assigned to the Principal.
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Return a space-delimited string of scopes for the Principal.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Return an array of data describing the user for the Principal.
     *
     * @return array|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Determine if the Principal has a valid OAuth2 refresh_token.
     *
     * @return bool
     */
    public function hasRefreshToken()
    {
        return !empty($this->refreshToken);
    }

    /**
     * Determine if the Principal has an expired OAuth2 access_token.
     *
     * @return bool
     */
    public function isExpired()
    {
        return time() > $this->expires;
    }

    private function __construct(array $data)
    {
        $this->expires = isset($data['expires']) ? $data['expires'] : time() + 3580;
        $this->scope = $data['scope'];
        $this->accessToken = $data['access_token'];
        if (isset($data['refresh_token'])) {
            $this->refreshToken = $data['refresh_token'];
        }

        $this->user = static::user($data);
    }
}
