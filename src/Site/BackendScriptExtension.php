<?php

namespace Bundle\Site;

use Bolt\Extension\SimpleExtension;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;


class BackendScriptExtension extends SimpleExtension
{
    protected function registerAssets()
    {
        $asset = JavaScript::create()
            ->setFileName('/rcc/caving/theme/iccc/js/backend.js')
            ->setLate(true)
            ->setPriority(5)
            ->setAttributes(['defer', 'async'])
            ->setZone(Zone::BACKEND)
        ;

        return [ $asset ];
    }
}
