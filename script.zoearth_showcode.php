<?php
defined('_JEXEC') or die ;

class Com_Z2InstallerScript
{
    //20140530 zoearth 補上第一次安裝實執行update
    function install($parent)
    {
        $this->update();
    }
    
    public function postflight($type, $parent)
    {
        $db = JFactory::getDBO();
        $status = new stdClass;
        $status->modules = array();
        $status->plugins = array();
        $src = $parent->getParent()->getPath('source');
        $manifest = $parent->getParent()->manifest;
        $plugins = $manifest->xpath('plugins/plugin');
        foreach ($plugins as $plugin)
        {
            $name = (string)$plugin->attributes()->plugin;
            $group = (string)$plugin->attributes()->group;
            $path = $src.'/plugins/'.$group;
            if (JFolder::exists($src.'/plugins/'.$group.'/'.$name))
            {
                $path = $src.'/plugins/'.$group.'/'.$name;
            }
            $installer = new JInstaller;
            $result = $installer->install($path);
            
            $query = "UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND element=".$db->Quote($name)." AND folder=".$db->Quote($group);
            $db->setQuery($query);
            $db->query();
            $status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
        }        
        $modules = $manifest->xpath('modules/module');
        foreach ($modules as $module)
        {
            $name = (string)$module->attributes()->module;
            $client = (string)$module->attributes()->client;
            if (is_null($client))
            {
                $client = 'site';
            }
            ($client == 'administrator') ? $path = $src.'/administrator/modules/'.$name : $path = $src.'/modules/'.$name;
            
            if($client == 'administrator')
            {
                $db->setQuery("SELECT id FROM #__modules WHERE `module` = ".$db->quote($name));
                $isUpdate = (int)$db->loadResult();
            }
            
            $installer = new JInstaller;
            $result = $installer->install($path);
            if ($result)
            {
                $root = $client == 'administrator' ? JPATH_ADMINISTRATOR : JPATH_SITE;
            }
            $status->modules[] = array('name' => $name, 'client' => $client, 'result' => $result);
            if($client == 'administrator' && !$isUpdate)
            {
                $position = version_compare(JVERSION, '3.0', '<') && $name == 'mod_z2_quickicons'? 'icon' : 'cpanel';
                $db->setQuery("UPDATE #__modules SET `position`=".$db->quote($position).",`published`='1' WHERE `module`=".$db->quote($name));
                $db->query();

                $db->setQuery("SELECT id FROM #__modules WHERE `module` = ".$db->quote($name));
                $id = (int)$db->loadResult();

                $db->setQuery("INSERT IGNORE INTO #__modules_menu (`moduleid`,`menuid`) VALUES (".$id.", 0)");
                $db->query();
            }
        }
        $this->installationResults($status);        
    }

    public function uninstall($parent)
    {
        $db = JFactory::getDBO();
        $status = new stdClass;
        $status->modules = array();
        $status->plugins = array();
        $manifest = $parent->getParent()->manifest;
        $plugins = $manifest->xpath('plugins/plugin');
        foreach ($plugins as $plugin)
        {
            $name = (string)$plugin->attributes()->plugin;
            $group = (string)$plugin->attributes()->group;
            $query = "SELECT `extension_id` FROM #__extensions WHERE `type`='plugin' AND element = ".$db->Quote($name)." AND folder = ".$db->Quote($group);
            $db->setQuery($query);
            $extensions = $db->loadColumn();
            if (count($extensions))
            {
                foreach ($extensions as $id)
                {
                    $installer = new JInstaller;
                    $result = $installer->uninstall('plugin', $id);
                }
                $status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
            }

        }
        $modules = $manifest->xpath('modules/module');
        foreach ($modules as $module)
        {
            $name = (string)$module->attributes()->module;
            $client = (string)$module->attributes()->client;
            $db = JFactory::getDBO();
            $query = "SELECT `extension_id` FROM `#__extensions` WHERE `type`='module' AND element = ".$db->Quote($name)."";
            $db->setQuery($query);
            $extensions = $db->loadColumn();
            if (count($extensions))
            {
                foreach ($extensions as $id)
                {
                    $installer = new JInstaller;
                    $result = $installer->uninstall('module', $id);
                }
                $status->modules[] = array('name' => $name, 'client' => $client, 'result' => $result);
            }
        }
        $this->uninstallationResults($status);
    }

