<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * EasyExclude Extension for Contao Open Source CMS
 *
 * @author     Frank Müller <frank.mueller@linking-you.de>
 * @license    LGPL-3.0+
 */

/**
 * Table tl_user
 */
// replace palettes
foreach($GLOBALS['TL_DCA']['tl_user']['palettes'] as $palette =>$v)
{
    if($palette == '__selector__')
    {
        continue;
    }

    $objUser = BackendUser::getInstance();

    if($objUser->isAdmin)
    {
        \Contao\CoreBundle\DataContainer\PaletteManipulator::create()
            ->addLegend('easyExclude_legend', null, \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
            ->addField('easyExclude_enable', 'easyExclude_legend')
            ->applyToPalette($palette, 'tl_user');
    }
}

// add field
$GLOBALS['TL_DCA']['tl_user']['fields']['easyExclude_enable'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_user']['easyExclude_enable'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                      => array('tl_class'=>'w50'),
    'sql' => "char(1) NOT NULL default ''"
);

