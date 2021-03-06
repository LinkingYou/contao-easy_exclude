<?php

/**
 * EasyExclude Extension for Contao Open Source CMS
 *
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @author     Frank Müller <frank.mueller@linking-you.de>
 * @license    LGPL-3.0+
 */

namespace EasyExclude\Backend;

use Contao\Backend;
use Contao\DC_Table;

if (!defined('TL_ROOT')) die('You cannot access this file directly!');

class EasyExclude extends Backend
{

	/**
	 * Initialize the object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Input');
	}


	/**
	 * HOOKED "outputBackendTemplate": Add the dropdown menu to choose the usergroup from
	 * @param string
	 * @param string
	 * @return string
	 */
	public function addUsergroupSelect($strContent, $strTemplate)
	{
		if($strTemplate == 'be_main' && $GLOBALS['EasyExclude']['addEasyExclude'])
		{
			$strContent = preg_replace('/(<h1 id="main_headline">.*<\/h1>)/', "$1" . $this->generateUsergroupSelect(), $strContent);
		}

		return $strContent;
	}


	/**
	 * Generates the dropdown box
	 * @return string
	 */
	private function generateUsergroupSelect()
	{
		$strSelect = '<div class="tl_panel"><div class=" tl_subpanel"><strong>EasyExclude Gruppe:</strong> <div id="easyExclude_container"><select id="easyExclude_usergroup" name="easyExclude_usergroup" class="tl_select">';

		$objGroups = $this->Database->query("SELECT id,name FROM tl_user_group WHERE disable!=1");

		// default is none
		$strSelect .= '<option value="0">-</option>';

		while ($objGroups->next())
		{
			$strSelect .= '<option value="' . $objGroups->id . '">' . $objGroups->name . '</option>';
		}

		$strSelect .= '</select></div></div><div class="clear"></div></div>';
		$strSelect .=  "<script>
						window.addEvent('domready', function()
						{
							new EasyExclude(
							{
								table: '" . $GLOBALS['EasyExclude']['strTable'] . "'
							});
						});
						</script>";

		return $strSelect;
	}


	/**
	 * HOOKED "loadDataContainer": Add the classes to the fields, javascript and css file, onload_callback
	 * @param string
	 */
	public function addEasyExclude($strTable)
	{
		if($this->hasPermissions())
		{
			// if there are no fields at all easy_exclude is useless
			if(is_array($GLOBALS['TL_DCA'][$strTable]['fields']) && count($GLOBALS['TL_DCA'][$strTable]['fields']))
			{
				// add the global css and javascripts
				$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/easyexclude/assets/easyExclude_src.min.js|static';
				$GLOBALS['TL_CSS'][]		= 'bundles/easyexclude/assets/easyExclude.css|screen';

				// add classes to the fields
				$arrFields = array_keys($GLOBALS['TL_DCA'][$strTable]['fields']);

				foreach($arrFields as $field)
				{
					$GLOBALS['TL_DCA'][$strTable]['fields'][$field]['eval']['tl_class'] = trim($GLOBALS['TL_DCA'][$strTable]['fields'][$field]['eval']['tl_class'] . ' easyExclude easyExcludeFN_' . $field);
				}

				// add the onload_callback where we get the $dc object and can check whether it's an instance of DC_Table or not
				$GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'][] = array('EasyExclude\\Backend\\EasyExclude', 'checkIfDCTableInstance');
			}
		}
	}


	/**
	 * HOOKED "onload_callback": Check if instance of DC_Table and if so, set the global variable to true, so we can add the dropdown in the outputBackendTemplate hook
	 * @param object
	 */
	public function checkIfDCTableInstance($dc)
	{
		$GLOBALS['EasyExclude']['addEasyExclude'] = false;

		if($dc instanceof DC_Table && $this->Input->get('act') == 'edit')
		{
			// globals to enable easy_exclude
			$GLOBALS['EasyExclude']['addEasyExclude'] = true;
			$GLOBALS['EasyExclude']['strTable'] = $dc->table;
		}
	}


	/**
	 * Do some ajax things
	 * @param string
	 * @param object
	 */
	public function doAjaxForMe($strAction, DC_Table $dc)
	{
		/**
		 * This action gets a list of all fields that the usergroup has access to
		 */
		if($strAction == 'easyExcludeGetFieldRights')
		{
			$objAlexf = $this->Database->prepare("SELECT alexf FROM tl_user_group WHERE id=?")->limit(1)->execute($this->Input->post('usergroup'));
			$arrAlexf = deserialize($objAlexf->alexf);

            $arrAllowedFields = array();

			if ($arrAlexf) {
                $key = $this->Input->post('table') . '::';

                foreach($arrAlexf as $field)
                {
                    if(strpos($field, $key) !== false)
                    {
                        $arrAllowedFields[] = str_replace($key, '', $field);
                    }
                }

            }

			// prevent returning "Array" instead of an empty output
			if (!count($arrAllowedFields))
			{
				$arrAllowedFields = '';
			}
			// if it has entries, make sure they are unique
			else
			{
				// use array_values() here to make sure it's an array.
				// array_unique() can cause an unnatural order of the keys which in turn results in json_encode() making an object instead of an array
				$arrAllowedFields = array_values(array_unique($arrAllowedFields));
			}

			 $this->outputAjax($arrAllowedFields);
		}

		/**
		 * This action stores the changes made for a certain field
		 */
		if($strAction == 'easyExcludeSaveChange')
		{
			$uid = $this->Input->post('usergroup');
			$state = $this->Input->post('state');

			$key = $this->Input->post('table') . '::' . $this->Input->post('field');

			$arrAlexf = array();
			$objAlexf = $this->Database->prepare("SELECT alexf FROM tl_user_group WHERE id=?")->limit(1)->execute($uid);
			$arrAlexf = deserialize($objAlexf->alexf);

			// remove
			if($state == 0)
			{
				unset($arrAlexf[array_search($key, $arrAlexf)]);
			}

			// add
			if($state == 1)
			{
				$arrAlexf[] = $key;
			}

			// update
			$this->Database->prepare("UPDATE tl_user_group SET alexf=? WHERE id=?")->execute(serialize($arrAlexf), $uid);

			// output anyway because Contao wants the token
			$this->outputAjax('');
		}
	}


	/**
	 * Check wether user is admin and has easy_exclude enabled
	 * @return boolean
	 */
	private function hasPermissions()
	{
		$this->import('BackendUser', 'User');

		// the user has to be admin and enabled easyExclude
		return ($this->User->isAdmin && $this->User->easyExclude_enable);
	}


	/**
	 * Output data requested by ajax
	 * @param mixed content
	 */
	private function outputAjax($varContent)
	{
		echo json_encode(array
		(
			'content'	=> $varContent,
			'token'		=> REQUEST_TOKEN
		));
		exit;
	}
}