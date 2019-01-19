<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;


class PhotoScriptExtension extends SimpleExtension
{
    protected function registerAssets()
    {
        $widgetObj = new Widget();
        $widgetObj
            ->setZone('backend')
            ->setLocation('files_below_header')
            ->setCallback([$this, 'script'])
            ->setCallbackArguments([])
            ->setDefer(false)
        ;
        $asset = JavaScript::create()
            ->setFileName('/theme/iccc/js/photoscript.js')
            ->setLate(true)
            ->setPriority(5)
            ->setAttributes(['defer', 'async'])
            ->setZone(Zone::BACKEND)
        ;

        return [ $widgetObj, $asset ];
    }

    public function script() 
    {
        $app = $this->getContainer();

        // Data to pass into the widget
        $data = [ 'title' => 'hello' ];


        // Render the template, and return the results
        return $this->renderTemplate('script.twig', $data);
    }
}