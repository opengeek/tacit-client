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

class TwigExtension extends \Twig_Extension
{

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'tacit';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('ago', [$this, 'ago'])
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('referrer', [$this, 'referrer']),
            new \Twig_SimpleFunction('self', [$this, 'self'])
        ];
    }

    public function ago($start, $end = null)
    {
        $then = new \DateTime(strtotime($start));
        if (!($then instanceof \DateTime)) {
            $then = new \DateTime();
        }
        $now  = $end ? new \DateTime(strtotime($end)) : new \DateTime();

        $interval = $now->diff($then);

        if ($interval->y > 0) {
            return $interval->format("%y {$this->pluralize($interval->y, 'year')} ago");
        }
        if ($interval->m > 0) {
            return $interval->format("%m {$this->pluralize($interval->m, 'month')} ago");
        }
        if ($interval->d > 0) {
            return $interval->format("%d {$this->pluralize($interval->d, 'day')} ago");
        }
        if ($interval->h > 0) {
            return $interval->format("%h {$this->pluralize($interval->h, 'hour')} ago");
        }
        if ($interval->i > 0) {
            return $interval->format("%i {$this->pluralize($interval->i, 'minute')} ago");
        }
        if ($interval->s > 30) {
            return 'less than a minute ago';
        }
        return 'just now';
    }

    public function referrer($localOnly = true, $appName = 'default')
    {
        $app = Slim::getInstance($appName);

        $referrer = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referrer = $_SERVER['HTTP_REFERER'];
            if ($localOnly && strpos($_SERVER['HTTP_REFERER'], $app->request()->getUrl()) !== 0) {
                $referrer = $app->request()->getUrl() . $app->request->getRootUri();
            }
        }
        return $referrer;
    }

    public function self($withBase = true, $appName = 'default')
    {
        $request = Slim::getInstance($appName)->request();
        $uri = $request->getUrl();
        if ($withBase) {
            $uri .= $request->getRootUri();
        }
        $uri .= $request->getResourceUri();
        return $uri;
    }

    private function pluralize($value, $singular)
    {
        return $value > 1 ? $singular . 's' : $singular;
    }
}
