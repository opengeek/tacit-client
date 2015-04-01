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
use Slim\Slim;

class View
{
    protected $app;
    protected $scope;

    public static function hasScope($scope, $app = null)
    {
        if (null === $app) $app = Slim::getInstance();

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

    public function __construct(Slim $app, $scope = '')
    {
        $this->app =& $app;
        $this->scope = $scope;

        if (!empty($this->scope)) $this->checkScope();
    }

    public function handle()
    {
        $argCount = func_num_args();
        if ($argCount > 0) {
            $template = func_get_arg(0);
            $data = $argCount > 1 ? func_get_arg(1) : [];

            try {
                $this->app->render($template, $data);
            } catch (\Exception $e) {
                $this->app->halt(500, $e->getMessage());
            }
            $this->app->stop();
        }
        $this->app->notFound();
    }

    protected function checkScope()
    {
        if (!static::hasScope($this->scope)) {
            $this->app->halt(403, 'You do not have rights to access this resource.');
        }
    }

}
