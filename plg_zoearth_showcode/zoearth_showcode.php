<?php
defined('_JEXEC') or die ;

jimport('joomla.plugin.plugin');

class plgSystemZoearth_ShowCode extends JPlugin
{
    function onAfterRender()
    {
		static $showed;
        $response = JResponse::getBody();
        //可能有 prettyprint 的 class
        if (strpos($response,'prettyprint') && !$showed)
        {
			$showed = TRUE;
            //載入JS CSS
			$addText = '
<script src="//google-code-prettify.googlecode.com/svn/loader/run_prettify.js"></script>
<style type="text/css">
.codeblock {
	display: block; /* fixes a strange ie margin bug */
	font-family: Courier New;
	font-size: 10pt;
	overflow:auto;
	background: #f0f0f0 url(data:image/gif;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAASwCAYAAAAt7rCDAAAABHNCSVQICAgIfAhkiAAAAQJJREFUeJzt0kEKhDAMBdA4zFmbM+W0upqFOhXrDILwsimFR5pfMrXW5jhZr7PwRlxVX8//jNHrGhExjXzdu9c5IiIz+7iqVmB7Hwp4OMa2nhhwN/PRGEMBh3Zjt6KfpzPztxW9MSAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzAMwzB8HS+J9kUTvzEDMwAAAABJRU5ErkJggg==) left top repeat-y;
	border: 1px solid #ccc;
	padding: 10px 10px 10px 21px !important;
	max-height:1000px;
	line-height: 1.2em;
}
</style>
';
			$response = JResponse::getBody();
			JResponse::setBody(str_replace('</head>',$addText.'</head>',$response));
        }
    }
}