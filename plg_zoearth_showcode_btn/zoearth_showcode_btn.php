<?php
defined('_JEXEC') or die;

class plgZoearthShowCodeBtn extends JPlugin
{
    public function onDisplay($name)
    {
// 		$txt = $this->params->get('custom_text', '[set your custom text in plugin parameters]');
// 		$label = $this->params->get('label', 'Custom Text');
// 		$jsCode = "
//                 function insertCustText(editor) {
//                     jInsertEditorText('".$txt."', editor);
//                 }
// 				";
				
// 		$doc = JFactory::getDocument();
// 		$doc->addScriptDeclaration($jsCode);
		
        $button = new JObject();				
		$button->modal = false;
		$button->class = 'btn';
        $button->text = 'ZoearthShowCode';
        $button->name = 'ZoearthShowCode name';
		//$button->onclick = 'insertCustText(\''.$name.'\');return false;';				$button->link = '#';
        $button->onclick = 'alert("good!");return false;';
        $button->link = '#';
        return $button;
    }
}
?>