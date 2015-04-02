<?php
/*
 * This file is part of the Tacit Client package.
 *
 * Copyright (c) Jason Coward (jason@opengeek.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tacit\Middleware;

use Slim\Middleware;
use Tacit\Client\Principal;

/**
 * Slim Middleware for handling OAuth2 Sessions from a Tacit app.
 *
 * @package Tacit\Middleware
 */
class Session extends Middleware
{
    /**
     * Load an authenticated user from the session if it exists.
     */
    public function call()
    {
        /** @var Principal $principal */
        $principal = Principal::instance();
        if ($principal) {
            $this->app->container->set('principal', $principal);
            $data['accessToken'] = $principal->getAccessToken();
            $user = $principal->getUser();
            if (null !== $user) {
                $data['username'] = $user['username'];
                $data['user_id'] = $user['id'];
                $data['user'] = $user;
            };
            if ($principal->hasRefreshToken()) {
                $data['refreshToken'] = $principal->getRefreshToken();
            }
            $this->app->view()->appendData($data);
        }

        $this->next->call();
    }
}
