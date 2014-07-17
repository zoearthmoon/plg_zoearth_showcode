<?php
defined('_JEXEC') or die ;

jimport('joomla.plugin.plugin');

class plgSystemZoearthShowCode extends JPlugin
{
    function onAfterRender()
    {
        $response = JResponse::getBody();
        //可能有 prettyprint 的 class
        if (strpos('prettyprint',$response))
        {
            //載入JS
            
        }
    }
}