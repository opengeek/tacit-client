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
use Tacit\Client\RestfulException;

class Login extends View
{
    public function handle()
    {
        switch ($this->app->request->getMethod()) {
            case 'GET':
                /* if already authenticated, redirect to Home? */
                $principal = $this->app->container->get('principal');
                if ($principal instanceof Principal && $principal->isAuthorized()) {
                    $this->app->redirect($this->app->request->getUrl() . $this->app->urlFor('Home'));
                }

                $this->app->render('login.twig');
                break;
            case 'POST':
                $this->post();
                break;
            default:
                $this->app->halt(405, 'Method Not Allowed');
                break;
        }
    }

    public function post()
    {
        try {
            if (Principal::authenticate($this->app->request->post('username'), $this->app->request->post('password'))) {
                $this->app->redirect($this->app->request->post('return', $this->app->request->getUrl() . $this->app->urlFor('Home')));
            }
        } catch (RestfulException $e) {
            $er = $e->getResource();
            $this->app->view()->appendData(
                [
                    'errorMessage' => $e->getMessage(),
                    'errorDescription' => $e->getDescription(),
                    'errors' => isset($er['property']) && is_array($er['property']) ? $er['property'] : []
                ]
            );
        }

        $this->app->render('login.twig', $this->app->request->post(null, []));
    }
}
