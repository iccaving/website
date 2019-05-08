<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Silex\Application;

require_once('HelperFunctions.php');

class BaseExtension extends SimpleExtension
{
    protected function registerServices(Application $app)
    {
        $app['twig'] = $app->share($app->extend(
            'twig',
            function ($twig) use ($app) {
                $twig->addGlobal('siteurl', $app['config']->get('general/siteurl'));
                return $twig;
            }
        ));
    }
}
