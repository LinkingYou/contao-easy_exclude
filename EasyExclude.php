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
 * @package    easyExclude
 * @license    LGPL
 * @filesource
 */
	

/**
 * Class EasyExclude 
 *
 * @copyright  certo web & design GmbH 2010 - 2011 
 * @author     Yanick Witschi <yanick.witschi@certo-net.ch> 
 * @package    Controller
 */
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
            $strContent = preg_replace('/(<h1 class="main_headline">[^<].*<\/h1>)/', "$1" . $this->generateUsergroupSelect(), $strContent);
        }
         
        return $strContent;
    }
    
    
    /**
     * Generates the dropdown box
     * @return string
     */
    private function generateUsergroupSelect()
    {
        $strSelect = '<div id="easyExclude_container"><select id="easyExclude_usergroup" name="easyExclude_usergroup" class="tl_select">';
        
        $objGroups = $this->Database->query("SELECT id,name FROM tl_user_group WHERE disable!=1");
        
        // default is none
        $strSelect .= '<option value="0">-</option>';
        
        while ($objGroups->next())
        {
            $strSelect .= '<option value="' . $objGroups->id . '">' . $objGroups->name . '</option>';
        }
        
        $strSelect .= '</select></div>';
        
        // add javascript call
        $arrTags = self::getScriptTags();
        
        $strSelect .=  $arrTags[0] . "window.addEvent('domready', function()
                        {
                            new EasyExclude(
                            {
                                table: '" . $GLOBALS['EasyExclude']['strTable'] . "'
                            });
                        });" . $arrTags[1];
        
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
                $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/easy_exclude/html/easyExclude.js';
                $GLOBALS['TL_CSS'][]        = 'system/modules/easy_exclude/html/easyExclude.css|screen';
            
                // add classes to the fields
                $arrFields = array_keys($GLOBALS['TL_DCA'][$strTable]['fields']);
                
                foreach($arrFields as $field)
                {
                    $GLOBALS['TL_DCA'][$strTable]['fields'][$field]['eval']['tl_class'] .= trim($GLOBALS['TL_DCA'][$strTable]['fields'][$field]['eval']['tl_class'] . ' easyExclude easyExcludeFN_' . $field);                
                }
                
                // add the onload_callback where we get the $dc object and can check whether it's an instance of DC_Table or not
                $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'][] = array('EasyExclude', 'checkIfDCTableInstance');
            }
        }
    }
    
    
    /**
     * HOOKED "onload_callback": Check if instance of DC_Table and if so, set the global variable to true, so we can add the dropdown in the outputBackendTemplate hook
     * @param object
     */
    public function checkIfDCTableInstance(DataContainer $dc)
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
    public function doAjaxForMe($strAction, DataContainer $dc)
    {
        /**
         * This action gets a list of all fields that the usergroup has access to
         */
        if($strAction == 'easyExcludeGetFieldRights')
        {
            $objAlexf = $this->Database->prepare("SELECT alexf FROM tl_user_group WHERE id=?")->limit(1)->execute($this->Input->post('usergroup'));
            $arrAlexf = deserialize($objAlexf->alexf);
            
            $arrAllowedFields = array();
            $key = $this->Input->post('table') . '::';

            foreach($arrAlexf as $field)
            {
                if(strpos($field, $key) !== false)
                {
                    $arrAllowedFields[] = str_replace($key, '', $field);
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
        // return token from Contao 2.10 on
        if (version_compare(VERSION.'.'.BUILD, '2.10.0', '>='))
        {
            echo json_encode(array
            (
                'content'    => $varContent,
                'token'        => REQUEST_TOKEN
            ));
        }
        else
        {
            echo json_encode($varContent);
        }
        exit;
    }
    
    
    /**
     * Get html & javascript tags depending on output format (Contao 2.10)
     * @return array
     */
    public static function getScriptTags()
    {
        if (version_compare(VERSION.'.'.BUILD, '2.10.0', '>='))
        {
            return array('<script>', '</script>');
        }
        else
        {
            return array('<script type="text/javascript">'."\n".'<!--//--><![CDATA[//><!--' . "\n", '//--><!]]>'."\n".'</script>');
        }
    }
}