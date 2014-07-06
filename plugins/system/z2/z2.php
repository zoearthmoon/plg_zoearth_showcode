<?php
defined('_JEXEC') or die ;

jimport('joomla.plugin.plugin');

class plgSystemZ2 extends JPlugin
{

    function plgSystemZ2(&$subject, $config)
    {
        parent::__construct($subject, $config);
    }

    function onAfterRoute()
    {
        //20140303 zoearth 率先先載入jquery
        JHtml::_('bootstrap.framework');
        $mainframe = JFactory::getApplication();
        $user = JFactory::getUser();
        $basepath = ($mainframe->isSite()) ? JPATH_SITE : JPATH_ADMINISTRATOR;

        JPlugin::loadLanguage('com_z2', $basepath);
        JPlugin::loadLanguage('com_z2.j16', JPATH_ADMINISTRATOR, null, true);

        if ($mainframe->isAdmin())
        {
            return;
        }
        if ((JRequest::getCmd('task') == 'add' || JRequest::getCmd('task') == 'edit') && JRequest::getCmd('option') == 'com_z2')
        {
            return;
        }

        // Joomla! modal trigger
        if ( !$user->guest || (JRequest::getCmd('option') == 'com_z2' && JRequest::getCmd('view') == 'item') || defined('Z2_JOOMLA_MODAL_REQUIRED') ){
            JHTML::_('behavior.modal');
        }

        $params = JComponentHelper::getParams('com_z2');

        //20140212 zoearth 刪除google search

        //20140212 zoearth 不使用Z2預設CSS
        $menu = JFactory::getApplication()->getMenu();
        $menuId = @$menu->getActive()->id;
        if ($menuId > 0 && JRequest::getCmd('option') != 'com_z2' )
        {
            
            $pathways = & $mainframe->getPathway()->getPathway();
            $mainframe->getPathway()->setPathway(array());
            
            
            
            
            .................
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            $db = JFactory::getDBO();
            $language = Z2HelperLang::getNowLang();
            $addArray = Z2HelperQueryData::$categoryAddArray;
            $addSelect = '';
            foreach ($addArray as $v)
            {
                $addSelect .= ",lang.".$v;
            }
            $addJoin = " LEFT JOIN #__z2_categories_lang AS lang ON lang.catid = c.id";
            $addWhere = " AND lang.language = ".$db->Quote($language)." AND lang.active=1 ";
            $query = "SELECT c.* $addSelect
                FROM #__z2_categories AS c $addJoin
                WHERE c.menuId = ".(int)$menuId." AND published=1 AND trash=0 $addWhere ";
            $db->setQuery($query,0,1);
            $row = $db->loadObjectlist();
            if ($row)
            {
                $row->extra_fields = Z2HelperExtendField::getExtendField($row->extra_fields);
                Z2HelperRelateMenu::$data = $row;
            }
        }
    }

    //20140212 zoearth 刪除Z2的會員功能
    //function onAfterDispatch(){}

    //20140212 zoearth 初始化需要設定的
    function onAfterInitialise()
    {
        // Determine Joomla! version
        if (version_compare(JVERSION, '3.0', 'ge'))
        {
            define('Z2_JVERSION', '30');
        }
        else if (version_compare(JVERSION, '2.5', 'ge'))
        {
            define('Z2_JVERSION', '25');
        }
        else
        {
            define('Z2_JVERSION', '15');
        }

        // Define the DS constant under Joomla! 3.0
        if (!defined('DS'))
        {
            define('DS', DIRECTORY_SEPARATOR);
        }

        // Import Joomla! classes
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        jimport('joomla.application.component.controller');
        jimport('joomla.application.component.model');
        jimport('joomla.application.component.view');
        
        // Get application
        $mainframe = JFactory::getApplication();

        //helper
        require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'lang.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'queryData.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'extendField.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'relateMenu.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'utilities.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'image.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'route.php');
        require_once(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'helpers'.DS.'paramData.php');//20140421 zoearth
        
        // Load the Z2 classes
        JLoader::register('Z2Table', JPATH_ADMINISTRATOR.'/components/com_z2/tables/table.php');
        JLoader::register('Z2Controller', JPATH_BASE.'/components/com_z2/controllers/controller.php');
        JLoader::register('Z2Model', JPATH_ADMINISTRATOR.'/components/com_z2/models/model.php');
        
        if ($mainframe->isSite())
        {
            Z2Model::addIncludePath(JPATH_SITE.DS.'components'.DS.'com_z2'.DS.'models');
        }
        else
        {
            // Fix warning under Joomla! 1.5 caused by conflict in model names
            if (Z2_JVERSION != '15' || (Z2_JVERSION == '15' && JRequest::getCmd('option') != 'com_users'))
            {
                Z2Model::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_z2'.DS.'models');
            }
        }
        JLoader::register('Z2View', JPATH_ADMINISTRATOR.'/components/com_z2/views/view.php');
        JLoader::register('Z2HelperHTML', JPATH_ADMINISTRATOR.'/components/com_z2/helpers/html.php');

        // Community Builder integration
        $componentParams = JComponentHelper::getParams('com_z2');
        //20140212 zoearth 不使用Community Builder 大頭貼
        define('Z2_CB', false);
        
        // Define JoomFish compatibility version.
        if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomfish'.DS.'joomfish.php'))
        {
            define('Z2_JF_ID', 'lang_id');
        }
        
        //20140212 zoearth 刪除joomfish功能
        return;
    }

    function onAfterRender()
    {
        $response = JResponse::getBody();
        $searches = array(
            '<meta name="og:url"',
            '<meta name="og:title"',
            '<meta name="og:type"',
            '<meta name="og:image"',
            '<meta name="og:description"'
        );
        $replacements = array(
            '<meta property="og:url"',
            '<meta property="og:title"',
            '<meta property="og:type"',
            '<meta property="og:image"',
            '<meta property="og:description"'
        );
        if (JString::strpos($response, 'prefix="og: http://ogp.me/ns#"') === false)
        {
            $searches[] = '<html ';
            $searches[] = '<html>';
            $replacements[] = '<html prefix="og: http://ogp.me/ns#" ';
            $replacements[] = '<html prefix="og: http://ogp.me/ns#">';
        }
        $response = JString::str_ireplace($searches, $replacements, $response);
        JResponse::setBody($response);
    }
}