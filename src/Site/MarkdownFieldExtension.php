<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Bolt\Storage\FieldManager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\Type\FieldTypeBase;
use Bolt\Storage\QuerySet;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;

class MarkdownFieldExtension extends SimpleExtension
{

    public function getServiceProviders()
    {
        return [
            $this,
            new FieldProvider()
        ];
    }

    protected function registerTwigPaths()
    {
        return [
            'theme/iccc' => ['position' => 'prepend', 'namespace'=>'bolt']
        ];
    }

    protected function registerAssets()
    {
		$app = $this->getContainer();
		$siteurl = $app['config']->get('general/siteurl');

        $jsasset1 = JavaScript::create()
            ->setFileName($siteurl . '/theme/iccc/js/editormd/editormd.js')
            ->setLate(true)
            ->setPriority(5)
            ->setZone(Zone::BACKEND)
        ;
        $jsasset2 = JavaScript::create()
            ->setFileName($siteurl . '/theme/iccc/js/editormd/languages/en.js')
            ->setLate(true)
            ->setPriority(5)
            ->setZone(Zone::BACKEND)
        ;
        $jsasset3 = JavaScript::create()
        ->setFileName($siteurl . '/theme/iccc/js/editormd/init.js')
        ->setLate(true)
        ->setPriority(5)
        ->setZone(Zone::BACKEND)
    ;
        $cssasset = Stylesheet::create()
            ->setFileName($siteurl . '/theme/iccc/js/editormd/css/editormd.min.css')
            ->setLate(true)
            ->setPriority(5)
            ->setZone(Zone::BACKEND)
        ;

        return [ $jsasset1, $jsasset2, $jsasset3, $cssasset ];
    }

}

class FieldProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['storage.typemap'] = array_merge(
            $app['storage.typemap'],
            [
                'markdown' => MarkdownFieldType::class
            ]
        );

        $app['storage.field_manager'] = $app->share(
            $app->extend(
                'storage.field_manager',
                function (FieldManager $manager) {
                    $manager->addFieldType('markdown', new MarkdownFieldType());

                    return $manager;
                }
            )
        );

    }

    public function boot(Application $app)
    {
    }
}

class MarkdownFieldType extends FieldTypeBase
{

    public function getName()
    {
        return 'markdown';
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getStorageOptions()
    {
        return [
          'default' => ''
        ];
    }

}