    public function update($type='')
    {
        $db = JFactory::getDBO();
        
        //20140513 zoearth 這邊直接執行install.sql
        $templine = '';
        $lines = file(JPATH_ADMINISTRATOR.'/components/com_z2/install.mysql.sql');
        foreach ($lines as $line)
        {
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;
            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';')
            {
                $db->setQuery($templine);
                $db->query();
                $templine = '';
            }
        }
        
        //20140122 zoearth 補上欄位
        $fields = $db->getTableColumns('#__z2_categories');
        if (!array_key_exists('menuId', $fields))
        {
            $query = "ALTER TABLE #__z2_categories 
                    ADD `menuId` int(11) NOT NULL DEFAULT '0' AFTER `useExtraFieldsGroup` ";
            $db->setQuery($query);
            $db->query();
        }
        if (!array_key_exists('inherit', $fields))
        {
            $query = "ALTER TABLE `#__z2_categories` 
                    ADD `inherit` INT( 1 ) NOT NULL DEFAULT '1' 
                    COMMENT '是否繼承設定' AFTER `parent` ,
                    ADD INDEX ( `inherit` ) ;";
            $db->setQuery($query);
            $db->query();
        }
        //20140617 zoearth 跳轉分類
        if (!array_key_exists('gotoCategory', $fields))
        {
            $query = "ALTER TABLE #__z2_categories
                    ADD `gotoCategory` int(11) NOT NULL DEFAULT '0' AFTER `useExtraFieldsGroup` ";
            $db->setQuery($query);
            $db->query();
        }
        //20140619 zoearth sitemap功能
        if (!array_key_exists('sitemap', $fields))
        {
            $query = "ALTER TABLE #__z2_categories
                    ADD `sitemap` int(3) NOT NULL DEFAULT '0' AFTER `useExtraFieldsGroup` ";
            $db->setQuery($query);
            $db->query();
        }
        
        //20140507 zoearth 新增 usergroups 的 extraFieldsGroup
        $fields = $db->getTableColumns('#__usergroups');
        if (!array_key_exists('extraFieldsGroup', $fields))
        {
            $query = "ALTER TABLE #__usergroups
                    ADD `extraFieldsGroup` int(11) NOT NULL DEFAULT '0' COMMENT '附加欄位' AFTER `title` ";
            $db->setQuery($query);
            $db->query();
        }
        
        //20140411 zoearth 刪除欄位(迴圈)
        $deleteColumns = array();
        $deleteColumns[] = array('#__z2_additional_categories','id');
        $deleteColumns[] = array('#__z2_items_lang','id');
        $deleteColumns[] = array('#__z2_categories_lang','id');
        $deleteColumns[] = array('#__z2_extra_fields_lang','id');
        $deleteColumns[] = array('#__z2_extra_fields_value','id');
        
        //20140411 zoearth 刪除多餘欄位
        foreach ($deleteColumns as $data)
        {
            $table  = $data[0];
            $column = $data[1];
            $fields = $db->getTableColumns($table);
            if (array_key_exists($column, $fields))
            {
                $query = "ALTER TABLE `".$table."` DROP `".$column."`;";
                $db->setQuery($query);
                $db->query();
            }
        }
        
        //20140411 zoearth 補上keys(迴圈)
        $addKeys = array();
        $addKeys[] = array('#__z2_additional_categories','catid');
        $addKeys[] = array('#__z2_additional_categories','itemID');
        $addKeys[] = array('#__z2_categories_lang','active');
        $addKeys[] = array('#__z2_categories_lang','catid');
        
        foreach ($addKeys as $data)
        {
            $table  = $data[0];
            $column = $data[1];
            $keys = $db->gettablekeys($table);
            $haveKey = FALSE;
            $needKey = 'catid';
            foreach ($keys as $key)
            {
                if ($key->Column_name == $column)
                {
                    $haveKey = TRUE;
                }
            }
            if (!$haveKey)
            {
                $query = "ALTER TABLE ".$table." ADD INDEX ".$column." (".$column.");";
                $db->setQuery($query);
                $db->query();
            }
        }
        
        //20140418 zoearth 修改一些DB數值
        //20140418 zoearth 取消原本附加分類外掛
        $query = "UPDATE #__extensions SET enabled = 0 WHERE element = 'z2additonalcategories'; ";

        //20140418 zoearth 取消原本搜尋外掛
        $query = "UPDATE #__extensions SET enabled = 0 WHERE element = 'z2' AND folder = 'finder'; ";
        
        $db->setQuery($query);
        $db->query();
        
