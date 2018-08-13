<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * EasyExclude Extension for Contao Open Source CMS
 *
 * @author     Frank Müller <frank.mueller@linking-you.de>
 * @license    LGPL-3.0+
 */

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['outputBackendTemplate'][]	= array('EasyExclude\\Backend\\EasyExclude', 'addUsergroupSelect');
$GLOBALS['TL_HOOKS']['loadDataContainer'][]		= array('EasyExclude\\Backend\\EasyExclude', 'addEasyExclude');
$GLOBALS['TL_HOOKS']['executePostActions'][]	= array('EasyExclude\\Backend\\EasyExclude', 'doAjaxForMe');