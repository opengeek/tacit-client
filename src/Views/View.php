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

use Slim\Slim;
use Tacit\Client\Principal;

/**
 * Simple View class for a Tacit web app.
 *
 * @package Tacit\Views
 */
class View
{
    /**
     * @var Slim The Slim application controlling the View.
     */
    protected $app;
    /**
     * @var string Space-delimited string of scopes a Principal must have to access the view.
     */
    protected $scope;

    /**
     * Create an instance of the View.
     *
     * @param Slim   $app The Slim app controlling the View.
     * @param string $scope Space-delimited string of scopes to require for access.
     */
    public function __construct(Slim $app, $scope = '')
    {
        $this->app =& $app;
        $this->scope = $scope;

        if (!empty($this->scope)) {
            $this->checkScope();
        }
    }

    /**
     * Determine if a Principal has the scope(s) required to access this View.
     *
     * @param string    $scope Space-delimited string of scopes to check.
     * @param null|Slim $app An optional Slim app containing the Principal.
     *
     * @throws \Slim\Exception\Stop
     * @return bool
     */
    public static function hasScope($scope, $app = null)
    {
        if (null === $app) {
            $app = Slim::getInstance();
        }

        /** @var Principal $principal */
        $principal = $app->container->get('principal');
        if (null === $principal) {
            $resourceUrl = $app->request->getRootUri() . $app->request->getResourceUri();
            $app->render('login.twig', ['return' => $resourceUrl], 401);
            $app->stop();
        }
        $scopes = explode(' ', $principal->getScope());

        $scope = explode(' ', $scope);
        $matches = array_intersect($scope, $scopes);

        return count($matches) === count($scope);
    }

    /**
     * Handle a request for a View by a specified template name.
     *
     * @throws \Slim\Exception\Stop To halt execution and finalize the response.
     */
    public function handle()
    {
        $argCount = func_num_args();
        if ($argCount > 0) {
            $template = func_get_arg(0);
            $data = $argCount > 1 ? func_get_arg(1) : [];

            try {
                $this->app->view()->display($template, $data);
            } catch (\Exception $e) {
                $this->app->halt(500, $e->getMessage());
            }
            $this->app->stop();
        }
        $this->app->notFound();
    }

    /**
     * Check to see if the Principal has appropriate scope to access the Resource.
     */
    protected function checkScope()
    {
        if (!static::hasScope($this->scope, $this->app)) {
            $this->app->halt(403, 'You do not have rights to access this resource.');
        }
    }
}
