<?php

/**
 * EasyExclude Extension for Contao Open Source CMS
 *
 * @author     Frank Müller <frank.mueller@linking-you.de>
 * @license    LGPL-3.0+
 */

namespace EasyExclude\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create('EasyExclude\EasyExcludeBundle')
                ->setLoadAfter([ContaoCoreBundle::class])
        ];
    }
}