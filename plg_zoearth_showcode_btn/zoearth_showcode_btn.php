<?php
defined('_JEXEC') or die;

class plgButtonZoearth_ShowCode_Btn extends JPlugin
{
    public function onDisplay($name)
    {
		static $showed;
		if (!$showed):
		$lang = JFactory::getLanguage();
		$lang->load('plg_zoearth_showcode_btn',JPATH_ADMINISTRATOR);
		?>
		<script language="Javascript">
		var showCodeInput = function (editorName){
			jQuery.data(document.body,"editorName",editorName);
			jQuery('#showCodeInputModal').modal('show');			
		};
		jQuery(document).ready(function() {
			jQuery('#showCodeInputModal').on('show',function (){
				//出現modal時清空textarea
				jQuery("#showCodeContent").val("");
			});
			//儲存
			jQuery("#zoearthCodeInput").click(function(){
				var editorName = jQuery.data(document.body,"editorName");
				var showCodeContent = jQuery("#showCodeContent").val().trim();
				showCodeContent = '<pre class="prettyprint" style="background-color: #c0c0c0;">'+showCodeContent+'</pre><br>';
				jInsertEditorText(showCodeContent, editorName);
				jQuery('#showCodeInputModal').modal('hide');
			});
		});
		</script>
		<div id="showCodeInputModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h3 id="myModalLabel"><?php echo JText::_('PLG_ZOEARTH_SHOW_CODE_BTN')?></h3>
			</div>
			<div class="modal-body">
				<textarea id="showCodeContent" rows="8" class="span12"></textarea>
			</div>
			<div class="modal-footer">
				<a class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JOFF') ?></a>
				<a class="btn btn-primary" id="zoearthCodeInput" ><?php echo JText::_('JSUBMIT') ?></a>
			</div>
		</div>
		<?php
		$showed = TRUE;
		endif;
		
        $button = new JObject();				
		$button->modal = FALSE;
		$button->class = 'btn';
		$button->title = JText::_('PLG_ZOEARTH_SHOW_CODE_BTN');
        $button->text = JText::_('PLG_ZOEARTH_SHOW_CODE_BTN');
        $button->name = 'comment';
        $button->onclick = 'showCodeInput(\''.$name.'\');return false;';
        $button->link = '#';
        return $button;
    }
}
?>