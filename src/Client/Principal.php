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


use Tacit\Client;

class Principal
{
    const SESSION_KEY_PRINCIPAL = '__principal';

    protected $accessToken;
    protected $authorized;
    protected $expires;
    protected $refreshToken;
    protected $scope;
    protected $user;

    public static function authenticate($username, $password)
    {
        if (session_status() === PHP_SESSION_DISABLED) return false;

        $resource = static::accessToken($username, $password);
        $resource['expires'] = time() + (integer)$resource['expires_in'];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        } else {
            session_regenerate_id(true);
        }
        $_SESSION[static::SESSION_KEY_PRINCIPAL] = $resource;

        return true;
    }

    public static function instance($scope = 'public user')
    {
        if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
            session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (isset($_SESSION[static::SESSION_KEY_PRINCIPAL])) {
                $data = $_SESSION[static::SESSION_KEY_PRINCIPAL];
                if (is_array($data)) {
                    if (!isset($data['expires'])) $data['expires'] = time() - 3600;
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

    public static function endSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path']);
            session_destroy();
        }
    }

    protected static function accessToken($username, $password)
    {
        $app = Slim::getInstance();

        $clientKey = $app->config('api.identity');
        $clientSecret = Identity::getSecretKey($clientKey);
        $response = (new Client(rtrim($app->config('api.endpoint'), '/') . '/'))->post(
            'security/token',
            [
                'Authorization' => 'Basic ' . base64_encode("{$clientKey}:{$clientSecret}"),
                'Content-Type' => 'application/json'
            ],
            json_encode([
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
                'scope' => 'public user'
            ]),
            [
                'exceptions' => false,
            ]
        )->send();
        if ($response->isError()) throw new RestfulException(
            json_decode($response->getBody(true), true),
            $response->getStatusCode()
        );

        return json_decode($response->getBody(true), true);
    }

    protected static function refreshToken($refreshToken, $scope = 'public user')
    {
        $app = Slim::getInstance();

        $clientKey = $app->config('api.identity');
        $clientSecret = Identity::getSecretKey($clientKey);
        $response = (new Client(rtrim($app->config('api.endpoint'), '/') . '/'))->post(
            'security/token',
            [
                'Authorization' => 'Basic ' . base64_encode("{$clientKey}:{$clientSecret}"),
                'Content-Type' => 'application/json'
            ],
            json_encode([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope' => $scope
            ]),
            [
                'exceptions' => false,
            ]
        )->send();
        if ($response->isError()) throw new RestfulException(
            json_decode($response->getBody(true), true),
            $response->getStatusCode()
        );

        return json_decode($response->getBody(true), true);
    }

    protected static function user(array $data)
    {
        if (isset($data['user'])) {
            return $data['user'];
        }

        $app = Slim::getInstance();

        $api = $app->container->get('api');

        try {
            /** @var Response $response */
            $response = $api->get('security/token/ident');

            if (!$response->isError()) {
                $data['user'] = $response->getResource();
                $_SESSION[static::SESSION_KEY_PRINCIPAL] = $data;
                return $data['user'];
            }
        } catch (\Exception $e) {
            /* TODO: attempt to refresh expired OAuth2 tokens */
        }
        static::endSession();
        return null;
    }

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

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function hasRefreshToken()
    {
        return !empty($this->refreshToken);
    }

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

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[static::SESSION_KEY_PRINCIPAL] = $data;
        }
    }
}
