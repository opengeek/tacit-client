<?php
/*
 * This file is part of the Tacit Client package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tacit\Views;

use Tacit\Client\Principal;

/**
 * A Logout View.
 *
 * @package Tacit\Views
 */
class Logout extends View
{
    public function handle()
    {
        switch ($this->app->request->getMethod()) {
            case 'GET':
            case 'POST':
                /* if not already authenticated, redirect to Login */
                $principal = $this->app->container->get('principal');
                if (!$principal instanceof Principal) {
                    $this->app->redirect($this->app->request->getUrl() . $this->app->urlFor('Login'));
                }

                $redirectUrl = $this->app->request->params('return')
                    ?: $this->app->request->getUrl() . $this->app->urlFor('Home');

                Principal::endSession();

                $this->app->container->set('principal', null);
                unset($principal);

                $this->app->redirect($redirectUrl);
                break;
            default:
                $this->app->halt(405, 'Method Not Allowed');
                break;
        }
    }
}
