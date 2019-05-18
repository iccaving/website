<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Bolt\Menu\MenuEntry;

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
    
    protected function registerMenuEntries()
    {
        $menu = MenuEntry::create('photo-menu', '../files/files/photo_archive/')
            ->setLabel('Photos')
            ->setIcon('fa:photo')
            ->setPermission('files:uploads')
        ;
        
        return [
            $menu,
        ];
    }
    
}