        /* *****************************
         * 20140422 zoearth 這邊開始新增Z2會員群組 以及設定
         * *****************************/
        
        //移除原本模組顯示 Joomla原本文章功能 與 Z2的功能
        $query = "UPDATE #__modules SET published = 0 WHERE title IN (
                'Quick Icons',
                'Popular Articles',
                'Recently Added Articles',
                'Logged-in Users',
                'Z2 Quick Icons (admin)',
                'Z2 Stats (admin)'
                ) ";
        $db->setQuery($query);
        $db->query();
        
        //新增Z2群組   先檢查26.27是否存在
        $query = $db->getQuery(true);
        $query->select('*')
            ->from("#__usergroups")
            ->where("id IN (26,27)");
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        $haveUsergroups = array();
        foreach ($rows as $row)
        {
            $haveUsergroups[] = $row->id;
        }
        //Z2_會員群組
        if (!in_array('26',$haveUsergroups))
        {
            $query = "INSERT INTO `#__usergroups` (`id`, `parent_id`, `lft`, `rgt`, `title`) VALUES (26, 1, 0, 0, 'Z2_Users');";
            $db->setQuery($query);
            $db->query();
        }

        //Z2_管理群組
        if (!in_array('27',$haveUsergroups))
        {
            $query = "INSERT INTO `#__usergroups` (`id`, `parent_id`, `lft`, `rgt`, `title`) VALUES (27, 1, 0, 0, 'Z2_Managers');";
            $db->setQuery($query);
            $db->query();
        }

        //新增會員群組的登入權限
        $query = $db->getQuery(true);
        $query->select('*')
            ->from("#__assets")
            ->where("name IN ('root.1','com_z2')");
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        foreach ($rows as $row)
        {
            $rules = json_decode($row->rules);
            //基本權限
            if ($row->name == 'root.1')
            {
                //會員權限
                $rules->{"core.login.site"}->{"26"} = 1;
                //管理權限
                $rules->{"core.login.admin"}->{"27"} = 1;
                $rules->{"core.login.offline"}->{"27"} = 1;
                $rules->{"core.create"}->{"27"} = 1;
                $rules->{"core.delete"}->{"27"} = 1;
                $rules->{"core.edit"}->{"27"} = 1;
                $rules->{"core.edit.state"}->{"27"} = 1;
                $rules->{"core.edit.own"}->{"27"} = 1;
                $query = "UPDATE #__assets SET rules = '".json_encode($rules)."' WHERE name = 'root.1'; ";
                $db->setQuery($query);
                $db->query();
            }
            elseif ($row->name == 'com_z2')
            {
                //管理權限
                if (!$rules->{"core.admin"})
                {
                    $rules->{"core.admin"} = new stdClass();
                }
                if (!$rules->{"core.manage"})
                {
                    $rules->{"core.manage"} = new stdClass();
                }
                $rules->{"core.admin"}->{"27"} = 1;
                $rules->{"core.manage"}->{"27"} = 1;
                $query = "UPDATE #__assets SET rules = '".json_encode($rules)."' WHERE name = 'com_z2'; ";
                $db->setQuery($query);
                $db->query();
            }
        }
        
        //20140423 zoearth 更新 瀏覽層級名稱與刪除不需要的
        $query = "UPDATE `#__viewlevels` SET title = '公開' WHERE title = 'Public' ;";
        $db->setQuery($query);
        $db->query();
        $query = "UPDATE `#__viewlevels` SET title = '已註冊',rules = '[2,6,8,26,27]' WHERE title = 'Special' ;";
        $db->setQuery($query);
        $db->query();
        $query = "DELETE FROM #__viewlevels WHERE title IN ('Registered','Guest','Super Users');";
        $db->setQuery($query);
        $db->query();
        
        //修改會員預設註冊後的群組
        $query = $db->getQuery(true);
        $query->select('*')
            ->from("#__extensions")
            ->where("name = 'com_users' ");
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        $row = $rows[0];
        $rules = json_decode($row->params);
        if (!($rules->{"new_usertype"} >= 26 ))
        {
            $rules->{"new_usertype"} = 26;
            $query = "UPDATE #__extensions SET params = '".json_encode($rules)."' WHERE name = 'com_users'; ";
            $db->setQuery($query);
            $db->query();
        }

        //20140623 zoearth 更新jui的js檔案
        //20140623 zoearth jquery ui
        @unlink(JPATH_SITE.'/media/jui/js/jquery.ui.core.min.js');
        @unlink(JPATH_SITE.'/media/jui/js/jquery.ui.sortable.min.js');
        //20140625 zoearth chosen
        @unlink(JPATH_SITE.'/media/jui/js/chosen.jquery.min.js');
        @unlink(JPATH_SITE.'/media/jui/css/chosen.css');
        //20140623 zoearth jquery ui
        @copy(JPATH_SITE.'/media/z2/assets/js/jquery.ui.core.min.js',JPATH_SITE.'/media/jui/js/jquery.ui.core.min.js');
        @copy(JPATH_SITE.'/media/z2/assets/js/jquery.ui.sortable.min.js',JPATH_SITE.'/media/jui/js/jquery.ui.sortable.min.js');
        //20140625 zoearth chosen
        @copy(JPATH_SITE.'/media/z2/assets/js/chosen.jquery.min.js',JPATH_SITE.'/media/jui/js/chosen.jquery.min.js');
        @copy(JPATH_SITE.'/media/z2/assets/css/chosen.css',JPATH_SITE.'/media/jui/css/chosen.css');
    }
    
    private function installationResults($status)
    {
        $language = JFactory::getLanguage();
        $language->load('com_z2');
        $rows = 0; ?>
        <img src="<?php echo JURI::root(true); ?>/media/z2/assets/images/system/Z2_Logo_126x48_24.png" alt="Z2" align="right" />
        <h2><?php echo JText::_('Z2_INSTALLATION_STATUS'); ?></h2>
        <table class="adminlist table table-striped">
            <thead>
                <tr>
                    <th class="title" colspan="2"><?php echo JText::_('Z2_EXTENSION'); ?></th>
                    <th width="30%"><?php echo JText::_('Z2_STATUS'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="row0">
                    <td class="key" colspan="2"><?php echo 'Z2 '.JText::_('Z2_COMPONENT'); ?></td>
                    <td><strong><?php echo JText::_('Z2_INSTALLED'); ?></strong></td>
                </tr>
                <?php if (count($status->modules)): ?>
                <tr>
                    <th><?php echo JText::_('Z2_MODULE'); ?></th>
                    <th><?php echo JText::_('Z2_CLIENT'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->modules as $module): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo $module['name']; ?></td>
                    <td class="key"><?php echo ucfirst($module['client']); ?></td>
                    <td><strong><?php echo ($module['result'])?JText::_('Z2_INSTALLED'):JText::_('Z2_NOT_INSTALLED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (count($status->plugins)): ?>
                <tr>
                    <th><?php echo JText::_('Z2_PLUGIN'); ?></th>
                    <th><?php echo JText::_('Z2_GROUP'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->plugins as $plugin): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                    <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
                    <td><strong><?php echo ($plugin['result'])?JText::_('Z2_INSTALLED'):JText::_('Z2_NOT_INSTALLED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    private function uninstallationResults($status)
    {
        $language = JFactory::getLanguage();
        $language->load('com_z2');
        $rows = 0;
        ?>
        <h2><?php echo JText::_('Z2_REMOVAL_STATUS'); ?></h2>
        <table class="adminlist table table-striped">
            <thead>
                <tr>
                    <th class="title" colspan="2"><?php echo JText::_('Z2_EXTENSION'); ?></th>
                    <th width="30%"><?php echo JText::_('Z2_STATUS'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="row0">
                    <td class="key" colspan="2"><?php echo 'Z2 '.JText::_('Z2_COMPONENT'); ?></td>
                    <td><strong><?php echo JText::_('Z2_REMOVED'); ?></strong></td>
                </tr>
                <?php if (count($status->modules)): ?>
                <tr>
                    <th><?php echo JText::_('Z2_MODULE'); ?></th>
                    <th><?php echo JText::_('Z2_CLIENT'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->modules as $module): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo $module['name']; ?></td>
                    <td class="key"><?php echo ucfirst($module['client']); ?></td>
                    <td><strong><?php echo ($module['result'])?JText::_('Z2_REMOVED'):JText::_('Z2_NOT_REMOVED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (count($status->plugins)): ?>
                <tr>
                    <th><?php echo JText::_('Z2_PLUGIN'); ?></th>
                    <th><?php echo JText::_('Z2_GROUP'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->plugins as $plugin): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                    <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
                    <td><strong><?php echo ($plugin['result'])?JText::_('Z2_REMOVED'):JText::_('Z2_NOT_REMOVED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        }
    }