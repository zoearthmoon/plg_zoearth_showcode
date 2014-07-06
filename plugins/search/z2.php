<?php
defined('_JEXEC') or die ;

jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'searchEncode.php');

class plgSearchZ2 extends JPlugin
{
    function onContentSearchAreas()
    {
        return $this->onSearchAreas();
    }

    function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
    {
        return $this->onSearch($text, $phrase, $ordering, $areas);
    }

    function onSearchAreas()
    {
        //20140530 zoearth 修改最小搜尋字元數
        $lang = JFactory::getLanguage();
        $lang->setLowerLimitSearchWordCallback(function(){return 1;});
        
        JPlugin::loadLanguage('plg_search_z2', JPATH_ADMINISTRATOR);
        static $areas = array('z2' => 'Z2_ITEMS');
        return $areas;
    }

    function onSearch($text, $phrase = '', $ordering = '', $areas = null)
    {
        JPlugin::loadLanguage('plg_search_z2', JPATH_ADMINISTRATOR);
        jimport('joomla.html.parameter');
        
        $tagIDs = array();
        $itemIDs = array();

        require_once (JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_search'.DS.'helpers'.DS.'search.php');
        require_once (JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'route.php');

        $searchText = $text;
        if (is_array($areas))
        {
            if (!array_intersect($areas, array_keys($this->onSearchAreas())))
            {
                return array();
            }
        }

        $plugin = JPluginHelper::getPlugin('search', 'z2');
        $pluginParams = class_exists('JParameter') ? new JParameter($plugin->params) : new JRegistry($plugin->params);
        $limit = $pluginParams->def('search_limit', 50);

        $text = JString::trim($text);
        
        if ($text == '')
        {
            return array();
        }
        
        $rows = array();
        
        if ($limit > 0)
        {
            $db = JFactory::getDBO();
            $language = Z2HelperLang::getNowLang();
            $user = JFactory::getUser();
            
            //20131104 zoearth 取得其他語系資料
            $addArray = Z2HelperQueryData::$itemAddArray;
            foreach ($addArray as $v)
            {
                $addSelect .= ",lang.".$v;
            }
            $addJoin = " LEFT JOIN #__z2_items_lang AS lang ON lang.itemId = i.id";
            $addWhere = " AND lang.language = ".$db->Quote($language)." AND lang.active=1 ";
            
            $jnow = JFactory::getDate();
            $now = $jnow->toSql();
            
            $nullDate = $db->getNullDate();
            
            $query = "SELECT i.* $addSelect
                FROM #__z2_items AS i $addJoin ";
            
            $addWhere .= "AND MATCH (lang.extra_fields_search) AGAINST ('".Z2HelperSearchEncode::toSearchKey($text)."' IN BOOLEAN MODE) ";
            
            $query .= "WHERE
                i.published=1 AND (
                i.publish_up = ".$db->Quote($nullDate)." OR
                i.publish_up <= ".$db->Quote($now)." ) AND (
                i.publish_down = ".$db->Quote($nullDate)." OR
                i.publish_down >= ".$db->Quote($now)." )
                AND i.trash=0 $addWhere ";
            
            $query .= " AND i.access IN(".implode(',',$user->getAuthorisedViewLevels()).") ";
            switch ($ordering)
            {
                case 'oldest':  $query .= 'ORDER BY i.created ASC'; break;
                case 'popular': $query .= 'ORDER BY i.hits DESC'; break;
                case 'alpha':   $query .= 'ORDER BY i.title ASC'; break;
                case 'category':$query .= 'ORDER BY c.name ASC, i.title ASC';break;
                case 'newest':
                default:        $query .= 'ORDER BY i.created DESC';break;
            }

            $db->setQuery($query,0,$limit);
            $rows = $db->loadObjectList();
            $menus = array();
            $i = 0;
            foreach ($rows as $item)
            {
                $menus[$i] = (object)Z2HelperQueryData::itemData($item);
                $menus[$i]->title = $menus[$i]->name;
                $menus[$i]->href = $menus[$i]->link;
                
                $menus[$i]->active = FALSE;
                $i++;
            }
        }
        //text created count results
        $results = $menus;
        return $results;
    }

}
