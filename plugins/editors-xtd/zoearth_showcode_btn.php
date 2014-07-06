<?php
/**
 * @version   	1.4
 * @package     Joomla
 * @subpackage  System
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters. All rights reserved.
 * @license     GNU GPL v2.0
 */
 
defined('_JEXEC') or die;

class plgButtonCustomText extends JPlugin
{
    public function onDisplay($name)
    {
		$txt = $this->params->get('custom_text', '[set your custom text in plugin parameters]');
		$label = $this->params->get('label', 'Custom Text');
		$jsCode = "
                function insertCustText(editor) {
                    jInsertEditorText('".$txt."', editor);
                }
				";
				
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration($jsCode);
		
        $button = new JObject();				
		$button->modal = false;
		$button->class = 'btn';
        $button->text = $label;
        $button->name = 'blank';
		$button->onclick = 'insertCustText(\''.$name.'\');return false;';				$button->link = '#';
        return $button;
    }
}
?>