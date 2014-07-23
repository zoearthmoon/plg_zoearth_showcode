<?php
defined('_JEXEC') or die;

class plgButtonZoearth_ShowCode_Btn extends JPlugin
{
    public function onDisplay($name)
    {
		?>
		<script language="Javascript">
		var showCodeInput = function (){
			jQuery('#showCodeInputModal').modal('show');
		};
		jQuery(document).ready(function() {
			jQuery('#showCodeInputModal').on('show',function (){
				//出現modal時清空textarea
				jQuery("#showCodeContent").val("");
			});
			//儲存
			jQuery("#zoearthCodeInput").click(function(){
				var showCodeContent = jQuery("#showCodeContent").val().trim();
				showCodeContent = '<pre class="prettyprint" style="background-color: #c0c0c0;">'+showCodeContent+'</pre><br>';
				jInsertEditorText(showCodeContent, 'jform_articletext');
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
        $button = new JObject();				
		$button->modal = FALSE;
		$button->class = 'btn';
        $button->text = JText::_('PLG_ZOEARTH_SHOW_CODE_BTN');
        $button->name = 'ZoearthShowCode Name';
        $button->onclick = 'showCodeInput();return false;';
        $button->link = '#';
        return $button;
    }
}
?>