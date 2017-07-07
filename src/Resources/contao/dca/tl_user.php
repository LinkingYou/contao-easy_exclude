<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  terminal42 gmbh
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @package    Backend
 * @license    LGPL
 * @filesource
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

